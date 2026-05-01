<?php
declare(strict_types=1);

namespace Docflow\Archiving;

use PDO;
use RuntimeException;

final class LabelRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function hasAnyRows(): bool
    {
        $statement = $this->pdo->query('SELECT 1 FROM archiving_labels LIMIT 1');
        return $statement->fetchColumn() !== false;
    }

    public function loadAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, description, min_score, is_system, system_label_key, rules_json
            FROM archiving_labels
            ORDER BY is_system DESC, name ASC, id ASC'
        );
        $rows = $statement->fetchAll();
        $labels = [];
        $systemLabels = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = $this->rowToLabel($row);
            if ($label === null) {
                continue;
            }
            if ((int) ($row['is_system'] ?? 0) === 1) {
                $systemLabelKey = is_string($label['systemLabelKey'] ?? null) ? trim((string) $label['systemLabelKey']) : '';
                $systemLabels[$systemLabelKey !== '' ? $systemLabelKey : $label['id']] = $label;
            } else {
                $labels[] = $label;
            }
        }

        return [
            'labels' => $labels,
            'systemLabels' => $systemLabels,
        ];
    }

    public function replaceAll(array $labels, array $systemLabels): void
    {
        $this->pdo->beginTransaction();
        try {
            $insert = $this->pdo->prepare(
                'INSERT INTO archiving_labels (
                    id,
                    name,
                    description,
                    min_score,
                    is_system,
                    system_label_key,
                    rules_json,
                    created_at,
                    updated_at
                ) VALUES (
                    :id,
                    :name,
                    :description,
                    :min_score,
                    :is_system,
                    :system_label_key,
                    :rules_json,
                    :created_at,
                    :updated_at
                )'
                . ' ON CONFLICT(id) DO UPDATE SET
                    name = excluded.name,
                    description = excluded.description,
                    min_score = excluded.min_score,
                    is_system = excluded.is_system,
                    system_label_key = excluded.system_label_key,
                    rules_json = excluded.rules_json,
                    updated_at = excluded.updated_at'
            );
            $timestamp = date(DATE_ATOM);
            $nextIds = [];
            foreach ($labels as $label) {
                if (is_array($label)) {
                    $id = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
                    if ($id !== '') {
                        $nextIds[$id] = true;
                    }
                    $this->insertLabel($insert, $label, false, $timestamp);
                }
            }
            foreach ($systemLabels as $label) {
                if (is_array($label)) {
                    $id = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
                    if ($id !== '') {
                        $nextIds[$id] = true;
                    }
                    $this->insertLabel($insert, $label, true, $timestamp);
                }
            }
            $existingRows = $this->pdo->query('SELECT id FROM archiving_labels')->fetchAll();
            $delete = $this->pdo->prepare('DELETE FROM archiving_labels WHERE id = :id');
            foreach (is_array($existingRows) ? $existingRows : [] as $row) {
                $id = is_array($row) && is_string($row['id'] ?? null) ? trim((string) $row['id']) : '';
                if ($id !== '' && !isset($nextIds[$id])) {
                    $delete->execute([':id' => $id]);
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

    private function rowToLabel(array $row): ?array
    {
        $id = is_string($row['id'] ?? null) ? trim((string) $row['id']) : '';
        $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
        if ($id === '' || $name === '') {
            return null;
        }
        $rules = [];
        $decoded = json_decode(is_string($row['rules_json'] ?? null) ? (string) $row['rules_json'] : '[]', true);
        if (is_array($decoded)) {
            $rules = array_values(array_filter($decoded, static fn($rule): bool => is_array($rule)));
        }

        return [
            'id' => $id,
            'name' => $name,
            'description' => is_string($row['description'] ?? null) ? (string) $row['description'] : '',
            'minScore' => max(1, (int) ($row['min_score'] ?? 1)),
            'systemLabelKey' => is_string($row['system_label_key'] ?? null) ? trim((string) $row['system_label_key']) : '',
            'rules' => $rules,
        ];
    }

    private function insertLabel(\PDOStatement $insert, array $label, bool $isSystem, string $timestamp): void
    {
        $id = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
        $name = is_string($label['name'] ?? null) ? trim((string) $label['name']) : '';
        if ($id === '' || $name === '') {
            return;
        }
        $rulesJson = json_encode(
            array_values(array_filter(is_array($label['rules'] ?? null) ? $label['rules'] : [], static fn($rule): bool => is_array($rule))),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if (!is_string($rulesJson)) {
            throw new RuntimeException('Could not encode label rules.');
        }
        $systemLabelKey = $isSystem && is_string($label['systemLabelKey'] ?? null)
            ? trim((string) $label['systemLabelKey'])
            : '';

        $insert->execute([
            ':id' => $id,
            ':name' => $name,
            ':description' => is_string($label['description'] ?? null) ? (string) $label['description'] : '',
            ':min_score' => max(1, (int) ($label['minScore'] ?? 1)),
            ':is_system' => $isSystem ? 1 : 0,
            ':system_label_key' => $systemLabelKey,
            ':rules_json' => $rulesJson,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);
    }
}
