<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Repositories\UserRepository;
use App\Support\Auth;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Forces a child who still has a parent-set preset password to choose their
 * own before using the rest of the app. Lets the set-password + logout
 * routes through so they can actually complete the change.
 */
final class PasswordChangeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UserRepository $users
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $id = Auth::id();
        if ($id !== null) {
            $user = $this->users->find($id);
            $path = $request->getUri()->getPath();
            $allowed = in_array($path, ['/set-password', '/logout'], true);

            if ($user && (int) $user['must_change_password'] === 1 && !$allowed) {
                return $this->responseFactory->createResponse(302)->withHeader('Location', '/set-password');
            }
        }

        return $handler->handle($request);
    }
}
