<?php

namespace Syntax\Core\Database;

use PDO;

class MigrationRunner
{
    protected PDO $pdo;
    protected string $connectionName;

    public function __construct(string $connectionName = 'default')
    {
        $this->connectionName = $connectionName;
        $this->pdo = ConnectionManager::connection($connectionName);
        $this->ensureMigrationTable();
    }

    protected function ensureMigrationTable(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS _migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INTEGER NOT NULL,
                applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS _migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }

        $this->pdo->exec($sql);
    }

    /**
     * Run all pending migrations from given directories.
     */
    public function run(array $directories): array
    {
        $applied = $this->getAppliedMigrations();
        $files = $this->getMigrationFiles($directories);
        $pending = array_diff_key($files, array_flip($applied));

        if (empty($pending)) {
            return [];
        }

        $batch = $this->getNextBatchNumber();
        $executed = [];

        foreach ($pending as $name => $filePath) {
            require_once $filePath;

            // Get migration object
            $className = $this->getClassNameFromFileName($name);
            if (!class_exists($className)) {
                throw new \RuntimeException("Migration class {$className} not found in {$filePath}");
            }

            $migration = new $className();
            if (method_exists($migration, 'up')) {
                try {
                    $migration->up($this->pdo);

                    $stmt = $this->pdo->prepare('INSERT INTO _migrations (migration, batch) VALUES (?, ?)');
                    $stmt->execute([$name, $batch]);

                    if ($this->pdo->inTransaction()) {
                        $this->pdo->commit();
                    }
                    $executed[] = $name;
                } catch (\Throwable $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    throw new \RuntimeException("Migration failed: {$name} - " . $e->getMessage(), 0, $e);
                }
            }
        }

        return $executed;
    }

    protected function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query('SELECT migration FROM _migrations ORDER BY id ASC');
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    protected function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query('SELECT MAX(batch) as max_batch FROM _migrations');
        $row = $stmt->fetch();
        return ((int) ($row['max_batch'] ?? 0)) + 1;
    }

    protected function getMigrationFiles(array $directories): array
    {
        $files = [];
        foreach ($directories as $dir) {
            if (!is_dir($dir)) continue;

            $items = scandir($dir);
            sort($items);
            foreach ($items as $item) {
                if (str_ends_with($item, '.php')) {
                    $name = pathinfo($item, PATHINFO_FILENAME);
                    $files[$name] = $dir . DIRECTORY_SEPARATOR . $item;
                }
            }
        }
        ksort($files);
        return $files;
    }

    protected function getClassNameFromFileName(string $fileName): string
    {
        // Strip leading timestamp/digits e.g. "001_create_users_table" -> "CreateUsersTable"
        $parts = explode('_', $fileName);
        if (is_numeric($parts[0])) {
            array_shift($parts);
        }
        return str_replace(' ', '', ucwords(implode(' ', $parts)));
    }
}
