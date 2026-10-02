<?php

namespace Syntax\Core\Http\Middleware;

use Syntax\Core\Http\Request;
use Syntax\Core\Http\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Safe HTTP methods do not require CSRF token
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // If request is JSON API with Bearer token, CSRF can be bypassed
        if ($request->isJson() && !empty($request->header('authorization'))) {
            return $next($request);
        }

        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        $submittedToken = $request->input('_csrf_token') ?? $request->header('x-csrf-token') ?? '';

        if (empty($sessionToken) || empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
            if ($request->isJson()) {
                return Response::json([
                    'success' => false,
                    'code' => 419,
                    'message' => 'CSRF token mismatch or expired.',
                    'errors' => ['_csrf_token' => 'Invalid CSRF token']
                ], 419);
            }

            return Response::html('<h1>419 Page Expired</h1><p>CSRF token mismatch. Silakan muat ulang halaman.</p>', 419);
        }

        return $next($request);
    }
}
