<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SetupUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return User::query()->doesntExist();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190', 'unique:users,email'],
            'mobile' => ['nullable', 'string', 'max:30', 'unique:users,mobile'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام را وارد کنید.',
            'password.min' => 'رمز عبور باید حداقل ۱۰ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور هم‌خوان نیست.',
        ];
    }
}
