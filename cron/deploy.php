<?php

/*
 * Post-upload tasks that would normally be run over SSH: preflight checks,
 * pending migrations and Laravel's config/route/view/event caches.
 *
 * Suggested cron: every 5 minutes. It is a no-op until a new
 * package is uploaded (the RELEASE file changes) or .env is edited. A change
 * is acted on one run after it is first seen, so a file-manager extraction
 * that is still in progress is never deployed half-way. A failed deploy is
 * not retried until something changes again, to avoid an e-mail every run.
 *
 *     --force   deploy now, even if nothing changed or the last attempt failed
 *     --seed    also run the database seeder (default categories for the
 *               first user, after setup). Use as a one-off cron with --force.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\BufferedOutput;

require __DIR__.'/_runner.php';

const DEPLOY_SETTLE_SECONDS = 120;

$args = $_SERVER['argv'] ?? [];
$force = in_array('--force', $args, true);
$seed = in_array('--seed', $args, true);

cron_lock('deploy');

$envFile = CRON_BASE_PATH.'/.env';
$releaseFile = CRON_BASE_PATH.'/RELEASE';
$stateFile = CRON_BASE_PATH.'/storage/framework/deploy-state.json';

if (! is_file($envFile)) {
    cron_fail('deploy', '.env is missing. Copy .env.example to .env and fill it in (docs/shared-hosting.md).');
}

$release = is_file($releaseFile) ? trim((string) file_get_contents($releaseFile)) : 'unknown';
$fingerprint = sha1($release."\n".sha1_file($envFile));
$state = is_file($stateFile) ? (json_decode((string) file_get_contents($stateFile), true) ?: []) : [];

$saveState = function (string $status) use ($stateFile, $fingerprint, $release) {
    file_put_contents($stateFile, json_encode([
        'fingerprint' => $fingerprint,
        'release' => $release,
        'status' => $status,
        'at' => time(),
    ], JSON_PRETTY_PRINT));
};

if (! $force) {
    $sameChange = ($state['fingerprint'] ?? null) === $fingerprint;

    if ($sameChange && in_array($state['status'] ?? null, ['ok', 'failed'], true)) {
        exit(0);
    }

    if (! $sameChange) {
        $saveState('pending');
        cron_log('deploy', "Change detected (release {$release}); deploying on a later run.");

        exit(0);
    }

    if (time() - (int) ($state['at'] ?? 0) < DEPLOY_SETTLE_SECONDS) {
        exit(0);
    }
}

// Drop a stale config cache before booting, so this run reads the current .env.
@unlink(CRON_BASE_PATH.'/bootstrap/cache/config.php');

foreach (['bootstrap/cache', 'storage/app/private', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $dir) {
    if (! is_dir(CRON_BASE_PATH.'/'.$dir)) {
        @mkdir(CRON_BASE_PATH.'/'.$dir, 0755, true);
    }
}

$code = cron_run('deploy', function (Kernel $artisan, BufferedOutput $output) use ($release, $seed) {
    $output->writeln("Deploying release {$release} with PHP ".PHP_VERSION.' ('.PHP_BINARY.')');

    $problems = [];

    $required = ['ctype', 'dom', 'fileinfo', 'filter', 'gd', 'iconv', 'json', 'libxml', 'mbstring', 'openssl', 'pdo_mysql', 'session', 'simplexml', 'tokenizer', 'xml', 'xmlreader', 'xmlwriter', 'zip', 'zlib'];
    $missing = array_values(array_filter($required, function (string $extension) {
        return ! extension_loaded($extension);
    }));

    if ($missing !== []) {
        $problems[] = 'Missing PHP extensions: '.implode(', ', $missing).'. Enable them in the panel\'s PHP settings.';
    }

    foreach (['bootstrap/cache', 'storage/app/private', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $dir) {
        if (! is_writable(CRON_BASE_PATH.'/'.$dir)) {
            $problems[] = "{$dir} is not writable (set the folder to 755).";
        }
    }

    if (! config('app.key')) {
        $problems[] = 'APP_KEY is empty. Generate one locally with `php artisan key:generate --show` and put it in .env.';
    }

    if (config('app.debug')) {
        $problems[] = 'APP_DEBUG must be false in production.';
    }

    if (config('hashing.driver') === 'argon2id' && ! defined('PASSWORD_ARGON2ID')) {
        $problems[] = 'This PHP build has no Argon2id support. Ask the host to enable it, or set HASH_DRIVER=bcrypt before the first user is created.';
    }

    if (! is_file(public_path('build/manifest.json')) && ! is_file(dirname(CRON_BASE_PATH).'/public_html/build/manifest.json')) {
        $problems[] = 'Compiled assets (build/manifest.json) are missing. Upload the package built by deploy/shared-hosting/build.sh.';
    }

    try {
        DB::connection()->getPdo();
    } catch (Throwable $e) {
        $problems[] = 'Cannot connect to the database: '.$e->getMessage();
    }

    if ($problems !== []) {
        foreach ($problems as $problem) {
            $output->writeln('PREFLIGHT: '.$problem);
        }

        return 1;
    }

    // Migrate first: optimize:clear also clears the application cache, which
    // fails on a first deploy if CACHE_STORE=database and the table is not there yet.
    $steps = [
        ['migrate', ['--force' => true]],
        ['optimize:clear', []],
    ];

    if ($seed) {
        $steps[] = ['db:seed', ['--force' => true]];
    }

    $steps[] = ['optimize', []];

    foreach ($steps as $step) {
        $output->writeln('> php artisan '.$step[0]);
        $status = $artisan->call($step[0], $step[1], $output);

        if ($status !== 0) {
            return $status;
        }
    }

    return 0;
});

$saveState($code === 0 ? 'ok' : 'failed');

exit($code);
