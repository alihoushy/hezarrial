<?php

/*
 * Runs Laravel's scheduler (php artisan schedule:run): the recurring-transaction
 * reminders, the SMS-text cleanup and, when QUEUE_CONNECTION is not "sync", the
 * queue worker, all from one cron entry.
 *
 * Suggested cron: every minute, "* * * * *". Nothing is printed unless a run fails.
 */

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\BufferedOutput;

require __DIR__.'/_runner.php';

exit(cron_run('schedule-run', function (Kernel $artisan, BufferedOutput $output) {
    return $artisan->call('schedule:run', [], $output);
}));
