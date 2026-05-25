<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\AccountHead;
use App\Models\PaymentHeadMapping;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PaymentHeadMappingController extends Controller
{
    public function index(Request $request)
    {
        $context = $request->get('context', PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT);
        $branchId = $request->get('branch_id');
        $methods = PaymentHeadMapping::methodOptions();
        $controlHeadField = PaymentHeadMapping::controlHeadFieldForContext($context);
        $controlHeadLabel = PaymentHeadMapping::controlHeadLabelForContext($context);

        $accountHeads = AccountHead::query()
            ->valid()
            ->leaves()
            ->orderBy('HeadCode')
            ->get(['HeadId', 'HeadCode', 'HeadName']);

        $branches = \App\Models\Branch::query()->where('status', 1)->get();

        $existingQuery = PaymentHeadMapping::query()
            ->forContext($context)
            ->active();

        if ($branchId !== null && $branchId !== '') {
            $existingQuery->forBranch($branchId);
        } else {
            $existingQuery->whereNull('branch_id');
        }

        // Get full models to access is_locked, mapped by payment_method
        $existing = $existingQuery->get()->keyBy('payment_method');
        $controlHeadId = $this->currentControlHeadId($controlHeadField);

        return view('backEnd.accounts.settings.payment-head-mappings', compact(
            'context',
            'branchId',
            'methods',
            'accountHeads',
            'branches',
            'existing',
            'controlHeadField',
            'controlHeadLabel',
            'controlHeadId'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $methods = array_keys(PaymentHeadMapping::methodOptions());

        $validated = $request->validate([
            'context' => [
                'required',
                'string',
                'max:60',
                Rule::in([
                    PaymentHeadMapping::CONTEXT_SUPPLIER_PAYMENT,
                    PaymentHeadMapping::CONTEXT_CUSTOMER_PAYMENT,
                ]),
            ],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'control_head_id' => ['nullable', 'integer', 'exists:accounts_head,HeadId'],
            'mappings' => ['required', 'array'],
            'mappings.*' => ['nullable', 'integer', 'exists:accounts_head,HeadId'],
            'locks' => ['nullable', 'array'],
            'locks.*' => ['nullable', 'boolean'],
        ]);

        $context = (string) $validated['context'];
        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        $controlHeadId = isset($validated['control_head_id']) ? (int) $validated['control_head_id'] : null;
        $inputMappings = (array) ($validated['mappings'] ?? []);
        $inputLocks = (array) ($validated['locks'] ?? []);
        $userId = auth()->id();

        DB::transaction(function () use ($context, $branchId, $controlHeadId, $inputMappings, $inputLocks, $methods, $userId) {
            $this->syncControlHeadSetting($context, $controlHeadId);

            foreach ($methods as $method) {
                $headId = (int) ($inputMappings[$method] ?? 0);
                $isLocked = ! empty($inputLocks[$method]);

                $query = PaymentHeadMapping::query()
                    ->forContext($context)
                    ->where('payment_method', $method);

                if ($branchId !== null) {
                    $query->forBranch($branchId);
                } else {
                    $query->whereNull('branch_id');
                }

                $mapping = $query->first();

                if ($headId <= 0) {
                    if ($mapping) {
                        $mapping->delete();
                    }

                    continue;
                }

                if (! $mapping) {
                    $mapping = new PaymentHeadMapping([
                        'context' => $context,
                        'payment_method' => $method,
                        'branch_id' => $branchId,
                        'created_by' => $userId,
                    ]);
                }

                $mapping->fill([
                    'account_head_id' => $headId,
                    'is_active' => true,
                    'is_locked' => $isLocked,
                    'updated_by' => $userId,
                ]);

                if (! $mapping->exists && $mapping->created_by === null) {
                    $mapping->created_by = $userId;
                }

                $mapping->save();
            }
        });

        Cache::forget('accounts_settings');
        Toastr::success('Payment to Accounts Head mapping updated successfully.');

        return redirect()->route('admin.accounts.payment-head-mappings.index', ['context' => $context, 'branch_id' => $branchId]);
    }

    private function currentControlHeadId(?string $field): ?int
    {
        if ($field === null || ! Schema::hasTable('accounts_settings')) {
            return null;
        }

        $value = DB::table('accounts_settings')
            ->where('Validity', 1)
            ->value($field);

        return $value !== null ? (int) $value : null;
    }

    private function syncControlHeadSetting(string $context, ?int $controlHeadId): void
    {
        $field = PaymentHeadMapping::controlHeadFieldForContext($context);
        if ($field === null || ! Schema::hasTable('accounts_settings')) {
            return;
        }

        if (! DB::table('accounts_settings')->where('Validity', 1)->exists()) {
            DB::table('accounts_settings')->insert([
                'Validity' => 1,
            ]);
        }

        DB::table('accounts_settings')
            ->where('Validity', 1)
            ->update([
                $field => $controlHeadId,
            ]);
    }
}
