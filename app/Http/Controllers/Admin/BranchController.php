<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $validated = $request->validate([
            'search' => 'nullable|string|max:120',
            'status' => 'nullable|in:1,0,active,inactive',
        ]);

        $branches = Branch::query()
            ->when(! empty($validated['search']), function ($query) use ($validated) {
                $keyword = trim((string) $validated['search']);
                $query->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('code', 'like', '%'.$keyword.'%')
                        ->orWhere('phone', 'like', '%'.$keyword.'%');
                });
            })
            ->when(isset($validated['status']) && $validated['status'] !== '', function ($query) use ($validated) {
                $status = strtolower((string) $validated['status']);
                $query->where('status', in_array($status, ['1', 'active'], true));
            })
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        return view('backEnd.branch.index', compact('branches'));
    }

    public function create(): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        return view('backEnd.branch.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'code' => ['required', 'string', 'max:30', Rule::unique('branches', 'code')],
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:40',
            'status' => 'nullable|boolean',
        ]);

        Branch::create([
            'name' => trim((string) $validated['name']),
            'code' => strtoupper(trim((string) $validated['code'])),
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        Toastr::success('Branch created successfully.');

        return redirect()->route('admin.branches.index');
    }

    public function edit(int $branch): View|RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $branch = Branch::query()->findOrFail($branch);

        return view('backEnd.branch.edit', compact('branch'));
    }

    public function update(Request $request, int $branch): RedirectResponse
    {
        if (! $this->ready()) {
            return $this->missingTableRedirect();
        }

        $branch = Branch::query()->findOrFail($branch);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'code' => ['required', 'string', 'max:30', Rule::unique('branches', 'code')->ignore($branch->id)],
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:40',
            'status' => 'nullable|boolean',
        ]);

        $branch->update([
            'name' => trim((string) $validated['name']),
            'code' => strtoupper(trim((string) $validated['code'])),
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        Toastr::success('Branch updated successfully.');

        return redirect()->route('admin.branches.index');
    }

    private function ready(): bool
    {
        return Schema::hasTable('branches');
    }

    private function missingTableRedirect(): RedirectResponse
    {
        Toastr::error('Branches module is not ready. Run migrations first.');

        return redirect()->route('admin.dashboard');
    }
}
