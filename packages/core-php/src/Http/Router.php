<?php

namespace Syntax\Core\Http;

use Syntax\Core\Http\Middleware\Pipeline;

class Route
{
    public string $method;
    public string $uri;
    public mixed $action;
    public array $middlewares = [];
    public array $params = [];

    public function __construct(string $method, string $uri, mixed $action)
    {
        $this->method = strtoupper($method);
        $this->uri = '/' . ltrim($uri, '/');
        $this->action = $action;
    }

    public function middleware(array|string $middleware): static
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }
}

class Router
{
    protected array $routes = [];
    protected array $groupStack = [];

    public function get(string $uri, mixed $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, mixed $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, mixed $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function delete(string $uri, mixed $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    protected function addRoute(string $method, string $uri, mixed $action): Route
    {
        $prefix = '';
        $groupMiddlewares = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $m = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $groupMiddlewares = array_merge($groupMiddlewares, $m);
            }
        }

        $fullUri = rtrim($prefix . '/' . ltrim($uri, '/'), '/');
        if (empty($fullUri)) {
            $fullUri = '/';
        }

        $route = new Route($method, $fullUri, $action);
        if (!empty($groupMiddlewares)) {
            $route->middleware($groupMiddlewares);
        }

        $this->routes[] = $route;
        return $route;
    }

    /**
     * Dispatch the request against registered routes.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = '/' . ltrim($request->getUri(), '/');
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {
            if ($route->method !== $method) {
                continue;
            }

            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route->uri);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                // Run route through its middlewares
                $pipeline = new Pipeline();
                return $pipeline
                    ->send($request)
                    ->through($route->middlewares)
                    ->then(function (Request $req) use ($route, $params): Response {
                        return $this->executeAction($route->action, $req, $params);
                    });
            }
        }

        if ($request->isJson()) {
            return Response::json([
                'success' => false,
                'code' => 404,
                'message' => 'Endpoint not found',
                'errors' => null
            ], 404);
        }

        return Response::html('<h1>404 Not Found</h1><p>Halaman yang Anda tuju tidak ditemukan.</p>', 404);
    }

    protected function executeAction(mixed $action, Request $request, array $params): Response
    {
        if (is_callable($action)) {
            $result = call_user_func($action, $request, ...array_values($params));
        } elseif (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
            $instance = is_string($class) ? new $class() : $class;
            $result = call_user_func([$instance, $method], $request, ...array_values($params));
        } else {
            throw new \RuntimeException('Invalid route action handler.');
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        return Response::html((string) $result);
    }
}
