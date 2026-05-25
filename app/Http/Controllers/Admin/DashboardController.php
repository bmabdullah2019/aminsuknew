<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DashboardIntegrityService;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Session;
use Toastr;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin|dashboard-view', ['only' => ['dashboard']]);
        $this->middleware('role_or_permission:Admin|admin-password-change', ['only' => ['changepassword', 'newpassword']]);
    }

    public function dashboard(DashboardIntegrityService $dashboardIntegrityService)
    {
        // Keep dashboard responsive under concurrent admin traffic.
        $warehouseId = Session::get('warehouse_id');
        if (! $warehouseId) {
            $warehouse = Warehouse::main()->active()->first() ?? Warehouse::active()->first();
            $warehouseId = $warehouse?->id;
        }

        $cacheKey = 'admin:dashboard:v1:warehouse:'.((int) ($warehouseId ?? 0));
        $cached = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($warehouseId) {
            $total_order = Order::count();
            $today_order = Order::where('created_at', '>=', Carbon::today())->count();
            $neworder = Order::where(['order_status' => '1'])->count();
            $pendingorder = Order::where(['order_status' => '1'])->with('customer')->limit(10)->get();
            $total_product = Product::count();
            $total_customer = Customer::count();
            $latest_order = Order::where('created_at', '>=', Carbon::today())
                ->latest()
                ->limit(10)
                ->with('customer', 'product', 'product.image')
                ->get();
            $latest_customer = Customer::latest()->limit(5)->get();
            $today_delivery = Order::where(['order_status' => '5'])->where('created_at', '>=', Carbon::today())->count();
            $total_delivery = Order::where(['order_status' => '5'])->count();
            $last_week = Order::where(['order_status' => '5'])->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
            $last_month = Order::where(['order_status' => '5'])->whereMonth('created_at', '=', Carbon::now()->subMonth()->month)->count();
            $monthly_sale = Order::selectRaw('DATE(created_at) as date, SUM(amount) as amount')
                ->where('order_status', '5')
                ->groupBy('date')
                ->orderByDesc('date')
                ->limit(30)
                ->get();

            $low_stock_products = collect();
            $low_stock_count = 0;
            if ($warehouseId) {
                $lowStockBase = Product::query()
                    ->join('warehouse_stock as ws', function ($join) use ($warehouseId) {
                        $join->on('products.id', '=', 'ws.product_id')
                            ->where('ws.warehouse_id', '=', $warehouseId);
                    })
                    ->where('ws.available_quantity', '<=', 10);

                $low_stock_products = (clone $lowStockBase)
                    ->select('products.*')
                    ->with(['image', 'category'])
                    ->orderBy('ws.available_quantity', 'asc')
                    ->limit(10)
                    ->get();

                $low_stock_count = (clone $lowStockBase)->count();
            }

            $today_revenue = Order::where('created_at', '>=', Carbon::today())
                ->where('order_status', '!=', 'cancelled')
                ->sum('amount');

            return compact(
                'total_order',
                'today_order',
                'neworder',
                'pendingorder',
                'total_product',
                'total_customer',
                'latest_order',
                'latest_customer',
                'today_delivery',
                'total_delivery',
                'last_week',
                'last_month',
                'monthly_sale',
                'low_stock_products',
                'low_stock_count',
                'today_revenue'
            );
        });

        extract($cached);

        $integrity_summary = $dashboardIntegrityService->summary();

        return view('backEnd.admin.dashboard', compact(
            'total_order',
            'today_order',
            'neworder',
            'pendingorder',
            'total_product',
            'total_customer',
            'latest_order',
            'latest_customer',
            'today_delivery',
            'total_delivery',
            'last_week',
            'last_month',
            'monthly_sale',
            'low_stock_products',
            'low_stock_count',
            'today_revenue',
            'integrity_summary'
        ));
    }

    public function changepassword()
    {
        return view('backEnd.admin.changepassword');
    }

    public function newpassword(Request $request)
    {
        $this->validate($request, [
            'old_password' => 'required',
            'new_password' => 'required',
            'confirm_password' => 'required_with:new_password|same:new_password|',
        ]);

        $user = User::find(Auth::id());
        $hashPass = $user->password;

        if (Hash::check($request->old_password, $hashPass)) {

            $user->fill([
                'password' => Hash::make($request->new_password),
            ])->save();

            Toastr::success('Success', 'Password changed successfully!');

            return redirect()->route('admin.dashboard');
        } else {
            Toastr::error('Failed', 'Old password not match!');

            return back();
        }
    }

    public function locked()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        Session::put('locked', true);

        return view('backEnd.auth.locked');
    }

    public function unlocked(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }
        $password = $request->password;
        if (Hash::check($password, Auth::user()->password)) {
            Session::forget('locked');
            Toastr::success('Success', 'You are logged in successfully!');

            return redirect()->route('admin.dashboard');
        }
        Toastr::error('Failed', 'Your password not match!');

        return back();
    }
}
