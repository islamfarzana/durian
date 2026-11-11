<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrdersProduct;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Models\VendorsBankDetail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VendorPayoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_vendor_requests_a_payout_from_cleared_earnings(): void
    {
        $store = $this->storeWithDeliveredSale(1000, commission: 10);

        $response = $this->actingAs(Admin::factory()->forVendor($store)->create(), 'admin')->post('/admin/payouts', ['amount' => 600]);

        $response->assertRedirect()->assertSessionHas('success_message');
        $this->assertDatabaseHas('vendor_payouts', ['vendor_id' => $store->id, 'amount' => 600, 'status' => VendorPayout::REQUESTED]);
    }

    public function test_vendor_cannot_request_more_than_the_available_balance(): void
    {
        $store = $this->storeWithDeliveredSale(1000, commission: 10);
        VendorPayout::factory()->for($store)->paid()->create(['amount' => 500]);

        $response = $this->actingAs(Admin::factory()->forVendor($store)->create(), 'admin')->post('/admin/payouts', ['amount' => 450]);

        $response->assertSessionHasErrors(['amount' => 'You can request up to ৳400 right now.']);
        $this->assertDatabaseMissing('vendor_payouts', ['vendor_id' => $store->id, 'status' => VendorPayout::REQUESTED]);
    }

    public function test_undelivered_sales_are_not_available_for_payout(): void
    {
        $store = Vendor::factory()->create();
        VendorsBankDetail::query()->forceCreate(['vendor_id' => $store->id, 'account_holder_name' => 'Owner', 'bank_name' => 'Bank', 'account_number' => '123', 'bank_ifsc_code' => 'X']);
        OrdersProduct::factory()->for(Product::factory()->soldBy($store))->create(['product_price' => 1000]);

        $response = $this->actingAs(Admin::factory()->forVendor($store)->create(), 'admin')->post('/admin/payouts', ['amount' => 500]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('vendor_payouts', ['vendor_id' => $store->id]);
    }

    public function test_admin_marks_a_payout_as_paid_with_a_reference(): void
    {
        $payout = VendorPayout::factory()->create();

        $this->actingAs(Admin::factory()->superadmin()->create(), 'admin')
            ->post('/admin/payouts/'.$payout->id, ['action' => 'paid', 'reference' => 'BEFTN-998877'])
            ->assertRedirect();

        $this->assertDatabaseHas('vendor_payouts', ['id' => $payout->id, 'status' => VendorPayout::PAID, 'reference' => 'BEFTN-998877']);
    }

    public function test_vendor_cannot_settle_payouts(): void
    {
        $store = Vendor::factory()->create();
        $payout = VendorPayout::factory()->for($store)->create();

        $this->actingAs(Admin::factory()->forVendor($store)->create(), 'admin')
            ->post('/admin/payouts/'.$payout->id, ['action' => 'paid', 'reference' => 'SELF-PAY'])
            ->assertForbidden();

        $this->assertSame(VendorPayout::REQUESTED, $payout->fresh()->status);
    }

    private function storeWithDeliveredSale(float $amount, float $commission): Vendor
    {
        $store = Vendor::factory()->create(['commission' => $commission]);
        VendorsBankDetail::query()->forceCreate(['vendor_id' => $store->id, 'account_holder_name' => 'Owner', 'bank_name' => 'Bank', 'account_number' => '0123456789', 'bank_ifsc_code' => 'BANK0001']);
        OrdersProduct::factory()
            ->for(Order::factory()->delivered())
            ->for(Product::factory()->soldBy($store))
            ->delivered()
            ->create(['product_price' => $amount, 'commission' => $commission]);

        return $store;
    }
}
