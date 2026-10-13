<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->role === 'customer');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['coupon_code' => strtoupper(trim((string) $this->input('coupon_code')))]);
    }

    public function rules(): array
    {
        return [
            'shipping_address' => 'required|string|min:10|max:2000',
            'coupon_code' => 'nullable|string|max:40',
            'idempotency_key' => 'required|uuid',
            'payment_method' => 'sometimes|required|in:cod,gcash,maya,credit_card',
        ];
    }
}
