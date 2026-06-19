<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Csrf;
use App\Support\Flash;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Validates the CSRF token on every state-changing (POST) request. */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array(strtoupper($request->getMethod()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $body = (array) $request->getParsedBody();
            if (!Csrf::validate($body['_csrf'] ?? null)) {
                Flash::error('Your session expired. Please try again.');
                $referer = $request->getHeaderLine('Referer') ?: '/';
                return $this->responseFactory->createResponse(302)->withHeader('Location', $referer);
            }
        }

        return $handler->handle($request);
    }
}
