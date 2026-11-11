<?php

namespace App\Http\Controllers\Admin;

use App\Models\City;
use App\Models\Country;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    // Country
    public function countries()
    {

        Session::put('page', 'country');
        $countries = Country::get()->toArray();
        return view('admin.countries.countries')->with(compact('countries'));
    }

    public function updateCountryStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            if ($data['status'] == 'Active') {
                $status = 0;
            } else {
                $status = 1;
            }
            Country::where('id', $data['country_id'])->update(['status' => $status]);
            return response()->json([
                'status'     => $status,
                'country_id' => $data['country_id']
            ]);
        }
    }

    public function deleteCountry($id)
    {
        Country::where('id', $id)->delete();

        $message = 'Country has been deleted successfully!';

        return redirect()->back()->with('success_message', $message);
    }

    public function addEditCountry(Request $request, $id = null)
    {

        Session::put('page', 'country');
        if ($id == '') {
            $title = 'Add Country';
            $country = new Country();

            $message = 'Country added successfully!';
        } else {
            $title = 'Edit Country';
            $country = Country::find($id);

            $message = 'Country updated successfully!';
        }

        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [
                'country_name' => 'required|regex:/^[\pL\s\-\',&]+$/u',
            ];

            $customMessages = [
                'country_name.required' => 'Country Name is required',
                'country_name.regex'    => 'Valid Country Name is required',
            ];

            $this->validate($request, $rules, $customMessages);
            $country->country_name   = $data['country_name'];
            $country->country_code   = $data['country_code'];
            $country->language   = $data['language'];
            $country->status = 1;
            $country->save();
            return redirect('admin/countries')->with('success_message', $message);
        }
        return view('admin.countries.add_edit_country')->with(compact('title', 'country'));
    }

    // City
    public function cities()
    {
        Session::put('page', 'city');
        $cities = City::with(['country'])->get()->toArray();
        return view('admin.cities.cities')->with(compact('cities'));
    }

    public function updateCityStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            if ($data['status'] == 'Active') {
                $status = 0;
            } else {
                $status = 1;
            }
            City::where('id', $data['city_id'])->update(['status' => $status]);
            return response()->json([
                'status'     => $status,
                'city_id' => $data['city_id']
            ]);
        }
    }

    public function deleteCity($id)
    {
        City::where('id', $id)->delete();

        $message = 'City has been deleted successfully!';

        return redirect()->back()->with('success_message', $message);
    }

    public function addEditCity(Request $request, $id = null)
    {
        Session::put('page', 'city');
        if ($id == '') {
            $title = 'Add City';
            $city = new City();

            $message = 'City added successfully!';
        } else {
            $title = 'Edit City';
            $city = City::find($id);

            $message = 'City updated successfully!';
        }

        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [
                'city_name' => 'required|regex:/^[\pL\s\-\',&]+$/u',
            ];

            $customMessages = [
                'city_name.required' => 'City Name is required',
                'city_name.regex'    => 'Valid City Name is required',
            ];

            $this->validate($request, $rules, $customMessages);
            $city->city_name   = $data['city_name'];
            $city->country_id   = $data['country_id'];
            $city->status = 1;
            $city->save();
            return redirect('admin/cities')->with('success_message', $message);
        }
        $countries = \App\Models\Country::where('status', 1)->get()->toArray();
        return view('admin.cities.add_edit_city')->with(compact('title', 'city', 'countries'));
    }
}
