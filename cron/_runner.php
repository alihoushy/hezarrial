<?php

/*
 * Entry guard for the cron tasks in this directory (see docs/shared-hosting.md).
 *
 * The shared host has no SSH, so every periodic task is a standalone PHP file
 * the control panel's cron calls directly:
 *
 *     /path/to/php /home/USER/hezarrial/cron/<task>.php
 *
 * Each task runs under a non-blocking lock (an overlapping run exits quietly),
 * appends its output to storage/logs/cron.log, and prints to stdout only when
 * it fails, so the panel's cron e-mail arrives only when something is wrong.
 *
 * This file and the task files deliberately avoid PHP 8 syntax: a panel's
 * default `php` is often older than the site's, and the version check below
 * must get a chance to explain that instead of dying on a parse error.
 */

// These files live outside the document root; this only matters if the
// hosting layout is ever misconfigured and a browser reaches them.
if (isset($_SERVER['REQUEST_METHOD'])) {
    http_response_code(404);
    exit;
}

// phpspreadsheet caps PHP below 8.5 and Laravel 13 needs 8.3.
if (PHP_VERSION_ID < 80300 || PHP_VERSION_ID >= 80500) {
    $message = sprintf(
        'PHP %s (%s) is not supported; point the cron command at a PHP 8.3 or 8.4 binary.',
        PHP_VERSION,
        PHP_BINARY
    );
    @file_put_contents(dirname(__DIR__).'/storage/logs/cron.log', '['.date('c').'] bootstrap: FAILED: '.$message."\n", FILE_APPEND);
    echo $message."\n";
    exit(1);
}

require __DIR__.'/_functions.php';
