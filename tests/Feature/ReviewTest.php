<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrdersProduct;
use App\Models\Product;
use App\Models\Rating;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_who_has_not_ordered_the_product_cannot_review_it(): void
    {
        $product = Product::factory()->inStock()->create();

        $response = $this->actingAs(User::factory()->create())->from('/product/'.$product->id)
            ->post('/add-rating', ['product_id' => $product->id, 'rating' => 5, 'review' => 'Great']);

        $response->assertRedirect('/product/'.$product->id)->assertSessionHas('error_message', 'Only customers who ordered this product can review it.');
        $this->assertDatabaseMissing('ratings', ['product_id' => $product->id]);
    }

    public function test_customer_who_ordered_the_product_leaves_a_review_for_approval(): void
    {
        $customer = User::factory()->create();
        $product = Product::factory()->inStock()->create();
        OrdersProduct::factory()->for(Order::factory()->for($customer)->delivered())->for($product)->create();

        $response = $this->actingAs($customer)->post('/add-rating', ['product_id' => $product->id, 'rating' => 4, 'review' => 'Fresh and well packed']);

        $response->assertRedirect()->assertSessionHas('success_message');
        $this->assertDatabaseHas('ratings', ['user_id' => $customer->id, 'product_id' => $product->id, 'rating' => 4, 'status' => 0]);
    }

    public function test_vendor_replies_to_a_review_of_their_product(): void
    {
        $store = Vendor::factory()->create();
        $rating = Rating::factory()->for(Product::factory()->soldBy($store))->create();

        $this->actingAs(Admin::factory()->forVendor($store)->create(), 'admin')
            ->post('/admin/ratings/'.$rating->id.'/reply', ['seller_reply' => 'Thank you for shopping with us!'])
            ->assertRedirect();

        $this->assertSame('Thank you for shopping with us!', $rating->fresh()->seller_reply);
    }

    public function test_vendor_cannot_reply_to_another_stores_review(): void
    {
        $rating = Rating::factory()->for(Product::factory()->soldBy(Vendor::factory()->create()))->create();

        $this->actingAs(Admin::factory()->forVendor(Vendor::factory()->create())->create(), 'admin')
            ->post('/admin/ratings/'.$rating->id.'/reply', ['seller_reply' => 'Hijacked reply'])
            ->assertNotFound();

        $this->assertNull($rating->fresh()->seller_reply);
    }

    public function test_vendor_cannot_approve_reviews(): void
    {
        $rating = Rating::factory()->create(['status' => 0]);

        $this->actingAs(Admin::factory()->forVendor(Vendor::factory()->create())->create(), 'admin')
            ->postJson('/admin/update-rating-status', ['rating_id' => $rating->id, 'status' => 'Inactive'])
            ->assertForbidden();

        $this->assertSame(0, $rating->fresh()->status);
    }
}
