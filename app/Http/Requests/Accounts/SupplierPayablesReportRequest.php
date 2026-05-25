<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;

class SupplierPayablesReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('supplier-report-dues');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => is_string($this->input('search'))
                ? trim($this->input('search'))
                : $this->input('search'),
        ]);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'only_due' => ['nullable', 'in:0,1'],
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:due_desc,due_asc,purchase_desc,purchase_asc,paid_desc,paid_asc,supplier_asc,supplier_desc'],
            'export' => ['nullable', 'in:xlsx'],
        ];
    }

    public function branchId(): ?int
    {
        $branchId = $this->validated('branch_id');

        return filled($branchId) ? (int) $branchId : null;
    }

    public function supplierId(): ?int
    {
        $supplierId = $this->validated('supplier_id');

        return filled($supplierId) ? (int) $supplierId : null;
    }

    public function onlyDue(): bool
    {
        return ! array_key_exists('only_due', $this->validated()) || $this->validated('only_due') === '1';
    }

    public function searchTerm(): string
    {
        return (string) ($this->validated('search') ?? '');
    }

    public function sortOption(): string
    {
        return (string) ($this->validated('sort') ?? 'due_desc');
    }

    public function shouldExportXlsx(): bool
    {
        return $this->validated('export') === 'xlsx';
    }
}
