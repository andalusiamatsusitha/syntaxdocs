<?php

namespace Syntax\Core\Auth;

use Syntax\Core\Http\Request;
use Syntax\Core\Http\Response;
use Syntax\Core\Http\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    protected string $loginUrl = '/login';

    public function __construct(string $loginUrl = '/login')
    {
        $this->loginUrl = $loginUrl;
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!AuthManager::check()) {
            if ($request->isJson()) {
                return Response::json([
                    'success' => false,
                    'code' => 401,
                    'message' => 'Unauthenticated. Silakan login terlebih dahulu.',
                    'errors' => null
                ], 401);
            }

            return Response::redirect($this->loginUrl);
        }

        return $next($request);
    }
}
