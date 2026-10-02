<?php

namespace Syntax\Core\Database;

use PDO;
use PDOStatement;

class QueryBuilder
{
    protected PDO $pdo;
    protected string $table = '';
    protected array $columns = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $orders = [];
    protected ?int $limitValue = null;
    protected ?int $offsetValue = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? ConnectionManager::connection('default');
    }

    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function select(string ...$columns): static
    {
        $this->columns = !empty($columns) ? $columns : ['*'];
        return $this;
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => strtoupper($operator),
        ];
        $this->bindings[] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
        ];
        foreach ($values as $val) {
            $this->bindings[] = $val;
        }

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = "{$column} " . strtoupper($direction);
        return $this;
    }

    public function limit(int $limit, ?int $offset = null): static
    {
        $this->limitValue = $limit;
        if ($offset !== null) {
            $this->offsetValue = $offset;
        }
        return $this;
    }

    public function get(): array
    {
        $sql = $this->toSelectSql();
        $stmt = $this->execute($sql, $this->bindings);
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }

    public function find(mixed $id, string $primaryKey = 'id'): ?array
    {
        return $this->where($primaryKey, '=', $id)->first();
    }

    public function count(): int
    {
        $columns = implode(', ', $this->columns);
        $this->columns = ['COUNT(*) as aggregate_count'];
        $sql = $this->toSelectSql();
        $stmt = $this->execute($sql, $this->bindings);
        $row = $stmt->fetch();
        $this->columns = [$columns];
        return (int) ($row['aggregate_count'] ?? 0);
    }

    public function insert(array $data): string|int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->execute($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update(array $data): int
    {
        $setClauses = [];
        $setBindings = [];

        foreach ($data as $column => $value) {
            $setClauses[] = "{$column} = ?";
            $setBindings[] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s', $this->table, implode(', ', $setClauses));
        $whereSql = $this->buildWhereSql();
        if (!empty($whereSql)) {
            $sql .= ' WHERE ' . $whereSql;
        }

        $allBindings = array_merge($setBindings, $this->bindings);
        $stmt = $this->execute($sql, $allBindings);
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        $whereSql = $this->buildWhereSql();
        if (!empty($whereSql)) {
            $sql .= ' WHERE ' . $whereSql;
        }

        $stmt = $this->execute($sql, $this->bindings);
        return $stmt->rowCount();
    }

    protected function toSelectSql(): string
    {
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $this->columns), $this->table);

        $whereSql = $this->buildWhereSql();
        if (!empty($whereSql)) {
            $sql .= ' WHERE ' . $whereSql;
        }

        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limitValue !== null) {
            $sql .= " LIMIT {$this->limitValue}";
            if ($this->offsetValue !== null) {
                $sql .= " OFFSET {$this->offsetValue}";
            }
        }

        return $sql;
    }

    protected function buildWhereSql(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $clauses = [];
        foreach ($this->wheres as $where) {
            if ($where['type'] === 'basic') {
                $clauses[] = "{$where['column']} {$where['operator']} ?";
            } elseif ($where['type'] === 'in') {
                $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                $clauses[] = "{$where['column']} IN ({$placeholders})";
            }
        }

        return implode(' AND ', $clauses);
    }

    protected function execute(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }
}
