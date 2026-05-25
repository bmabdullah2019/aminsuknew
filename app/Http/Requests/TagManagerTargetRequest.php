<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TagManagerTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hidden_id' => ['required', 'integer', 'exists:google_tag_managers,id'],
        ];
    }
}
