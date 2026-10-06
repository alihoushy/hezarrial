<?php

/*
 * Turns due recurring transactions into reminders (app:process-recurring-transactions).
 *
 * Suggested cron: hourly, "0 * * * *". The command is idempotent and advances
 * each item by one period per run, so frequent runs also catch up quickly
 * after the host's cron has been down for a while.
 */

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\BufferedOutput;

require __DIR__.'/_runner.php';

exit(cron_run('process-recurring-transactions', function (Kernel $artisan, BufferedOutput $output) {
    return $artisan->call('app:process-recurring-transactions', [], $output);
}));
