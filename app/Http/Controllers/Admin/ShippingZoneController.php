<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingCharge;
use App\Models\ShippingZone;
use App\Models\ShippingZoneArea;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Toastr;

class ShippingZoneController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:shipping-zone-list|shipping-list', ['only' => ['index']]);
        $this->middleware('permission:shipping-zone-create|shipping-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:shipping-zone-edit|shipping-edit', ['only' => ['edit', 'update', 'syncAreas']]);
        $this->middleware('permission:shipping-zone-delete|shipping-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $zones = ShippingZone::withCount('areas')
            ->orderBy('id', 'ASC')
            ->get();

        return view('backEnd.shipping.zones.index', compact('zones'));
    }

    public function create()
    {
        $shippingCharges = ShippingCharge::where('status', 1)->orderBy('name')->get();

        return view('backEnd.shipping.zones.create', compact('shippingCharges'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'areas' => 'nullable|array',
            'areas.*' => 'nullable|string|max:255',
            'shipping_charge_ids' => 'nullable|array',
            'shipping_charge_ids.*' => 'nullable|integer|exists:shipping_charges,id',
            'custom_areas' => 'nullable|string',
        ]);

        $slug = Str::slug($request->name);
        $slugBase = $slug;
        $counter = 1;
        while (ShippingZone::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $counter++;
        }

        $zone = ShippingZone::create([
            'name' => $request->name,
            'slug' => $slug,
            'status' => $request->boolean('status', true) ? 1 : 0,
        ]);

        $this->saveAreas($zone, $request);

        Toastr::success('Shipping zone created successfully', 'Success');

        return redirect()->route('admin.shipping.zones.index');
    }

    public function edit($id)
    {
        $zone = ShippingZone::with('areas')->findOrFail($id);
        $shippingCharges = ShippingCharge::where('status', 1)->orderBy('name')->get();

        return view('backEnd.shipping.zones.edit', compact('zone', 'shippingCharges'));
    }

    public function update(Request $request, $id)
    {
        $zone = ShippingZone::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'areas' => 'nullable|array',
            'areas.*' => 'nullable|string|max:255',
            'shipping_charge_ids' => 'nullable|array',
            'shipping_charge_ids.*' => 'nullable|integer|exists:shipping_charges,id',
            'custom_areas' => 'nullable|string',
        ]);

        $zone->update([
            'name' => $request->name,
            'status' => $request->boolean('status', true) ? 1 : 0,
        ]);

        // Re-sync areas
        $zone->areas()->delete();
        $this->saveAreas($zone, $request);

        Toastr::success('Shipping zone updated successfully', 'Success');

        return redirect()->route('admin.shipping.zones.index');
    }

    public function destroy($id)
    {
        $zone = ShippingZone::findOrFail($id);

        $rateCount = $zone->rates()->count();
        if ($rateCount > 0) {
            Toastr::error("Cannot delete: {$rateCount} shipping rates use this zone. Delete rates first.", 'Error');

            return redirect()->back();
        }

        $zone->areas()->delete();
        $zone->delete();

        Toastr::success('Shipping zone deleted successfully', 'Success');

        return redirect()->route('admin.shipping.zones.index');
    }

    /**
     * AJAX endpoint: sync areas for a zone.
     */
    public function syncAreas(Request $request, $zoneId)
    {
        $zone = ShippingZone::findOrFail($zoneId);

        $request->validate([
            'areas' => 'required|array|min:1',
            'areas.*' => 'required|string|max:255',
            'area_charge_ids' => 'nullable|array',
            'area_charge_ids.*' => 'nullable|integer|exists:shipping_charges,id',
        ]);

        $zone->areas()->delete();
        $this->saveAreas($zone, $request);

        return response()->json([
            'success' => true,
            'message' => 'Areas updated successfully.',
            'count' => $zone->areas()->count(),
        ]);
    }

    private function saveAreas(ShippingZone $zone, Request $request): void
    {
        $rows = [];

        $selectedChargeIds = collect($request->input('shipping_charge_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedChargeIds->isNotEmpty()) {
            ShippingCharge::query()
                ->whereIn('id', $selectedChargeIds)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->each(function (ShippingCharge $charge) use (&$rows) {
                    $rows[] = [
                        'area_name' => $charge->name,
                        'shipping_charge_id' => (int) $charge->id,
                    ];
                });
        }

        $areas = array_values(array_filter(array_map('trim', (array) $request->input('areas', []))));
        $chargeIds = (array) $request->input('area_charge_ids', []);
        foreach ($areas as $index => $areaName) {
            if ($areaName === '') {
                continue;
            }

            $rows[] = [
                'area_name' => $areaName,
                'shipping_charge_id' => ! empty($chargeIds[$index]) ? (int) $chargeIds[$index] : null,
            ];
        }

        $customAreas = preg_split('/\r\n|\r|\n/', (string) $request->input('custom_areas', '')) ?: [];
        foreach ($customAreas as $areaName) {
            $areaName = trim($areaName);
            if ($areaName !== '') {
                $rows[] = [
                    'area_name' => $areaName,
                    'shipping_charge_id' => null,
                ];
            }
        }

        collect($rows)
            ->unique(fn ($row) => mb_strtolower($row['area_name']))
            ->values()
            ->each(function (array $row) use ($zone) {
                ShippingZoneArea::create([
                    'shipping_zone_id' => $zone->id,
                    'area_name' => $row['area_name'],
                    'shipping_charge_id' => $row['shipping_charge_id'],
                ]);
            });
    }
}
