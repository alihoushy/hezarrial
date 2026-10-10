<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\Registration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        abort_unless(Registration::isOpen(), 404);

        if (! Registration::looksHuman($input)) {
            throw ValidationException::withMessages(['email' => __('ثبت‌نام انجام نشد. صفحه را دوباره باز کنید و دوباره تلاش کنید.')]);
        }

        // Addresses are stored in lower case, so the uniqueness check must compare them that way too.
        $input['email'] = mb_strtolower(trim((string) ($input['email'] ?? '')));

        Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        // The very first account is the owner of a fresh installation, which may have no
        // mailbox configured yet, so it does not wait for a verification mail.
        $isFirst = User::query()->doesntExist();

        $user = User::create([
            'name' => trim($input['name']),
            'email' => $input['email'],
            'password' => $input['password'],
            'settings' => [
                'currency_display' => 'both',
                'persian_digits' => app()->getLocale() === 'fa',
                'theme' => 'system',
                'locale' => app()->getLocale(),
            ],
        ]);

        if ($isFirst) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }
}
