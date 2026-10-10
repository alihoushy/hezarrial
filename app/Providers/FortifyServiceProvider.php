<?php

namespace App\Providers;

use App\Actions\CreateDefaultCategories;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\PasswordResetLinkFailedResponse;
use App\Http\Responses\SignedOutResponse;
use App\Models\User;
use App\Support\Mobile;
use App\Support\Registration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LogoutResponse::class, SignedOutResponse::class);
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkFailedResponse::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Sign in with an email address or a mobile number, as before.
        Fortify::authenticateUsing(function (Request $request) {
            $login = trim((string) $request->input('login'));
            $column = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
            $user = $column === 'email'
                ? User::query()->where('email', mb_strtolower($login))->first()
                : User::query()->where('mobile', Mobile::normalize($login) ?? $login)->whereNotNull('mobile_verified_at')->first();

            // Hash a dummy when the user is unknown so the response time does not give them away.
            if (! $user) {
                Hash::check($request->input('password', ''), Hash::make('dummy-password'));

                return null;
            }

            return Hash::check($request->input('password', ''), $user->password) ? $user : null;
        });

        // Confirming a password must look at the password hash. Fortify's default searches the user
        // by the "username" field, which here is the virtual "login" (email or mobile) and not a column.
        Fortify::confirmPasswordsUsing(fn (User $user, ?string $password = null) => filled($password) && Hash::check($password, $user->password));

        Fortify::loginView(fn () => User::query()->doesntExist() ? redirect()->route('register') : Inertia::render('auth/login'));
        Fortify::registerView(function () {
            abort_unless(Registration::isOpen(), 404);

            return Inertia::render('auth/register', ['formToken' => Registration::token()]);
        });
        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('auth/forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', ['token' => $request->route('token'), 'email' => $request->query('email')]));
        Fortify::verifyEmailView(fn () => Inertia::render('auth/verify-email'));
        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));

        // A new account starts with the usual categories, in the language it signed up with.
        Event::listen(Registered::class, fn (Registered $event) => app(CreateDefaultCategories::class)->handle($event->user, app()->getLocale()));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by((string) $request->session()->get('login.id')));
    }
}
