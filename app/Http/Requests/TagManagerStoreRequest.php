<?php

namespace App\Http\Requests;

use App\Models\GoogleTagManager;
use Illuminate\Foundation\Http\FormRequest;

class TagManagerStoreRequest extends FormRequest
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
        return [
            'code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:google_tag_managers,code'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
