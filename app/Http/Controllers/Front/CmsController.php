<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CmsController extends Controller
{

    public function contact(Request $request)
    {

        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [

                'name'    => 'required|string|max:100',
                'email'   => 'required|email|max:150',
                'subject' => 'required|max:200',
                'message' => 'required'
            ];
            $customMessages = [

                'name.required'    => 'Name is required',
                'name.string'      => 'Name must be string',

                'email.required'   => 'Email is required',
                'email.email'      => 'Valid email is required',

                'subject.required' => 'Subject is requireed',

                'message.required' => 'Message is required'
            ];

            $validator = \Illuminate\Support\Facades\Validator::make($data, $rules, $customMessages);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $email = 'admin@admin.com';
            $messageData = [
                'name'    => $data['name'],
                'email'   => $data['email'],
                'subject' => $data['subject'],
                'comment' => $data['message']
            ];

            \Illuminate\Support\Facades\Mail::send('emails.inquiry', $messageData, function ($message) use ($email) {
                $message->to($email)->subject('Inquiry from a user');
            });
            $message = 'Thanks for your inquiry. We will get back to you soon.';
            return redirect()->back()->with('success_message', $message);
        }
        return view('front.pages.contact');
    }
}
