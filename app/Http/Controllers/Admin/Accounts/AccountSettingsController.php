<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\AccountHead;
use App\Models\Accounts\AccountSetting;
use App\Models\Accounts\AccountSettingItem;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AccountSettingsController extends Controller
{
    private const GLOBAL_FIELDS = [
        'Asset', 'Liability', 'Equity', 'Income', 'Expense',
        'Cash', 'Bank', 'Payable', 'AdditionalCost', 'Receivable',
        'CashAdvance', 'UndepositedFund', 'Inventory', 'WorkInProcess',
        'Sales', 'COGS', 'SalesReturn', 'VATPayable', 'DiscountAllowed',
        'Wastage', 'AdjustProfit', 'AdjustLoss', 'BulkPackCollection',
        'OwnerEquity', 'SalaryPayable', 'Salary',
    ];

    public function edit()
    {
        $settings = $this->currentSettings();
        $itemSettings = AccountSettingItem::query()
            ->valid()
            ->orderBy('ItemType')
            ->get();

        $heads = AccountHead::query()
            ->valid()
            ->orderBy('HeadCode')
            ->get(['HeadId', 'HeadCode', 'HeadName', 'AccType', 'HasChild']);

        return view('backEnd.accounts.settings.account-settings', compact('settings', 'itemSettings', 'heads'));
    }

    public function update(Request $request): RedirectResponse
    {
        $availableFields = $this->availableGlobalFields();

        $rules = collect($availableFields)
            ->mapWithKeys(fn (string $field) => [$field => ['nullable', 'integer', 'exists:accounts_head,HeadId']])
            ->all();

        $rules['item_settings'] = ['nullable', 'array'];
        $rules['item_settings.*.id'] = ['nullable', 'integer', 'exists:accounts_settings_item,id'];
        $rules['item_settings.*.ItemType'] = ['required_with:item_settings', 'string', 'max:5'];
        foreach (['Inventory', 'WorkInProcess', 'Sales', 'COGS', 'SalesReturn', 'Wastage'] as $field) {
            $rules['item_settings.*.'.$field] = ['nullable', 'integer', 'exists:accounts_head,HeadId'];
        }

        $validated = $request->validate($rules);
        $settings = $this->currentSettings();
        $this->validateGlobalMappings($settings, $validated);
        $this->validateItemMappings($validated['item_settings'] ?? []);

        DB::transaction(function () use ($validated, $availableFields) {
            DB::table('accounts_settings')
                ->where('Validity', 1)
                ->update(collect($validated)->only($availableFields)->all());

            foreach (($validated['item_settings'] ?? []) as $itemData) {
                $payload = collect($itemData)
                    ->only(['ItemType', 'Inventory', 'WorkInProcess', 'Sales', 'COGS', 'SalesReturn', 'Wastage'])
                    ->merge(['Validity' => 1])
                    ->all();

                AccountSettingItem::query()->updateOrCreate(
                    ['id' => $itemData['id'] ?? null],
                    $payload
                );
            }
        });

        Cache::forget('accounts_settings');
        Toastr::success('Accounts settings updated successfully.');

        return redirect()->route('admin.accounts.settings.edit');
    }

    private function currentSettings(): AccountSetting
    {
        $settings = AccountSetting::current();
        if ($settings) {
            return $settings;
        }

        DB::table('accounts_settings')->insert(['Validity' => 1]);

        return AccountSetting::current() ?? new AccountSetting(['Validity' => 1]);
    }

    private function availableGlobalFields(): array
    {
        return array_values(array_filter(self::GLOBAL_FIELDS, function (string $field) {
            return Schema::hasColumn('accounts_settings', $field);
        }));
    }

    private function validateGlobalMappings(AccountSetting $settings, array $input): void
    {
        $merged = array_merge($settings->getAttributes(), $input);

        $checks = [
            'Cash' => 'Asset',
            'Bank' => 'Asset',
            'Receivable' => 'Asset',
            'Inventory' => 'Asset',
            'WorkInProcess' => 'Asset',
            'UndepositedFund' => 'Asset',
            'Payable' => 'Liability',
            'CashAdvance' => 'Liability',
            'SalaryPayable' => 'Liability',
            'VATPayable' => 'Liability',
            'Sales' => 'Income',
            'SalesReturn' => 'Income',
            'COGS' => 'Expense',
            'DiscountAllowed' => 'Expense',
            'Wastage' => 'Expense',
            'Salary' => 'Expense',
        ];

        $errors = [];
        foreach ($checks as $field => $typeField) {
            if (! Schema::hasColumn('accounts_settings', $field)) {
                continue;
            }

            $headId = (int) ($merged[$field] ?? 0);
            $expectedType = (int) ($merged[$typeField] ?? 0);
            if ($headId <= 0 || $expectedType <= 0) {
                continue;
            }

            $head = AccountHead::query()->valid()->find($headId);
            if (! $head) {
                $errors[$field] = $field.' must point to an active account head.';
                continue;
            }

            if ((int) $head->AccType !== $expectedType) {
                $errors[$field] = $field.' must belong to the '.$typeField.' account type.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function validateItemMappings(array $items): void
    {
        $settings = $this->currentSettings();
        $typeMap = [
            'Inventory' => (int) $settings->Asset,
            'WorkInProcess' => (int) $settings->Asset,
            'Sales' => (int) $settings->Income,
            'SalesReturn' => (int) $settings->Income,
            'COGS' => (int) $settings->Expense,
            'Wastage' => (int) $settings->Expense,
        ];

        $errors = [];
        foreach ($items as $index => $item) {
            foreach ($typeMap as $field => $expectedType) {
                $headId = (int) ($item[$field] ?? 0);
                if ($headId <= 0 || $expectedType <= 0) {
                    continue;
                }

                $head = AccountHead::query()->valid()->find($headId);
                if ($head && (int) $head->AccType !== $expectedType) {
                    $errors["item_settings.$index.$field"] = "$field for item type {$item['ItemType']} has the wrong account type.";
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
