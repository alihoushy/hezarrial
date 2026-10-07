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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('sms-ingest', fn () => Limit::perMinute(30));

        foreach ([Account::class, Backup::class, Budget::class, Category::class, Check::class, Debt::class, IncomingSms::class, Loan::class, Person::class, RecurringTransaction::class, Reminder::class, SmsPattern::class, Transaction::class] as $model) {
            Gate::policy($model, UserOwnedPolicy::class);
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $request = $response->request;
            $status = $response->statusCode();

            // The SMS ingest API and other JSON clients keep Laravel's responses.
            if ($request->is('api/*') || ($request->expectsJson() && ! $request->header('X-Inertia'))) {
                return null;
            }

            if ($status === 419) {
                return back()->with('status', 'نشست شما منقضی شده بود. لطفاً دوباره تلاش کنید.');
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
