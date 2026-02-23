<?php

declare(strict_types=1);

/**
 * Front controller - all requests routed here.
 */

// Load environment (optional .env-style support via getenv)
if (file_exists(__DIR__ . '/.env.php')) {
    $env = require __DIR__ . '/.env.php';
    foreach ($env as $k => $v) {
        putenv("{$k}={$v}");
    }
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/Validator.php';

// CORS headers for API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-Requested-With');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Config\\' => __DIR__ . '/config/',
        'Utils\\' => __DIR__ . '/utils/',
        'Models\\' => __DIR__ . '/models/',
        'Controllers\\' => __DIR__ . '/controllers/',
        'Services\\' => __DIR__ . '/services/',
        'Middleware\\' => __DIR__ . '/middleware/',
        'Routes\\' => __DIR__ . '/routes/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relative = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

try {
    $url = $_GET['url'] ?? '';
    $url = rtrim($url, '/');
    if (strpos($url, 'api/') !== false) {
        $url = preg_replace('#^.*?(api/.*)$#', '$1', $url);
    }
    if ($url !== '' && strpos($url, 'api/') !== 0) {
        $url = 'api/' . ltrim($url, '/');
    }
    $url = $url === '' ? '/' : '/' . $url;

    $routes = require __DIR__ . '/routes/api.php';
    $router = new Routes\Router($routes);
    $router->dispatch($url, $_SERVER['REQUEST_METHOD']);
} catch (Throwable $e) {
    if (getenv('APP_DEBUG') === '1') {
        Utils\Response::serverError($e->getMessage());
    }
    Utils\Response::serverError('Internal server error.');
}
