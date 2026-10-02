<?php

namespace Syntax\Core\Auth;

use Syntax\Core\Database\DB;

class AuthManager
{
    protected static string $sessionKey = '_auth_user_id';
    protected static ?array $cachedUser = null;

    /**
     * Attempt to authenticate against database table using email & password.
     */
    public static function attempt(string $email, string $password, string $table = 'users', string $connection = 'default'): bool
    {
        $user = DB::connection($connection)
            ->table($table)
            ->where('email', $email)
            ->first();

        if (!$user) {
            return false;
        }

        if (!PasswordHasher::verify($password, $user['password'])) {
            return false;
        }

        static::login($user);
        return true;
    }

    /**
     * Log in a user by setting session ID.
     */
    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Regenerate session ID to prevent session fixation attacks
        @session_regenerate_id(true);

        $_SESSION[static::$sessionKey] = $user['id'];
        static::$cachedUser = $user;
    }

    /**
     * Log the current user out.
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        unset($_SESSION[static::$sessionKey]);
        static::$cachedUser = null;
        @session_regenerate_id(true);
    }

    /**
     * Check if a user is currently authenticated.
     */
    public static function check(): bool
    {
        return static::id() !== null;
    }

    /**
     * Get the authenticated user ID.
     */
    public static function id(): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        return $_SESSION[static::$sessionKey] ?? null;
    }

    /**
     * Get the authenticated user record from database.
     */
    public static function user(string $table = 'users', string $connection = 'default'): ?array
    {
        $id = static::id();
        if ($id === null) {
            return null;
        }

        if (static::$cachedUser !== null && (static::$cachedUser['id'] ?? null) == $id) {
            return static::$cachedUser;
        }

        $user = DB::connection($connection)
            ->table($table)
            ->where('id', $id)
            ->first();

        static::$cachedUser = $user;
        return $user;
    }
}
