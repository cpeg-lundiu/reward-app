<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Auth;
use App\Support\Flash;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Restricts a route group to a single role. A logged-in user of the wrong
 * role is bounced to their own home rather than shown someone else's pages.
 */
final class RoleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private string $role
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (Auth::role() !== $this->role) {
            Flash::error('You do not have access to that page.');
            $home = Auth::isParent() ? '/parent' : (Auth::isChild() ? '/child' : '/login');
            return $this->responseFactory->createResponse(302)->withHeader('Location', $home);
        }

        return $handler->handle($request);
    }
}
