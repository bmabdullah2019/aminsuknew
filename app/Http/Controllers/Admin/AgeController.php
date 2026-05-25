<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Age;
use Illuminate\Http\Request;
use Toastr;

class AgeController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin|age-list', ['only' => ['index']]);
        $this->middleware('role_or_permission:Admin|age-create', ['only' => ['create', 'store']]);
        $this->middleware('role_or_permission:Admin|age-edit', ['only' => ['edit', 'update', 'active', 'inactive']]);
        $this->middleware('role_or_permission:Admin|age-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $show_data = Age::orderBy('id', 'DESC')->get();

        return view('backEnd.age.index', compact('show_data'));
    }

    public function create()
    {
        return view('backEnd.age.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'ageName' => 'required',
            'status' => 'required',
        ]);

        $input = $request->all();

        Age::create($input);

        Toastr::success('Success', 'Age created successfully');

        return redirect()->route('admin.ages.index');
    }

    public function edit($id)
    {
        $edit_data = Age::findOrFail($id);

        return view('backEnd.age.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'ageName' => 'required',
            'status' => 'required',
        ]);

        $update_data = Age::findOrFail($request->id);
        $input = $request->all();
        $update_data->update($input);

        Toastr::success('Success', 'Age updated successfully');

        return redirect()->route('admin.ages.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Age::findOrFail($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Age inactive successfully');

        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Age::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Age active successfully');

        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = Age::findOrFail($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success', 'Age deleted successfully');

        return redirect()->back();
    }
}
