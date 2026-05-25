<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\IpBlock;
use App\Models\PhoneBlock;
use App\Services\PhoneBlockService;
use Auth;
use File;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Toastr;

class CustomerManageController extends Controller
{
    public function __construct()
    {
        $this->middleware('role_or_permission:Admin|customer-list', ['only' => ['index']]);
        $this->middleware('role_or_permission:Admin|customer-view', ['only' => ['profile']]);
        $this->middleware('role_or_permission:Admin|customer-edit', ['only' => ['edit', 'update']]);
        $this->middleware('role_or_permission:Admin|customer-status-change', ['only' => ['active', 'inactive']]);
        $this->middleware('role_or_permission:Admin|customer-admin-login', ['only' => ['adminlog']]);
        $this->middleware('role_or_permission:Admin|customer-ip-block', ['only' => ['ip_block', 'ipblock_store', 'ipblock_update', 'ipblock_destroy', 'phone_block', 'phoneblock_store', 'phoneblock_update', 'phoneblock_toggle', 'phoneblock_destroy']]);
    }

    public function index(Request $request)
    {
        if ($request->keyword) {
            $show_data = Customer::orWhere('phone', $request->keyword)->orWhere('name', $request->keyword)->paginate(20);
        } else {
            $show_data = Customer::paginate(20);
        }

        return view('backEnd.customer.index', compact('show_data'));
    }

    public function edit($id)
    {
        $edit_data = Customer::findOrFail($id);

        return view('backEnd.customer.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'phone' => 'required',
            'email' => 'required',
        ]);

