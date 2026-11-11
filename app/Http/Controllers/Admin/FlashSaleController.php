<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admins run flash sale campaigns. Vendors join a campaign with their own
 * products, and their entries wait for an admin to approve them.
 */
class FlashSaleController extends Controller
{
    public function index(): View
    {
        Session::put('page', 'flash_sales');
        $admin = Auth::guard('admin')->user();
        $isVendor = $admin->type === 'vendor';

        $flashSales = FlashSale::query()
            ->withCount([
                'items' => fn ($items) => $items->when($isVendor, fn ($own) => $own->where('vendor_id', $admin->vendor_id)),
                'approvedItems',
                'items as pending_items_count' => fn ($items) => $items->where('status', FlashSaleProduct::PENDING),
            ])
            ->when($isVendor, fn ($query) => $query->where('status', 1)->where('ends_at', '>', now()))
            ->orderByDesc('starts_at')
            ->get();

        return view('admin.flash_sales.flash_sales', [
            'flashSales' => $flashSales,
            'isVendor' => $isVendor,
            'timezone' => config('marketplace.timezone'),
        ]);
    }

    public function addEdit(Request $request, $id = null): View|RedirectResponse
    {
        Session::put('page', 'flash_sales');
        $flashSale = $id === null ? new FlashSale(['status' => 1]) : FlashSale::query()->findOrFail($id);
        $timezone = config('marketplace.timezone');

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:120'],
                'starts_at' => ['required', 'date'],
                'ends_at' => ['required', 'date', 'after:starts_at'],
            ], [
                'ends_at.after' => 'The sale must end after it starts.',
            ]);

            $flashSale->fill([
                'title' => $validated['title'],
                'starts_at' => Carbon::parse($validated['starts_at'], $timezone)->utc(),
                'ends_at' => Carbon::parse($validated['ends_at'], $timezone)->utc(),
                'status' => $request->boolean('status') ? 1 : 0,
            ])->save();

            return redirect('admin/flash-sales/'.$flashSale->id.'/items')->with('success_message', 'Flash sale saved. Add products to it below.');
        }

        return view('admin.flash_sales.add_edit_flash_sale', [
            'flashSale' => $flashSale,
            'title' => $flashSale->exists ? 'Edit Flash Sale' : 'Add Flash Sale',
            'timezone' => $timezone,
        ]);
    }

    public function items(int $id): View
    {
        Session::put('page', 'flash_sales');
        $admin = Auth::guard('admin')->user();
        $isVendor = $admin->type === 'vendor';
        $flashSale = FlashSale::query()->findOrFail($id);

        abort_if($isVendor && ($flashSale->status !== 1 || $flashSale->ends_at->isPast()), 404);

        $items = $flashSale->items()
            ->with('product:id,product_name,product_code,product_price,vendor_id')
            ->when($isVendor, fn ($query) => $query->where('vendor_id', $admin->vendor_id))
            ->orderBy('status')
            ->orderByDesc('id')
            ->get();

        $products = Product::query()
            ->where('status', 1)
            ->when($isVendor, fn ($query) => $query->where('vendor_id', $admin->vendor_id))
            ->whereNotIn('id', $flashSale->items()->select('product_id'))
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'product_code', 'product_price']);

        return view('admin.flash_sales.items', [
            'flashSale' => $flashSale,
            'items' => $items,
            'products' => $products,
            'isVendor' => $isVendor,
            'timezone' => config('marketplace.timezone'),
        ]);
    }

    public function storeItem(Request $request, int $id): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        $isVendor = $admin->type === 'vendor';
        $flashSale = FlashSale::query()->findOrFail($id);

        abort_if($flashSale->ends_at->isPast() || ($isVendor && $flashSale->status !== 1), 404);

        $ownProduct = Rule::exists('products', 'id');

        if ($isVendor) {
            $ownProduct->where('vendor_id', $admin->vendor_id);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', $ownProduct, Rule::unique('flash_sale_products', 'product_id')->where('flash_sale_id', $flashSale->id)],
            'discount_percent' => ['required', 'numeric', 'min:1', 'max:90'],
            'stock_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ], [
            'product_id.exists' => 'Choose one of your products.',
            'product_id.unique' => 'That product is already in this flash sale.',
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);

        FlashSaleProduct::query()->create([
            'flash_sale_id' => $flashSale->id,
            'product_id' => $product->id,
            'vendor_id' => (int) $product->vendor_id,
            'discount_percent' => $validated['discount_percent'],
            'stock_limit' => $validated['stock_limit'] ?? null,
            'status' => $isVendor ? FlashSaleProduct::PENDING : FlashSaleProduct::APPROVED,
        ]);

        return back()->with('success_message', $isVendor
            ? 'Submitted. Your product joins the sale once an admin approves it.'
            : 'Product added to the flash sale.');
    }

    public function updateItemStatus(Request $request, int $itemId): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([FlashSaleProduct::APPROVED, FlashSaleProduct::REJECTED])],
        ]);

        $item = FlashSaleProduct::query()->findOrFail($itemId);
        $item->update(['status' => (int) $validated['status']]);

        return back()->with('success_message', 'Flash sale item '.strtolower($item->statusLabel()).'.');
    }

    public function destroyItem(int $itemId): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        $item = FlashSaleProduct::query()
            ->when($admin->type === 'vendor', fn ($query) => $query->where('vendor_id', $admin->vendor_id))
            ->findOrFail($itemId);

        $item->delete();

        return back()->with('success_message', 'Product removed from the flash sale.');
    }
}
