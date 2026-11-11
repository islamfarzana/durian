<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Vendor;
use App\Services\Orders\CancelOrder;
use App\Services\Orders\CannotCancelOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Order tabs, the way a customer thinks about where an order is.
     *
     * @var array<string, array{label: string, statuses: array<int, string>}>
     */
    private const TABS = [
        'all' => ['label' => 'All', 'statuses' => []],
        'processing' => ['label' => 'To ship', 'statuses' => ['New', 'Pending', 'Order Placed', 'In Process', 'Paid']],
        'shipped' => ['label' => 'On the way', 'statuses' => ['Shipped', 'Partially Shipped', 'Partially Delivered']],
        'delivered' => ['label' => 'Delivered', 'statuses' => ['Delivered']],
        'cancelled' => ['label' => 'Cancelled', 'statuses' => ['Cancelled']],
    ];

    public function orders(Request $request, $id = null): View
    {
        if ($id !== null) {
            return $this->show($request, (int) $id);
        }

        $tab = array_key_exists((string) $request->input('tab'), self::TABS) ? (string) $request->input('tab') : 'all';
        $statuses = self::TABS[$tab]['statuses'];

        $orders = Order::query()
            ->with('orders_products.product:id,product_image')
            ->where('user_id', $request->user()->id)
            ->when($statuses !== [], fn ($query) => $query->whereIn('order_status', $statuses))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('front.orders.orders', [
            'orders' => $orders,
            'tabs' => self::TABS,
            'tab' => $tab,
            'meta_title' => 'My orders',
        ]);
    }

    public function cancel(Request $request, int $order, CancelOrder $cancelOrder): RedirectResponse
    {
        $order = Order::query()->where('user_id', $request->user()->id)->findOrFail($order);

        try {
            $cancelOrder->handle($order);
        } catch (CannotCancelOrder $exception) {
            return back()->with('error_message', $exception->getMessage());
        }

        return back()->with('success_message', 'Order #'.$order->id.' has been cancelled.');
    }

    /**
     * A customer can only open their own orders; anyone else's order id is a 404.
     */
    private function show(Request $request, int $id): View
    {
        $order = Order::query()
            ->with(['orders_products.product:id,product_image', 'logs' => fn ($logs) => $logs->orderBy('id')])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $stores = Vendor::query()
            ->with('businessDetails:id,vendor_id,shop_name')
            ->whereIn('id', $order->orders_products->pluck('vendor_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        return view('front.orders.order_details', [
            'order' => $order,
            'packages' => $order->orders_products->groupBy('vendor_id'),
            'stores' => $stores,
            'reviewedProductIds' => Rating::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('product_id', $order->orders_products->pluck('product_id'))
                ->pluck('product_id')
                ->map(fn ($productId): int => (int) $productId)
                ->all(),
            'meta_title' => 'Order #'.$order->id,
        ]);
    }
}
