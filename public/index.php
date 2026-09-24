<?php

// When Apache rewrites /<mount>/<route> into /<mount>/public/index.php it keeps
// REQUEST_URI = "/<mount>/<route>" but sets SCRIPT_NAME = "/<mount>/public/index.php".
// That mismatch breaks Symfony's base-path detection and 404s every route.
// Recover the mount prefix from REDIRECT_URL and align SCRIPT_NAME/PHP_SELF so
// Laravel computes the path (and asset/route URLs) relative to the real mount.
$redirectUrl = $_SERVER['REDIRECT_URL'] ?? null;
if ($redirectUrl
    && str_ends_with(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? ''), '/public/index.php')) {
    $mount = preg_replace('#/public(?:/.*)?$#', '', $redirectUrl) ?? '';
    $requestPath = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?? '';

    if (! str_starts_with($requestPath, $mount.'/public')) {
        $_SERVER['SCRIPT_NAME'] = $mount.'/index.php';
        $_SERVER['PHP_SELF'] = $mount.'/index.php';
    }
}

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
