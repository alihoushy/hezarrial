<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SmsIngestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'متن پیام الزامی است.',
            'message.min' => 'متن پیام بسیار کوتاه است.',
            'message.max' => 'متن پیام بسیار طولانی است.',
        ];
    }
}
