<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function loginRegister()
    {
        return view('front.users.login_register');
    }

    public function userDashboard(Request $request)
    {
        $user = $request->user();

        return view('front.users.user_dashboard', [
            'recentOrders' => $user->orders()->with('orders_products.product:id,product_image')->orderByDesc('id')->limit(5)->get(),
            'counts' => [
                'orders' => $user->orders()->count(),
                'processing' => $user->orders()->whereIn('order_status', ['New', 'Pending', 'Order Placed', 'In Process', 'Paid'])->count(),
                'wishlist' => $user->wishlist()->count(),
                'stores' => $user->followedStores()->count(),
            ],
            'followedStores' => $user->followedStores()->with('businessDetails:id,vendor_id,shop_name')->limit(6)->get(),
            'meta_title' => 'My account',
        ]);
    }

    public function followedStores(Request $request)
    {
        return view('front.users.followed_stores', [
            'stores' => $request->user()->followedStores()
                ->with('businessDetails:id,vendor_id,shop_name,shop_city')
                ->withCount(['followers', 'products' => fn ($products) => $products->where('status', 1)])
                ->orderByDesc('store_follows.created_at')
                ->get(),
            'meta_title' => 'Followed stores',
        ]);
    }

    public function userRegister(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            $validator = Validator::make($request->all(), [

                'name' => 'required|string|max:100',
                'mobile' => 'required|numeric|digits:11',
                'email' => 'required|email|max:150|unique:users',
                'password' => 'required|min:6',
                'accept' => 'required',

            ], [

                'accept.required' => 'Please accept our Terms & Conditions',
            ]);
            if ($validator->passes()) {

                $user = new User;

                $user->name = $data['name'];
                $user->mobile = $data['mobile'];
                $user->email = $data['email'];
                $user->password = bcrypt($data['password']);
                $user->status = 0;

                $user->save();

                $email = $data['email'];
                $messageData = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'code' => base64_encode($data['email']),
                ];
                Mail::send('emails.confirmation', $messageData, function ($message) use ($email) {
                    $message->to($email)->subject('Confirm your E-commerce Application Account');
                });
                $redirectTo = url('user/login-register');

                return response()->json([
                    'type' => 'success',
                    'url' => $redirectTo,
                    'message' => 'Please confirm your email to activate your account!',
                ]);                /*

                $message = 'Dear customer, you have successfully registered with E-commerce Application. Login to your account to access orders, addresses and available offers';
                $mobile = $data['mobile'];
                \App\Models\Sms::sendSms($message, $mobile);
                */
            } else {

                return response()->json([
                    'type' => 'error',
                    'errors' => $validator->messages(),
                ]);
            }
        }
    }

    public function userLogin(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            $validator = Validator::make($request->all(), [

                'email' => 'required|email|max:150|exists:users',
                'password' => 'required|min:6',
            ]);
            if ($validator->passes()) {

                if (Auth::attempt([
                    'email' => $data['email'],
                    'password' => $data['password'],
                ])) {

                    if (Auth::user()->status == 0) {
                        Auth::logout();

                        return response()->json([
                            'type' => 'inactive',

                            'message' => 'Your account is not activated! Please confirm your account (by clicking on the Activation Link in the Confirmation Mail) to activate your account.',
                        ]);
                    }
                    if (! empty(Session::get('session_id'))) {
                        $user_id = Auth::user()->id;
                        $session_id = Session::get('session_id');

                        Cart::where('session_id', $session_id)->update(['user_id' => $user_id]);
                    }
                    $request->session()->regenerate();
                    $hasCart = Cart::where('user_id', Auth::id())->exists();
                    $redirectTo = $request->session()->pull('url.intended', $hasCart ? url('cart') : url('user/dashboard'));

                    return response()->json([
                        'type' => 'success',
                        'url' => $redirectTo,
                    ]);
                } else {

                    return response()->json([
                        'type' => 'incorrect',
                        'message' => 'Incorrect Email or Password! Wrong Credentials!',
                    ]);
                }
            } else {

                return response()->json([
                    'type' => 'error',
                    'errors' => $validator->messages(),
                ]);
            }
        }
    }

    public function userLogout()
    {
        Auth::logout();
        Session::flush();

        return redirect('/');
    }

    public function confirmAccount($code)
    {
        $email = base64_decode($code);
        $userCount = User::where('email', $email)->count();
        if ($userCount > 0) {

            $userDetails = User::where('email', $email)->first();
            if ($userDetails->status == 1) {

                return redirect('user/login-register')->with('error_message', 'Your account is already activated. You can login now.');
            } else {
                User::where('email', $email)->update([
                    'status' => 1,
                ]);

                $messageData = [
                    'name' => $userDetails->name,
                    'mobile' => $userDetails->mobile,
                    'email' => $email,

                ];
                Mail::send('emails.register', $messageData, function ($message) use ($email) {
                    $message->to($email)->subject('Welcome to E-commerce Application');
                });

                return redirect('user/login-register')->with('success_message', 'Your account is activated. You can login now.');
            }
        } else {
            abort(404);
        }
    }

    public function forgotPassword(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();

            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:150|exists:users',

            ], [
                'email.exists' => 'Email does not exist',
            ]);
            if ($validator->passes()) {
                $new_password = Str::random(16);
                User::where('email', $data['email'])->update([
                    'password' => bcrypt($new_password),
                ]);
                $userDetails = User::where('email', $data['email'])->first()->toArray();
                $email = $data['email'];
                $messageData = [
                    'name' => $userDetails['name'],
                    'email' => $email,
                    'password' => $new_password,

                ];
                Mail::send('emails.user_forgot_password', $messageData, function ($message) use ($email) {
                    $message->to($email)->subject('New Password - E-commerce Application');
                });

                return response()->json([
                    'type' => 'success',
                    'message' => 'New Password sent to your registered email.',
                ]);
            } else {

                return response()->json([
                    'type' => 'error',
                    'errors' => $validator->messages(),
                ]);
            }
        } else {
            return view('front.users.forgot_password');
        }
    }

    public function userAccount(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            $validator = Validator::make($request->all(), [

                'name' => 'required|string|max:100',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'address' => 'required|string|max:100',
                'country' => 'required|string|max:100',
                'mobile' => 'required|numeric|digits_between:10,11',
                'pincode' => 'required|digits_between:4,6',

            ] /*, [

                'accept.required' => 'Please accept our Terms & Conditions'
            ]*/);

            if ($validator->passes()) {

                User::where('id', Auth::user()->id)->update([
                    'name' => $data['name'],
                    'mobile' => $data['mobile'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'country' => $data['country'],
                    'pincode' => $data['pincode'],
                    'address' => $data['address'],
                ]);

                return response()->json([
                    'type' => 'success',

                    'message' => 'Your contact/billing details successfully updated!',
                ]);
            } else {

                return response()->json([
                    'type' => 'error',
                    'errors' => $validator->messages(),
                ]);
            }
        } else {

            $countries = Country::where('status', 1)->get()->toArray();

            return view('front.users.user_account')->with(compact('countries'));
        }
    }

    public function userUpdatePassword(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            $validator = Validator::make($request->all(), [

                'current_password' => 'required',
                'new_password' => 'required|min:6',
                'confirm_password' => 'required|min:6|same:new_password',

            ] /*, [

                'accept.required' => 'Please accept our Terms & Conditions'
            ]*/);

            if ($validator->passes()) {
                $current_password = $data['current_password'];
                $checkPassword = User::where('id', Auth::user()->id)->first();

                if (Hash::check($current_password, $checkPassword->password)) {

                    $user = User::find(Auth::user()->id);
                    $user->password = bcrypt($data['new_password']);
                    $user->save();

                    return response()->json([
                        'type' => 'success',
                        'message' => 'Account password successfully updated!',
                    ]);
                } else {
                    return response()->json([
                        'type' => 'incorrect',
                        'message' => 'Your current password is incorrect!',
                    ]);
                }
            } else {

                return response()->json([
                    'type' => 'error',
                    'errors' => $validator->messages(),
                ]);
            }
        }
    }
}
