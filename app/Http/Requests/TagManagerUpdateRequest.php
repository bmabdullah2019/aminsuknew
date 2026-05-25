<?php

namespace App\Http\Requests;

use App\Models\GoogleTagManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TagManagerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => GoogleTagManager::normalizeCode((string) $this->input('code', '')),
        ]);
    }

    public function rules(): array
    {
        $id = (int) $this->input('id');

        return [
            'id' => ['required', 'integer', 'exists:google_tag_managers,id'],
            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('google_tag_managers', 'code')->ignore($id),
            ],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
