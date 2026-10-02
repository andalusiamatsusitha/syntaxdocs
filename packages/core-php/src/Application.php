<?php

namespace Syntax\Core;

use Syntax\Core\Http\Request;
use Syntax\Core\Http\Response;
use Syntax\Core\Http\Router;
use Syntax\Core\Http\Middleware\Pipeline;
use Syntax\Core\Http\Middleware\CorsMiddleware;
use Syntax\Core\Http\Middleware\SecurityHeadersMiddleware;
use Syntax\Core\Http\Middleware\SessionMiddleware;
use Syntax\Core\Support\Env;

class Application
{
    protected string $basePath;
    protected Router $router;
    protected array $globalMiddlewares = [
        SecurityHeadersMiddleware::class,
        SessionMiddleware::class,
    ];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->router = new Router();
        $this->bootstrap();
    }

    protected function bootstrap(): void
    {
        // 1. Load application .env if exists
        $envFile = $this->basePath . '/.env';
        if (file_exists($envFile)) {
            Env::load($envFile);
        }
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function addGlobalMiddleware(string $middlewareClass): static
    {
        $this->globalMiddlewares[] = $middlewareClass;
        return $this;
    }

    /**
     * Handle the incoming HTTP request and return a Response.
     */
    public function handle(Request $request): Response
    {
        $pipeline = new Pipeline();

        return $pipeline
            ->send($request)
            ->through($this->globalMiddlewares)
            ->then(function (Request $req): Response {
                return $this->router->dispatch($req);
            });
    }

    /**
     * Run the application: capture current request, process, and send response.
     */
    public function run(?Request $request = null): void
    {
        $req = $request ?? Request::capture();
        $response = $this->handle($req);
        $response->send();
    }
}