        $input = $request->except('hidden_id');
        $update_data = Customer::findOrFail($request->hidden_id);
        // new password

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
            $uploadpath = 'public/uploads/customer/';
            $imageUrl = $uploadpath.$name;
            ImageHelper::resizeAndSaveWebp($image->getRealPath(), $imageUrl, 100, 100, 90);
            $input['image'] = $imageUrl;
            File::delete($update_data->image);
        } else {
            $input['image'] = $update_data->image;
        }
        $input['status'] = $request->status ? 1 : 0;
        $update_data->update($input);

        Toastr::success('Success', 'Data update successfully');

        return redirect()->route('admin.customers.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Customer::findOrFail($request->hidden_id);
        $inactive->status = 'inactive';
        $inactive->save();
        Toastr::success('Success', 'Data inactive successfully');

        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Customer::findOrFail($request->hidden_id);
        $active->status = 'active';
        $active->save();
        Toastr::success('Success', 'Data active successfully');

        return redirect()->back();
    }

    public function profile(Request $request)
    {
        $profile = Customer::with('orders')->find($request->id);

        return view('backEnd.customer.profile', compact('profile'));
    }

    public function adminlog(Request $request)
    {
        $customer = Customer::findOrFail($request->hidden_id);
        Auth::guard('customer')->loginUsingId($customer->id);

        return redirect()->route('customer.account');
    }

    public function ip_block(Request $request)
    {
        $data = IpBlock::query()->latest('id')->get();
        $totalBlocked = IpBlock::count();
        $todayBlocked = IpBlock::whereDate('created_at', now()->toDateString())->count();
        $latestBlockedAt = IpBlock::max('created_at');

        return view('backEnd.reports.ipblock', compact(
            'data',
            'totalBlocked',
            'todayBlocked',
            'latestBlockedAt'
        ));
    }

    public function ipblock_store(Request $request)
    {

        $store_data = new IpBlock;
        $store_data->ip_no = $request->ip_no;
        $store_data->reason = $request->reason;
        $store_data->save();
        Toastr::success('Success', 'IP address add successfully');

        return redirect()->back();
    }

    public function ipblock_update(Request $request)
    {
        $update_data = IpBlock::findOrFail($request->id);
        $update_data->ip_no = $request->ip_no;
        $update_data->reason = $request->reason;
        $update_data->save();
        Toastr::success('Success', 'IP address update successfully');

        return redirect()->back();
    }

    public function ipblock_destroy(Request $request)
    {
        $delete_data = IpBlock::findOrFail($request->id)->delete();
        Toastr::success('Success', 'IP address delete successfully');

        return redirect()->back();
    }

    public function phone_block(Request $request)
    {
        if (! $this->ensurePhoneBlockTableExists()) {
            return redirect()->back();
        }

        $data = PhoneBlock::query()->latest('id')->get();
        $totalBlocked = PhoneBlock::count();
        $activeBlocked = PhoneBlock::where('is_active', true)->count();
        $todayBlocked = PhoneBlock::whereDate('blocked_at', now()->toDateString())->count();
        $latestBlockedAt = PhoneBlock::max('blocked_at');

        return view('backEnd.reports.phoneblock', compact(
            'data',
            'totalBlocked',
            'activeBlocked',
            'todayBlocked',
            'latestBlockedAt'
        ));
    }

    public function phoneblock_store(Request $request, PhoneBlockService $phoneBlockService)
    {
        if (! $this->ensurePhoneBlockTableExists()) {
            return redirect()->back();
        }

        $validated = $request->validate([
            'phone' => 'required|string|max:30',
            'reason' => 'required|string|max:500',
        ]);

        $storeData = $phoneBlockService->manualBlock($validated['phone'], $validated['reason']);
        if (! $storeData) {
            Toastr::error('Invalid phone number format.', 'Failed');

            return redirect()->back()->withInput();
        }

        Toastr::success('Success', 'Phone number blocked successfully');

        return redirect()->back();
    }

    public function phoneblock_update(Request $request, PhoneBlockService $phoneBlockService)
    {
        if (! $this->ensurePhoneBlockTableExists()) {
            return redirect()->back();
        }

        $validated = $request->validate([
            'id' => 'required|integer|exists:phone_blocks,id',
            'phone' => 'required|string|max:30',
            'reason' => 'required|string|max:500',
            'cancel_count' => 'nullable|integer|min:0|max:999999',
            'is_active' => 'nullable|boolean',
        ]);

        $updateData = PhoneBlock::findOrFail((int) $validated['id']);
        $normalizedPhone = $phoneBlockService->normalizePhone($validated['phone']);
        if ($normalizedPhone === '') {
            Toastr::error('Invalid phone number format.', 'Failed');

            return redirect()->back()->withInput();
        }

        $duplicate = PhoneBlock::query()
            ->where('normalized_phone', $normalizedPhone)
            ->where('id', '!=', (int) $updateData->id)
            ->exists();
        if ($duplicate) {
            Toastr::error('Another blocked phone already exists for this number.', 'Failed');

            return redirect()->back()->withInput();
        }

        $updateData->phone = $validated['phone'];
        $updateData->normalized_phone = $normalizedPhone;
        $updateData->reason = $validated['reason'];
        if (isset($validated['cancel_count'])) {
            $updateData->cancel_count = (int) $validated['cancel_count'];
        }

        $isActive = array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $updateData->is_active;
        $updateData->is_active = $isActive;
        if ($isActive && ! $updateData->blocked_at) {
            $updateData->blocked_at = now();
        }
        $updateData->save();

        Toastr::success('Success', 'Blocked phone updated successfully');

        return redirect()->back();
    }

    public function phoneblock_toggle(Request $request)
    {
        if (! $this->ensurePhoneBlockTableExists()) {
            return redirect()->back();
        }

        $validated = $request->validate([
            'id' => 'required|integer|exists:phone_blocks,id',
            'is_active' => 'required|boolean',
        ]);

        $record = PhoneBlock::findOrFail((int) $validated['id']);
        $record->is_active = (bool) $validated['is_active'];
        if ($record->is_active && ! $record->blocked_at) {
            $record->blocked_at = now();
        }
        $record->save();

        Toastr::success('Success', $record->is_active ? 'Phone blocked successfully' : 'Phone unblocked successfully');

        return redirect()->back();
    }

    public function phoneblock_destroy(Request $request)
    {
        if (! $this->ensurePhoneBlockTableExists()) {
            return redirect()->back();
        }

        $validated = $request->validate([
            'id' => 'required|integer|exists:phone_blocks,id',
        ]);

        PhoneBlock::findOrFail((int) $validated['id'])->delete();
        Toastr::success('Success', 'Blocked phone removed successfully');

        return redirect()->back();
    }

    private function ensurePhoneBlockTableExists(): bool
    {
        if (Schema::hasTable('phone_blocks')) {
            return true;
        }

        Toastr::error('Phone block table is missing. Please run migrations first.', 'Failed');

        return false;
    }
}
