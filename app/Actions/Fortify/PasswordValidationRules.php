<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /** At least 10 characters with a letter and a digit (letters of any script count). */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::min(10)->letters()->numbers(), 'confirmed'];
    }
}
