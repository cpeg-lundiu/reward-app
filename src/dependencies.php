<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ChildController;
use App\Controllers\ParentController;
use App\Repositories\CurrencyChangeRepository;
use App\Repositories\RewardClaimRepository;
use App\Repositories\RewardRepository;
use App\Repositories\TaskCompletionRepository;
use App\Repositories\TaskRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\MoneyService;
use App\Services\RewardService;
use App\Services\TaskService;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;

use function DI\autowire;
use function DI\get;

return [
    Slim\App::class => function (ContainerInterface $c): App {
        return AppFactory::create();
    },

    // Response factory for middleware that issue redirects.
    Psr\Http\Message\ResponseFactoryInterface::class => function (): Psr\Http\Message\ResponseFactoryInterface {
        return new Slim\Psr7\Factory\ResponseFactory();
    },

    // --- Database ---
    PDO::class => function (ContainerInterface $c): PDO {
        $db = $c->get('settings')['db'];
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        return new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    },

    // --- View renderer (pure PHP templates with a shared layout) ---
    PhpRenderer::class => function (ContainerInterface $c): PhpRenderer {
        $renderer = new PhpRenderer($c->get('settings')['templates']);
        $renderer->setLayout('layout.php');

        return $renderer;
    },

    // --- Repositories (autowired with PDO) ---
    UserRepository::class => autowire(),
    TransactionRepository::class => autowire(),
    TaskRepository::class => autowire(),
    TaskCompletionRepository::class => autowire(),
    RewardRepository::class => autowire(),
    RewardClaimRepository::class => autowire(),
    CurrencyChangeRepository::class => autowire(),

    // --- Services ---
    AuthService::class => autowire(),
    AccountService::class => autowire(),
    MoneyService::class => autowire(),
    TaskService::class => autowire(),
    RewardService::class => autowire(),

    // --- Controllers ---
    AuthController::class => autowire(),
    ParentController::class => autowire(),
    ChildController::class => autowire(),
];
