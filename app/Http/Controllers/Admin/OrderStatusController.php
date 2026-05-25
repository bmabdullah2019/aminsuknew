<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Toastr;

class OrderStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin|orderstatus-list', ['only' => ['index']]);
        $this->middleware('role_or_permission:Admin|orderstatus-create', ['only' => ['create', 'store']]);
        $this->middleware('role_or_permission:Admin|orderstatus-edit', ['only' => ['edit', 'update', 'active', 'inactive']]);
        $this->middleware('role_or_permission:Admin|orderstatus-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = OrderStatus::orderBy('id', 'DESC')->get();

        return view('backEnd.orderstatus.index', compact('data'));
    }

    public function create()
    {
        return view('backEnd.orderstatus.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'status' => 'required',
        ]);
        $input = $request->all();
        $input['slug'] = strtolower(preg_replace('/\s+/u', '-', trim($request->name)));
        OrderStatus::create($input);
        Toastr::success('Success', 'Data insert successfully');

        return redirect()->route('admin.orderstatus.index');
    }

    public function edit($id)
    {
        $edit_data = OrderStatus::findOrFail($id);

        return view('backEnd.orderstatus.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);
        $update_data = OrderStatus::findOrFail($request->id);
        $input = $request->all();
        $input['status'] = $request->status ? 1 : 0;
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->route('admin.orderstatus.index');
    }

    public function inactive(Request $request)
    {
        $inactive = OrderStatus::findOrFail($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');

        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = OrderStatus::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');

        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $delete_data = OrderStatus::findOrFail($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');

        return redirect()->back();
    }
}
