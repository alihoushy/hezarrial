<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['login' => ['required', 'string'], 'password' => ['required', 'string']];
    }

    public function authenticate(): void
    {
        $credentials = filter_var($this->login, FILTER_VALIDATE_EMAIL)
            ? ['email' => $this->login, 'password' => $this->password]
            : ['mobile' => $this->login, 'password' => $this->password];

        if (! Auth::attempt($credentials, false)) {
            throw ValidationException::withMessages(['login' => __('اطلاعات ورود درست نیست.')]);
        }

        $this->session()->regenerate();
    }
}
