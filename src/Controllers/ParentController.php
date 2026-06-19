<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\RewardClaimRepository;
use App\Repositories\RewardRepository;
use App\Repositories\TaskCompletionRepository;
use App\Repositories\TaskRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\AccountService;
use App\Services\MoneyService;
use App\Services\RewardService;
use App\Services\TaskService;
use App\Support\Auth;
use App\Support\Flash;
use App\Support\Money;
use App\Support\PasswordPolicy;
use App\Support\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

final class ParentController extends AbstractController
{
    private const TX_PAGE_SIZE = 10;

    public function __construct(
        PhpRenderer $view,
        UserRepository $users,
        private AccountService $accounts,
        private MoneyService $money,
        private TaskService $tasksService,
        private RewardService $rewardsService,
        private TransactionRepository $transactions,
        private TaskRepository $tasks,
        private TaskCompletionRepository $completions,
        private RewardRepository $rewards,
        private RewardClaimRepository $claims
    ) {
        parent::__construct($view, $users);
    }

    private function parentId(): int
    {
        return (int) Auth::id();
    }

    private function currency(): string
    {
        return $this->currentUser()['currency'] ?? 'USD';
    }

    /** Flash a service's validation errors and bounce back. */
    private function fail(Response $response, ValidationException $e, string $to): Response
    {
        foreach ($e->errors() as $message) {
            Flash::error($message);
        }
        return $this->redirect($response, $to);
    }

    // --- Dashboard ---------------------------------------------------------

    public function dashboard(Request $request, Response $response): Response
    {
        $data = $this->accounts->dashboard($this->parentId());

        return $this->render($response, 'parent/dashboard.php', [
            'title' => 'My Family',
            'active_nav' => 'home',
            'children' => $data['children'],
            'pending' => $data['pending'],
            'currency' => $this->currency(),
        ]);
    }

    public function showAddChild(Request $request, Response $response): Response
    {
        return $this->render($response, 'parent/child-add.php', [
            'title' => 'Add a child',
            'active_nav' => 'home',
            'password_rules' => PasswordPolicy::rulesText(),
        ]);
    }

    public function addChild(Request $request, Response $response): Response
    {
        $d = (array) $request->getParsedBody();
        try {
            $this->accounts->addChild(
                $this->parentId(),
                (string) ($d['display_name'] ?? ''),
                (string) ($d['username'] ?? ''),
                (string) ($d['password'] ?? ''),
                (string) ($d['avatar_emoji'] ?? '')
            );
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/children/add');
        }

        Flash::success('Child account created! 🎉');
        return $this->redirect($response, '/parent');
    }

    // --- Money -------------------------------------------------------------

    public function showMoney(Request $request, Response $response): Response
    {
        return $this->render($response, 'parent/money.php', [
            'title' => 'Money',
            'active_nav' => 'money',
            'children' => $this->users->childrenOf($this->parentId()),
            'withdrawals' => $this->transactions->pendingWithdrawalsForParent($this->parentId()),
            'currency' => $this->currency(),
        ]);
    }

    public function addBalance(Request $request, Response $response): Response
    {
        $d = (array) $request->getParsedBody();
        try {
            $this->money->addBalance(
                $this->parentId(),
                (int) ($d['child_id'] ?? 0),
                (string) ($d['amount'] ?? ''),
                trim((string) ($d['note'] ?? '')) ?: null
            );
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/money');
        }

        Flash::success('Balance added. 💰');
        return $this->redirect($response, '/parent/money');
    }

    public function convertStars(Request $request, Response $response): Response
    {
        $d = (array) $request->getParsedBody();
        try {
            $this->money->convertStarsToBalance(
                $this->parentId(),
                (int) ($d['child_id'] ?? 0),
                (int) ($d['stars'] ?? 0),
                (string) ($d['amount'] ?? '')
            );
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/money');
        }

        Flash::success('Stars converted to balance. ⭐➡️💰');
        return $this->redirect($response, '/parent/money');
    }

    public function approveWithdraw(Request $request, Response $response, array $args): Response
    {
        try {
            $this->money->approveWithdraw($this->parentId(), (int) $args['id']);
            Flash::success('Withdrawal approved.');
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/money');
        }
        return $this->redirect($response, '/parent/money');
    }

    public function rejectWithdraw(Request $request, Response $response, array $args): Response
    {
        try {
            $this->money->rejectWithdraw($this->parentId(), (int) $args['id']);
            Flash::success('Withdrawal rejected.');
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/money');
        }
        return $this->redirect($response, '/parent/money');
    }

    // --- Per-child transaction history ------------------------------------

    public function showChildTransactions(Request $request, Response $response, array $args): Response
    {
        $child = $this->users->childOfParent($this->parentId(), (int) $args['id']);
        if (!$child) {
            Flash::error('That child was not found.');
            return $this->redirect($response, '/parent');
        }

        $items = $this->transactions->forChildPaged((int) $child['id'], self::TX_PAGE_SIZE, 0);

        return $this->render($response, 'parent/child-transactions.php', [
            'title' => $child['display_name'] . "'s history",
            'active_nav' => 'money',
            'child' => $child,
            'currency' => $this->currency(),
            'items' => $items,
            'page_size' => self::TX_PAGE_SIZE,
            'has_more' => count($items) === self::TX_PAGE_SIZE,
        ]);
    }

