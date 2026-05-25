<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LicenseController extends Controller
{
    public function index()
    {
        $licenses = License::orderBy('created_at', 'desc')->paginate(20);

        return view('admin.licenses.index', compact('licenses'));
    }

    public function create()
    {
        return view('admin.licenses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'domain' => 'required|string|unique:licenses,domain',
            'status' => 'required|in:active,inactive,suspended',
            'expires_at' => 'nullable|date',
        ]);

        $domain = strtolower(trim($request->domain));
        if (str_starts_with($domain, 'www.')) {
            $domain = substr($domain, 4);
        }

        License::create([
            'domain' => $domain,
            'license_key' => Str::random(64),
            'status' => $request->status,
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->route('admin.licenses.index')->with('success', 'License created successfully.');
    }

    public function edit(License $license)
    {
        return view('admin.licenses.edit', compact('license'));
    }

    public function update(Request $request, License $license)
    {
        $request->validate([
            'domain' => 'required|string|unique:licenses,domain,'.$license->id,
            'status' => 'required|in:active,inactive,suspended',
            'expires_at' => 'nullable|date',
        ]);

        $domain = strtolower(trim($request->domain));
        if (str_starts_with($domain, 'www.')) {
            $domain = substr($domain, 4);
        }

        $license->update([
            'domain' => $domain,
            'status' => $request->status,
            'expires_at' => $request->expires_at,
        ]);

        if ($request->has('regenerate_key')) {
            $license->update(['license_key' => Str::random(64)]);
        }

        return redirect()->route('admin.licenses.index')->with('success', 'License updated successfully.');
    }

    public function destroy(License $license)
    {
        $license->delete();

        return redirect()->route('admin.licenses.index')->with('success', 'License deleted successfully.');
    }

    public function rotateKey(License $license)
    {
        $license->update(['license_key' => Str::random(64)]);

        return back()->with('success', 'License key rotated.');
    }
}
