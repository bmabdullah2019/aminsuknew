<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use Brian2694\Toastr\Facades\Toastr;
use DB;
use File;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function getCategory(Request $request)
    {
        $category = DB::table('categories')
            ->where('service_category', $request->service_category)
            ->pluck('name', 'id');

        return response()->json($category);
    }

    public function __construct()
    {
        $this->middleware('permission:subcategory-list|subcategory-create|subcategory-edit|subcategory-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:subcategory-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:subcategory-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:subcategory-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Subcategory::orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->with('category')->get();

        return view('backEnd.subcategory.index', compact('data'));
    }

    public function create()
    {
        $categories = Category::orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->get();

        return view('backEnd.subcategory.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'category_id' => 'required',
            'subcategoryName' => 'required',
            'status' => 'required',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        // image processing with native GD
        $image = $request->file('image');
        if ($image != null) {
            $name = time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            $uploadpath = 'public/uploads/subcategory/';
            $imageUrl = $uploadpath.$name;
            ImageHelper::processAndSaveWebp($image->getRealPath(), $imageUrl, 90);
        } else {
            $imageUrl = null;
        }

        $input = $request->all();

        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->subcategoryName));
        $input['slug'] = str_replace('/', '', $input['slug']);
        $input['sort_order'] = (int) ($request->sort_order ?? 0);

        $input['image'] = $imageUrl;
        Subcategory::create($input);
        Toastr::success('Success', 'Data insert successfully');

        return redirect()->route('admin.subcategories.index');
    }

    public function edit($id)
    {
        $edit_data = Subcategory::findOrFail($id);
        $categories = Category::orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->select('id', 'name')->get();

        return view('backEnd.subcategory.edit', compact('edit_data', 'categories'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'category_id' => 'required',
            'subcategoryName' => 'required',
            'status' => 'required',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $update_data = Subcategory::findOrFail($request->id);
        $input = $request->all();
        $image = $request->file('image');

        if ($image) {
            // image processing with native GD
            $name = time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            $uploadpath = 'public/uploads/subcategory/';
            $imageUrl = $uploadpath.$name;
            ImageHelper::processAndSaveWebp($image->getRealPath(), $imageUrl, 90);
            $input['image'] = $imageUrl;
            File::delete($update_data->image);
        } else {
            $input['image'] = $update_data->image;
        }

        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->subcategoryName));
        $input['slug'] = str_replace('/', '', $input['slug']);
        $input['sort_order'] = (int) ($request->sort_order ?? 0);
        $input['status'] = $request->status ? 1 : 0;

        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->route('admin.subcategories.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Subcategory::findOrFail($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');

        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Subcategory::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');

        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        try {
            $delete_data = Subcategory::findOrFail($request->hidden_id);

            // Check if there are any products linked to this subcategory
            $productCount = \App\Models\Product::where('subcategory_id', $request->hidden_id)->count();

            if ($productCount > 0) {
                Toastr::error('Error', 'Cannot delete subcategory. '.$productCount.' product(s) are linked to this subcategory. Please reassign or delete the products first.');

                return redirect()->back();
            }

            // Check if there are any child categories linked
            $childCount = \App\Models\Childcategory::where('subcategory_id', $request->hidden_id)->count();

            if ($childCount > 0) {
                Toastr::error('Error', 'Cannot delete subcategory. '.$childCount.' child category(ies) are linked to this subcategory. Please reassign or delete the child categories first.');

                return redirect()->back();
            }

            $delete_data->delete();
            Toastr::success('Success', 'Data delete successfully');

            return redirect()->back();
        } catch (\Exception $e) {
            \Log::error('Subcategory deletion failed: '.$e->getMessage());
            Toastr::error('Error', 'Failed to delete subcategory: '.$e->getMessage());

            return redirect()->back();
        }
    }
}