    /** JSON endpoint that feeds the lazy-loading transaction list. */
    public function childTransactionsData(Request $request, Response $response, array $args): Response
    {
        $child = $this->users->childOfParent($this->parentId(), (int) $args['id']);
        if (!$child) {
            $response->getBody()->write(json_encode(['error' => 'not_found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $offset = max(0, (int) ($request->getQueryParams()['offset'] ?? 0));
        $items = $this->transactions->forChildPaged((int) $child['id'], self::TX_PAGE_SIZE, $offset);

        $html = $this->view->fetch('parent/_transaction-rows.php', [
            'items' => $items,
            'currency' => $this->currency(),
        ]);

        $response->getBody()->write((string) json_encode([
            'html' => $html,
            'count' => count($items),
            'hasMore' => count($items) === self::TX_PAGE_SIZE,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    // --- Settings / currency ----------------------------------------------

    public function showSettings(Request $request, Response $response): Response
    {
        return $this->render($response, 'parent/settings.php', [
            'title' => 'Settings',
            'active_nav' => 'settings',
            'currencies' => Money::currencies(),
            'currency' => $this->currency(),
        ]);
    }

    public function changeCurrency(Request $request, Response $response): Response
    {
        $d = (array) $request->getParsedBody();
        try {
            $this->money->changeCurrency(
                $this->parentId(),
                (string) ($d['currency'] ?? ''),
                (string) ($d['rate'] ?? '')
            );
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/settings');
        }

        Flash::success('Currency updated and balances converted. 💱');
        return $this->redirect($response, '/parent/settings');
    }

    // --- Tasks -------------------------------------------------------------

    public function showTasks(Request $request, Response $response): Response
    {
        return $this->render($response, 'parent/tasks.php', [
            'title' => 'Tasks',
            'active_nav' => 'tasks',
            'children' => $this->users->childrenOf($this->parentId()),
            'tasks' => $this->tasks->forParent($this->parentId()),
            'today' => date('Y-m-d'),
        ]);
    }

    public function addTask(Request $request, Response $response): Response
    {
        try {
            $this->tasksService->createTask($this->parentId(), (array) $request->getParsedBody());
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/tasks');
        }

        Flash::success('Task added. ⭐');
        return $this->redirect($response, '/parent/tasks');
    }

    public function showTaskApprovals(Request $request, Response $response): Response
    {
        return $this->render($response, 'parent/task-approvals.php', [
            'title' => 'Task approvals',
            'active_nav' => 'tasks',
            'submissions' => $this->completions->pendingForParent($this->parentId()),
        ]);
    }

    public function approveTask(Request $request, Response $response, array $args): Response
    {
        try {
            $this->tasksService->approveCompletion($this->parentId(), (int) $args['id']);
            Flash::success('Task approved and stars awarded! ⭐');
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/tasks/approvals');
        }
        return $this->redirect($response, '/parent/tasks/approvals');
    }

    public function rejectTask(Request $request, Response $response, array $args): Response
    {
        try {
            $this->tasksService->rejectCompletion($this->parentId(), (int) $args['id']);
            Flash::success('Task submission rejected.');
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/tasks/approvals');
        }
        return $this->redirect($response, '/parent/tasks/approvals');
    }

    // --- Rewards -----------------------------------------------------------

    public function showRewards(Request $request, Response $response): Response
    {
        return $this->render($response, 'parent/rewards.php', [
            'title' => 'Rewards',
            'active_nav' => 'rewards',
            'children' => $this->users->childrenOf($this->parentId()),
            'rewards' => $this->rewards->forParent($this->parentId()),
        ]);
    }

    public function addReward(Request $request, Response $response): Response
    {
        try {
            $this->rewardsService->createReward($this->parentId(), (array) $request->getParsedBody());
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/rewards');
        }

        Flash::success('Reward added. 🎁');
        return $this->redirect($response, '/parent/rewards');
    }

    public function showRewardClaims(Request $request, Response $response): Response
    {
        return $this->render($response, 'parent/reward-claims.php', [
            'title' => 'Reward requests',
            'active_nav' => 'rewards',
            'claims' => $this->claims->pendingForParent($this->parentId()),
        ]);
    }

    public function completeClaim(Request $request, Response $response, array $args): Response
    {
        try {
            $this->rewardsService->completeClaim($this->parentId(), (int) $args['id']);
            Flash::success('Reward marked complete. 🎉');
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/rewards/claims');
        }
        return $this->redirect($response, '/parent/rewards/claims');
    }

    public function rejectClaim(Request $request, Response $response, array $args): Response
    {
        try {
            $this->rewardsService->rejectClaim($this->parentId(), (int) $args['id']);
            Flash::success('Reward request rejected and stars refunded.');
        } catch (ValidationException $e) {
            return $this->fail($response, $e, '/parent/rewards/claims');
        }
        return $this->redirect($response, '/parent/rewards/claims');
    }
}
