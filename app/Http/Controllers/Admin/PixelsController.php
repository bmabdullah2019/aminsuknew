<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PixelStoreRequest;
use App\Http\Requests\PixelTargetRequest;
use App\Http\Requests\PixelUpdateRequest;
use App\Models\EcomPixel;
use Illuminate\Http\Request;
use Toastr;

class PixelsController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin|pixel-list', ['only' => ['index']]);
        $this->middleware('role_or_permission:Admin|pixel-create', ['only' => ['create', 'store']]);
        $this->middleware('role_or_permission:Admin|pixel-edit', ['only' => ['show', 'edit', 'update', 'active', 'inactive']]);
        $this->middleware('role_or_permission:Admin|pixel-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = EcomPixel::orderBy('id', 'DESC')->get();

        return view('backEnd.pixels.index', compact('data'));
    }

    public function create()
    {
        return view('backEnd.pixels.create');
    }

    public function show($id)
    {
        $edit_data = EcomPixel::findOrFail((int) $id);

        return view('backEnd.pixels.edit', compact('edit_data'));
    }

    public function store(PixelStoreRequest $request)
    {
        $validated = $request->validated();

        EcomPixel::create([
            'code' => (string) $validated['code'],
            'status' => $request->boolean('status') ? 1 : 0,
        ]);

        Toastr::success('Success', 'Data insert successfully');

        return redirect()->route('admin.pixels.index');
    }

    public function edit($id)
    {
        $edit_data = EcomPixel::findOrFail($id);

        return view('backEnd.pixels.edit', compact('edit_data'));
    }

    public function update(PixelUpdateRequest $request)
    {
        $validated = $request->validated();

        $update_data = EcomPixel::findOrFail((int) $validated['id']);
        $update_data->update([
            'code' => (string) $validated['code'],
            'status' => $request->boolean('status') ? 1 : 0,
        ]);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->route('admin.pixels.index');
    }

    public function inactive(PixelTargetRequest $request)
    {
        $validated = $request->validated();

        $inactive = EcomPixel::findOrFail((int) $validated['hidden_id']);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');

        return redirect()->back();
    }

    public function active(PixelTargetRequest $request)
    {
        $validated = $request->validated();

        $active = EcomPixel::findOrFail((int) $validated['hidden_id']);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');

        return redirect()->back();
    }

    public function destroy(PixelTargetRequest $request)
    {
        $validated = $request->validated();

        $delete_data = EcomPixel::findOrFail((int) $validated['hidden_id']);
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');

        return redirect()->back();
    }
}
