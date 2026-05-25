<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;
use Toastr;

class SizeController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin|size-list', ['only' => ['index']]);
        $this->middleware('role_or_permission:Admin|size-create', ['only' => ['create', 'store']]);
        $this->middleware('role_or_permission:Admin|size-edit', ['only' => ['edit', 'update', 'active', 'inactive']]);
        $this->middleware('role_or_permission:Admin|size-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $show_data = Size::orderBy('id', 'DESC')->get();

        return view('backEnd.size.index', compact('show_data'));
    }

    public function create()
    {
        return view('backEnd.size.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'status' => 'required',
        ]);

        $input = $request->all();

        Size::create($input);

        Toastr::success('Success', 'Data insert successfully');

        return redirect()->route('admin.sizes.index');
    }

    public function edit($id)
    {
        $edit_data = Size::findOrFail($id);

        return view('backEnd.size.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'status' => 'required',
        ]);
        // image one
        $update_data = Size::findOrFail($request->id);
        $input = $request->all();
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->route('admin.sizes.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Size::findOrFail($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');

        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Size::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');

        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = Size::findOrFail($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');

        return redirect()->back();
    }
}
