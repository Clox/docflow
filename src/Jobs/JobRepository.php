<?php
declare(strict_types=1);

namespace Docflow\Jobs;

use PDO;
use RuntimeException;

final class JobRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(string $jobId): ?array
    {
        $normalizedId = trim($jobId);
        if ($normalizedId === '') {
            throw new RuntimeException('Job id is required.');
        }

        $statement = $this->pdo->prepare(
            'SELECT id, sender_id, auto_sender_id, created_at, updated_at
            FROM jobs
            WHERE id = :id
            LIMIT 1'
        );
        $statement->execute([':id' => $normalizedId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function upsertSenderSnapshotIds(string $jobId, ?int $senderId, ?int $autoSenderId): void
    {
        $normalizedId = trim($jobId);
        if ($normalizedId === '') {
            throw new RuntimeException('Job id is required.');
        }

        $timestamp = date(DATE_ATOM);
        $statement = $this->pdo->prepare(
            'INSERT INTO jobs (
                id,
                sender_id,
                auto_sender_id,
                created_at,
                updated_at
            ) VALUES (
                :id,
                :sender_id,
                :auto_sender_id,
                :created_at,
                :updated_at
            )
            ON CONFLICT(id) DO UPDATE SET
                sender_id = excluded.sender_id,
                auto_sender_id = excluded.auto_sender_id,
                updated_at = excluded.updated_at'
        );
        $statement->execute([
            ':id' => $normalizedId,
            ':sender_id' => $senderId !== null && $senderId > 0 ? $senderId : null,
            ':auto_sender_id' => $autoSenderId !== null && $autoSenderId > 0 ? $autoSenderId : null,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);
    }
}
