<?php
declare(strict_types=1);

namespace Docflow\Database;

use PDO;
use RuntimeException;

final class MigrationRunner
{
    private PDO $pdo;
    private string $migrationsDirectory;

    public function __construct(PDO $pdo, string $migrationsDirectory)
    {
        $this->pdo = $pdo;
        $this->migrationsDirectory = rtrim($migrationsDirectory, '/');
    }

    public function migrate(): array
    {
        $this->ensureMigrationsTable();

        $files = $this->migrationFiles();
        $alreadyApplied = $this->appliedMigrationNames();
        $appliedNow = [];

        foreach ($files as $name => $path) {
            if (isset($alreadyApplied[$name])) {
                continue;
            }

            $sql = file_get_contents($path);
            if ($sql === false) {
                throw new RuntimeException('Could not read migration file: ' . $path);
            }

            $runWithoutTransaction = $this->shouldRunWithoutTransaction($sql);

            if ($runWithoutTransaction) {
                $this->pdo->exec($sql);

                $statement = $this->pdo->prepare(
                    'INSERT INTO migrations (name, applied_at) VALUES (:name, :applied_at)'
                );
                $statement->execute([
                    ':name' => $name,
                    ':applied_at' => date(DATE_ATOM),
                ]);

                $appliedNow[] = $name;
                continue;
            }

            $this->pdo->beginTransaction();
            try {
                $this->pdo->exec($sql);

                $statement = $this->pdo->prepare(
                    'INSERT INTO migrations (name, applied_at) VALUES (:name, :applied_at)'
                );
                $statement->execute([
                    ':name' => $name,
                    ':applied_at' => date(DATE_ATOM),
                ]);

                $this->pdo->commit();
                $appliedNow[] = $name;
            } catch (\Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $e;
            }
        }

        return [
            'applied' => $appliedNow,
            'all' => array_keys($files),
        ];
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                applied_at TEXT NOT NULL
            )'
        );
    }

    private function migrationFiles(): array
    {
        if (!is_dir($this->migrationsDirectory)) {
            throw new RuntimeException('Missing migrations directory: ' . $this->migrationsDirectory);
        }

        $paths = glob($this->migrationsDirectory . '/*.sql');
        if (!is_array($paths)) {
            return [];
        }

        sort($paths, SORT_STRING);

        $files = [];
        foreach ($paths as $path) {
            $name = basename($path);
            $files[$name] = $path;
        }

        return $files;
    }

    private function appliedMigrationNames(): array
    {
        $rows = $this->pdo->query('SELECT name FROM migrations')->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $names = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = $row['name'] ?? null;
            if (is_string($name) && $name !== '') {
                $names[$name] = true;
            }
        }

        return $names;
    }

    private function shouldRunWithoutTransaction(string $sql): bool
    {
        return preg_match('/^\s*--\s*no-transaction\b/im', $sql) === 1;
    }
}
