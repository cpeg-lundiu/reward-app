<?php

declare(strict_types=1);

use Slim\App;

return function (App $app): void {
    $settings = $app->getContainer()->get('settings');

    // Parse form-encoded and JSON request bodies.
    $app->addBodyParsingMiddleware();

    // Routing must run before the error handler so route-not-found is handled.
    $app->addRoutingMiddleware();

    // Error handling — verbose only when APP_DEBUG is on.
    $app->addErrorMiddleware((bool) $settings['debug'], true, true);
};
