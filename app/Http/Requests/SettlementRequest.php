<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettlementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'account_id' => ['required', Rule::exists(Account::class, 'id')->where('user_id', $this->user()->id)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
