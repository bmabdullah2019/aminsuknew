<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingProfile;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Http\Request;
use Toastr;

class ShippingRateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:shipping-rate-list|shipping-list', ['only' => ['index']]);
        $this->middleware('permission:shipping-rate-create|shipping-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:shipping-rate-edit|shipping-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:shipping-rate-delete|shipping-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $query = ShippingRate::with(['zone', 'profile']);

        if ($request->filled('zone_id')) {
            $query->where('shipping_zone_id', (int) $request->zone_id);
        }

        if ($request->filled('profile_id')) {
            $query->where('shipping_profile_id', (int) $request->profile_id);
        }

        $rates = $query->orderBy('shipping_zone_id')
            ->orderBy('shipping_profile_id')
            ->orderBy('min_weight')
            ->get();

        $zones = ShippingZone::active()->orderBy('name')->get();
        $profiles = ShippingProfile::active()->orderBy('name')->get();

        return view('backEnd.shipping.rates.index', compact('rates', 'zones', 'profiles'));
    }

    public function create()
    {
        $zones = ShippingZone::active()->orderBy('name')->get();
        $profiles = ShippingProfile::active()->orderBy('name')->get();

        return view('backEnd.shipping.rates.create', compact('zones', 'profiles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'shipping_zone_id' => 'required|integer|exists:shipping_zones,id',
            'shipping_profile_id' => 'required|integer|exists:shipping_profiles,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|min:0|gte:min_weight',
            'rate' => 'required|numeric|min:0',
        ]);

        if ($this->hasOverlappingRate($request)) {
            Toastr::error('A rate with overlapping weight range already exists for this zone/profile combination.', 'Duplicate');

            return redirect()->back()->withInput();
        }

        ShippingRate::create([
            'shipping_zone_id' => $request->shipping_zone_id,
            'shipping_profile_id' => $request->shipping_profile_id,
            'min_weight' => $request->min_weight,
            'max_weight' => $request->max_weight,
            'rate' => (int) $request->rate,
            'status' => $request->boolean('status', true) ? 1 : 0,
        ]);

        Toastr::success('Shipping rate created successfully', 'Success');

        return redirect()->route('admin.shipping.rates.index');
    }

    public function edit($id)
    {
        $rate = ShippingRate::with(['zone', 'profile'])->findOrFail($id);
        $zones = ShippingZone::active()->orderBy('name')->get();
        $profiles = ShippingProfile::active()->orderBy('name')->get();

        return view('backEnd.shipping.rates.edit', compact('rate', 'zones', 'profiles'));
    }

    public function update(Request $request, $id)
    {
        $rate = ShippingRate::findOrFail($id);

        $request->validate([
            'shipping_zone_id' => 'required|integer|exists:shipping_zones,id',
            'shipping_profile_id' => 'required|integer|exists:shipping_profiles,id',
            'min_weight' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|min:0|gte:min_weight',
            'rate' => 'required|numeric|min:0',
        ]);

        if ($this->hasOverlappingRate($request, (int) $rate->id)) {
            Toastr::error('A rate with overlapping weight range already exists for this zone/profile combination.', 'Duplicate');

            return redirect()->back()->withInput();
        }

        $rate->update([
            'shipping_zone_id' => $request->shipping_zone_id,
            'shipping_profile_id' => $request->shipping_profile_id,
            'min_weight' => $request->min_weight,
            'max_weight' => $request->max_weight,
            'rate' => (int) $request->rate,
            'status' => $request->boolean('status', true) ? 1 : 0,
        ]);

        Toastr::success('Shipping rate updated successfully', 'Success');

        return redirect()->route('admin.shipping.rates.index');
    }

    public function destroy($id)
    {
        $rate = ShippingRate::findOrFail($id);
        $rate->delete();

        Toastr::success('Shipping rate deleted successfully', 'Success');

        return redirect()->route('admin.shipping.rates.index');
    }

    private function hasOverlappingRate(Request $request, ?int $ignoreId = null): bool
    {
        return ShippingRate::where('shipping_zone_id', $request->shipping_zone_id)
            ->where('shipping_profile_id', $request->shipping_profile_id)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where(function ($q) use ($request) {
                $q->whereBetween('min_weight', [$request->min_weight, $request->max_weight])
                    ->orWhereBetween('max_weight', [$request->min_weight, $request->max_weight])
                    ->orWhere(function ($q2) use ($request) {
                        $q2->where('min_weight', '<=', $request->min_weight)
                            ->where('max_weight', '>=', $request->max_weight);
                    });
            })
            ->exists();
    }
}
