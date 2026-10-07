<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'account_id' => ['required', Rule::exists(Account::class, 'id')->where('user_id', $user->id)],
            'destination_account_id' => ['nullable', Rule::exists(Account::class, 'id')->where('user_id', $user->id)],
            'category_id' => ['nullable', Rule::exists(Category::class, 'id')->where('user_id', $user->id)],
            'person_id' => ['nullable', Rule::exists(Person::class, 'id')->where('user_id', $user->id)],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['nullable', 'date_format:H:i'],
            'description' => ['nullable', 'string', 'max:1000'],
            'reference_number' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $type = $this->input('type');

                if ($type === TransactionType::TransferOut->value) {
                    if (! $this->filled('destination_account_id')) {
                        $validator->errors()->add('destination_account_id', __('انتخاب حساب مقصد برای انتقال الزامی است.'));
                    }

                    if ($this->input('account_id') && $this->input('account_id') === $this->input('destination_account_id')) {
                        $validator->errors()->add('destination_account_id', __('حساب مبدا و مقصد نباید یکی باشد.'));
                    }
                }

                if (in_array($type, [TransactionType::Income->value, TransactionType::Expense->value], true) && ! $this->filled('category_id')) {
                    $validator->errors()->add('category_id', __('انتخاب دسته‌بندی برای درآمد و هزینه الزامی است.'));
                }

                if ($type === TransactionType::Adjustment->value && ! $this->filled('description')) {
                    $validator->errors()->add('description', __('برای اصلاح مانده، ثبت دلیل الزامی است.'));
                }
            },
        ];
    }
}
