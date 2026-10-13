<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->isAdmin());
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('id'))],
            'password' => [$this->route('id') ? 'nullable' : 'required', Password::min(12)->mixedCase()->numbers()],
            'role' => ['required', Rule::in(['admin', 'vendor', 'customer'])],
            'active' => 'required|boolean',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=3000,max_height=3000',
        ];
    }
}
