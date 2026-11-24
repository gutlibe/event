<?php

declare(strict_types=1);

require dirname(__DIR__).'/app/bootstrap.php';

/** @var App\Router $router */
$router = require BASE_PATH.'/routes/web.php';

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'
);
