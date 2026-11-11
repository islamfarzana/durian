<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreFollowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_follows_and_unfollows_a_store(): void
    {
        $customer = User::factory()->create();
        $store = Vendor::factory()->create();

        $this->actingAs($customer)->postJson('/store/'.$store->slug.'/follow')->assertOk()->assertJsonPath('following', true)->assertJsonPath('followers', 1);
        $this->assertDatabaseHas('store_follows', ['user_id' => $customer->id, 'vendor_id' => $store->id]);

        $this->actingAs($customer)->postJson('/store/'.$store->slug.'/follow')->assertOk()->assertJsonPath('following', false);
        $this->assertDatabaseMissing('store_follows', ['user_id' => $customer->id, 'vendor_id' => $store->id]);
    }

    public function test_following_an_unapproved_store_is_not_found(): void
    {
        $customer = User::factory()->create();
        $store = Vendor::factory()->unapproved()->create();

        $this->actingAs($customer)->postJson('/store/'.$store->slug.'/follow')->assertNotFound();
        $this->assertDatabaseMissing('store_follows', ['vendor_id' => $store->id]);
    }
}
