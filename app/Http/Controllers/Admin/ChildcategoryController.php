<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Childcategory;
use App\Models\Subcategory;
use DB;
use Illuminate\Http\Request;
use Toastr;

class ChildcategoryController extends Controller
{
    public function getSubCategory(Request $request)
    {
        $category = DB::table('subcategories')
            ->where('subcategorytype', $request->childcategorytype)
            ->pluck('subcategoryName', 'id');

        return response()->json($category);
    }

    public function __construct()
    {
        $this->middleware('permission:childcategory-list|childcategory-create|childcategory-edit|childcategory-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:childcategory-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:childcategory-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:childcategory-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Childcategory::orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->with('subcategory')->get();

        return view('backEnd.childcategory.index', compact('data'));
    }

    public function create()
    {
        return view('backEnd.childcategory.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'subcategory_id' => 'required',
            'childcategoryName' => 'required',
            'status' => 'required',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        // image with intervention

        $input = $request->all();

        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->childcategoryName));
        $input['slug'] = str_replace('/', '', $input['slug']);
        $input['sort_order'] = (int) ($request->sort_order ?? 0);

        Childcategory::create($input);
        Toastr::success('Success', 'Data insert successfully');

        return redirect()->route('admin.childcategories.index');
    }

    public function edit($id)
    {
        $edit_data = Childcategory::findOrFail($id);
        $categories = Subcategory::select('id', 'subcategoryName')->get();

        return view('backEnd.childcategory.edit', compact('edit_data', 'categories'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'subcategory_id' => 'required',
            'childcategoryName' => 'required',
            'status' => 'required',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $update_data = Childcategory::findOrFail($request->id);
        $input = $request->all();

        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->childcategoryName));
        $input['slug'] = str_replace('/', '', $input['slug']);
        $input['sort_order'] = (int) ($request->sort_order ?? 0);
        $input['status'] = $request->status ? 1 : 0;

        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->route('admin.childcategories.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Childcategory::findOrFail($request->hidden_id);
        if ($inactive) {
            $inactive->status = 0;
            $inactive->save();
            Toastr::success('Success', 'Data inactive successfully');
        } else {
            Toastr::error('Error', 'Data not found');
        }

        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Childcategory::findOrFail($request->hidden_id);
        if ($active) {
            $active->status = 1;
            $active->save();
            Toastr::success('Success', 'Data active successfully');
        } else {
            Toastr::error('Error', 'Data not found');
        }

        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = Childcategory::findOrFail($request->hidden_id);
        if ($delete_data) {
            $delete_data->delete();
            Toastr::success('Success', 'Data delete successfully');
        } else {
            Toastr::error('Error', 'Data not found');
        }

        return redirect()->back();
    }
}
