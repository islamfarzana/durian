<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class RatingController extends Controller
{
    public function ratings()
    {
        Session::put('page', 'ratings');
        $ratings = $this->visibleRatings()->with(['user', 'product'])->latest()->get()->toArray();
        $isVendor = Auth::guard('admin')->user()->type === 'vendor';

        return view('admin.ratings.ratings')->with(compact('ratings', 'isVendor'));
    }

    public function updateRatingStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            if ($data['status'] == 'Active') {
                $status = 0;
            } else {
                $status = 1;
            }
            Rating::where('id', $data['rating_id'])->update(['status' => $status]);

            return response()->json([
                'status' => $status,
                'rating_id' => $data['rating_id'],
            ]);
        }
    }

    public function deleteRating($id)
    {
        Rating::where('id', $id)->delete();
        $message = 'Rating has been deleted successfully!';

        return redirect()->back()->with('success_message', $message);
    }

    /**
     * A seller's public reply under a review of one of their products.
     */
    public function reply(Request $request, int $id): RedirectResponse
    {
        $rating = $this->visibleRatings()->findOrFail($id);
        $validated = $request->validate(['seller_reply' => ['required', 'string', 'max:1000']]);

        $rating->update([
            'seller_reply' => $validated['seller_reply'],
            'replied_at' => now(),
        ]);

        return back()->with('success_message', 'Reply saved. It shows under the review once the review is approved.');
    }

    /**
     * @return Builder<Rating>
     */
    private function visibleRatings(): Builder
    {
        $admin = Auth::guard('admin')->user();

        return Rating::query()->when(
            $admin->type === 'vendor',
            fn (Builder $query) => $query->whereIn('product_id', Product::query()->select('id')->where('vendor_id', $admin->vendor_id)),
        );
    }
}
