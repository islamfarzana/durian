<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\NewsletterSubscriber;

class NewsletterController extends Controller
{

    public function addSubscriber(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            $subscriberCount = NewsletterSubscriber::where('email', $data['subscriber_email'])->count();

            if ($subscriberCount > 0) {
                return 'Email already exists';
            } else {

                $subscriber = new NewsletterSubscriber;

                $subscriber->email = $data['subscriber_email'];
                $subscriber->status = 1;

                $subscriber->save();
                return 'Email saved in our database';
            }
        }
    }
}
