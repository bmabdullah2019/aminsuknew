<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Toastr;

class ShippingProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:shipping-profile-list|shipping-list', ['only' => ['index']]);
        $this->middleware('permission:shipping-profile-create|shipping-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:shipping-profile-edit|shipping-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:shipping-profile-delete|shipping-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $profiles = ShippingProfile::withCount('products')
            ->orderBy('id', 'ASC')
            ->get();

        return view('backEnd.shipping.profiles.index', compact('profiles'));
    }

    public function create()
    {
        return view('backEnd.shipping.profiles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        $slug = Str::slug($request->name);
        $slugBase = $slug;
        $counter = 1;
        while (ShippingProfile::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $counter++;
        }

        if ($request->boolean('is_default')) {
            ShippingProfile::where('is_default', true)->update(['is_default' => false]);
        }

        ShippingProfile::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'is_default' => $request->boolean('is_default'),
            'status' => $request->boolean('status', true) ? 1 : 0,
        ]);

        Toastr::success('Shipping profile created successfully', 'Success');

        return redirect()->route('admin.shipping.profiles.index');
    }

    public function edit($id)
    {
        $profile = ShippingProfile::findOrFail($id);

        return view('backEnd.shipping.profiles.edit', compact('profile'));
    }

    public function update(Request $request, $id)
    {
        $profile = ShippingProfile::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        if ($request->boolean('is_default') && ! $profile->is_default) {
            ShippingProfile::where('is_default', true)->update(['is_default' => false]);
        }

        $profile->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_default' => $request->boolean('is_default'),
            'status' => $request->boolean('status', true) ? 1 : 0,
        ]);

        Toastr::success('Shipping profile updated successfully', 'Success');

        return redirect()->route('admin.shipping.profiles.index');
    }

    public function destroy($id)
    {
        $profile = ShippingProfile::findOrFail($id);

        $productCount = $profile->products()->count();
        if ($productCount > 0) {
            Toastr::error("Cannot delete: {$productCount} products use this profile", 'Error');

            return redirect()->back();
        }

        $profile->rates()->delete();
        $profile->delete();

        Toastr::success('Shipping profile deleted successfully', 'Success');

        return redirect()->route('admin.shipping.profiles.index');
    }
}
