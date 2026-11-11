<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Rating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    /**
     * Reviews are open to customers who ordered the product, once per product,
     * and appear after an admin approves them.
     */
    public function addRating(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->back()->with('error_message', 'Log in to rate this product');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Please click on a star to rate the product!',
            'rating.between' => 'Please click on a star to rate the product!',
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        $user = Auth::user();

        if (! $user->hasPurchased($product)) {
            return redirect()->back()->with('error_message', 'Only customers who ordered this product can review it.');
        }

        if (Rating::query()->where('user_id', $user->id)->where('product_id', $product->id)->exists()) {
            return redirect()->back()->with('error_message', 'You\'ve already rated this product before!');
        }

        $rating = new Rating;
        $rating->user_id = $user->id;
        $rating->product_id = $product->id;
        $rating->review = $validated['review'] ?? '';
        $rating->rating = $validated['rating'];
        $rating->status = 0;
        $rating->save();

        return redirect()->back()->with('success_message', 'Thanks for rating the product! It\'ll be shown after being approved by an admin!');
    }
}
