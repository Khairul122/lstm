<?php

declare(strict_types=1);

require __DIR__ . '/autoload.php';
require __DIR__ . '/../helpers/functions.php';

use Core\Router;
use Core\Session;

Session::start();

$router = new Router();
require __DIR__ . '/../routes/web.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

if (
    $basePath !== ''
    && $basePath !== '/'
    && strncasecmp($requestUri, $basePath, strlen($basePath)) === 0
) {
    $requestUri = substr($requestUri, strlen($basePath));
    $requestUri = $requestUri === '' ? '/' : $requestUri;
}

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $requestUri
);
