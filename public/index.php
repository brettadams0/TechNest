<?php

declare(strict_types=1);

/*
 * Front controller: every request that is not a static file arrives here.
 * Apache reaches it through public/.htaccess, PHP's built-in server through
 * `composer serve`, which passes this file as the router script.
 */

use TechNest\Application;
use TechNest\Config;
use TechNest\Database\Database;
use TechNest\Http\Request;
use TechNest\Security\NativeSession;
use TechNest\Support\SystemClock;

if (PHP_SAPI === 'cli-server') {
    $path = parse_url(is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
    if (is_string($path) && $path !== '/' && is_file(__DIR__ . $path)) {
        return false;
    }
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

ini_set('display_errors', '0');

$config = Config::fromEnvironment($root);
$request = Request::fromGlobals();

$unavailable = static function (string $message): never {
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><meta charset="utf-8"><title>TechNest is unavailable</title>',
    '<h1>TechNest is not ready</h1><p>', htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), '</p>';
    exit;
};

try {
    if ($config->dbDriver() === 'sqlite' && !is_file($config->get('DB_PATH', $root . '/var/technest.sqlite'))) {
        $unavailable('The database has not been created yet. Run `composer setup`, then reload.');
    }
    $db = Database::connect($config, $root);
} catch (Throwable $e) {
    error_log('[technest] database: ' . $e->getMessage());
    $unavailable($config->debug() ? $e->getMessage() : 'The store could not reach its database.');
}

$app = new Application($root, $config, $db, new NativeSession($request->isSecure()), new SystemClock());
$app->handle($request)->send();
