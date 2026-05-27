<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Backup;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Check;
use App\Models\Debt;
use App\Models\Loan;
use App\Models\Person;
use App\Models\RecurringTransaction;
use App\Models\Reminder;
use App\Models\SmsPattern;
use App\Models\Transaction;
use App\Policies\UserOwnedPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        foreach ([Account::class, Backup::class, Budget::class, Category::class, Check::class, Debt::class, Loan::class, Person::class, RecurringTransaction::class, Reminder::class, SmsPattern::class, Transaction::class] as $model) {
            Gate::policy($model, UserOwnedPolicy::class);
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
