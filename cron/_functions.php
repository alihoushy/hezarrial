<?php

/*
 * Helpers for the cron entry points. Loaded by _runner.php only after it has
 * confirmed a supported PHP version, so this file may use modern syntax.
 */

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Output\BufferedOutput;

define('CRON_BASE_PATH', dirname(__DIR__));

// Keeps cron.log timestamps consistent before Laravel boots; booting then
// applies APP_TIMEZONE as usual.
date_default_timezone_set('Asia/Tehran');

function cron_log(string $job, string $message): void
{
    $file = CRON_BASE_PATH.'/storage/logs/cron.log';

    // Keep the log bounded: one rotated generation of roughly 1 MB.
    if (is_file($file) && filesize($file) > 1024 * 1024) {
        @rename($file, $file.'.1');
    }

    $stamp = date('c');
    $lines = preg_split('/\R/', rtrim($message)) ?: [];
    $body = implode('', array_map(fn (string $line): string => "[{$stamp}] {$job}: {$line}\n", $lines));

    @file_put_contents($file, $body, FILE_APPEND | LOCK_EX);
}

function cron_fail(string $job, string $message): never
{
    cron_log($job, 'FAILED: '.$message);
    echo "[{$job}] {$message}\n";

    exit(1);
}

function cron_lock(string $job): void
{
    static $handles = [];

    if (isset($handles[$job])) {
        return;
    }

    $handle = @fopen(CRON_BASE_PATH."/storage/framework/cron-{$job}.lock", 'c');

    if ($handle === false) {
        cron_fail($job, 'Cannot open the lock file. Is storage/framework writable?');
    }

    if (! flock($handle, LOCK_EX | LOCK_NB)) {
        cron_log($job, 'Previous run is still in progress; skipped.');

        exit(0);
    }

    // Held until the process exits.
    $handles[$job] = $handle;
}

function cron_app(): Application
{
    static $app = null;

    if ($app !== null) {
        return $app;
    }

    if (! is_file(CRON_BASE_PATH.'/vendor/autoload.php')) {
        throw new RuntimeException('vendor/ is missing. Upload the package built by deploy/shared-hosting/build.sh.');
    }

    // Some hosts run cron through php-cgi; without this Laravel would treat
    // the process as a web request.
    $_SERVER['APP_RUNNING_IN_CONSOLE'] = $_ENV['APP_RUNNING_IN_CONSOLE'] = 'true';

    require_once CRON_BASE_PATH.'/vendor/autoload.php';

    $app = require CRON_BASE_PATH.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    return $app;
}

/**
 * Boot Laravel, run the task and return its exit code.
 *
 * @param  Closure(Kernel, BufferedOutput): int  $task
 */
function cron_run(string $job, Closure $task): int
{
    cron_lock($job);

    $started = microtime(true);
    $output = null;

    try {
        $app = cron_app();
        $output = new BufferedOutput;
        $code = (int) $task($app->make(Kernel::class), $output);
        $text = $output->fetch();
    } catch (Throwable $e) {
        $code = 1;
        $text = ($output?->fetch() ?? '').sprintf('%s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine());

        if (isset($app)) {
            try {
                $app->make(ExceptionHandler::class)->report($e);
            } catch (Throwable) {
                // The cron log above already has the error.
            }
        }
    }

    $summary = sprintf('exit=%d in %.1fs', $code, microtime(true) - $started);
    cron_log($job, trim($text) === '' ? $summary : rtrim($text)."\n".$summary);

    if ($code !== 0) {
        echo "[{$job}] {$summary}\n{$text}\n";
    }

    return $code;
}
