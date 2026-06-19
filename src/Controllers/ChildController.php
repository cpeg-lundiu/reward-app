<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\RewardClaimRepository;
use App\Repositories\RewardRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\MoneyService;
use App\Services\RewardService;
use App\Services\TaskService;
use App\Support\Auth;
use App\Support\Flash;
use App\Support\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

final class ChildController extends AbstractController
{
    public function __construct(
        PhpRenderer $view,
        UserRepository $users,
        private MoneyService $money,
        private TaskService $tasksService,
        private RewardService $rewardsService,
        private TransactionRepository $transactions,
        private RewardRepository $rewards,
        private RewardClaimRepository $claims
    ) {
        parent::__construct($view, $users);
    }

    private function currency(): string
    {
        $parent = $this->users->find((int) $this->currentUser()['parent_id']);
        return $parent['currency'] ?? 'USD';
    }

    private function fail(Response $response, ValidationException $e, string $to): Response
    {
        foreach ($e->errors() as $message) {
            Flash::error($message);
        }
        return $this->redirect($response, $to);
    }

    // --- Piggy bank --------------------------------------------------------

    public function piggy(Request $request, Response $response): Response
    {
        $child = $this->currentUser();
        $childId = (int) $child['id'];
        $pending = $this->transactions->pendingWithdrawCents($childId);

        return $this->render($response, 'child/piggy.php', [
            'title' => 'My Piggy Bank',
            'active_nav' => 'piggy',
            'currency' => $this->currency(),
            'balance_cents' => (int) $child['balance_cents'],
            'available_cents' => (int) $child['balance_cents'] - $pending,
            'pending_cents' => $pending,
            'stars' => (int) $child['stars'],
            'transactions' => $this->transactions->forChild($childId, 10),
        ]);
    }

    public function requestWithdraw(Request $request, Response $response): Response
    {
        $d = (array) $request->getParsedBody();
        try {
            $this->money->requestWithdraw(
                $this->currentUser(),
                (string) ($d['amount'] ?? ''),
                trim((string) ($d['note'] ?? '')) ?: null
            );
            Flash::success('Withdrawal request sent to your parent. 🐷');
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/child');
        }
        return $this->redirect($response, '/child');
    }

    // --- Tasks calendar ----------------------------------------------------

    public function tasks(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $year = (int) ($params['year'] ?? date('Y'));
        $month = (int) ($params['month'] ?? date('n'));
        if ($month < 1 || $month > 12) {
            $year = (int) date('Y');
            $month = (int) date('n');
        }

        $calendar = $this->tasksService->calendarMonth((int) $this->currentUser()['id'], $year, $month);

        return $this->render($response, 'child/tasks.php', [
            'title' => 'My Tasks',
            'active_nav' => 'tasks',
            'calendar' => $calendar,
            'today' => date('Y-m-d'),
        ]);
    }

    public function completeTask(Request $request, Response $response): Response
    {
        $d = (array) $request->getParsedBody();
        $year = (int) ($d['year'] ?? date('Y'));
        $month = (int) ($d['month'] ?? date('n'));
        $back = '/child/tasks?year=' . $year . '&month=' . $month;

        try {
            $this->tasksService->markComplete(
                (int) $this->currentUser()['id'],
                (int) ($d['task_id'] ?? 0),
                (string) ($d['due_date'] ?? '')
            );
            Flash::success('Nice work! Your parent will approve it soon. ⭐');
        } catch (ValidationException $e) {
            return $this->fail($response, $e, $back);
        }
        return $this->redirect($response, $back);
    }

    // --- Rewards -----------------------------------------------------------

    public function rewards(Request $request, Response $response): Response
    {
        $child = $this->currentUser();

        return $this->render($response, 'child/rewards.php', [
            'title' => 'Rewards',
            'active_nav' => 'rewards',
            'stars' => (int) $child['stars'],
            'rewards' => $this->rewards->availableForChild((int) $child['id'], (int) $child['parent_id']),
            'claims' => $this->claims->forChild((int) $child['id']),
        ]);
    }

    public function claimReward(Request $request, Response $response): Response
    {
        $d = (array) $request->getParsedBody();
        try {
            $this->rewardsService->claimReward($this->currentUser(), (int) ($d['reward_id'] ?? 0));
            Flash::success('Reward claimed! Your parent will hand it over soon. 🎁');
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/child/rewards');
        }
        return $this->redirect($response, '/child/rewards');
    }
}
