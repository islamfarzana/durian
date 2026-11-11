<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\NewsletterSubscriber;

class NewsletterController extends Controller
{

    public function subscribers()
    {

        Session::put('page', 'subscribers');
        $subscribers = NewsletterSubscriber::get()->toArray();
        return view('admin.subscribers.subscribers')->with(compact('subscribers'));
    }

    public function updateSubscriberStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            if ($data['status'] == 'Active') {
                $status = 0;
            } else {
                $status = 1;
            }
            NewsletterSubscriber::where('id', $data['subscriber_id'])->update(['status' => $status]);
            return response()->json([
                'status'        => $status,
                'subscriber_id' => $data['subscriber_id']
            ]);
        }
    }

    public function deleteSubscriber($id)
    {
        NewsletterSubscriber::where('id', $id)->delete();

        $message = 'Subscriber has been deleted successfully!';
        return redirect()->back()->with('success_message', $message);
    }

    public function exportSubscribers()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\subscribersExport, 'subscribers.xlsx');
    }
}
