<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\FlashSaleProduct;
use App\Models\Order;
use App\Models\OrdersProduct;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductsAttribute;
use App\Models\Rating;
use App\Models\Section;
use App\Models\StoreTheme;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Models\VendorsBankDetail;
use App\Models\VendorsBusinessDetail;
use App\Services\Vendors\VendorLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Facades\Image;

class AdminController extends Controller
{
    public function dashboard(VendorLedger $ledger)
    {

        Session::put('page', 'dashboard');
        $admin = Auth::guard('admin')->user();

        if ($admin->type === 'vendor') {
            return $this->vendorDashboard($ledger, $admin->vendor_id);
        }

        $sectionsCount = Section::count();
        $categoriesCount = Category::count();
        $productsCount = Product::count();
        $ordersCount = Order::count();
        $couponsCount = Coupon::count();
        $brandsCount = Brand::count();
        $usersCount = User::count();
        $openOrders = Order::query()->where('order_status', '!=', 'Cancelled');
        $gmv = (float) (clone $openOrders)->sum('grand_total');

        $marketplace = [
            'gmv' => $gmv,
            'orders_today' => (clone $openOrders)->whereDate('created_at', now()->toDateString())->count(),
            'average_order' => $ordersCount > 0 ? $gmv / max(1, (clone $openOrders)->count()) : 0,
            'commission' => (float) OrdersProduct::query()
                ->join('orders', 'orders.id', '=', 'orders_products.order_id')
                ->where('orders.order_status', '!=', 'Cancelled')
                ->where('orders_products.vendor_id', '>', 0)
                ->selectRaw('COALESCE(SUM(orders_products.product_price * orders_products.product_qty * orders_products.commission / 100), 0) as total')
                ->value('total'),
            'active_vendors' => Vendor::query()->where('status', 1)->count(),
            'pending_vendors' => Vendor::query()->where('status', 0)->count(),
            'pending_payouts' => VendorPayout::query()->where('status', VendorPayout::REQUESTED)->count(),
            'pending_payout_amount' => (float) VendorPayout::query()->where('status', VendorPayout::REQUESTED)->sum('amount'),
            'pending_reviews' => Rating::query()->where('status', 0)->count(),
            'pending_flash_items' => FlashSaleProduct::query()->where('status', FlashSaleProduct::PENDING)->count(),
            'unanswered_questions' => ProductQuestion::query()->whereNull('answer')->count(),
        ];

        $topStores = OrdersProduct::query()
            ->join('orders', 'orders.id', '=', 'orders_products.order_id')
            ->where('orders.order_status', '!=', 'Cancelled')
            ->where('orders_products.vendor_id', '>', 0)
            ->groupBy('orders_products.vendor_id')
            ->selectRaw('orders_products.vendor_id, SUM(orders_products.product_price * orders_products.product_qty) as gross, SUM(orders_products.product_qty) as units')
            ->orderByDesc('gross')
            ->limit(5)
            ->get();
        $storeNames = Vendor::query()->with('businessDetails:id,vendor_id,shop_name')->whereIn('id', $topStores->pluck('vendor_id'))->get()->keyBy('id');
        $recentOrders = Order::query()->orderByDesc('id')->limit(8)->get();

        return view('admin/dashboard')->with(compact('sectionsCount', 'categoriesCount', 'productsCount', 'ordersCount', 'couponsCount', 'brandsCount', 'usersCount', 'marketplace', 'topStores', 'storeNames', 'recentOrders'));
    }

    public function updateVendorOfficial(Request $request)
    {
        $validated = $request->validate(['vendor_id' => ['required', 'integer', 'exists:vendors,id']]);
        $vendor = Vendor::query()->findOrFail($validated['vendor_id']);
        $vendor->is_official = ! $vendor->is_official;
        $vendor->save();

        return redirect()->back()->with('success_message', $vendor->store_name.($vendor->is_official ? ' now has the Official Store badge.' : ' no longer has the Official Store badge.'));
    }

