<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;

class AccountHeadRequest extends FormRequest
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
            'HeadId' => 'nullable|integer|exists:accounts_head,HeadId',
            'HeadCode' => 'required|string|max:100',
            'HeadName' => 'required|string|max:100',
            'ParentId' => 'required|integer',
            'AccType' => 'required|integer',
            'Label' => 'required|integer|min:1',
            'Description' => 'nullable|string|max:500',
        ];
    }
}
