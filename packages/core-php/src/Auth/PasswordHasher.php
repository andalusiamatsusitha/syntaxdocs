<?php

namespace Syntax\Core\Auth;

class PasswordHasher
{
    /**
     * Hash the given plain password using modern Argon2id with Bcrypt fallback.
     */
    public static function hash(string $password): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_hash($password, $algo);
    }

    /**
     * Verify a plain password against the hashed string.
     */
    public static function verify(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Check if a hash needs rehashing due to updated algorithm options.
     */
    public static function needsRehash(string $hashedPassword): bool
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_needs_rehash($hashedPassword, $algo);
    }
}