    private function vendorDashboard(VendorLedger $ledger, int $vendorId)
    {
        $vendor = Vendor::query()->withCount('followers')->findOrFail($vendorId);
        $lines = OrdersProduct::query()->where('vendor_id', $vendor->id);
        $ratings = Rating::query()
            ->where('status', 1)
            ->whereIn('product_id', Product::query()->select('id')->where('vendor_id', $vendor->id))
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        return view('admin.vendor_dashboard', [
            'vendor' => $vendor,
            'summary' => $ledger->summary($vendor),
            'lineCounts' => [
                'to_ship' => (clone $lines)->where(fn ($line) => $line->whereNull('item_status')->orWhereIn('item_status', ['Pending', 'In Process']))->count(),
                'shipped' => (clone $lines)->where('item_status', 'Shipped')->count(),
                'delivered' => (clone $lines)->where('item_status', 'Delivered')->count(),
            ],
            'productsCount' => Product::query()->where('vendor_id', $vendor->id)->count(),
            'lowStock' => ProductsAttribute::query()
                ->with('product:id,product_name')
                ->whereIn('product_id', Product::query()->select('id')->where('vendor_id', $vendor->id)->where('status', 1))
                ->where('status', 1)
                ->where('stock', '<=', 5)
                ->orderBy('stock')
                ->limit(8)
                ->get(),
            'ratingAverage' => (int) $ratings->total > 0 ? round((float) $ratings->average, 1) : null,
            'ratingTotal' => (int) $ratings->total,
            'unansweredQuestions' => ProductQuestion::query()->where('vendor_id', $vendor->id)->whereNull('answer')->count(),
            'recentLines' => OrdersProduct::query()->with('order:id,order_status,created_at,city')->where('vendor_id', $vendor->id)->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    public function login(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [
                'email' => 'required|email|max:255',
                'password' => 'required',
            ];

            $customMessages = [
                'email.required' => 'Email Address is required!',
                'email.email' => 'Valid Email Address is required',
                'password.required' => 'Password is required!',
            ];

            $this->validate($request, $rules, $customMessages);
            if (Auth::guard('admin')->attempt(['email' => $data['email'], 'password' => $data['password']])) {
                if (Auth::guard('admin')->user()->type == 'vendor' && Auth::guard('admin')->user()->confirm == 'No') {
                    Auth::guard('admin')->logout();

                    return redirect()->back()->with('error_message', 'Please confirm your email to activate your Vendor Account');
                } elseif (Auth::guard('admin')->user()->type != 'vendor' && Auth::guard('admin')->user()->status == '0') {
                    Auth::guard('admin')->logout();

                    return redirect()->back()->with('error_message', 'Your admin account is not active');
                } else {
                    $request->session()->regenerate();

                    return redirect('/admin/dashboard');
                }
            } else {
                return redirect()->back()->with('error_message', 'Invalid Email or Password');
            }
        }

        return view('admin/login');
    }

    public function logout()
    {

        if (Auth::guard('admin')->user()->type != 'vendor') {
            Auth::guard('admin')->logout();

            return redirect('admin/login');
        } else {
            Auth::guard('admin')->logout();

            return redirect('vendor/login-register');
        }
    }

    public function updateAdminPassword(Request $request)
    {

        Session::put('page', 'update_admin_password');
        if ($request->isMethod('post')) {
            $data = $request->all();

            if (Hash::check($data['current_password'], Auth::guard('admin')->user()->password)) {

                if ($data['confirm_password'] == $data['new_password']) {
                    Admin::where('id', Auth::guard('admin')->user()->id)->update([
                        'password' => bcrypt($data['new_password']),
                    ]);

                    return redirect()->back()->with('success_message', 'Admin Password has been updated successfully!');
                } else {
                    return redirect()->back()->with('error_message', 'New Password and Confirm Password does not match!');
                }
            } else {
                return redirect()->back()->with('error_message', 'Your current admin password is Incorrect!');
            }
        }
        $adminDetails = Admin::where('email', Auth::guard('admin')->user()->email)->first()->toArray();

        return view('admin/profiles/update_admin_password')->with(compact('adminDetails'));
    }

    public function checkAdminPassword(Request $request)
    {
        $data = $request->all();

        if (Hash::check($data['current_password'], Auth::guard('admin')->user()->password)) {
            return 'true';
        } else {
            return 'false';
        }
    }

    public function updateAdminDetails(Request $request)
    {

        Session::put('page', 'update_admin_details');
        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'admin_name' => 'required|regex:/^[\pL\s\-]+$/u',
                'admin_mobile' => 'required|numeric',
            ];

            $customMessages = [
                'admin_name.required' => 'Name is required',
                'admin_name.regex' => 'Valid Name is required',
                'admin_mobile.required' => 'Mobile is required',
                'admin_mobile.numeric' => 'Valid Mobile is required',
            ];

            $this->validate($request, $rules, $customMessages);

            if ($request->hasFile('admin_image')) {
                $image_tmp = $request->file('admin_image');
                if ($image_tmp->isValid()) {

                    $extension = $image_tmp->getClientOriginalExtension();
                    $imageName = rand(111, 99999).'.'.$extension;
                    $imagePath = 'admin/images/photos/'.$imageName;
                    Image::make($image_tmp)->save($imagePath);
                }
            } elseif (! empty($data['current_admin_image'])) {
                $imageName = $data['current_admin_image'];
            } else {
                $imageName = '';
            }
            Admin::where('id', Auth::guard('admin')->user()->id)->update([
                'name' => $data['admin_name'],
                'mobile' => $data['admin_mobile'],
                'image' => $imageName,
            ]);

            return redirect()->back()->with('success_message', 'Admin details updated successfully!');
        }

        return view('admin/profiles/update_admin_details');
    }

