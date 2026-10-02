<?php

namespace Syntax\Core\Http;

class Response
{
    protected string $content;
    protected int $status;
    protected array $headers;

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function html(string $html, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new static($html, $status, $headers);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/json; charset=UTF-8';
        $encoded = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return new static($encoded ?: '{}', $status, $headers);
    }

    public static function redirect(string $url, int $status = 302, array $headers = []): static
    {
        $headers['Location'] = $url;
        return new static('', $status, $headers);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Send response headers and body to client output.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        echo $this->content;
    }
}
