<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use File;
use Illuminate\Http\Request;
use Toastr;

class BrandController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin|brand-list', ['only' => ['index']]);
        $this->middleware('role_or_permission:Admin|brand-create', ['only' => ['create', 'store']]);
        $this->middleware('role_or_permission:Admin|brand-edit', ['only' => ['edit', 'update', 'active', 'inactive']]);
        $this->middleware('role_or_permission:Admin|brand-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Brand::orderBy('id', 'DESC')->get();

        return view('backEnd.brand.index', compact('data'));
    }

    public function create()
    {
        return view('backEnd.brand.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'status' => 'required',
        ]);
        // image processing with native GD
        $image = $request->file('image');
        if ($image) {
            $name = time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            $uploadpath = 'public/uploads/brand/';
            $imageUrl = $uploadpath.$name;
            ImageHelper::resizeAndSaveWebp($image->getRealPath(), $imageUrl, 210, 210, 90);
        } else {
            $imageUrl = null;
        }

        $input = $request->all();
        $input['slug'] = strtolower(preg_replace('/\s+/u', '-', trim($request->name)));
        $input['image'] = $imageUrl;
        Brand::create($input);
        Toastr::success('Success', 'Data insert successfully');

        return redirect()->route('admin.brands.index');
    }

    public function edit($id)
    {
        $edit_data = Brand::findOrFail($id);

        return view('backEnd.brand.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);
        $update_data = Brand::findOrFail($request->id);
        $input = $request->all();
        $image = $request->file('image');
        if ($image) {
            // image processing with native GD
            $name = time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            $uploadpath = 'public/uploads/brand/';
            $imageUrl = $uploadpath.$name;
            ImageHelper::resizeAndSaveWebp($image->getRealPath(), $imageUrl, 210, 210, 90);
            $input['image'] = $imageUrl;
            File::delete($update_data->image);
        } else {
            $input['image'] = $update_data->image;
        }
        $input['status'] = $request->status ? 1 : 0;
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->route('admin.brands.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Brand::findOrFail($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');

        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Brand::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');

        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = Brand::findOrFail($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');

        return redirect()->back();
    }
}
