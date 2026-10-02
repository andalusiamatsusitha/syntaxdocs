<?php

namespace Syntax\Core\Support;

class Env
{
    protected static array $variables = [];

    /**
     * Load environment variables from a .env file path.
     */
    public static function load(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip surrounding quotes
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            static::$variables[$key] = $value;
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    /**
     * Get an environment variable value with optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value !== false) {
            return static::parseValue($value);
        }

        if (array_key_exists($key, static::$variables)) {
            return static::parseValue(static::$variables[$key]);
        }

        if (array_key_exists($key, $_ENV)) {
            return static::parseValue($_ENV[$key]);
        }

        return $default;
    }

    protected static function parseValue(string $value): mixed
    {
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}
