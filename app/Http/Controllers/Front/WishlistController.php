<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $products = $request->user()->wishlist()
            ->visible()
            ->withCardData()
            ->orderByDesc('wishlists.created_at')
            ->get();

        return view('front.users.wishlist', ['products' => $products, 'meta_title' => 'Favourites']);
    }

    public function toggle(Request $request, int $product): JsonResponse|RedirectResponse
    {
        $product = Product::query()->visible()->findOrFail($product);
        $result = $request->user()->wishlist()->toggle($product->id);
        $wished = $result['attached'] !== [];

        if ($request->expectsJson()) {
            return response()->json([
                'wished' => $wished,
                'count' => $request->user()->wishlist()->count(),
                'message' => $wished ? 'Saved to your favourites.' : 'Removed from your favourites.',
            ]);
        }

        return back()->with('success_message', $wished ? 'Saved to your favourites.' : 'Removed from your favourites.');
    }
}
