<?php

namespace Syntax\Core\Database;

use PDO;
use Syntax\Core\Support\Env;

class ConnectionManager
{
    protected static array $connections = [];
    protected static array $configs = [];

    /**
     * Register a database connection configuration.
     */
    public static function register(string $name, array $config): void
    {
        static::$configs[$name] = $config;
    }

    /**
     * Retrieve or instantiate a PDO connection by name.
     */
    public static function connection(string $name = 'default'): PDO
    {
        if (isset(static::$connections[$name])) {
            return static::$connections[$name];
        }

        $config = static::$configs[$name] ?? static::getDefaultConfig();

        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        if ($driver === 'sqlite') {
            $dsn = "sqlite:{$database}";
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        static::$connections[$name] = $pdo;
        return $pdo;
    }

    protected static function getDefaultConfig(): array
    {
        return [
            'driver' => Env::get('DB_DRIVER', 'mysql'),
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => (int) Env::get('DB_PORT', 3306),
            'database' => Env::get('DB_DATABASE', 'syntax_db'),
            'username' => Env::get('DB_USERNAME', 'root'),
            'password' => Env::get('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
        ];
    }

    /**
     * Start a database transaction.
     */
    public static function beginTransaction(string $name = 'default'): bool
    {
        return static::connection($name)->beginTransaction();
    }

    /**
     * Commit a database transaction.
     */
    public static function commit(string $name = 'default'): bool
    {
        return static::connection($name)->commit();
    }

    /**
     * Rollback a database transaction.
     */
    public static function rollBack(string $name = 'default'): bool
    {
        return static::connection($name)->rollBack();
    }
}
