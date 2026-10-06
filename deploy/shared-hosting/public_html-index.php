<?php

// index.php for the "public_html" layout (docs/shared-hosting.md): the contents
// of public/ sit in ~/public_html and the application in ~/hezarrial beside it.
// If the application folder has a different name, change $appPath.

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appPath = __DIR__.'/../hezarrial';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appPath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

// public/ was moved out of the application, so asset lookups such as the Vite
// manifest must resolve here.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
