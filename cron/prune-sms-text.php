<?php

/*
 * Clears the raw text of bank messages that were already handled (app:prune-sms-text).
 *
 * Suggested cron: once a day, "15 3 * * *".
 */

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\BufferedOutput;

require __DIR__.'/_runner.php';

exit(cron_run('prune-sms-text', function (Kernel $artisan, BufferedOutput $output) {
    return $artisan->call('app:prune-sms-text', [], $output);
}));
