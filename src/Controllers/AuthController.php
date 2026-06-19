<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\Auth;
use App\Support\Flash;
use App\Support\Money;
use App\Support\PasswordPolicy;
use App\Support\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

final class AuthController extends AbstractController
{
    public function __construct(PhpRenderer $view, UserRepository $users, private AuthService $auth)
    {
        parent::__construct($view, $users);
    }

    public function home(Request $request, Response $response): Response
    {
        if (Auth::isParent()) {
            return $this->redirect($response, '/parent');
        }
        if (Auth::isChild()) {
            return $this->redirect($response, '/child');
        }

        return $this->redirect($response, '/login');
    }

    public function showLogin(Request $request, Response $response): Response
    {
        if (Auth::check()) {
            return $this->home($request, $response);
        }

        return $this->render($response, 'auth/login.php', ['title' => 'Log in']);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $role = ($data['role'] ?? 'parent') === 'child' ? 'child' : 'parent';
        $identifier = trim((string) ($data['identifier'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $user = $role === 'parent'
            ? $this->auth->attemptParentLogin($identifier, $password)
            : $this->auth->attemptChildLogin($identifier, $password);

        if (!$user) {
            Flash::error('Those login details did not match. Please try again.');
            return $this->redirect($response, '/login');
        }

        Auth::login((int) $user['id'], $user['role']);

        if ($user['role'] === 'child' && (int) $user['must_change_password'] === 1) {
            return $this->redirect($response, '/set-password');
        }

        return $this->redirect($response, $user['role'] === 'parent' ? '/parent' : '/child');
    }

    public function showRegister(Request $request, Response $response): Response
    {
        if (Auth::check()) {
            return $this->home($request, $response);
        }

        return $this->render($response, 'auth/register.php', [
            'title' => 'Create a parent account',
            'currencies' => Money::currencies(),
            'password_rules' => PasswordPolicy::rulesText(),
        ]);
    }

    public function register(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        try {
            $id = $this->auth->registerParent(
                (string) ($data['email'] ?? ''),
                (string) ($data['display_name'] ?? ''),
                (string) ($data['password'] ?? ''),
                (string) ($data['currency'] ?? '')
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $message) {
                Flash::error($message);
            }
            return $this->redirect($response, '/register');
        }

        Auth::login($id, 'parent');
        Flash::success('Welcome! Your family account is ready. 🎉');

        return $this->redirect($response, '/parent');
    }

    public function showSetPassword(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/set-password.php', [
            'title' => 'Choose your password',
            'password_rules' => PasswordPolicy::rulesText(),
        ]);
    }

    public function setPassword(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $childId = Auth::id();

        try {
            $this->auth->setChildPassword(
                (int) $childId,
                (string) ($data['password'] ?? ''),
                (string) ($data['confirm'] ?? '')
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $message) {
                Flash::error($message);
            }
            return $this->redirect($response, '/set-password');
        }

        Flash::success('Your password is set. Welcome! 🐷');

        return $this->redirect($response, '/child');
    }

    public function logout(Request $request, Response $response): Response
    {
        Auth::logout();
        Flash::success('You have been logged out.');

        return $this->redirect($response, '/login');
    }
}
