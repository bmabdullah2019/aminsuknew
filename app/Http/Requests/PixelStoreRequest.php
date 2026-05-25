<?php

namespace App\Http\Requests;

use App\Models\EcomPixel;
use Illuminate\Foundation\Http\FormRequest;

class PixelStoreRequest extends FormRequest
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
        return [
            'code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:ecom_pixels,code'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
