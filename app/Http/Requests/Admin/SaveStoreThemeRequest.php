<?php

namespace App\Http\Requests\Admin;

use App\Services\Themes\ThemeCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SaveStoreThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('admin')->user()?->type === 'superadmin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('store_themes', 'slug')->ignore($this->route('id')),
            ],
            'niche' => ['required', Rule::in(array_keys(ThemeCatalog::niches()))],
            'description' => ['nullable', 'string', 'max:2000'],
            'primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'surface' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_heading' => ['required', Rule::in(ThemeCatalog::fonts())],
            'font_body' => ['required', Rule::in(ThemeCatalog::fonts())],
            'header_style' => ['required', Rule::in(array_keys(ThemeCatalog::headerStyles()))],
            'product_card' => ['required', Rule::in(array_keys(ThemeCatalog::productCards()))],
            'announcement' => ['nullable', 'string', 'max:160'],
            'custom_css' => ['nullable', 'string', 'max:20000'],
            'activate' => ['nullable', 'boolean'],
            'preview_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Theme name is required.',
            'slug.required' => 'Theme slug is required.',
            'slug.regex' => 'Use lowercase letters, numbers, and hyphens for the slug.',
            'slug.unique' => 'That theme slug is already in use.',
            'niche.required' => 'Choose the kind of storefront this theme is for.',
        ];
    }
}
