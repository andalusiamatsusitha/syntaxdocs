<?php

namespace Syntax\Core\Http\Middleware;

use Syntax\Core\Http\Request;
use Syntax\Core\Http\Response;

class Pipeline
{
    protected array $pipes = [];
    protected Request $passable;

    public function send(Request $passable): static
    {
        $this->passable = $passable;
        return $this;
    }

    public function through(array $pipes): static
    {
        $this->pipes = $pipes;
        return $this;
    }

    public function then(callable $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            function ($next, $pipe) {
                return function (Request $request) use ($next, $pipe): Response {
                    if (is_string($pipe)) {
                        $pipe = new $pipe();
                    }

                    if ($pipe instanceof MiddlewareInterface) {
                        return $pipe->handle($request, $next);
                    }

                    if (is_callable($pipe)) {
                        return $pipe($request, $next);
                    }

                    return $next($request);
                };
            },
            function (Request $request) use ($destination): Response {
                $response = $destination($request);
                if (!$response instanceof Response) {
                    $response = Response::html((string) $response);
                }
                return $response;
            }
        );

        return $pipeline($this->passable);
    }
}
