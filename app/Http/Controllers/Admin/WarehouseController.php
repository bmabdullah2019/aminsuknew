<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Constructor - Apply permissions
     */
    public function __construct()
    {
        $this->middleware('permission:warehouse-list', ['only' => ['index']]);
        $this->middleware('permission:warehouse-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:warehouse-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:warehouse-delete', ['only' => ['destroy']]);
        $this->middleware('permission:warehouse-view', ['only' => ['show']]);
        $this->middleware('permission:warehouse-activate', ['only' => ['activate', 'deactivate']]);
    }

    /**
     * Display a listing of warehouses
     */
    public function index(Request $request)
    {
        $query = Warehouse::with('manager');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->inactive();
            }
        }

        // Filter by type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        $warehouses = $query->latest()->paginate(20);

        return view('backEnd.warehouse.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new warehouse
     */
    public function create()
    {
        $managers = User::permission('warehouse-list')->get();

        return view('backEnd.warehouse.create', compact('managers'));
    }

    /**
     * Store a newly created warehouse
     */
    public function store(WarehouseRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->has('is_active') ? true : ($request->input('is_active', true));

        Warehouse::create($validated);

        Toastr::success('Warehouse created successfully', 'Success');

        return redirect()->route('admin.warehouse.index');
    }

    /**
     * Display the specified warehouse
     */
    public function show($id)
    {
        $warehouse = Warehouse::with(['manager', 'stock.product'])->findOrFail($id);

        // Get statistics
        $stats = [
            'total_stock_value' => $warehouse->total_stock_value,
            'total_products' => $warehouse->stock()->count(),
            'low_stock_count' => $warehouse->low_stock_count,
            'recent_movements' => $warehouse->movements()->latest()->take(10)->get(),
        ];

        return view('backEnd.warehouse.show', compact('warehouse', 'stats'));
    }

    /**
     * Show the form for editing the specified warehouse
     */
    public function edit($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $managers = User::permission('warehouse-list')->get();

        return view('backEnd.warehouse.edit', compact('warehouse', 'managers'));
    }

    /**
     * Update the specified warehouse
     */
    public function update(WarehouseRequest $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $validated = $request->validated();
        $validated['is_active'] = $request->has('is_active') ? true : ($request->input('is_active', $warehouse->is_active));

        $warehouse->update($validated);

        Toastr::success('Warehouse updated successfully', 'Success');

        return redirect()->route('admin.warehouse.index');
    }

    /**
     * Remove the specified warehouse
     */
    public function destroy(Request $request, $id = null)
    {
        $targetId = $id ?? $request->input('hidden_id') ?? $request->input('id');

        if (! $targetId) {
            Toastr::error('Invalid warehouse delete request', 'Error');

            return redirect()->back();
        }

        $warehouse = Warehouse::findOrFail($targetId);

        // Check if warehouse has stock
        if ($warehouse->stock()->where('physical_quantity', '>', 0)->exists()) {
            Toastr::error('Cannot delete warehouse with existing stock', 'Error');

            return redirect()->back();
        }

        // Check if warehouse has pending transfers
        if ($warehouse->transfersFrom()->whereIn('status', ['pending', 'approved', 'dispatched'])->exists() ||
            $warehouse->transfersTo()->whereIn('status', ['pending', 'approved', 'dispatched'])->exists()) {
            Toastr::error('Cannot delete warehouse with pending transfers', 'Error');

            return redirect()->back();
        }

        $warehouse->delete();

        Toastr::success('Warehouse deleted successfully', 'Success');

        return redirect()->route('admin.warehouse.index');
    }

    /**
     * Activate warehouse
     */
    public function activate($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->activate();

        Toastr::success('Warehouse activated successfully', 'Success');

        return redirect()->back();
    }

    /**
     * Deactivate warehouse
     */
    public function deactivate($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->deactivate();

        Toastr::success('Warehouse deactivated successfully', 'Success');

        return redirect()->back();
    }
}
