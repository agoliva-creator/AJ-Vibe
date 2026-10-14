<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->isVendor());
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['processing', 'shipped', 'delivered'])], 'item_id' => [$this->user()->isVendor() ? 'required' : 'nullable', 'integer']];
    }
}
