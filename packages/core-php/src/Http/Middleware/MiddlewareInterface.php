<?php

namespace Syntax\Core\Http\Middleware;

use Syntax\Core\Http\Request;
use Syntax\Core\Http\Response;

interface MiddlewareInterface
{
    /**
     * Process an incoming request and return a response.
     *
     * @param Request $request
     * @param callable $next fn(Request $request): Response
     * @return Response
     */
    public function handle(Request $request, callable $next): Response;
}
