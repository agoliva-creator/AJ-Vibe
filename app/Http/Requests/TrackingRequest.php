<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isVendor();
    }

    public function rules(): array
    {
        return ['item_id' => 'required|integer', 'courier' => 'required|string|max:80', 'tracking_number' => 'required|string|max:100'];
    }
}
