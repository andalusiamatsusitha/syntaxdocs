<?php

namespace Syntax\Core\Http\Middleware;

use Syntax\Core\Http\Request;
use Syntax\Core\Http\Response;

class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            $response = new Response('', 204);
            return $this->attachCorsHeaders($response);
        }

        $response = $next($request);
        return $this->attachCorsHeaders($response);
    }

    protected function attachCorsHeaders(Response $response): Response
    {
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        $response->setHeader('Access-Control-Max-Age', '86400');
        return $response;
    }
}
