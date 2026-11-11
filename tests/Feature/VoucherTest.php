<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VoucherTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_voucher_only_discounts_that_stores_items(): void
    {
        $store = Vendor::factory()->create();
        $this->addToBag(Product::factory()->soldBy($store)->inStock()->create(['product_price' => 500]));
        $this->addToBag(Product::factory()->soldBy(Vendor::factory()->create())->inStock()->create(['product_price' => 300]));
        $voucher = Coupon::factory()->forStore($store)->percentage(10)->create();

        $response = $this->postJson('/cart/voucher', ['code' => $voucher->coupon_code]);

        $response->assertOk()->assertJsonPath('message', 'Voucher applied — you save ৳50.');
        $response->assertSessionHas('couponCode', $voucher->coupon_code);
    }

    public function test_voucher_below_its_minimum_spend_is_rejected_with_the_shortfall(): void
    {
        $this->addToBag(Product::factory()->inStock()->create(['product_price' => 400]));
        $voucher = Coupon::factory()->create(['min_spend' => 1000]);

        $response = $this->postJson('/cart/voucher', ['code' => $voucher->coupon_code]);

        $response->assertUnprocessable()->assertJsonPath('message', 'Add ৳600 more of eligible items to use this voucher.');
        $response->assertSessionMissing('couponCode');
    }

    public function test_expired_voucher_is_rejected(): void
    {
        $this->addToBag(Product::factory()->inStock()->create());
        $voucher = Coupon::factory()->expired()->create();

        $this->postJson('/cart/voucher', ['code' => $voucher->coupon_code])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This voucher has expired.');
    }

    public function test_single_use_voucher_is_rejected_for_a_customer_who_used_it(): void
    {
        $customer = User::factory()->create();
        $voucher = Coupon::factory()->singleUse()->create();
        Order::factory()->for($customer)->create(['coupon_code' => $voucher->coupon_code]);
        $this->actingAs($customer);
        $this->addToBag(Product::factory()->inStock()->create());

        $this->postJson('/cart/voucher', ['code' => $voucher->coupon_code])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'You have already used this voucher.');
    }

    public function test_percentage_voucher_is_capped_at_its_maximum_discount(): void
    {
        $this->addToBag(Product::factory()->inStock()->create(['product_price' => 1000]));
        $voucher = Coupon::factory()->percentage(20, maxDiscount: 120)->create();

        $this->postJson('/cart/voucher', ['code' => $voucher->coupon_code])
            ->assertOk()
            ->assertJsonPath('message', 'Voucher applied — you save ৳120.');
    }

    private function addToBag(Product $product): void
    {
        $this->postJson('/cart/items', ['product_id' => $product->id, 'size' => 'Standard', 'quantity' => 1])->assertOk();
    }
}
