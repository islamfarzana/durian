<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductsAttribute;
use App\Models\ProductsFilter;
use App\Models\ProductsImage;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Facades\Image;

class ProductsController extends Controller
{
    public function products()
    {
        Session::put('page', 'products');
        $adminType = Auth::guard('admin')->user()->type;
        $vendor_id = Auth::guard('admin')->user()->vendor_id;

        if ($adminType == 'vendor') {
            $vendorStatus = Auth::guard('admin')->user()->status;
            if ($vendorStatus == 0) {
                return redirect('admin/update-vendor-details/personal')->with('error_message', 'Your Vendor Account is not approved yet. Please make sure to fill your valid personal, business and bank details.');
            }
        }
        $products = Product::with([
            'section' => function ($query) {
                $query->select('id', 'name');
            },
            'category' => function ($query) {
                $query->select('id', 'category_name');
            },
        ]);
        if ($adminType == 'vendor') {
            $produtcs = $products->where('vendor_id', $vendor_id);
        }

        $products = $products->get()->toArray();

        return view('admin.products.products')->with(compact('products'));
    }

    public function updateProductStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            if ($data['status'] == 'Active') {
                $status = 0;
            } else {
                $status = 1;
            }
            $this->authorizeProduct($data['product_id']);
            Product::where('id', $data['product_id'])->update(['status' => $status]);

