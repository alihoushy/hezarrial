<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'parent_id' => ['nullable', Rule::exists(Category::class, 'id')->where('user_id', $this->user()->id)],
            'icon' => ['nullable', 'string', 'max:64'],
            'color' => ['nullable', 'string', 'max:32'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
