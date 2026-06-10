<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:jpeg,jpg,png,webp', 'dimensions:min_width=1,min_height=1,max_width=8000,max_height=8000'],
            'collection' => ['sometimes', 'string', 'max:64'],
            'visibility' => ['sometimes', 'in:public,private'],
        ];
    }
}
