<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->isAdmin());
    }

    public function rules(): array
    {
        return [
            'store_name' => 'required|string|max:100',
            'tax_rate_bps' => 'required|integer|min:0|max:10000',
            'low_stock_threshold' => 'required|integer|min:0|max:1000',
            'shipping_fee_cents' => 'sometimes|required|integer|min:0|max:1000000',
        ];
    }
}
