<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_gets_401_when_saving_a_favourite(): void
    {
        $product = Product::factory()->inStock()->create();

        $this->postJson('/wishlist/'.$product->id)->assertUnauthorized();
    }

    public function test_customer_saves_and_then_removes_a_favourite(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->inStock()->create();

        $this->actingAs($customer)->postJson('/wishlist/'.$product->id)->assertOk()->assertJsonPath('wished', true);
        $this->assertDatabaseHas('wishlists', ['user_id' => $customer->id, 'product_id' => $product->id]);

        $this->actingAs($customer)->postJson('/wishlist/'.$product->id)->assertOk()->assertJsonPath('wished', false);
        $this->assertDatabaseMissing('wishlists', ['user_id' => $customer->id, 'product_id' => $product->id]);
    }

    public function test_favourites_page_lists_saved_products(): void
    {
        $customer = User::factory()->create();
        $customer->wishlist()->attach(Product::factory()->inStock()->create(['product_name' => 'Saved Tamarind Candy']));

        $this->actingAs($customer)->get('/wishlist')->assertOk()->assertSee('Saved Tamarind Candy');
    }
}
