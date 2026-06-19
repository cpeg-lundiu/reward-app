<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Flash;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer;

/**
 * Shared rendering + redirect helpers. Injects the data every page/layout
 * needs (current user, flash messages, CSRF token) so controllers only pass
 * page-specific data.
 */
abstract class AbstractController
{
    private ?array $cachedUser = null;
    private bool $userLoaded = false;

    public function __construct(
        protected PhpRenderer $view,
        protected UserRepository $users
    ) {
    }

    protected function currentUser(): ?array
    {
        if (!$this->userLoaded) {
            $id = Auth::id();
            $this->cachedUser = $id !== null ? $this->users->find($id) : null;
            $this->userLoaded = true;
        }

        return $this->cachedUser;
    }

    protected function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        $shared = [
            'auth_user' => $this->currentUser(),
            'flash' => Flash::pull(),
            'csrf_token' => Csrf::token(),
            'active_nav' => $data['active_nav'] ?? null,
            'title' => $data['title'] ?? 'Piggy Rewards',
        ];

        return $this->view->render($response, $template, array_merge($shared, $data));
    }

    protected function redirect(ResponseInterface $response, string $to): ResponseInterface
    {
        return $response->withHeader('Location', $to)->withStatus(302);
    }
}
