<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user() !== null);
    }

    public function rules(): array
    {
        $sensitiveChange = ($this->filled('email') && $this->input('email') !== $this->user()->email) || $this->filled('password');

        return [
            'name' => 'required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'current_password' => [Rule::requiredIf($sensitiveChange), 'nullable', 'current_password:web'],
            'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()],
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=3000,max_height=3000',
        ];
    }
}
