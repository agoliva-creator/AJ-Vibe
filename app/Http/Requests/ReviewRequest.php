<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->role === 'customer');
    }

    public function rules(): array
    {
        return ['rating' => 'required|integer|min:1|max:5', 'body' => 'required|string|min:5|max:2000'];
    }
}
