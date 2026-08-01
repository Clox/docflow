<?php
declare(strict_types=1);

namespace Docflow\Clients;

use Docflow\Banking\BankAccountRepository;
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
            'SELECT id, first_name, last_name, folder_name, personal_identity_number,
                normalized_personal_identity_number, preferred_first_name_index,
                sort_order, created_at, updated_at
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
            $normalizedPin = PersonalIdentityNumberNormalizer::normalize($pin);
            $preferredFirstNameIndex = $this->normalizePreferredFirstNameIndex(
                $client['preferredFirstNameIndex'] ?? null,
                $firstName
            );

            if ($folderName === '') {
                throw new RuntimeException('Each client must have folderName.');
            }
            if ($pin !== '' && $normalizedPin === null) {
                throw new RuntimeException('Personnummer måste innehålla tolv siffror eller lämnas tomt.');
            }

            $normalized[] = [
                'id' => isset($client['id']) && is_numeric($client['id']) && (int) $client['id'] > 0
                    ? (int) $client['id']
                    : null,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'folderName' => $folderName,
                'personalIdentityNumber' => $pin !== '' ? $pin : null,
                'normalizedPersonalIdentityNumber' => $normalizedPin,
                'preferredFirstNameIndex' => $preferredFirstNameIndex,
                'sortOrder' => $index,
            ];
        }

        $seenPins = [];
        $seenIds = [];
        foreach ($normalized as $client) {
            $normalizedPin = $client['normalizedPersonalIdentityNumber'];
            if (is_string($normalizedPin) && isset($seenPins[$normalizedPin])) {
                throw new RuntimeException('Samma personnummer kan inte användas av flera huvudmän.');
            }
            if (is_string($normalizedPin)) {
                $seenPins[$normalizedPin] = true;
            }
            $clientId = $client['id'];
            if (is_int($clientId) && isset($seenIds[$clientId])) {
                throw new RuntimeException('Huvudman-id förekommer flera gånger.');
            }
            if (is_int($clientId)) {
                $seenIds[$clientId] = true;
            }
        }

        $timestamp = date(DATE_ATOM);

        $this->pdo->beginTransaction();
        try {
            $existingIds = [];
            foreach ($this->pdo->query('SELECT id FROM clients')->fetchAll() ?: [] as $row) {
                if (is_array($row) && isset($row['id'])) {
                    $existingIds[(int) $row['id']] = true;
                }
            }

            // Allows two existing principals to exchange personnummer in one save.
            $this->pdo->exec('UPDATE clients SET normalized_personal_identity_number = NULL');

            $insertStatement = $this->pdo->prepare(
                'INSERT INTO clients (
                    first_name,
                    last_name,
                    folder_name,
                    personal_identity_number,
                    normalized_personal_identity_number,
                    preferred_first_name_index,
                    sort_order,
                    created_at,
                    updated_at
                ) VALUES (
                    :first_name,
                    :last_name,
                    :folder_name,
                    :personal_identity_number,
                    :normalized_personal_identity_number,
                    :preferred_first_name_index,
                    :sort_order,
                    :created_at,
                    :updated_at
                )'
            );
            $updateStatement = $this->pdo->prepare(
                'UPDATE clients SET
                    first_name = :first_name,
                    last_name = :last_name,
                    folder_name = :folder_name,
                    personal_identity_number = :personal_identity_number,
                    normalized_personal_identity_number = :normalized_personal_identity_number,
                    preferred_first_name_index = :preferred_first_name_index,
                    sort_order = :sort_order,
                    updated_at = :updated_at
                 WHERE id = :id'
            );

            $retainedIds = [];

            foreach ($normalized as $client) {
                $params = [
                    ':first_name' => $client['firstName'],
                    ':last_name' => $client['lastName'],
                    ':folder_name' => $client['folderName'],
                    ':personal_identity_number' => $client['personalIdentityNumber'],
                    ':normalized_personal_identity_number' => $client['normalizedPersonalIdentityNumber'],
                    ':preferred_first_name_index' => $client['preferredFirstNameIndex'],
                    ':sort_order' => $client['sortOrder'],
                    ':updated_at' => $timestamp,
                ];
                $clientId = $client['id'];
                if (is_int($clientId)) {
                    if (!isset($existingIds[$clientId])) {
                        throw new RuntimeException('Huvudmannen som skulle uppdateras finns inte längre.');
                    }
                    $updateStatement->execute($params + [':id' => $clientId]);
                    $retainedIds[] = $clientId;
                    continue;
                }
                $insertStatement->execute($params + [':created_at' => $timestamp]);
                $retainedIds[] = (int) $this->pdo->lastInsertId();
            }

            if ($retainedIds === []) {
                $this->pdo->exec('DELETE FROM clients');
            } else {
                $placeholders = implode(', ', array_fill(0, count($retainedIds), '?'));
                $deleteStatement = $this->pdo->prepare('DELETE FROM clients WHERE id NOT IN (' . $placeholders . ')');
                $deleteStatement->execute($retainedIds);
            }

            if ($this->tableExists('bank_accounts')) {
                (new BankAccountRepository($this->pdo))->recalculateAutomaticLinks();
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

    private function tableExists(string $tableName): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM sqlite_master WHERE type = :type AND name = :name LIMIT 1'
        );
        $statement->execute([
            ':type' => 'table',
            ':name' => $tableName,
        ]);
        return $statement->fetchColumn() !== false;
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
