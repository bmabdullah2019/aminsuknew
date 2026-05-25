<?php

namespace App\Http\Requests;

use App\Models\EcomPixel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PixelUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => EcomPixel::normalizeCode((string) $this->input('code', '')),
        ]);
    }

    public function rules(): array
    {
        $id = (int) $this->input('id');

        return [
            'id' => ['required', 'integer', 'exists:ecom_pixels,id'],
            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('ecom_pixels', 'code')->ignore($id),
            ],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