    public function deleteAdminImage($id)
    {

        $adminImage = Admin::select('image')->where('id', $id)->first();
        $admin_image_path = 'admin/images/photos/';
        if (file_exists($admin_image_path.$adminImage->image)) {
            unlink($admin_image_path.$adminImage->image);
        }
        Admin::where('id', $id)->update(['image' => '']);

        $message = 'Admin Image has been deleted successfully!';

        return redirect()->back()->with('success_message', $message);
    }

    public function addEditAdmin(Request $request, $id = null)
    {

        Session::put('page', 'add_edit_admin');

        if ($id == '') {
            $title = 'Add New Admin';
            $admin = new Admin;

            $message = 'Admin user added successfully!';
        } else {
            $title = 'Edit Admin Details';
            $admin = Admin::find($id);

            $message = 'Admin details updated successfully!';
        }

        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'admin_name' => 'required|regex:/^[\pL\s\-]+$/u',
                'admin_mobile' => 'required|numeric',
                'email' => 'required|email'.($data['email'] != $admin->email ? '|unique:admins' : ''),
            ];

            if ($admin) {
                $customMessages = [
                    'admin_name.required' => 'Name is required',
                    'admin_name.regex' => 'Valid Name is required',
                    'admin_mobile.required' => 'Mobile is required',
                    'admin_mobile.numeric' => 'Valid Mobile is required',
                    'email.required' => 'Email is required',
                    'email.unique' => 'Email alreay exists',
                ];
            }

            $this->validate($request, $rules, $customMessages);

            if (! empty($data['password'])) {
                if ($data['confirm_password'] != $data['password']) {
                    return redirect()->back()->with('error_message', 'Password does not match!');
                }

                $admin->password = bcrypt($data['password']);
            }
            if ($request->hasFile('admin_image')) {
                $image_tmp = $request->file('admin_image');
                if ($image_tmp->isValid()) {

                    $extension = $image_tmp->getClientOriginalExtension();
                    $imageName = rand(111, 99999).'.'.$extension;
                    $imagePath = 'admin/images/photos/'.$imageName;
                    Image::make($image_tmp)->save($imagePath);
                }
            } else {
                $imageName = ! empty($admin->image) ? $admin->image : '';
            }
            $admin->name = $data['admin_name'];
            $admin->mobile = $data['admin_mobile'];
            $admin->email = $data['email'];
            $admin->type = $data['admin_type'];
            $admin->status = 0;
            $admin->vendor_id = 0;
            $admin->image = $imageName;

