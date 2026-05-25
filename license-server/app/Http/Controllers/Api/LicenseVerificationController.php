<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'domain' => ['required', 'string', 'max:255'],
            'app_key' => ['required', 'string'],
            'signature' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'inactive',
                'expires_at' => null,
                'message' => 'Invalid request.',
            ], 422);
        }

        $domain = strtolower(trim((string) $request->input('domain')));
        if (str_starts_with($domain, 'www.')) {
            $domain = substr($domain, 4);
        }

        $license = License::where('domain', $domain)->first();
        if (! $license) {
            return response()->json([
                'status' => 'inactive',
                'expires_at' => null,
                'message' => 'Domain not licensed.',
            ]);
        }

        // --- Request authenticity (HMAC) ---
        // IMPORTANT: This must match the client implementation.
        $licenseKey = (string) $license->license_key; // decrypted via cast
        $appKeyExpected = hash('sha256', $licenseKey);

        if (! hash_equals($appKeyExpected, (string) $request->input('app_key'))) {
            return response()->json([
                'status' => 'inactive',
                'expires_at' => null,
                'message' => 'Invalid app key.',
            ], 401);
        }

        $payload = $domain.'|'.$appKeyExpected.'|lic_v1';
        $sigExpected = hash_hmac('sha256', $payload, $licenseKey);

        if (! hash_equals($sigExpected, (string) $request->input('signature'))) {
            return response()->json([
                'status' => 'inactive',
                'expires_at' => null,
                'message' => 'Invalid signature.',
            ], 401);
        }

        // --- License evaluation ---
        $now = now();
        $isExpired = $license->expires_at !== null && $license->expires_at->isPast();

        if ($license->status !== 'active') {
            $license->last_checked_at = $now;
            $license->save();

            return response()->json([
                'status' => 'inactive',
                'expires_at' => optional($license->expires_at)->toIso8601String(),
                'message' => 'License is not active.',
            ]);
        }

        if ($isExpired) {
            $license->last_checked_at = $now;
            $license->save();

            return response()->json([
                'status' => 'inactive',
                'expires_at' => $license->expires_at?->toIso8601String(),
                'message' => 'License expired.',
            ]);
        }

        $license->last_checked_at = $now;
        $license->save();

        return response()->json([
            'status' => 'active',
            'expires_at' => $license->expires_at?->toIso8601String(),
            'message' => 'License active.',
        ]);
    }
}
