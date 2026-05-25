<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class VoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'TranId' => 'nullable|integer|exists:accounts_transaction,TranId',
            'TranDate' => 'required|date',
            'TranNo' => 'nullable|string|max:50',
            'Remarks' => 'nullable|string|max:500',
            'TotalDebit' => 'required|numeric|min:0.01',
            'TotalCredit' => 'required|numeric|same:TotalDebit',
            'HeadId' => 'required|array|min:2',
            'HeadId.*' => 'required|integer|exists:accounts_head,HeadId',
            'SubId' => 'nullable|array',
            'SubId.*' => 'nullable|integer|exists:accounts_subsidiary,SubId',
            'Debit' => 'required|array',
            'Debit.*' => 'nullable|numeric|min:0',
            'Credit' => 'required|array',
            'Credit.*' => 'nullable|numeric|min:0',
            'Narration' => 'nullable|array',
            'Narration.*' => 'nullable|string|max:500',
            'BankName' => 'nullable|array',
            'BankName.*' => 'nullable|string|max:150',
            'BranchName' => 'nullable|array',
            'BranchName.*' => 'nullable|string|max:150',
            'ChequeNo' => 'nullable|array',
            'ChequeNo.*' => 'nullable|string|max:100',
            'ChequeDate' => 'nullable|array',
            'ChequeDate.*' => 'nullable|date',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $headIds = $this->input('HeadId', []);
            $debits = $this->input('Debit', []);
            $credits = $this->input('Credit', []);
            $subIds = $this->input('SubId', []);
            $narrations = $this->input('Narration', []);
            $bankNames = $this->input('BankName', []);
            $branchNames = $this->input('BranchName', []);
            $chequeNos = $this->input('ChequeNo', []);
            $chequeDates = $this->input('ChequeDate', []);

            $lineCount = count($headIds);
            $parallelCounts = [
                'Debit' => count($debits),
                'Credit' => count($credits),
                'SubId' => count($subIds),
                'Narration' => count($narrations),
                'BankName' => count($bankNames),
                'BranchName' => count($branchNames),
                'ChequeNo' => count($chequeNos),
                'ChequeDate' => count($chequeDates),
            ];

            foreach ($parallelCounts as $field => $count) {
                if ($count !== 0 && $count !== $lineCount) {
                    $validator->errors()->add($field, 'Voucher line data is inconsistent. Please refresh and try again.');

                    return;
                }
            }

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($headIds as $index => $headId) {
                $debit = (float) ($debits[$index] ?? 0);
                $credit = (float) ($credits[$index] ?? 0);

                if ($debit > 0 && $credit > 0) {
                    $validator->errors()->add("Debit.$index", 'Each line can contain either a debit or a credit amount, not both.');
                }

                if ($debit <= 0 && $credit <= 0) {
                    $validator->errors()->add("Debit.$index", 'Each line must contain a debit or credit amount greater than zero.');
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                $validator->errors()->add('TotalCredit', 'Total debit and total credit must be equal.');
            }
        });
    }
}
