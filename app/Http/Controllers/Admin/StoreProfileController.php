<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Intervention\Image\Facades\Image;

/**
 * The public face of a vendor's store: URL, logo, banner, tagline, and holiday mode.
 */
class StoreProfileController extends Controller
{
    private const IMAGE_DIRECTORY = 'front/images/store_images';

    public function edit(): View
    {
        Session::put('page', 'store_profile');
        $vendor = $this->vendor();

        return view('admin.store_profile.edit', [
            'vendor' => $vendor->loadCount('followers')->load('businessDetails'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $vendor = $this->vendor();
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('vendors', 'slug')->ignore($vendor->id)],
            'tagline' => ['nullable', 'string', 'max:160'],
            'about' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'slug.regex' => 'Use lowercase letters, numbers, and dashes for the store address.',
            'slug.unique' => 'Another store already uses that address.',
        ]);

        $vendor->slug = $validated['slug'];
        $vendor->tagline = $validated['tagline'] ?? null;
        $vendor->about = $validated['about'] ?? null;
        $vendor->holiday_mode = $request->boolean('holiday_mode');

        if ($request->hasFile('logo')) {
            $vendor->logo = $this->storeImage($request->file('logo'), 'logo', 320, 320);
        }

        if ($request->hasFile('banner')) {
            $vendor->banner = $this->storeImage($request->file('banner'), 'banner', 1600, 400);
        }

        $vendor->save();

        return back()->with('success_message', 'Store profile updated.');
    }

    private function vendor(): Vendor
    {
        return Vendor::query()->findOrFail(Auth::guard('admin')->user()->vendor_id);
    }

    /**
     * Re-encode the upload so only image data reaches the public folder.
     */
    private function storeImage(UploadedFile $file, string $kind, int $width, int $height): string
    {
        File::ensureDirectoryExists(public_path(self::IMAGE_DIRECTORY));
        $path = self::IMAGE_DIRECTORY.'/'.$kind.'-'.Str::random(20).'.jpg';

        Image::make($file)->fit($width, $height)->encode('jpg', 85)->save(public_path($path));

        return $path;
    }
}
