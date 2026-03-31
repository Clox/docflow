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
            'SELECT
                id,
                sender_id,
                auto_sender_id,
                analysis_client_id,
                analysis_sender_id,
                analysis_category_id,
                analysis_labels_json,
                analysis_fields_json,
                analysis_system_fields_json,
                analyzed_at,
                merged_objects_gzip,
                created_at,
                updated_at
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

    public function upsertAnalysisSnapshot(string $jobId, array $autoResult, ?string $analyzedAt = null): void
    {
        $normalizedId = trim($jobId);
        if ($normalizedId === '') {
            throw new RuntimeException('Job id is required.');
        }

        $timestamp = is_string($analyzedAt) && trim($analyzedAt) !== '' ? trim($analyzedAt) : date(DATE_ATOM);
        $labelsJson = $this->encodeJsonArray(is_array($autoResult['labels'] ?? null) ? $autoResult['labels'] : []);
        $fieldsJson = $this->encodeJsonMap(is_array($autoResult['fields'] ?? null) ? $autoResult['fields'] : []);
        $systemFieldsJson = $this->encodeJsonMap(is_array($autoResult['systemFields'] ?? null) ? $autoResult['systemFields'] : []);

        $statement = $this->pdo->prepare(
            'INSERT INTO jobs (
                id,
                analysis_client_id,
                analysis_sender_id,
                analysis_category_id,
                analysis_labels_json,
                analysis_fields_json,
                analysis_system_fields_json,
                analyzed_at,
                created_at,
                updated_at
            ) VALUES (
                :id,
                :analysis_client_id,
                :analysis_sender_id,
                :analysis_category_id,
                :analysis_labels_json,
                :analysis_fields_json,
                :analysis_system_fields_json,
                :analyzed_at,
                :created_at,
                :updated_at
            )
            ON CONFLICT(id) DO UPDATE SET
                analysis_client_id = excluded.analysis_client_id,
                analysis_sender_id = excluded.analysis_sender_id,
                analysis_category_id = excluded.analysis_category_id,
                analysis_labels_json = excluded.analysis_labels_json,
                analysis_fields_json = excluded.analysis_fields_json,
                analysis_system_fields_json = excluded.analysis_system_fields_json,
                analyzed_at = excluded.analyzed_at,
                updated_at = excluded.updated_at'
        );
        $statement->execute([
            ':id' => $normalizedId,
            ':analysis_client_id' => is_string($autoResult['clientId'] ?? null) && trim((string) $autoResult['clientId']) !== ''
                ? trim((string) $autoResult['clientId'])
                : null,
            ':analysis_sender_id' => isset($autoResult['senderId']) && (int) $autoResult['senderId'] > 0
                ? (int) $autoResult['senderId']
                : null,
            ':analysis_category_id' => is_string($autoResult['categoryId'] ?? null) && trim((string) $autoResult['categoryId']) !== ''
                ? trim((string) $autoResult['categoryId'])
                : null,
            ':analysis_labels_json' => $labelsJson,
            ':analysis_fields_json' => $fieldsJson,
            ':analysis_system_fields_json' => $systemFieldsJson,
            ':analyzed_at' => $timestamp,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);
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

    public function findAnalysisSnapshot(string $jobId): ?array
    {
        $normalizedId = trim($jobId);
        if ($normalizedId === '') {
            throw new RuntimeException('Job id is required.');
        }

        $statement = $this->pdo->prepare(
            'SELECT
                analysis_client_id,
                analysis_sender_id,
                analysis_category_id,
                analysis_labels_json,
                analysis_fields_json,
                analysis_system_fields_json,
                analyzed_at
            FROM jobs
            WHERE id = :id
            LIMIT 1'
        );
        $statement->execute([':id' => $normalizedId]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        $labels = $this->decodeJsonArray($row['analysis_labels_json'] ?? null);
        $fields = $this->decodeJsonMap($row['analysis_fields_json'] ?? null);
        $systemFields = $this->decodeJsonMap($row['analysis_system_fields_json'] ?? null);
        $analyzedAt = is_string($row['analyzed_at'] ?? null) ? trim((string) $row['analyzed_at']) : '';

        $hasPayload = (is_string($row['analysis_client_id'] ?? null) && trim((string) $row['analysis_client_id']) !== '')
            || (isset($row['analysis_sender_id']) && (int) $row['analysis_sender_id'] > 0)
            || (is_string($row['analysis_category_id'] ?? null) && trim((string) $row['analysis_category_id']) !== '')
            || $labels !== []
            || $fields !== []
            || $systemFields !== []
            || $analyzedAt !== '';
        if (!$hasPayload) {
            return null;
        }

        return [
            'clientId' => is_string($row['analysis_client_id'] ?? null) ? trim((string) $row['analysis_client_id']) : null,
            'senderId' => isset($row['analysis_sender_id']) && (int) $row['analysis_sender_id'] > 0 ? (int) $row['analysis_sender_id'] : null,
            'categoryId' => is_string($row['analysis_category_id'] ?? null) ? trim((string) $row['analysis_category_id']) : null,
            'labels' => $labels,
            'fields' => $fields,
            'systemFields' => $systemFields,
            'analyzedAt' => $analyzedAt !== '' ? $analyzedAt : null,
        ];
    }

    private function encodeJsonArray(array $value): string
    {
        $json = json_encode(array_values($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('Could not encode JSON array.');
        }

        return $json;
    }

    private function encodeJsonMap(array $value): string
    {
        $map = $value === [] ? new \stdClass() : $value;
        $json = json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('Could not encode JSON object.');
        }

        return $json;
    }

    private function decodeJsonArray(mixed $raw): array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function decodeJsonMap(mixed $raw): array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
