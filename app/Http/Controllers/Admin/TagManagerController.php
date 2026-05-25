<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagManagerStoreRequest;
use App\Http\Requests\TagManagerTargetRequest;
use App\Http\Requests\TagManagerUpdateRequest;
use App\Models\GoogleTagManager;
use Illuminate\Http\Request;
use Toastr;

class TagManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin|tagmanager-list', ['only' => ['index']]);
        $this->middleware('role_or_permission:Admin|tagmanager-create', ['only' => ['create', 'store']]);
        $this->middleware('role_or_permission:Admin|tagmanager-edit', ['only' => ['show', 'edit', 'update', 'active', 'inactive']]);
        $this->middleware('role_or_permission:Admin|tagmanager-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = GoogleTagManager::orderBy('id', 'DESC')->get();

        return view('backEnd.tagmanager.index', compact('data'));
    }

    public function create()
    {
        return view('backEnd.tagmanager.create');
    }

    public function show($id)
    {
        $edit_data = GoogleTagManager::findOrFail((int) $id);

        return view('backEnd.tagmanager.edit', compact('edit_data'));
    }

    public function store(TagManagerStoreRequest $request)
    {
        $validated = $request->validated();

        GoogleTagManager::create([
            'code' => (string) $validated['code'],
            'status' => $request->boolean('status') ? 1 : 0,
        ]);

        Toastr::success('Success', 'Data insert successfully');

        return redirect()->route('admin.tagmanagers.index');
    }

    public function edit($id)
    {
        $edit_data = GoogleTagManager::findOrFail($id);

        return view('backEnd.tagmanager.edit', compact('edit_data'));
    }

    public function update(TagManagerUpdateRequest $request)
    {
        $validated = $request->validated();

        $update_data = GoogleTagManager::findOrFail((int) $validated['id']);
        $update_data->update([
            'code' => (string) $validated['code'],
            'status' => $request->boolean('status') ? 1 : 0,
        ]);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->route('admin.tagmanagers.index');
    }

    public function inactive(TagManagerTargetRequest $request)
    {
        $validated = $request->validated();

        $inactive = GoogleTagManager::findOrFail((int) $validated['hidden_id']);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');

        return redirect()->back();
    }

    public function active(TagManagerTargetRequest $request)
    {
        $validated = $request->validated();

        $active = GoogleTagManager::findOrFail((int) $validated['hidden_id']);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');

        return redirect()->back();
    }

    public function destroy(TagManagerTargetRequest $request)
    {
        $validated = $request->validated();

        $delete_data = GoogleTagManager::findOrFail((int) $validated['hidden_id']);
        $delete_data->delete();
        Toastr::success('Success', 'Data delete successfully');

        return redirect()->back();
    }
}
