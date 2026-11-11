<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\DeliveryAddress;
use App\Models\DeliverySlot;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_checkout_records_the_chosen_delivery_slot_and_free_delivery(): void
    {
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-11-02 09:00', 'Asia/Dhaka'));
        $customer = User::factory()->create();
        $address = DeliveryAddress::factory()->for($customer)->create();
        $slot = DeliverySlot::factory()->create(['label' => 'Test slot 2 PM – 4 PM', 'starts_at' => '14:00:00', 'ends_at' => '16:00:00']);
        $this->putInBag($customer, Product::factory()->inStock()->create(['product_price' => 1200]));

        $response = $this->actingAs($customer)->post('/checkout', [
            'address_id' => $address->id,
            'payment_gateway' => 'COD',
            'accept' => 'yes',
            'delivery_date' => '2026-11-02',
            'delivery_slot' => $slot->id,
            'notes' => 'Ring the bell twice',
        ]);

        $response->assertRedirect('/thanks');
        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'delivery_date' => '2026-11-02',
            'delivery_slot' => 'Test slot 2 PM – 4 PM',
            'notes' => 'Ring the bell twice',
            'shipping_charges' => 0,
            'grand_total' => 1200,
        ]);
        $this->assertDatabaseMissing('carts', ['user_id' => $customer->id]);
    }

    public function test_checkout_rejects_a_slot_that_starts_too_soon(): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 09:00', 'Asia/Dhaka'));
        $customer = User::factory()->create();
        $address = DeliveryAddress::factory()->for($customer)->create();
        $slot = DeliverySlot::factory()->create(['starts_at' => '08:00:00', 'ends_at' => '10:00:00']);
        $this->putInBag($customer, Product::factory()->inStock()->create());

        $response = $this->actingAs($customer)->from('/checkout')->post('/checkout', [
            'address_id' => $address->id,
            'payment_gateway' => 'COD',
            'accept' => 'yes',
            'delivery_date' => '2026-11-02',
            'delivery_slot' => $slot->id,
        ]);

        $response->assertRedirect('/checkout');
        $response->assertSessionHasErrors(['delivery_slot' => 'That delivery slot is no longer available. Please pick another one.']);
        $this->assertDatabaseMissing('orders', ['user_id' => $customer->id]);
    }

    public function test_checkout_rejects_another_customers_address(): void
    {
        $customer = User::factory()->create();
        $strangersAddress = DeliveryAddress::factory()->create();
        $this->putInBag($customer, Product::factory()->inStock()->create());

        $response = $this->actingAs($customer)->post('/checkout', ['address_id' => $strangersAddress->id, 'payment_gateway' => 'COD', 'accept' => 'yes']);

        $response->assertSessionHasErrors(['address_id' => 'Please choose a delivery address.']);
        $this->assertDatabaseMissing('orders', ['user_id' => $customer->id]);
    }

    public function test_checkout_saves_the_voucher_discount_on_the_order(): void
    {
        Mail::fake();
        $customer = User::factory()->create();
        $address = DeliveryAddress::factory()->for($customer)->create();
        $voucher = Coupon::factory()->create(['amount' => 75]);
        $this->putInBag($customer, Product::factory()->inStock()->create(['product_price' => 1500]));
        $this->actingAs($customer)->postJson('/cart/voucher', ['code' => $voucher->coupon_code])->assertOk();

        $response = $this->actingAs($customer)->post('/checkout', ['address_id' => $address->id, 'payment_gateway' => 'COD', 'accept' => 'yes']);

        $response->assertRedirect('/thanks');
        $this->assertDatabaseHas('orders', ['user_id' => $customer->id, 'coupon_code' => $voucher->coupon_code, 'coupon_amount' => 75, 'grand_total' => 1425]);
    }

    private function putInBag(User $customer, Product $product, int $quantity = 1): void
    {
        Cart::query()->forceCreate(['session_id' => 'checkout-'.$customer->id, 'user_id' => $customer->id, 'product_id' => $product->id, 'size' => 'Standard', 'quantity' => $quantity]);
    }

    public function test_cash_on_delivery_reduces_stock_once_and_places_the_order(): void
    {
        Mail::fake();
        $user = User::query()->findOrFail(1);
        $stockBefore = (int) DB::table('products_attributes')->where('product_id', 1)->where('size', 'Normal')->value('stock');
        $this->assertGreaterThan(0, $stockBefore);

        DB::table('carts')->where('user_id', $user->id)->delete();
        DB::table('carts')->insert([
            'session_id' => 'checkout-test',
            'user_id' => $user->id,
            'product_id' => 1,
            'size' => 'Normal',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/checkout', [
            'address_id' => 1,
            'payment_gateway' => 'COD',
            'accept' => 'Yes',
        ]);

        $response->assertRedirect('/thanks');
        $this->assertSame($stockBefore - 1, (int) DB::table('products_attributes')->where('product_id', 1)->where('size', 'Normal')->value('stock'));
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_gateway' => 'COD',
            'order_status' => 'Order Placed',
        ]);
    }

    public function test_checkout_rejects_a_quantity_above_stock_and_keeps_the_stock(): void
    {
        Mail::fake();
        $user = User::query()->findOrFail(1);
        $stockBefore = (int) DB::table('products_attributes')->where('product_id', 1)->where('size', 'Normal')->value('stock');

        DB::table('carts')->where('user_id', $user->id)->delete();
        DB::table('carts')->insert([
            'session_id' => 'checkout-test-over',
            'user_id' => $user->id,
            'product_id' => 1,
            'size' => 'Normal',
            'quantity' => $stockBefore + 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->from('/checkout')->post('/checkout', [
            'address_id' => 1,
            'payment_gateway' => 'COD',
            'accept' => 'Yes',
        ]);

        $response->assertRedirect('/cart');
        $response->assertSessionHas('error_message');
        $this->assertSame($stockBefore, (int) DB::table('products_attributes')->where('product_id', 1)->where('size', 'Normal')->value('stock'));
    }

    public function test_admin_delete_links_do_not_delete_on_get(): void
    {
        $admin = Admin::query()->where('email', 'superadmin@gmail.com')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get('/admin/delete-brand/1')
            ->assertStatus(405);

        $this->assertDatabaseHas('brands', ['id' => 1]);
    }
}
