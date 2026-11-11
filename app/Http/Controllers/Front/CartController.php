<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Cart\CartException;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function show(): View
    {
        return view('front.products.cart', [
            'meta_title' => 'Your bag',
        ]);
    }

    /**
     * Current bag for the docked panel and every quantity stepper on the page.
     */
    public function panel(): JsonResponse
    {
        return response()->json($this->payload());
    }

    /**
     * Add to the bag (mode=add) or set an exact quantity (mode=set, 0 removes).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'size' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
            'mode' => ['nullable', 'in:add,set'],
        ], [
            'size.required' => 'Please choose an option first.',
        ]);

        $product = Product::query()->visible()->find($validated['product_id']);

        if ($product === null) {
            return $this->failed($request, 'This product is no longer available.');
        }

        try {
            if (($validated['mode'] ?? 'add') === 'set') {
                $this->cart->set($product, $validated['size'], (int) $validated['quantity']);
            } else {
                $this->cart->add($product, $validated['size'], (int) $validated['quantity']);
            }
        } catch (CartException $exception) {
            return $this->failed($request, $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json($this->payload([
                'product_id' => $product->id,
                'size' => $validated['size'],
                'variant_quantity' => $this->cart->quantityOf($product, $validated['size']),
            ]));
        }

        return back()->with('success_message', 'Added to your bag.');
    }

    public function destroy(Request $request, int $cart): JsonResponse|RedirectResponse
    {
        $row = $this->cart->findRow($cart);

        abort_if($row === null, 404);

        $productId = (int) $row->product_id;
        $this->cart->remove($row);

        if ($request->expectsJson()) {
            return response()->json($this->payload(['product_id' => $productId]));
        }

        return back()->with('success_message', 'Removed from your bag.');
    }

    public function applyVoucher(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:64']], ['code.required' => 'Enter a voucher code.']);
        $result = $this->cart->applyVoucher($validated['code']);

        if (! $result->applies()) {
            return $this->failed($request, $result->error ?? 'That voucher does not apply to your bag.');
        }

        $message = 'Voucher applied — you save '.formatMoney($result->discount).'.';

        if ($request->expectsJson()) {
            return response()->json($this->payload(['message' => $message]));
        }

        return back()->with('success_message', $message);
    }

    public function removeVoucher(Request $request): JsonResponse|RedirectResponse
    {
        $this->cart->removeVoucher();

        if ($request->expectsJson()) {
            return response()->json($this->payload());
        }

        return back();
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function payload(array $extra = []): array
    {
        $bag = $this->cart->summary();

        return array_merge([
            'ok' => true,
            'count' => $bag->itemCount(),
            'subtotal' => formatMoney($bag->subtotal),
            'total' => formatMoney($bag->total()),
            'quantities' => (object) $bag->quantitiesByProduct(),
            'panel' => view('front.layout.cart_panel_body', ['bag' => $bag])->render(),
        ], $extra);
    }

    private function failed(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge($this->payload(), ['ok' => false, 'message' => $message]), 422);
        }

        return back()->with('error_message', $message);
    }
}