            $admin->save();

            return redirect('admin/admins')->with('success_message', $message);
        }

        return view('admin.admins.add_edit_admin')->with(compact('title', 'admin'));
    }

    public function updateVendorDetails($slug, Request $request)
    {
        if ($slug == 'personal') {

            Session::put('page', 'update_personal_details');
            if ($request->isMethod('post')) {
                $data = $request->all();
                $rules = [
                    'vendor_name' => 'required|regex:/^[\pL\s\-]+$/u',
                    'vendor_city' => 'required|regex:/^[\pL\s\-]+$/u',
                    'vendor_mobile' => 'required|numeric',
                ];

                $customMessages = [
                    'vendor_name.required' => 'Name is required',
                    'vendor_city.required' => 'City is required',
                    'vendor_city.regex' => 'Valid City alphabetical is required',
                    'vendor_name.regex' => 'Valid Name is required',
                    'vendor_mobile.required' => 'Mobile is required',
                    'vendor_mobile.numeric' => 'Valid Mobile is required',
                ];

                $this->validate($request, $rules, $customMessages);
                if ($request->hasFile('vendor_image')) {
                    $image_tmp = $request->file('vendor_image');

                    if ($image_tmp->isValid()) {

                        $extension = $image_tmp->getClientOriginalExtension();
                        $imageName = rand(111, 99999).'.'.$extension;
                        $imagePath = 'admin/images/photos/'.$imageName;
                        Image::make($image_tmp)->save($imagePath);
                    }
                } elseif (! empty($data['current_vendor_image'])) {
                    $imageName = $data['current_vendor_image'];
                } else {
                    $imageName = '';
                }

                Admin::where('id', Auth::guard('admin')->user()->id)->update([
                    'name' => $data['vendor_name'],
                    'mobile' => $data['vendor_mobile'],
                    'image' => $imageName,
                ]);
                Vendor::where('id', Auth::guard('admin')->user()->vendor_id)->update([
                    'name' => $data['vendor_name'],
                    'mobile' => $data['vendor_mobile'],
                    'address' => $data['vendor_address'],
                    'city' => $data['vendor_city'],
                    'state' => $data['vendor_state'],
                    'country' => $data['vendor_country'],
                    'pincode' => $data['vendor_pincode'],
                ]);

                return redirect()->back()->with('success_message', 'Vendor details updated successfully!');
            }
            $vendorDetails = Vendor::where('id', Auth::guard('admin')->user()->vendor_id)->first()->toArray();
        } elseif ($slug == 'business') {

            Session::put('page', 'update_business_details');
            if ($request->isMethod('post')) {
                $data = $request->all();
                $rules = [
                    'shop_name' => 'required|regex:/^[\pL\s\-]+$/u',
                    'shop_city' => 'required|regex:/^[\pL\s\-]+$/u',
                    'shop_mobile' => 'required|numeric',
                    'address_proof' => 'required',
                ];

                $customMessages = [
                    'shop_name.required' => 'Name is required',
                    'shop_city.required' => 'City is required',
                    'shop_city.regex' => 'Valid City alphabetical is required',
                    'shop_name.regex' => 'Valid Shop Name is required',
                    'shop_mobile.required' => 'Mobile is required',
                    'shop_mobile.numeric' => 'Valid Mobile is required',
                ];

                $this->validate($request, $rules, $customMessages);
                if ($request->hasFile('address_proof_image')) {
                    $image_tmp = $request->file('address_proof_image');

                    if ($image_tmp->isValid()) {

                        $extension = $image_tmp->getClientOriginalExtension();
                        $imageName = rand(111, 99999).'.'.$extension;
                        $imagePath = 'admin/images/proofs/'.$imageName;
                        Image::make($image_tmp)->save($imagePath);
                    }
                } elseif (! empty($data['current_address_proof'])) {
                    $imageName = $data['current_address_proof'];
                } else {
                    $imageName = '';
                }
                $vendorCount = VendorsBusinessDetail::where('vendor_id', Auth::guard('admin')->user()->vendor_id)->count();
                if ($vendorCount > 0) {

                    VendorsBusinessDetail::where('vendor_id', Auth::guard('admin')->user()->vendor_id)->update([
                        'shop_name' => $data['shop_name'],
                        'shop_mobile' => $data['shop_mobile'],
                        'shop_website' => $data['shop_website'],
                        'shop_address' => $data['shop_address'],
                        'shop_city' => $data['shop_city'],
                        'shop_state' => $data['shop_state'],
                        'shop_country' => $data['shop_country'],
                        'shop_pincode' => $data['shop_pincode'],
                        'business_license_number' => $data['business_license_number'],
                        'gst_number' => $data['gst_number'],
                        'pan_number' => $data['pan_number'],
                        'address_proof' => $data['address_proof'],
                        'address_proof_image' => $imageName,
                    ]);
                } else {

                    VendorsBusinessDetail::insert([
                        'vendor_id' => Auth::guard('admin')->user()->vendor_id,
                        'shop_name' => $data['shop_name'],
                        'shop_mobile' => $data['shop_mobile'],
                        'shop_website' => $data['shop_website'],
                        'shop_address' => $data['shop_address'],
                        'shop_city' => $data['shop_city'],
                        'shop_state' => $data['shop_state'],
                        'shop_country' => $data['shop_country'],
                        'shop_pincode' => $data['shop_pincode'],
                        'business_license_number' => $data['business_license_number'],
                        'gst_number' => $data['gst_number'],
                        'pan_number' => $data['pan_number'],
                        'address_proof' => $data['address_proof'],
                        'address_proof_image' => $imageName,
                    ]);
                }

                $themeId = $request->input('store_theme_id');
                $themeId = ($themeId === null || $themeId === '') ? null : (int) $themeId;

                if ($themeId !== null && ! StoreTheme::query()->whereKey($themeId)->where('status', 1)->exists()) {
                    $themeId = null;
                }

                Vendor::where('id', Auth::guard('admin')->user()->vendor_id)->update(['theme_id' => $themeId]);

                return redirect()->back()->with('success_message', 'Vendor details updated successfully!');
            }
            $vendorCount = VendorsBusinessDetail::where('vendor_id', Auth::guard('admin')->user()->vendor_id)->count();

            if ($vendorCount > 0) {
                $vendorDetails = VendorsBusinessDetail::where('vendor_id', Auth::guard('admin')->user()->vendor_id)->first()->toArray();
            } else {
                $vendorDetails = [];
            }
        } elseif ($slug == 'bank') {

            Session::put('page', 'update_bank_details');
            if ($request->isMethod('post')) {
                $data = $request->all();
                $rules = [
                    'account_holder_name' => 'required|regex:/^[\pL\s\-]+$/u',
                    'bank_name' => 'required',
                    'account_number' => 'required|numeric',
                    'bank_ifsc_code' => 'required',
                ];

                $customMessages = [
                    'account_holder_name.required' => 'Account Holder Name is required',
                    'bank_name.required' => 'Bank Name is required',
                    'account_holder_name.regex' => 'Valid Account Holder Name is required',
                    'account_number.required' => 'Account Number is required',
                    'account_number.numeric' => 'Valid Account Number is required',
                    'bank_ifsc_code.required' => 'Bank IFSC Code is required',
                ];

                $this->validate($request, $rules, $customMessages);
                $vendorCount = VendorsBankDetail::where('vendor_id', Auth::guard('admin')->user()->vendor_id)->count();
                if ($vendorCount > 0) {

                    VendorsBankDetail::where('vendor_id', Auth::guard('admin')->user()->vendor_id)->update([
                        'account_holder_name' => $data['account_holder_name'],
                        'bank_name' => $data['bank_name'],
                        'account_number' => $data['account_number'],
                        'bank_ifsc_code' => $data['bank_ifsc_code'],
                    ]);
                } else {

                    VendorsBankDetail::insert([
                        'vendor_id' => Auth::guard('admin')->user()->vendor_id,
                        'account_holder_name' => $data['account_holder_name'],
                        'bank_name' => $data['bank_name'],
                        'account_number' => $data['account_number'],
                        'bank_ifsc_code' => $data['bank_ifsc_code'],
                    ]);
                }

                return redirect()->back()->with('success_message', 'Vendor details updated successfully!');
            }
            $vendorCount = VendorsBankDetail::where('vendor_id', Auth::guard('admin')->user()->vendor_id)->count();
            if ($vendorCount > 0) {
                $vendorDetails = VendorsBankDetail::where('vendor_id', Auth::guard('admin')->user()->vendor_id)->first()->toArray();
            } else {
                $vendorDetails = [];
            }
        }
        $countries = Country::where('status', 1)->get()->toArray();
        $storeThemes = StoreTheme::query()->where('status', 1)->orderBy('name')->get();
        $vendorThemeId = Vendor::query()->whereKey(Auth::guard('admin')->user()->vendor_id)->value('theme_id');

        return view('admin/profiles/update_vendor_details')->with(compact('slug', 'vendorDetails', 'countries', 'storeThemes', 'vendorThemeId'));
    }

    public function updateVendorCommission(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            Vendor::where('id', $data['vendor_id'])->update(['commission' => $data['commission']]);

            return redirect()->back()->with('success_message', 'Vendor commission updated successfully!');
        }
    }

    public function admins($type = null)
    {
        $admins = Admin::query();
        if (! empty($type)) {
            $admins = $admins->where('type', $type);
            $title = ucfirst($type).'s';
            Session::put('page', 'view_'.strtolower($title));
        } else {
            $admins = $admins->whereNotIn('type', ['vendor', 'superadmin']);
            $title = 'All Admins/Subadmins';
            Session::put('page', 'view_all');
        }

        $admins = $admins->get()->toArray();
        $officialVendorIds = Vendor::query()->where('is_official', true)->pluck('id')->map(fn ($id): int => (int) $id)->all();

        return view('admin/admins/admins')->with(compact('admins', 'title', 'officialVendorIds'));
    }

    public function viewVendorDetails($id)
    {
        $vendorDetails = Admin::with('vendorPersonal', 'vendorBusiness', 'vendorBank')->where('id', $id)->first();
        $vendorDetails = json_decode(json_encode($vendorDetails), true);

        return view('admin/admins/view_vendor_details')->with(compact('vendorDetails'));
    }

    public function updateAdminStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            if ($data['status'] == 'Active') {
                $status = 0;
            } else {
                $status = 1;
            }
            Admin::where('id', $data['admin_id'])->update(['status' => $status]);
            $adminDetails = Admin::where('id', $data['admin_id'])->first()->toArray();
            if ($adminDetails['type'] == 'vendor' && $status == 1) {
                Vendor::where('id', $adminDetails['vendor_id'])->update(['status' => $status]);
                $email = $adminDetails['email'];
                $messageData = [
                    'email' => $adminDetails['email'],
                    'name' => $adminDetails['name'],
                    'mobile' => $adminDetails['mobile'],
                ];

                Mail::send('emails.vendor_approved', $messageData, function ($message) use ($email) {
                    $message->to($email)->subject('Vendor Account is Approved');
                });
            }

            $adminType = Auth::guard('admin')->user()->type;

            return response()->json([
                'status' => $status,
                'admin_id' => $data['admin_id'],
            ]);
        }
    }
}
