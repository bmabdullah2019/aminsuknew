<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PixelTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hidden_id' => ['required', 'integer', 'exists:ecom_pixels,id'],
        ];
    }
}
