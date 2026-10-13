<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (true);
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:100',
            'category' => 'nullable|integer|exists:categories,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
