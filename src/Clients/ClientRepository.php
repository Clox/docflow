<?php
declare(strict_types=1);

namespace Docflow\Clients;

use PDO;
use RuntimeException;

final class ClientRepository
{
    private PDO $pdo;
    private bool $schemaEnsured = false;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listAll(): array
    {
        $this->ensureSchema();
        $statement = $this->pdo->query(
            'SELECT id, first_name, last_name, folder_name, personal_identity_number, preferred_first_name_index, sort_order, created_at, updated_at
            FROM clients
            ORDER BY sort_order ASC, id ASC'
        );
        $rows = $statement->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function replaceAll(array $clients): array
    {
        $this->ensureSchema();
        $normalized = [];
        foreach (array_values($clients) as $index => $client) {
            if (!is_array($client)) {
                throw new RuntimeException('Each client must be an object.');
            }

            $firstName = is_string($client['firstName'] ?? null) ? trim((string) $client['firstName']) : '';
            $lastName = is_string($client['lastName'] ?? null) ? trim((string) $client['lastName']) : '';
            $folderName = is_string($client['folderName'] ?? null) ? trim((string) $client['folderName']) : '';
            $pinRaw = $client['personalIdentityNumber'] ?? '';
            $pin = is_string($pinRaw) || is_int($pinRaw) || is_float($pinRaw)
                ? trim((string) $pinRaw)
                : '';
            $preferredFirstNameIndex = $this->normalizePreferredFirstNameIndex(
                $client['preferredFirstNameIndex'] ?? null,
                $firstName
            );

            if ($folderName === '' || $pin === '') {
                throw new RuntimeException('Each client must have folderName and personalIdentityNumber.');
            }

            $normalized[] = [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'folderName' => $folderName,
                'personalIdentityNumber' => $pin,
                'preferredFirstNameIndex' => $preferredFirstNameIndex,
                'sortOrder' => $index,
            ];
        }

        $timestamp = date(DATE_ATOM);

        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('DELETE FROM clients');

            $statement = $this->pdo->prepare(
                'INSERT INTO clients (
                    first_name,
                    last_name,
                    folder_name,
                    personal_identity_number,
                    preferred_first_name_index,
                    sort_order,
                    created_at,
                    updated_at
                ) VALUES (
                    :first_name,
                    :last_name,
                    :folder_name,
                    :personal_identity_number,
                    :preferred_first_name_index,
                    :sort_order,
                    :created_at,
                    :updated_at
                )'
            );

            foreach ($normalized as $client) {
                $statement->execute([
                    ':first_name' => $client['firstName'],
                    ':last_name' => $client['lastName'],
                    ':folder_name' => $client['folderName'],
                    ':personal_identity_number' => $client['personalIdentityNumber'],
                    ':preferred_first_name_index' => $client['preferredFirstNameIndex'],
                    ':sort_order' => $client['sortOrder'],
                    ':created_at' => $timestamp,
                    ':updated_at' => $timestamp,
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return $this->listAll();
    }

    private function ensureSchema(): void
    {
        if ($this->schemaEnsured) {
            return;
        }

        $columns = $this->pdo->query('PRAGMA table_info(clients)')->fetchAll();
        $hasPreferredFirstNameIndex = false;
        foreach (is_array($columns) ? $columns : [] as $column) {
            if (!is_array($column)) {
                continue;
            }
            if (($column['name'] ?? null) === 'preferred_first_name_index') {
                $hasPreferredFirstNameIndex = true;
                break;
            }
        }

        if (!$hasPreferredFirstNameIndex) {
            $this->pdo->exec('ALTER TABLE clients ADD COLUMN preferred_first_name_index INTEGER NULL');
        }

        $this->schemaEnsured = true;
    }

    private function normalizePreferredFirstNameIndex(mixed $value, string $firstName): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if (!is_int($value) && !is_string($value) && !is_float($value)) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $index = (int) $value;
        if ($index < 0) {
            return null;
        }

        $parts = preg_split('/\s+/u', trim($firstName), -1, PREG_SPLIT_NO_EMPTY);
        $partCount = is_array($parts) ? count($parts) : 0;
        if ($partCount < 1 || $index >= $partCount) {
            return null;
        }

        return $index;
    }
}
