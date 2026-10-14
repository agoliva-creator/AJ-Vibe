<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->isVendor());
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($this->route('id'))],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => 'required|integer|min:1|max:'.($this->input('type') === 'percent' ? 100 : 100000000),
            'minimum_cents' => 'required|integer|min:0|max:100000000',
            'usage_limit' => 'nullable|integer|min:1|max:1000000',
            'active' => 'required|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ];
    }
}
