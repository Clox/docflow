<?php
declare(strict_types=1);

namespace Docflow\Jobs;

use PDO;
use RuntimeException;

final class DocumentMetadataRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findLabelIds(string $jobId): ?array
    {
        $normalizedJobId = $this->normalizeJobId($jobId);
        $statement = $this->pdo->prepare(
            'SELECT label_id
            FROM document_labels
            WHERE job_id = :job_id
            ORDER BY label_id ASC'
        );
        $statement->execute([':job_id' => $normalizedJobId]);
        $rows = $statement->fetchAll();
        if (!is_array($rows) || $rows === []) {
            return null;
        }

        $labelIds = [];
        foreach ($rows as $row) {
            $labelId = is_array($row) && is_string($row['label_id'] ?? null) ? trim((string) $row['label_id']) : '';
            if ($labelId !== '') {
                $labelIds[] = $labelId;
            }
        }

        return array_values(array_unique($labelIds));
    }

    public function replaceLabels(string $jobId, array $labelIds): void
    {
        $normalizedJobId = $this->normalizeJobId($jobId);
        $timestamp = date(DATE_ATOM);
        $normalizedLabelIds = array_values(array_unique(array_filter(array_map(
            static fn($value): string => is_string($value) ? trim($value) : '',
            $labelIds
        ), static fn(string $value): bool => $value !== '')));

        $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM document_labels WHERE job_id = :job_id');
            $delete->execute([':job_id' => $normalizedJobId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO document_labels (
                    job_id,
                    label_id,
                    created_at,
                    updated_at
                ) VALUES (
                    :job_id,
                    :label_id,
                    :created_at,
                    :updated_at
                )'
            );
            foreach ($normalizedLabelIds as $labelId) {
                $insert->execute([
                    ':job_id' => $normalizedJobId,
                    ':label_id' => $labelId,
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
    }

    public function findDataSelections(string $jobId): ?array
    {
        $normalizedJobId = $this->normalizeJobId($jobId);
        $statement = $this->pdo->prepare(
            'SELECT
                f.field_key,
                v.value,
                v.is_primary
            FROM document_data_values v
            INNER JOIN archiving_data_fields f ON f.id = v.archiving_data_field_id
            WHERE v.job_id = :job_id
                AND f.rules_scope = :rules_scope
            ORDER BY f.field_key ASC, v.is_primary DESC, v.id ASC'
        );
        $statement->execute([
            ':job_id' => $normalizedJobId,
            ':rules_scope' => 'active',
        ]);
        $rows = $statement->fetchAll();
        if (!is_array($rows) || $rows === []) {
            return null;
        }

        $selections = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $fieldKey = is_string($row['field_key'] ?? null) ? trim((string) $row['field_key']) : '';
            $value = is_string($row['value'] ?? null) ? trim((string) $row['value']) : '';
            if ($fieldKey === '' || $value === '') {
                continue;
            }
            if (!isset($selections[$fieldKey])) {
                $selections[$fieldKey] = [
                    'values' => [],
                    'primaryValue' => null,
                ];
            }
            $selections[$fieldKey]['values'][] = $value;
            if ((int) ($row['is_primary'] ?? 0) === 1) {
                $selections[$fieldKey]['primaryValue'] = $value;
            }
        }

        foreach ($selections as $fieldKey => $selection) {
            $values = array_values(array_unique(array_filter(
                array_map(static fn($value): string => is_string($value) ? trim($value) : '', $selection['values']),
                static fn(string $value): bool => $value !== ''
            )));
            if ($values === []) {
                unset($selections[$fieldKey]);
                continue;
            }
            $primaryValue = is_string($selection['primaryValue'] ?? null) && in_array($selection['primaryValue'], $values, true)
                ? $selection['primaryValue']
                : $values[0];
            $selections[$fieldKey] = [
                'values' => $values,
                'primaryValue' => $primaryValue,
            ];
        }

        ksort($selections, SORT_NATURAL);
        return $selections !== [] ? $selections : null;
    }

    public function replaceDataSelections(string $jobId, array $selections): void
    {
        $normalizedJobId = $this->normalizeJobId($jobId);
        $fieldIdsByKey = $this->activeFieldIdsByKey();
        $timestamp = date(DATE_ATOM);

        $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM document_data_values WHERE job_id = :job_id');
            $delete->execute([':job_id' => $normalizedJobId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO document_data_values (
                    job_id,
                    archiving_data_field_id,
                    value,
                    is_primary,
                    created_at,
                    updated_at
                ) VALUES (
                    :job_id,
                    :archiving_data_field_id,
                    :value,
                    :is_primary,
                    :created_at,
                    :updated_at
                )'
            );

            foreach ($selections as $fieldKey => $selection) {
                $normalizedFieldKey = is_string($fieldKey) ? trim($fieldKey) : '';
                if ($normalizedFieldKey === '' || !isset($fieldIdsByKey[$normalizedFieldKey]) || !is_array($selection)) {
                    continue;
                }
                $values = $this->selectionValues($selection);
                if ($values === []) {
                    continue;
                }
                $primaryValue = is_string($selection['primaryValue'] ?? null) ? trim((string) $selection['primaryValue']) : '';
                if ($primaryValue === '' || !in_array($primaryValue, $values, true)) {
                    $primaryValue = $values[0];
                }
                foreach ($values as $value) {
                    $insert->execute([
                        ':job_id' => $normalizedJobId,
                        ':archiving_data_field_id' => $fieldIdsByKey[$normalizedFieldKey],
                        ':value' => $value,
                        ':is_primary' => $value === $primaryValue ? 1 : 0,
                        ':created_at' => $timestamp,
                        ':updated_at' => $timestamp,
                    ]);
                }
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function activeFieldIdsByKey(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, field_key
            FROM archiving_data_fields
            WHERE rules_scope = :rules_scope
            ORDER BY id ASC'
        );
        $statement->execute([':rules_scope' => 'active']);
        $rows = $statement->fetchAll();
        $idsByKey = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $fieldKey = is_string($row['field_key'] ?? null) ? trim((string) $row['field_key']) : '';
            if ($id > 0 && $fieldKey !== '') {
                $idsByKey[$fieldKey] = $id;
            }
        }
        return $idsByKey;
    }

    private function selectionValues(array $selection): array
    {
        $values = [];
        foreach (['manualValues', 'values'] as $key) {
            foreach (is_array($selection[$key] ?? null) ? $selection[$key] : [] as $value) {
                $text = is_scalar($value) ? trim((string) $value) : '';
                if ($text !== '') {
                    $values[] = $text;
                }
            }
        }
        $primaryValue = is_scalar($selection['primaryValue'] ?? null) ? trim((string) $selection['primaryValue']) : '';
        if ($primaryValue !== '') {
            $values[] = $primaryValue;
        }
        return array_values(array_unique($values));
    }

    private function normalizeJobId(string $jobId): string
    {
        $normalized = trim($jobId);
        if ($normalized === '') {
            throw new RuntimeException('Job id is required.');
        }
        return $normalized;
    }
}
