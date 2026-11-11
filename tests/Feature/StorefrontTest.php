<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use DatabaseTransactions;

    public function test_home_page_shows_running_flash_sale_products_with_their_store(): void
    {
        $store = Vendor::factory()->official()->create(['name' => 'Rupsha Fresh Mart']);
        $product = Product::factory()->soldBy($store)->inStock()->create(['product_name' => 'Rupsha Golden Mango']);
        FlashSaleProduct::factory()->for($product)->create();

        $response = $this->get('/');

        $response->assertOk()->assertSee('Rupsha Golden Mango')->assertSee('Rupsha Fresh Mart');
    }

    public function test_product_page_shows_the_seller_card_and_delivery_options(): void
    {
        $store = Vendor::factory()->create(['name' => 'Meghna Electronics Hub']);
        $product = Product::factory()->soldBy($store)->inStock()->create(['product_name' => 'Meghna Earphones', 'return_days' => 7, 'warranty' => '6 months warranty']);

        $response = $this->get('/product/'.$product->id);

        $response->assertOk()
            ->assertSee('Meghna Earphones')
            ->assertSee('Meghna Electronics Hub')
            ->assertSee('7-day easy return')
            ->assertSee('6 months warranty')
            ->assertSee('Cash on delivery available');
    }

    public function test_inactive_product_page_is_not_found(): void
    {
        $product = Product::factory()->inactive()->inStock()->create();

        $this->get('/product/'.$product->id)->assertNotFound();
    }

    public function test_search_filters_by_brand(): void
    {
        $kept = Product::factory()->for(Brand::factory()->create(['name' => 'Kopotakkho Foods']))->inStock()->create(['product_name' => 'Zebrafruit Jam Kopotakkho']);
        Product::factory()->inStock()->create(['product_name' => 'Zebrafruit Jam Other']);

        $response = $this->get('/search?q=Zebrafruit&brand[]='.$kept->brand_id);

        $response->assertOk()->assertSee('Zebrafruit Jam Kopotakkho')->assertDontSee('Zebrafruit Jam Other');
    }

    public function test_search_hides_products_of_a_store_on_holiday(): void
    {
        Product::factory()->soldBy(Vendor::factory()->onHoliday()->create())->inStock()->create(['product_name' => 'Quokkaberry Holiday Pickle']);

        $this->get('/search?q=Quokkaberry')->assertOk()->assertDontSee('Quokkaberry Holiday Pickle');
    }

    public function test_search_term_is_escaped_in_the_page(): void
    {
        $response = $this->get('/search?q='.urlencode('<script>alert(1)</script>'));

        $response->assertOk();
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $response->getContent());
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->getContent());
    }

    public function test_search_suggestions_return_products_and_stores(): void
    {
        $store = Vendor::factory()->create(['name' => 'Xylocarp Traders']);
        Product::factory()->soldBy($store)->inStock()->create(['product_name' => 'Xylocarp Honey']);

        $response = $this->getJson('/search/suggest?q=Xylocarp');

        $response->assertOk()
            ->assertJsonPath('products.0.name', 'Xylocarp Honey')
            ->assertJsonPath('stores.0.name', 'Xylocarp Traders');
    }

    public function test_store_page_lists_only_that_stores_products(): void
    {
        $store = Vendor::factory()->create();
        Product::factory()->soldBy($store)->inStock()->create(['product_name' => 'Store Own Lantern']);
        Product::factory()->soldBy(Vendor::factory()->create())->inStock()->create(['product_name' => 'Elsewhere Lantern']);

        $response = $this->get('/store/'.$store->slug);

        $response->assertOk()->assertSee('Store Own Lantern')->assertDontSee('Elsewhere Lantern');
    }

    public function test_store_page_of_an_unapproved_vendor_is_not_found(): void
    {
        $store = Vendor::factory()->unapproved()->create();

        $this->get('/store/'.$store->slug)->assertNotFound();
    }

    public function test_old_vendor_url_redirects_to_the_store_page(): void
    {
        $store = Vendor::factory()->create();

        $this->get('/products/'.$store->id)->assertRedirect('/store/'.$store->slug)->assertStatus(301);
    }
}
