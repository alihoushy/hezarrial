<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Backup;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Check;
use App\Models\Debt;
use App\Models\IncomingSms;
use App\Models\Loan;
use App\Models\Person;
use App\Models\RecurringTransaction;
use App\Models\Reminder;
use App\Models\SmsPattern;
use App\Models\Transaction;
use App\Policies\UserOwnedPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Per user, so one noisy phone cannot use up another user's allowance.
        RateLimiter::for('sms-ingest', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));

        foreach ([Account::class, Backup::class, Budget::class, Category::class, Check::class, Debt::class, IncomingSms::class, Loan::class, Person::class, RecurringTransaction::class, Reminder::class, SmsPattern::class, Transaction::class] as $model) {
            Gate::policy($model, UserOwnedPolicy::class);
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Laravel's built-in account mails are English; these follow the app's languages.
        VerifyEmail::toMailUsing(fn (object $notifiable, string $url) => (new MailMessage)
            ->subject(__('تأیید ایمیل :app', ['app' => config('app.name')]))
            ->greeting(__('سلام :name', ['name' => $notifiable->name]))
            ->line(__('برای فعال شدن حساب، ایمیل خود را تأیید کنید.'))
            ->action(__('تأیید ایمیل'), $url)
            ->line(__('اگر شما حساب نساخته‌اید، این پیام را نادیده بگیرید.')));

        ResetPassword::toMailUsing(fn (object $notifiable, string $token) => (new MailMessage)
            ->subject(__('بازیابی رمز عبور :app', ['app' => config('app.name')]))
            ->greeting(__('سلام :name', ['name' => $notifiable->name]))
            ->line(__('درخواست بازیابی رمز عبور حساب شما رسیده است.'))
            ->action(__('تعیین رمز عبور تازه'), url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false)))
            ->line(__('این لینک تا :count دقیقه معتبر است.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(__('اگر شما درخواست نکرده‌اید، کاری لازم نیست؛ رمز عبور شما تغییری نمی‌کند.')));

        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $request = $response->request;
            $status = $response->statusCode();

            // The SMS ingest API and other JSON clients keep Laravel's responses.
            if ($request->is('api/*') || ($request->expectsJson() && ! $request->header('X-Inertia'))) {
                return null;
            }

            if ($status === 419) {
                return back()->with('status', __('نشست شما منقضی شده بود. لطفاً دوباره تلاش کنید.'));
            }

            // Keep Laravel's debug page for server errors while developing.
            if ($status >= 500 && config('app.debug')) {
                return null;
            }

            if (in_array($status, [403, 404, 429, 500, 503], true)) {
                return $response->render('error', ['status' => $status])->withSharedData();
            }

            return null;
        });
    }
}
