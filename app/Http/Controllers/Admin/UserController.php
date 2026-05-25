<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use DB;
use File;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;
use Toastr;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin|user-list', ['only' => ['index']]);
        $this->middleware('role_or_permission:Admin|user-create', ['only' => ['create', 'store']]);
        $this->middleware('role_or_permission:Admin|user-edit', ['only' => ['edit', 'update', 'active', 'inactive']]);
        $this->middleware('role_or_permission:Admin|user-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = User::orderBy('id', 'DESC')->get();

        return view('backEnd.users.index', compact('data'));
    }

    public function create()
    {
        $roles = Role::select('name')->get();

        return view('backEnd.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password',
            'roles' => 'required',
        ]);
        // image processing with native GD
        $image = $request->file('image');
        $name = time().'-'.$image->getClientOriginalName();
        $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
        $name = strtolower(preg_replace('/\s+/', '-', $name));
        $uploadpath = 'public/uploads/users/';
        $imageUrl = $uploadpath.$name;
        ImageHelper::resizeAndSaveWebp($image->getRealPath(), $imageUrl, 100, 100, 90);

        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
        $input['image'] = $imageUrl;

        $user = User::create($input);
        $user->assignRole($request->input('roles'));
        Toastr::success('Success', 'Data insert successfully');

        return redirect()->route('admin.users.index');
    }

    public function edit($id)
    {
        $edit_data = User::findOrFail($id);
        $roles = Role::get();

        return view('backEnd.users.edit', compact('edit_data', 'roles'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$request->hidden_id,
            'password' => 'same:confirm-password',
            'roles' => 'required',
        ]);

        $update_data = User::findOrFail($request->hidden_id);

        // new password
        $input = $request->all();
        if (! empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            $input = Arr::except($input, ['password']);
        }

        // new image
        $image = $request->file('image');
        if ($image) {
            // image processing with native GD
            $name = time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            $uploadpath = 'public/uploads/users/';
            $imageUrl = $uploadpath.$name;
            ImageHelper::resizeAndSaveWebp($image->getRealPath(), $imageUrl, 100, 100, 90);
            $input['image'] = $imageUrl;
            File::delete($update_data->image);
        } else {
            $input['image'] = $update_data->image;
        }
        $input['status'] = $request->status ? 1 : 0;
        $update_data->update($input);

        // role asign
        DB::table('model_has_roles')->where('model_id', $request->hidden_id)->delete();
        $update_data->assignRole($request->input('roles'));
        Toastr::success('Success', 'Data update successfully');

        return redirect()->route('admin.users.index');
    }

    public function inactive(Request $request)
    {
        $inactive = User::findOrFail($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');

        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = User::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success', 'Data active successfully');

        return redirect()->back();
    }

    public function destroy(Request $request)
    {

        $delete_data = User::findOrFail($request->hidden_id);
        if ($delete_data->id != 1) {
            File::delete($delete_data->image);
            $delete_data->delete();
            Toastr::success('Success', 'Data delete successfully');
        } else {
            Toastr::success('error', 'Data delete unsuccessfully');
        }

        return redirect()->back();
    }
}
