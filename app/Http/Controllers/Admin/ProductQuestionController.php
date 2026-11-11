<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class ProductQuestionController extends Controller
{
    public function index(): View
    {
        Session::put('page', 'questions');

        return view('admin.questions.questions', [
            'questions' => $this->visibleQuestions()
                ->with(['product:id,product_name,vendor_id', 'user:id,name,email'])
                ->orderByRaw('CASE WHEN answer IS NULL THEN 0 ELSE 1 END')
                ->latest()
                ->get(),
            'isVendor' => Auth::guard('admin')->user()->type === 'vendor',
        ]);
    }

    public function answer(Request $request, int $id): RedirectResponse
    {
        $question = $this->visibleQuestions()->findOrFail($id);
        $validated = $request->validate(['answer' => ['required', 'string', 'max:1000']]);

        $question->update(['answer' => $validated['answer'], 'answered_at' => now()]);

        return back()->with('success_message', 'Answer published on the product page.');
    }

    public function updateStatus(int $id): RedirectResponse
    {
        $question = ProductQuestion::query()->findOrFail($id);
        $question->update(['status' => $question->status === 1 ? 0 : 1]);

        return back()->with('success_message', $question->status === 1 ? 'Question is visible again.' : 'Question hidden from the product page.');
    }

    /**
     * Vendors answer questions about their own products only.
     *
     * @return Builder<ProductQuestion>
     */
    private function visibleQuestions(): Builder
    {
        $admin = Auth::guard('admin')->user();

        return ProductQuestion::query()->when($admin->type === 'vendor', fn (Builder $query) => $query->where('vendor_id', $admin->vendor_id));
    }
}
