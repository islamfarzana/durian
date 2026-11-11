<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrdersProduct;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VendorIsolationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_vendor_cannot_open_another_stores_product(): void
    {
        $product = Product::factory()->soldBy(Vendor::factory()->create())->create();

        $this->actingAs($this->vendorLogin(), 'admin')->get('/admin/add-edit-product/'.$product->id)->assertNotFound();
    }

    public function test_vendor_cannot_delete_another_stores_product(): void
    {
        $product = Product::factory()->soldBy(Vendor::factory()->create())->create();

        $this->actingAs($this->vendorLogin(), 'admin')->post('/admin/delete-product/'.$product->id)->assertNotFound();

        $this->assertModelExists($product);
    }

    public function test_vendor_cannot_update_another_stores_order_item(): void
    {
        $line = OrdersProduct::factory()->for(Product::factory()->soldBy(Vendor::factory()->create()))->create();

        $this->actingAs($this->vendorLogin(), 'admin')
            ->post('/admin/update-order-item-status', ['order_item_id' => $line->id, 'order_item_status' => 'Delivered'])
            ->assertNotFound();

        $this->assertSame('Pending', $line->fresh()->item_status);
    }

    public function test_vendor_cannot_open_an_order_without_their_items(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->vendorLogin(), 'admin')->get('/admin/orders/'.$order->id)->assertNotFound();
    }

    public function test_vendor_cannot_change_the_status_of_a_whole_order(): void
    {
        $store = Vendor::factory()->create();
        $line = OrdersProduct::factory()->for(Product::factory()->soldBy($store))->create();

        $this->actingAs(Admin::factory()->forVendor($store)->create(), 'admin')
            ->post('/admin/update-order-status', ['order_id' => $line->order_id, 'order_status' => 'Delivered'])
            ->assertForbidden();

        $this->assertSame('Order Placed', Order::query()->find($line->order_id)->order_status);
    }

    public function test_vendor_cannot_edit_another_stores_coupon(): void
    {
        $coupon = Coupon::factory()->forStore(Vendor::factory()->create())->create();

        $this->actingAs($this->vendorLogin(), 'admin')->get('/admin/add-edit-coupon/'.$coupon->id)->assertNotFound();
    }

    public function test_vendor_cannot_manage_homepage_banners(): void
    {
        $this->actingAs($this->vendorLogin(), 'admin')->get('/admin/banners')->assertForbidden();
    }

    public function test_vendor_dashboard_shows_the_store_and_its_orders_to_ship(): void
    {
        $store = Vendor::factory()->create(['name' => 'Dashboard Spice House']);
        OrdersProduct::factory()->count(2)->for(Product::factory()->soldBy($store))->create();

        $response = $this->actingAs(Admin::factory()->forVendor($store)->create(), 'admin')->get('/admin/dashboard');

        $response->assertOk()->assertSee('Dashboard Spice House')->assertSee('To ship');
    }

    public function test_admin_dashboard_shows_marketplace_totals(): void
    {
        $this->actingAs(Admin::factory()->superadmin()->create(), 'admin')
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Gross merchandise value')
            ->assertSee('Needs your attention');
    }

    private function vendorLogin(): Admin
    {
        return Admin::factory()->forVendor(Vendor::factory()->create())->create();
    }
}
