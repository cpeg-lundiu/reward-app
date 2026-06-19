<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$settings = require __DIR__ . '/../config/settings.php';

// Sessions (native PHP) — used for auth + flash messages + CSRF.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($settings['session_name']);
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(['settings' => $settings]);
$containerBuilder->addDefinitions(require __DIR__ . '/../src/dependencies.php');
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = $container->get(Slim\App::class);

(require __DIR__ . '/../src/middleware.php')($app);
(require __DIR__ . '/../src/routes.php')($app);

$app->run();
