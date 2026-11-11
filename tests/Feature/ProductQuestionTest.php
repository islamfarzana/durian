<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductQuestionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_question_is_routed_to_the_products_store(): void
    {
        $customer = User::factory()->create();
        $store = Vendor::factory()->create();
        $product = Product::factory()->soldBy($store)->inStock()->create();

        $response = $this->actingAs($customer)->post('/product/'.$product->id.'/questions', ['question' => 'Is this the large size?']);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_questions', ['product_id' => $product->id, 'vendor_id' => $store->id, 'user_id' => $customer->id, 'question' => 'Is this the large size?']);
    }

    public function test_question_that_is_too_short_is_rejected(): void
    {
        $product = Product::factory()->inStock()->create();

        $response = $this->actingAs(User::factory()->create())->post('/product/'.$product->id.'/questions', ['question' => 'Hi?']);

        $response->assertSessionHasErrors(['question' => 'Please write a little more so the seller can help.']);
        $this->assertDatabaseMissing('product_questions', ['product_id' => $product->id]);
    }

    public function test_vendor_answers_a_question_about_their_product(): void
    {
        $store = Vendor::factory()->create();
        $question = ProductQuestion::factory()->for(Product::factory()->soldBy($store))->create();

        $response = $this->actingAs(Admin::factory()->forVendor($store)->create(), 'admin')
            ->post('/admin/questions/'.$question->id.'/answer', ['answer' => 'Yes, it is the large size.']);

        $response->assertRedirect();
        $this->assertSame('Yes, it is the large size.', $question->fresh()->answer);
        $this->assertNotNull($question->fresh()->answered_at);
    }

    public function test_vendor_cannot_answer_another_stores_question(): void
    {
        $question = ProductQuestion::factory()->for(Product::factory()->soldBy(Vendor::factory()->create()))->create();
        $otherVendor = Admin::factory()->forVendor(Vendor::factory()->create())->create();

        $this->actingAs($otherVendor, 'admin')
            ->post('/admin/questions/'.$question->id.'/answer', ['answer' => 'Not my product'])
            ->assertNotFound();

        $this->assertNull($question->fresh()->answer);
    }

    public function test_answered_question_shows_on_the_product_page(): void
    {
        $question = ProductQuestion::factory()->answered('Packed the same morning.')->for(Product::factory()->inStock())->create(['question' => 'How fresh is it?']);

        $this->get('/product/'.$question->product_id)->assertOk()->assertSee('How fresh is it?')->assertSee('Packed the same morning.');
    }
}
