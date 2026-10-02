<?php

namespace Syntax\Core\Http;

class Request
{
    protected string $method;
    protected string $uri;
    protected array $query;
    protected array $post;
    protected array $headers;
    protected array $cookies;
    protected array $server;
    protected ?array $json = null;
    protected array $attributes = [];

    public function __construct(
        string $method = 'GET',
        string $uri = '/',
        array $query = [],
        array $post = [],
        array $headers = [],
        array $cookies = [],
        array $server = []
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->query = $query;
        $this->post = $post;
        $this->headers = $headers;
        $this->cookies = $cookies;
        $this->server = $server;
    }

    /**
     * Capture the current HTTP request from PHP globals.
     */
    public static function capture(): static
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$headerName] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headers[strtolower(str_replace('_', '-', $key))] = $value;
            }
        }

        return new static(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $uri,
            $_GET ?? [],
            $_POST ?? [],
            $headers,
            $_COOKIE ?? [],
            $_SERVER ?? []
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $normalized = strtolower(str_replace('_', '-', $key));
        return $this->headers[$normalized] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        if ($this->isJson()) {
            return array_merge($this->query, $this->json());
        }
        return array_merge($this->query, $this->post);
    }

    public function isJson(): bool
    {
        $contentType = $this->header('content-type', '');
        return str_contains($contentType, '/json') || str_contains($contentType, '+json');
    }

    public function json(): array
    {
        if ($this->json === null) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $this->json = is_array($decoded) ? $decoded : [];
        }
        return $this->json;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
