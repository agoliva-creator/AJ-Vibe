<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->role === 'customer');
    }

    public function rules(): array
    {
        return [
            'items' => 'present|array|max:100',
            'items.*' => 'array:product_id,quantity,variant_id',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
        ];
    }
}
