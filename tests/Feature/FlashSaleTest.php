<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\DeliveryAddress;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FlashSaleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_running_flash_sale_price_replaces_the_regular_discount(): void
    {
        $product = Product::factory()->discounted(10)->inStock()->create(['product_price' => 200]);
        FlashSaleProduct::factory()->for($product)->create(['discount_percent' => 25]);

        $response = $this->postJson('/cart/items', ['product_id' => $product->id, 'size' => 'Standard', 'quantity' => 1]);

        $response->assertOk()->assertJsonPath('subtotal', '৳150');
    }

    public function test_flash_price_stops_once_the_flash_stock_is_sold_out(): void
    {
        $product = Product::factory()->discounted(10)->inStock()->create(['product_price' => 200]);
        FlashSaleProduct::factory()->for($product)->limitedTo(10, soldCount: 10)->create(['discount_percent' => 25]);

        $response = $this->postJson('/cart/items', ['product_id' => $product->id, 'size' => 'Standard', 'quantity' => 1]);

        $response->assertOk()->assertJsonPath('subtotal', '৳180');
    }

    public function test_flash_price_does_not_apply_to_an_upcoming_sale(): void
    {
        $product = Product::factory()->inStock()->create(['product_price' => 200]);
        FlashSaleProduct::factory()->for($product)->for(FlashSale::factory()->upcoming())->create(['discount_percent' => 25]);

        $this->postJson('/cart/items', ['product_id' => $product->id, 'size' => 'Standard', 'quantity' => 1])
            ->assertOk()
            ->assertJsonPath('subtotal', '৳200');
    }

    public function test_checkout_charges_the_flash_price_and_counts_the_flash_units(): void
    {
        Mail::fake();
        $customer = User::factory()->create();
        $address = DeliveryAddress::factory()->for($customer)->create();
        $product = Product::factory()->inStock(10)->create(['product_price' => 200]);
        $flashItem = FlashSaleProduct::factory()->for($product)->limitedTo(50, soldCount: 5)->create(['discount_percent' => 25]);
        Cart::query()->forceCreate(['session_id' => 'flash', 'user_id' => $customer->id, 'product_id' => $product->id, 'size' => 'Standard', 'quantity' => 2]);

        $response = $this->actingAs($customer)->post('/checkout', ['address_id' => $address->id, 'payment_gateway' => 'COD', 'accept' => 'yes']);

        $response->assertRedirect('/thanks');
        $this->assertDatabaseHas('orders_products', ['product_id' => $product->id, 'product_price' => 150, 'product_qty' => 2]);
        $this->assertSame(7, $flashItem->fresh()->sold_count);
        $this->assertSame(2, $product->fresh()->sold_count);
    }

    public function test_vendor_entry_waits_for_admin_approval(): void
    {
        $store = Vendor::factory()->create();
        $product = Product::factory()->soldBy($store)->inStock()->create();
        $flashSale = FlashSale::factory()->create();

        $response = $this->actingAs(Admin::factory()->forVendor($store)->create(), 'admin')
            ->post('/admin/flash-sales/'.$flashSale->id.'/items', ['product_id' => $product->id, 'discount_percent' => 15]);

        $response->assertRedirect();
        $this->assertDatabaseHas('flash_sale_products', [
            'flash_sale_id' => $flashSale->id,
            'product_id' => $product->id,
            'vendor_id' => $store->id,
            'status' => FlashSaleProduct::PENDING,
        ]);
    }

    public function test_admin_approval_puts_a_vendor_entry_live(): void
    {
        $item = FlashSaleProduct::factory()->pending()->create();

        $response = $this->actingAs(Admin::factory()->superadmin()->create(), 'admin')
            ->post('/admin/flash-sale-items/'.$item->id.'/status', ['status' => FlashSaleProduct::APPROVED]);

        $response->assertRedirect();
        $this->assertSame(FlashSaleProduct::APPROVED, $item->fresh()->status);
    }

    public function test_vendor_cannot_enter_another_stores_product(): void
    {
        $store = Vendor::factory()->create();
        $otherProduct = Product::factory()->soldBy(Vendor::factory()->create())->inStock()->create();
        $flashSale = FlashSale::factory()->create();

        $response = $this->actingAs(Admin::factory()->forVendor($store)->create(), 'admin')
            ->post('/admin/flash-sales/'.$flashSale->id.'/items', ['product_id' => $otherProduct->id, 'discount_percent' => 15]);

        $response->assertSessionHasErrors(['product_id' => 'Choose one of your products.']);
        $this->assertDatabaseMissing('flash_sale_products', ['product_id' => $otherProduct->id]);
    }
}
