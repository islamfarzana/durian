<?php

namespace Tests\Feature;

use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\OrdersProduct;
use App\Models\Product;
use App\Models\ProductsAttribute;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerOrderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_cannot_open_another_customers_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create())->get('/user/orders/'.$order->id)->assertNotFound();
    }

    public function test_customer_sees_their_order_with_its_store_packages(): void
    {
        $customer = User::factory()->create();
        $line = OrdersProduct::factory()->for(Order::factory()->for($customer))->create(['product_name' => 'Ordered Lychee Basket']);

        $this->actingAs($customer)->get('/user/orders/'.$line->order_id)->assertOk()->assertSee('Ordered Lychee Basket')->assertSee('Cancel order');
    }

    public function test_cancelling_a_cash_on_delivery_order_returns_the_stock(): void
    {
        $customer = User::factory()->create();
        $variant = ProductsAttribute::factory()->for(Product::factory()->state(['sold_count' => 3]))->create(['stock' => 4]);
        $order = Order::factory()->for($customer)->create();
        OrdersProduct::factory()->for($order)->create(['product_id' => $variant->product_id, 'product_size' => $variant->size, 'product_qty' => 3]);

        $response = $this->actingAs($customer)->post('/user/orders/'.$order->id.'/cancel');

        $response->assertRedirect()->assertSessionHas('success_message');
        $this->assertSame('Cancelled', $order->fresh()->order_status);
        $this->assertSame(7, $variant->fresh()->stock);
        $this->assertSame(0, Product::query()->find($variant->product_id)->sold_count);
        $this->assertDatabaseHas('orders_logs', ['order_id' => $order->id, 'order_status' => 'Cancelled']);
    }

    public function test_shipped_order_cannot_be_cancelled(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->for($customer)->shipped()->create();

        $response = $this->actingAs($customer)->post('/user/orders/'.$order->id.'/cancel');

        $response->assertSessionHas('error_message', 'This order can no longer be cancelled. Please contact support.');
        $this->assertSame('Shipped', $order->fresh()->order_status);
    }

    public function test_customer_cannot_remove_another_customers_address(): void
    {
        $address = DeliveryAddress::factory()->create();

        $this->actingAs(User::factory()->create())->postJson('/remove-delivery-address', ['addressid' => $address->id])->assertNotFound();

        $this->assertModelExists($address);
    }

    public function test_customer_cannot_read_another_customers_address(): void
    {
        $address = DeliveryAddress::factory()->create();

        $this->actingAs(User::factory()->create())->postJson('/get-delivery-address', ['addressid' => $address->id])->assertNotFound();
    }
}
