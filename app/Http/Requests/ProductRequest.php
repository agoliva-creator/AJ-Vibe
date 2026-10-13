<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->isAdmin() || $this->user()?->isVendor());
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'price' => ['required', 'regex:/^\\d{1,7}(\\.\\d{1,2})?$/'],
            'stock' => 'required|integer|min:0|max:1000000',
            'category_id' => 'required|exists:categories,id',
            'status' => ['required', Rule::in(['active', 'draft'])],
            'vendor_id' => [$this->user()->isAdmin() ? 'required' : 'prohibited', Rule::exists('users', 'id')->where('role', 'vendor')->where('active', true)->whereNull('deleted_at')],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=6000,max_height=6000',
            'variants' => 'sometimes|array|max:100',
            'variants.*.size' => 'required|string|max:30',
            'variants.*.color' => 'required|string|max:50',
            'variants.*.stock' => 'required|integer|min:0|max:1000000',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('variant_rows')) {
            $rows = [];
            foreach (preg_split('/\R/', trim((string) $this->input('variant_rows'))) as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line));
                $rows[] = ['size' => $parts[0] ?? '', 'color' => $parts[1] ?? '', 'stock' => $parts[2] ?? null];
            }
            $this->merge(['variants' => $rows]);
        }
    }
}
