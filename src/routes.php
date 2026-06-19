<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ChildController;
use App\Controllers\ParentController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\PasswordChangeMiddleware;
use App\Middleware\RoleMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = $app->getContainer();
    $responseFactory = $container->get(ResponseFactoryInterface::class);
    $parentOnly = new RoleMiddleware($responseFactory, 'parent');
    $childOnly = new RoleMiddleware($responseFactory, 'child');

    // CSRF protection on all state-changing requests.
    $app->add(CsrfMiddleware::class);

    // --- Public routes ---
    $app->get('/', [AuthController::class, 'home']);
    $app->get('/login', [AuthController::class, 'showLogin']);
    $app->post('/login', [AuthController::class, 'login']);
    $app->get('/register', [AuthController::class, 'showRegister']);
    $app->post('/register', [AuthController::class, 'register']);
    $app->post('/logout', [AuthController::class, 'logout']);

    // --- Authenticated routes ---
    $app->group('', function (RouteCollectorProxy $group) use ($parentOnly, $childOnly): void {
        $group->get('/set-password', [AuthController::class, 'showSetPassword']);
        $group->post('/set-password', [AuthController::class, 'setPassword']);

        // Parent area
        $group->group('/parent', function (RouteCollectorProxy $p): void {
            $p->get('', [ParentController::class, 'dashboard']);
            $p->get('/children/add', [ParentController::class, 'showAddChild']);
            $p->post('/children/add', [ParentController::class, 'addChild']);

            $p->get('/money', [ParentController::class, 'showMoney']);
            $p->post('/money/add', [ParentController::class, 'addBalance']);
            $p->post('/money/convert-stars', [ParentController::class, 'convertStars']);
            $p->get('/children/{id}/transactions', [ParentController::class, 'showChildTransactions']);
            $p->get('/children/{id}/transactions/data', [ParentController::class, 'childTransactionsData']);
            $p->post('/withdrawals/{id}/approve', [ParentController::class, 'approveWithdraw']);
            $p->post('/withdrawals/{id}/reject', [ParentController::class, 'rejectWithdraw']);

            $p->get('/settings', [ParentController::class, 'showSettings']);
            $p->post('/settings/currency', [ParentController::class, 'changeCurrency']);

            $p->get('/tasks', [ParentController::class, 'showTasks']);
            $p->post('/tasks/add', [ParentController::class, 'addTask']);
            $p->get('/tasks/approvals', [ParentController::class, 'showTaskApprovals']);
            $p->post('/tasks/{id}/approve', [ParentController::class, 'approveTask']);
            $p->post('/tasks/{id}/reject', [ParentController::class, 'rejectTask']);

            $p->get('/rewards', [ParentController::class, 'showRewards']);
            $p->post('/rewards/add', [ParentController::class, 'addReward']);
            $p->get('/rewards/claims', [ParentController::class, 'showRewardClaims']);
            $p->post('/rewards/claims/{id}/complete', [ParentController::class, 'completeClaim']);
            $p->post('/rewards/claims/{id}/reject', [ParentController::class, 'rejectClaim']);
        })->add($parentOnly);

        // Child area
        $group->group('/child', function (RouteCollectorProxy $c): void {
            $c->get('', [ChildController::class, 'piggy']);
            $c->post('/withdraw', [ChildController::class, 'requestWithdraw']);
            $c->get('/tasks', [ChildController::class, 'tasks']);
            $c->post('/tasks/complete', [ChildController::class, 'completeTask']);
            $c->get('/rewards', [ChildController::class, 'rewards']);
            $c->post('/rewards/claim', [ChildController::class, 'claimReward']);
        })->add($childOnly);
    })->add(PasswordChangeMiddleware::class)->add(AuthMiddleware::class);
};
