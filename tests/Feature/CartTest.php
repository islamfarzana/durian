<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductsAttribute;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CartTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_adds_a_product_to_the_bag(): void
    {
        $product = Product::factory()->inStock(10)->create(['product_price' => 120]);

        $response = $this->postJson('/cart/items', ['product_id' => $product->id, 'size' => 'Standard', 'quantity' => 2]);

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('subtotal', '৳240')
            ->assertJsonPath('variant_quantity', 2);
        $this->assertDatabaseHas('carts', ['product_id' => $product->id, 'size' => 'Standard', 'quantity' => 2, 'user_id' => 0]);
    }

    public function test_adding_more_than_the_stock_returns_422_and_keeps_the_bag_empty(): void
    {
        $product = Product::factory()->inStock(3)->create();

        $response = $this->postJson('/cart/items', ['product_id' => $product->id, 'size' => 'Standard', 'quantity' => 5]);

        $response->assertUnprocessable()->assertJsonPath('message', 'Only 3 of this item are left.');
        $this->assertDatabaseMissing('carts', ['product_id' => $product->id]);
    }

    public function test_setting_the_quantity_to_zero_removes_the_line(): void
    {
        $product = Product::factory()->inStock(10)->create();
        $this->postJson('/cart/items', ['product_id' => $product->id, 'size' => 'Standard', 'quantity' => 1]);

        $response = $this->postJson('/cart/items', ['product_id' => $product->id, 'size' => 'Standard', 'quantity' => 0, 'mode' => 'set']);

        $response->assertOk()->assertJsonPath('count', 0);
        $this->assertDatabaseMissing('carts', ['product_id' => $product->id]);
    }

    public function test_a_customer_cannot_remove_another_customers_bag_line(): void
    {
        $owner = User::factory()->create();
        $variant = ProductsAttribute::factory()->create();
        $line = Cart::query()->forceCreate(['session_id' => 'owner-session', 'user_id' => $owner->id, 'product_id' => $variant->product_id, 'size' => $variant->size, 'quantity' => 1]);

        $response = $this->actingAs(User::factory()->create())->deleteJson('/cart/items/'.$line->id);

        $response->assertNotFound();
        $this->assertModelExists($line);
    }

    public function test_a_product_from_a_store_on_holiday_cannot_be_added(): void
    {
        $product = Product::factory()->soldBy(Vendor::factory()->onHoliday()->create())->inStock(10)->create();

        $response = $this->postJson('/cart/items', ['product_id' => $product->id, 'size' => 'Standard', 'quantity' => 1]);

        $response->assertUnprocessable()->assertJsonPath('message', 'This product is no longer available.');
        $this->assertDatabaseMissing('carts', ['product_id' => $product->id]);
    }
}
