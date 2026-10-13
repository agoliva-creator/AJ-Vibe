<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) (true);
    }

    public function rules(): array
    {
        return ['product_id' => 'required|integer|exists:products,id', 'variant_id' => 'nullable|integer|exists:product_variants,id', 'original_variant_id' => 'nullable|integer|exists:product_variants,id', 'quantity' => 'required|integer|min:0|max:1000'];
    }
}