            return response()->json([
                'status' => $status,
                'product_id' => $data['product_id'],
            ]);
        }
    }

    public function deleteProduct($id)
    {
        $this->authorizeProduct($id);
        Product::where('id', $id)->delete();

        $message = 'Product has been deleted successfully!';

        return redirect()->back()->with('success_message', $message);
    }

    public function addEditProduct(Request $request, $id = null)
    {

        Session::put('page', 'products');
        if ($id == '') {
            $title = 'Add Product';
            $product = new Product;

            $message = 'Product added successfully!';
        } else {
            $title = 'Edit Product';
            $this->authorizeProduct($id);
            $product = Product::find($id);

            $message = 'Product updated successfully!';
        }

        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'category_id' => 'required',
                'product_name' => 'required',
                'product_code' => 'required|regex:/^\w+$/',
                'product_price' => 'required|numeric',
                'product_color' => 'required|regex:/^[\pL\s\-]+$/u',
                'unit' => 'nullable|string|max:50',
                'country_of_origin' => 'nullable|string|max:80',
                'return_days' => 'nullable|integer|min:0|max:365',
                'warranty' => 'nullable|string|max:120',
            ];

            $customMessages = [
                'category_id.required' => 'Category is required',
                'product_name.required' => 'Product Name is required',
                'product_name.regex' => 'Valid Product Name is required',
                'product_code.required' => 'Product Code is required',
                'product_code.regex' => 'Valid Product Code is required',
                'product_price.required' => 'Product Price is required',
                'product_price.numeric' => 'Valid Product Price is required',
                'product_color.required' => 'Product Color is required',
                'product_color.regex' => 'Valid Product Color is required',

            ];

            $this->validate($request, $rules, $customMessages);

            if ($request->hasFile('product_image')) {
                $image_tmp = $request->file('product_image');
                if ($image_tmp->isValid()) {

                    $extension = $image_tmp->getClientOriginalExtension();
                    $imageName = rand(111, 99999).'.'.$extension;
                    $largeImagePath = 'front/images/product_images/large/'.$imageName;
                    $mediumImagePath = 'front/images/product_images/medium/'.$imageName;
                    $smallImagePath = 'front/images/product_images/small/'.$imageName;
                    Image::make($image_tmp)->resize(1000, 1000)->save($largeImagePath);
                    Image::make($image_tmp)->resize(500, 500)->save($mediumImagePath);
                    Image::make($image_tmp)->resize(250, 250)->save($smallImagePath);
                    $product->product_image = $imageName;
                }
            }

            if ($request->hasFile('product_video')) {
                $video_tmp = $request->file('product_video');

                if ($video_tmp->isValid()) {

                    $extension = $video_tmp->getClientOriginalExtension();
                    $videoName = rand().'.'.$extension;
                    $videoPath = 'front/videos/product_videos/';
                    $video_tmp->move($videoPath, $videoName);
                    $product->product_video = $videoName;
                }
            }
            $categoryDetails = Category::find($data['category_id']);
            $product->section_id = $categoryDetails['section_id'];
            $product->category_id = $data['category_id'];
            $product->brand_id = $data['brand_id'];
            $product->group_code = $data['group_code'];
            $productFilters = ProductsFilter::productFilters();
            foreach ($productFilters as $filter) {
                $filterAvailable = ProductsFilter::filterAvailable($filter['id'], $data['category_id']);
                if ($filterAvailable == 'Yes') {
                    if (isset($filter['filter_column']) && $data[$filter['filter_column']]) {

                        $product->{$filter['filter_column']} = $data[$filter['filter_column']];
                    }
                }
            }
            if ($id == '') {

                $adminType = Auth::guard('admin')->user()->type;

                $vendor_id = Auth::guard('admin')->user()->vendor_id;
                $admin_id = Auth::guard('admin')->user()->id;

                $product->admin_type = $adminType;
                $product->admin_id = $admin_id;

                if ($adminType == 'vendor') {
                    $product->vendor_id = $vendor_id;
                } else {
                    $product->vendor_id = 0;
                }
            }
            if (empty($data['product_discount'])) {
                $data['product_discount'] = 0;
            }

            if (empty($data['product_weight'])) {
                $data['product_weight'] = 0;
            }
            $product->product_name = $data['product_name'];
            $product->product_code = $data['product_code'];
            $product->product_color = $data['product_color'];
            $product->product_price = $data['product_price'];
            $product->product_discount = $data['product_discount'];
            $product->product_weight = $data['product_weight'];
            $product->unit = $data['unit'] ?? null;
            $product->country_of_origin = $data['country_of_origin'] ?? null;
            $product->return_days = isset($data['return_days']) && $data['return_days'] !== '' ? (int) $data['return_days'] : null;
            $product->warranty = $data['warranty'] ?? null;
            $product->description = $data['description'];
            $product->meta_title = $data['meta_title'];
            $product->meta_description = $data['meta_description'];
            $product->meta_keywords = $data['meta_keywords'];
            if (! empty($data['is_featured'])) {

                $product->is_featured = $data['is_featured'];
            } else {

                $product->is_featured = 'No';
            }
            if (! empty($data['is_bestseller'])) {

                $product->is_bestseller = $data['is_bestseller'];
            } else {

                $product->is_bestseller = 'No';
            }
            $product->status = 1;
            $product->save();

            return redirect('admin/products')->with('success_message', $message);
        }
        $categories = Section::with('categories')->get()->toArray();
        $brands = Brand::where('status', 1)->get()->toArray();

        return view('admin.products.add_edit_product')->with(compact('title', 'product', 'categories', 'brands'));
    }

    public function deleteProductImage($id)
    {

        $this->authorizeProduct($id);
        $productImage = Product::select('product_image')->where('id', $id)->first();
        $small_image_path = 'front/images/product_images/small/';
        $medium_image_path = 'front/images/product_images/medium/';
        $large_image_path = 'front/images/product_images/large/';
        if (file_exists($small_image_path.$productImage->product_image)) {
            unlink($small_image_path.$productImage->product_image);
        }
        if (file_exists($medium_image_path.$productImage->product_image)) {
            unlink($medium_image_path.$productImage->product_image);
        }
        if (file_exists($large_image_path.$productImage->product_image)) {
            unlink($large_image_path.$productImage->product_image);
        }
        Product::where('id', $id)->update(['product_image' => '']);

        $message = 'Product Image has been deleted successfully!';

        return redirect()->back()->with('success_message', $message);
    }

    public function deleteProductVideo($id)
    {

        $this->authorizeProduct($id);
        $productVideo = Product::select('product_video')->where('id', $id)->first();
        $product_video_path = 'front/videos/product_videos/';
        if (file_exists($product_video_path.$productVideo->product_video)) {
            unlink($product_video_path.$productVideo->product_video);
        }
        Product::where('id', $id)->update(['product_video' => '']);

        $message = 'Product Video has been deleted successfully!';

        return redirect()->back()->with('success_message', $message);
    }

    public function addAttributes(Request $request, $id)
    {
        Session::put('page', 'products');

        $this->authorizeProduct($id);
        $product = Product::select('id', 'product_name', 'product_code', 'product_color', 'product_price', 'product_image')->with('attributes')->find($id);

        if ($request->isMethod('post')) {
            $data = $request->all();
            foreach ($data['sku'] as $key => $value) {
                if (! empty($value)) {
                    $skuCount = ProductsAttribute::where('sku', $value)->count();
                    if ($skuCount > 0) {
                        return redirect()->back()->with('error_message', 'SKU already exists! Please add another SKU!');
                    }
                    $sizeCount = ProductsAttribute::where(['product_id' => $id, 'size' => $data['size'][$key]])->count();
                    if ($sizeCount > 0) {
                        return redirect()->back()->with('error_message', 'Size already exists! Please add another Size!');
                    }
                    $attribute = new ProductsAttribute;

                    $attribute->product_id = $id;
                    $attribute->sku = $value;
                    $attribute->size = $data['size'][$key];
                    $attribute->price = $data['price'][$key];
                    $attribute->stock = $data['stock'][$key];
                    $attribute->status = 1;

                    $attribute->save();
                }
            }

            return redirect()->back()->with('success_message', 'Product Attributes have been addded successfully!');
        }

        return view('admin.attributes.add_edit_attributes')->with(compact('product'));
    }

    public function updateAttributeStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            if ($data['status'] == 'Active') {
                $status = 0;
            } else {
                $status = 1;
            }
            $this->authorizeProduct(ProductsAttribute::where('id', $data['attribute_id'])->value('product_id'));
            ProductsAttribute::where('id', $data['attribute_id'])->update(['status' => $status]);

            return response()->json([
                'status' => $status,
                'attribute_id' => $data['attribute_id'],
            ]);
        }
    }

    public function editAttributes(Request $request)
    {
        Session::put('page', 'products');

        if ($request->isMethod('post')) {
            $data = $request->all();
            foreach ($data['attributeId'] as $key => $attribute) {
                if (! empty($attribute)) {
                    $this->authorizeProduct(ProductsAttribute::where('id', $attribute)->value('product_id'));
                    ProductsAttribute::where([
                        'id' => $data['attributeId'][$key],
                    ])->update([
                        'price' => $data['price'][$key],
                        'stock' => $data['stock'][$key],
                    ]);
                }
            }

            return redirect()->back()->with('success_message', 'Product Attributes have been updated successfully!');
        }
    }

    public function addImages(Request $request, $id)
    {
        Session::put('page', 'products');
        $this->authorizeProduct($id);

        $product = Product::select('id', 'product_name', 'product_code', 'product_color', 'product_price', 'product_image')->with('images')->find($id);
        if ($request->isMethod('post')) {
            $data = $request->all();
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                foreach ($images as $key => $image) {
                    $image_tmp = Image::make($image);
                    $image_name = $image->getClientOriginalName();
                    $extension = $image->getClientOriginalExtension();
                    $imageName = $image_name.rand(111, 99999).'.'.$extension;
                    $largeImagePath = 'front/images/product_images/large/'.$imageName;
                    $mediumImagePath = 'front/images/product_images/medium/'.$imageName;
                    $smallImagePath = 'front/images/product_images/small/'.$imageName;
                    Image::make($image_tmp)->resize(1000, 1000)->save($largeImagePath);
                    Image::make($image_tmp)->resize(500, 500)->save($mediumImagePath);
                    Image::make($image_tmp)->resize(250, 250)->save($smallImagePath);
                    $image = new ProductsImage;

                    $image->image = $imageName;
                    $image->product_id = $id;
                    $image->status = 1;

                    $image->save();
                }
            }

            return redirect()->back()->with('success_message', 'Product Images have been added successfully!');
        }

        return view('admin.images.add_images')->with(compact('product'));
    }

    public function updateImageStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            if ($data['status'] == 'Active') {
                $status = 0;
            } else {
                $status = 1;
            }
            $this->authorizeProduct(ProductsImage::where('id', $data['image_id'])->value('product_id'));
            ProductsImage::where('id', $data['image_id'])->update(['status' => $status]);

            return response()->json([
                'status' => $status,
                'image_id' => $data['image_id'],
            ]);
        }
    }

    public function deleteImage($id)
    {

        $productImage = ProductsImage::select('image', 'product_id')->where('id', $id)->firstOrFail();
        $this->authorizeProduct($productImage->product_id);
        $small_image_path = 'front/images/product_images/small/';
        $medium_image_path = 'front/images/product_images/medium/';
        $large_image_path = 'front/images/product_images/large/';
        if (file_exists($small_image_path.$productImage->image)) {
            unlink($small_image_path.$productImage->image);
        }
        if (file_exists($medium_image_path.$productImage->image)) {
            unlink($medium_image_path.$productImage->image);
        }
        if (file_exists($large_image_path.$productImage->image)) {
            unlink($large_image_path.$productImage->image);
        }
        ProductsImage::where('id', $id)->delete();

        $message = 'Product Image has been deleted successfully!';

        return redirect()->back()->with('success_message', $message);
    }

    /**
     * Vendors share these screens with admins but may only touch their own
     * products. Anyone else's product id is treated as missing.
     */
    private function authorizeProduct(int|string|null $productId): void
    {
        $admin = Auth::guard('admin')->user();

        abort_unless(
            Product::query()
                ->whereKey($productId)
                ->when($admin->type === 'vendor', fn ($query) => $query->where('vendor_id', $admin->vendor_id))
                ->exists(),
            404,
        );
    }
}
