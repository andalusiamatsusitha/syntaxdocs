<?php

namespace Syntax\Core\Database;

use PDO;

class DB
{
    /**
     * Start a query on a given table using the default (or specified) connection.
     */
    public static function table(string $table, string $connection = 'default'): QueryBuilder
    {
        $pdo = ConnectionManager::connection($connection);
        $qb = new QueryBuilder($pdo);
        return $qb->table($table);
    }

    /**
     * Get or set a connection name for chaining.
     */
    public static function connection(string $name = 'default'): ConnectionProxy
    {
        $pdo = ConnectionManager::connection($name);
        return new ConnectionProxy($pdo);
    }

    /**
     * Execute a raw SQL query with parameter bindings.
     */
    public static function raw(string $sql, array $bindings = [], string $connection = 'default'): array
    {
        $pdo = ConnectionManager::connection($connection);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    /**
     * Execute a raw SQL statement (DDL / schema creation).
     */
    public static function statement(string $sql, string $connection = 'default'): int|bool
    {
        $pdo = ConnectionManager::connection($connection);
        return $pdo->exec($sql);
    }

    public static function beginTransaction(string $connection = 'default'): bool
    {
        return ConnectionManager::beginTransaction($connection);
    }

    public static function commit(string $connection = 'default'): bool
    {
        return ConnectionManager::commit($connection);
    }

    public static function rollBack(string $connection = 'default'): bool
    {
        return ConnectionManager::rollBack($connection);
    }
}

class ConnectionProxy
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function table(string $table): QueryBuilder
    {
        $qb = new QueryBuilder($this->pdo);
        return $qb->table($table);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
