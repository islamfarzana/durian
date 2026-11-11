<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliverySlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class DeliverySlotController extends Controller
{
    public function index(): View
    {
        Session::put('page', 'delivery_slots');

        return view('admin.delivery_slots.delivery_slots', [
            'slots' => DeliverySlot::query()->orderBy('sort_order')->orderBy('starts_at')->get(),
        ]);
    }

    public function addEdit(Request $request, $id = null): View|RedirectResponse
    {
        Session::put('page', 'delivery_slots');
        $slot = $id === null ? new DeliverySlot(['status' => 1]) : DeliverySlot::query()->findOrFail($id);

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'label' => ['required', 'string', 'max:60'],
                'starts_at' => ['required', 'date_format:H:i'],
                'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
            ], [
                'ends_at.after' => 'The slot must end after it starts.',
            ]);

            $slot->fill([
                'label' => $validated['label'],
                'starts_at' => $validated['starts_at'].':00',
                'ends_at' => $validated['ends_at'].':00',
                'sort_order' => $validated['sort_order'] ?? 0,
                'status' => $request->boolean('status') ? 1 : 0,
            ])->save();

            return redirect('admin/delivery-slots')->with('success_message', 'Delivery slot saved.');
        }

        return view('admin.delivery_slots.add_edit_delivery_slot', [
            'slot' => $slot,
            'title' => $slot->exists ? 'Edit Delivery Slot' : 'Add Delivery Slot',
        ]);
    }

    public function updateStatus(int $id): RedirectResponse
    {
        $slot = DeliverySlot::query()->findOrFail($id);
        $slot->update(['status' => $slot->status === 1 ? 0 : 1]);

        return back()->with('success_message', $slot->label.' is now '.($slot->status === 1 ? 'open' : 'closed').'.');
    }
}
