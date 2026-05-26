<?php

namespace App\Http\Requests;

use App\Enums\DebtType;
use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DebtRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'person_id' => ['required', Rule::exists(Person::class, 'id')->where('user_id', $this->user()->id)],
            'type' => ['required', Rule::enum(DebtType::class)],
            'original_amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
