<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductQuestionController extends Controller
{
    public function store(Request $request, int $product): JsonResponse|RedirectResponse
    {
        $product = Product::query()->visible()->findOrFail($product);
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'question.required' => 'Type your question first.',
            'question.min' => 'Please write a little more so the seller can help.',
        ]);

        ProductQuestion::query()->create([
            'product_id' => $product->id,
            'vendor_id' => (int) $product->vendor_id,
            'user_id' => $request->user()->id,
            'question' => $validated['question'],
            'status' => 1,
        ]);

        $message = 'Thanks! The seller will answer your question here.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return back()->with('success_message', $message);
    }
}
