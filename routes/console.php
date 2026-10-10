<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled in-process (Schedule::call) instead of Schedule::command: command events start a
// shell process, which many shared hosts disable (proc_open/exec). Either way the cron entry is
// cron/schedule-run.php, once a minute (see docs/shared-hosting.md).
Schedule::call(fn () => Artisan::call('app:process-recurring-transactions'))->name('recurring-transactions')->dailyAt('03:00');
Schedule::call(fn () => Artisan::call('app:prune-sms-text'))->name('prune-sms-text')->dailyAt('03:15');

// There is no worker process on shared hosting: when mail and notifications are queued,
// the scheduler drains the queue once a minute.
Schedule::call(fn () => Artisan::call('queue:work', ['--stop-when-empty' => true, '--max-time' => 50, '--tries' => 3]))
    ->name('queue-work')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn () => config('queue.default') !== 'sync');
