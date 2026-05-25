<?php

namespace App\Http\Controllers;

use App\Models\PartialOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PartialOrderController extends Controller
{
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'nullable|string|max:128',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:2000',
            'products' => 'nullable|array|max:500',
            'products.*.id' => 'required',
            'products.*.qty' => 'nullable|integer|min:1|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Count non-empty fields among name, phone, address
        $fields = ['name', 'phone', 'address'];
        $filledCount = 0;
        foreach ($fields as $field) {
            if (! empty(trim($data[$field] ?? ''))) {
                $filledCount++;
            }
        }

        // If fewer than 2 fields filled, skip saving
        if ($filledCount < 2) {
            return response()->json(['ok' => true, 'message' => 'Not enough fields filled to save partial order']);
        }

        $deviceId = $data['device_id'] ?? $request->cookie('partial_device_id_v1') ?? (string) Str::uuid();
        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $address = trim($data['address'] ?? '');

        $products = collect($data['products'] ?? [])->map(function ($p) {
            return ['id' => (string) $p['id'], 'qty' => isset($p['qty']) ? (int) $p['qty'] : 1];
        })->values()->toArray();

        // Try to find existing partial order by device_id or phone
        $partial = PartialOrder::where('device_id', $deviceId)
            ->orWhere(function ($query) use ($phone) {
                if ($phone !== '') {
                    $query->where('phone', $phone);
                }
            })
            ->first();

        if ($partial) {
            $partial->update([
                'products' => $products,
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
                'status' => 'incomplete',
                'meta' => [
                    'ip' => $request->ip(),
                    'ua' => substr($request->header('User-Agent') ?? '', 0, 1000),
                    'last_saved_at' => now()->toDateTimeString(),
                ],
            ]);
        } else {
            $partial = PartialOrder::create([
                'device_id' => $deviceId,
                'products' => $products,
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
                'status' => 'incomplete',
                'meta' => [
                    'ip' => $request->ip(),
                    'ua' => substr($request->header('User-Agent') ?? '', 0, 1000),
                    'last_saved_at' => now()->toDateTimeString(),
                ],
            ]);
        }

        return response()->json(['ok' => true, 'device_id' => $deviceId, 'partial_id' => $partial->id, 'saved_at' => $partial->updated_at]);
    }

    public function load(Request $request)
    {
        $deviceId = $request->cookie('partial_device_id_v1') ?? $request->query('device_id');
        if (! $deviceId) {
            return response()->json(['ok' => true, 'data' => null]);
        }
        $partial = PartialOrder::where('device_id', $deviceId)->first();

        return response()->json(['ok' => true, 'data' => $partial]);
    }
}
