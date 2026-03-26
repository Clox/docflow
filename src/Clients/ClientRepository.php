<?php
declare(strict_types=1);

namespace Docflow\Clients;

use PDO;
use RuntimeException;

final class ClientRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, first_name, last_name, folder_name, personal_identity_number, sort_order, created_at, updated_at
            FROM clients
            ORDER BY sort_order ASC, id ASC'
        );
        $rows = $statement->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function replaceAll(array $clients): array
    {
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

            if ($folderName === '' || $pin === '') {
                throw new RuntimeException('Each client must have folderName and personalIdentityNumber.');
            }

            $normalized[] = [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'folderName' => $folderName,
                'personalIdentityNumber' => $pin,
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
                    sort_order,
                    created_at,
                    updated_at
                ) VALUES (
                    :first_name,
                    :last_name,
                    :folder_name,
                    :personal_identity_number,
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
}
