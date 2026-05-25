<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Toastr;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:category-list|category-create|category-edit|category-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:category-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:category-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:category-edit', ['only' => ['updateFrontViewOrder']]);
        $this->middleware('permission:category-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Category::orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->with('category')->get();

        // return $data;
        return view('backEnd.category.index', compact('data'));
    }

    public function create()
    {
        $categories = Category::orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->select('id', 'name')->get();

        return view('backEnd.category.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'status' => 'required',
            'home_banner' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'sort_order' => 'nullable|integer|min:0',
            'front_view_order' => 'nullable|integer|min:0',
        ]);

        $input = $request->all();
        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->name));
        $input['slug'] = str_replace('/', '', $input['slug']);

        $input['parent_id'] = $request->parent_id ? $request->parent_id : 0;
        $input['front_view'] = $request->front_view ? 1 : 0;
        $input['sort_order'] = (int) ($request->sort_order ?? 0);
        if (Schema::hasColumn('categories', 'front_view_order')) {
            $input['front_view_order'] = (int) ($request->front_view_order ?? 0);
        }
        $input['image'] = $this->storeCategoryImage($request->file('image'));
        if (Schema::hasColumn('categories', 'home_banner')) {
            $input['home_banner'] = $this->storeCategoryImage($request->file('home_banner'));
        } else {
            unset($input['home_banner']);
        }
        Category::create($input);
        Toastr::success('Success', 'Data insert successfully');

        return redirect()->route('admin.categories.index');
    }

    public function edit($id)
    {
        $edit_data = Category::findOrFail($id);
        $categories = Category::orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->select('id', 'name')->get();

        return view('backEnd.category.edit', compact('edit_data', 'categories'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'home_banner' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'sort_order' => 'nullable|integer|min:0',
            'front_view_order' => 'nullable|integer|min:0',
        ]);
        $update_data = Category::findOrFail($request->id);
        $input = $request->all();
        $image = $request->file('image');
        if ($image) {
            $input['image'] = $this->storeCategoryImage($image);
            File::delete($update_data->image);
        } else {
            $input['image'] = $update_data->image;
        }

        if (Schema::hasColumn('categories', 'home_banner')) {
            $homeBanner = $request->file('home_banner');
            if ($homeBanner) {
                $input['home_banner'] = $this->storeCategoryImage($homeBanner);
                if (! empty($update_data->home_banner)) {
                    File::delete($update_data->home_banner);
                }
            } else {
                $input['home_banner'] = $update_data->home_banner;
            }
        } else {
            unset($input['home_banner']);
        }

        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->name));
        $input['slug'] = str_replace('/', '', $input['slug']);

        $input['parent_id'] = $request->parent_id ? $request->parent_id : 0;
        $input['front_view'] = $request->front_view ? 1 : 0;
        $input['sort_order'] = (int) ($request->sort_order ?? 0);
        if (Schema::hasColumn('categories', 'front_view_order')) {
            $input['front_view_order'] = (int) ($request->front_view_order ?? 0);
        }
        $input['status'] = $request->status ? 1 : 0;

        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->route('admin.categories.index');
    }

    public function updateFrontViewOrder(Request $request)
    {
        $this->validate($request, [
            'orders' => 'nullable|array',
            'orders.*' => 'nullable|integer|min:0',
        ]);

        if (! Schema::hasColumn('categories', 'front_view_order')) {
            Toastr::error('Error', 'Front view order column is missing. Please run latest migration first.');

            return redirect()->back();
        }

        $orders = collect($request->input('orders', []))
            ->mapWithKeys(function ($order, $id) {
                return [(int) $id => (int) $order];
            });

        if ($orders->isEmpty()) {
            Toastr::error('Error', 'No front view order provided.');

            return redirect()->back();
        }

        DB::transaction(function () use ($orders) {
            foreach ($orders as $categoryId => $order) {
                Category::whereKey($categoryId)->update([
                    'front_view_order' => $order,
                ]);
            }
        });

        Toastr::success('Success', 'Front view category order updated successfully.');

        return redirect()->route('admin.categories.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Category::findOrFail($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');

        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Category::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');

        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = Category::findOrFail($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');

        return redirect()->back();
    }

    private function storeCategoryImage($image): ?string
    {
        if (! $image) {
            return null;
        }

        $name = time().'-'.$image->getClientOriginalName();
        $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
        $name = strtolower(preg_replace('/\s+/', '-', $name));
        $uploadpath = 'public/uploads/category/';
        $imageUrl = $uploadpath.$name;
        ImageHelper::processAndSaveWebp($image->getRealPath(), $imageUrl, 90);

        return $imageUrl;
    }
}
