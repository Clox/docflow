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
            'SELECT id, sender_id, auto_sender_id, merged_objects_gzip, created_at, updated_at
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

    public function upsertMergedObjectsPayload(string $jobId, array $payload): void
    {
        $normalizedId = trim($jobId);
        if ($normalizedId === '') {
            throw new RuntimeException('Job id is required.');
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('Could not encode merged objects payload.');
        }

        $compressed = gzencode($json, 6);
        if (!is_string($compressed)) {
            throw new RuntimeException('Could not compress merged objects payload.');
        }

        $timestamp = date(DATE_ATOM);
        $statement = $this->pdo->prepare(
            'INSERT INTO jobs (
                id,
                merged_objects_gzip,
                created_at,
                updated_at
            ) VALUES (
                :id,
                :merged_objects_gzip,
                :created_at,
                :updated_at
            )
            ON CONFLICT(id) DO UPDATE SET
                merged_objects_gzip = excluded.merged_objects_gzip,
                updated_at = excluded.updated_at'
        );
        $statement->bindValue(':id', $normalizedId, PDO::PARAM_STR);
        $statement->bindValue(':merged_objects_gzip', $compressed, PDO::PARAM_LOB);
        $statement->bindValue(':created_at', $timestamp, PDO::PARAM_STR);
        $statement->bindValue(':updated_at', $timestamp, PDO::PARAM_STR);
        $statement->execute();
    }

    public function clearMergedObjectsPayload(string $jobId): void
    {
        $normalizedId = trim($jobId);
        if ($normalizedId === '') {
            throw new RuntimeException('Job id is required.');
        }

        $timestamp = date(DATE_ATOM);
        $statement = $this->pdo->prepare(
            'INSERT INTO jobs (
                id,
                merged_objects_gzip,
                created_at,
                updated_at
            ) VALUES (
                :id,
                NULL,
                :created_at,
                :updated_at
            )
            ON CONFLICT(id) DO UPDATE SET
                merged_objects_gzip = NULL,
                updated_at = excluded.updated_at'
        );
        $statement->execute([
            ':id' => $normalizedId,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);
    }

    public function findMergedObjectsPayload(string $jobId): ?array
    {
        $normalizedId = trim($jobId);
        if ($normalizedId === '') {
            throw new RuntimeException('Job id is required.');
        }

        $statement = $this->pdo->prepare(
            'SELECT merged_objects_gzip
            FROM jobs
            WHERE id = :id
            LIMIT 1'
        );
        $statement->execute([':id' => $normalizedId]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        $compressed = $row['merged_objects_gzip'] ?? null;
        if (is_resource($compressed)) {
            $compressed = stream_get_contents($compressed);
        }
        if (!is_string($compressed) || $compressed === '') {
            return null;
        }

        $json = gzdecode($compressed);
        if (!is_string($json) || $json === '') {
            return null;
        }

        $payload = json_decode($json, true);
        return is_array($payload) ? $payload : null;
    }
}
