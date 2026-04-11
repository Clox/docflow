<?php
declare(strict_types=1);

namespace Docflow\Archiving;

use PDO;
use RuntimeException;

final class ExtractionFieldRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function hasAnyRows(): bool
    {
        $statement = $this->pdo->query('SELECT 1 FROM archiving_data_fields LIMIT 1');
        return $statement->fetchColumn() !== false;
    }

    public function loadScope(string $scope): array
    {
        $resolvedScope = $this->normalizeScope($scope);
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                rules_scope,
                field_type,
                field_key,
                name,
                sort_order,
                created_at,
                updated_at
            FROM archiving_data_fields
            WHERE rules_scope = :rules_scope
            ORDER BY field_type ASC, sort_order ASC, id ASC'
        );
        $statement->execute([':rules_scope' => $resolvedScope]);
        $rows = $statement->fetchAll();
        if (!is_array($rows) || $rows === []) {
            return [
                'fields' => [],
                'predefinedFields' => [],
            ];
        }

        $fieldIds = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $fieldId = isset($row['id']) ? (int) $row['id'] : 0;
            if ($fieldId > 0) {
                $fieldIds[] = $fieldId;
            }
        }

        $ruleSetsByFieldId = $this->loadRuleSetsForFieldIds($fieldIds);

        $customFields = [];
        $predefinedFields = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $fieldId = isset($row['id']) ? (int) $row['id'] : 0;
            $fieldType = is_string($row['field_type'] ?? null) ? trim((string) $row['field_type']) : '';
            $fieldKey = is_string($row['field_key'] ?? null) ? trim((string) $row['field_key']) : '';
            $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
            if ($fieldId < 1 || $fieldKey === '' || $name === '') {
                continue;
            }

            $field = [
                'key' => $fieldKey,
                'name' => $name,
                'ruleSets' => $ruleSetsByFieldId[$fieldId] ?? [],
            ];

            if ($fieldType === 'predefined') {
                $field['predefinedFieldKey'] = $fieldKey;
                $field['isPredefinedField'] = true;
                $predefinedFields[] = $field;
            } else {
                $customFields[] = $field;
            }
        }

        return [
            'fields' => $customFields,
            'predefinedFields' => $predefinedFields,
        ];
    }

    public function replaceScopes(array $active, array $draft): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->replaceScopeInTransaction(
                'active',
                is_array($active['fields'] ?? null) ? $active['fields'] : [],
                is_array($active['predefinedFields'] ?? null) ? $active['predefinedFields'] : []
            );
            $this->replaceScopeInTransaction(
                'draft',
                is_array($draft['fields'] ?? null) ? $draft['fields'] : [],
                is_array($draft['predefinedFields'] ?? null) ? $draft['predefinedFields'] : []
            );
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function replaceScopeInTransaction(string $scope, array $customFields, array $predefinedFields): void
    {
        $resolvedScope = $this->normalizeScope($scope);
        $deleteStatement = $this->pdo->prepare(
            'DELETE FROM archiving_data_fields WHERE rules_scope = :rules_scope'
        );
        $deleteStatement->execute([':rules_scope' => $resolvedScope]);

        $timestamp = date(DATE_ATOM);
        $insertField = $this->pdo->prepare(
            'INSERT INTO archiving_data_fields (
                rules_scope,
                field_type,
                field_key,
                name,
                sort_order,
                created_at,
                updated_at
            ) VALUES (
                :rules_scope,
                :field_type,
                :field_key,
                :name,
                :sort_order,
                :created_at,
                :updated_at
            )'
        );
        $insertRuleSet = $this->pdo->prepare(
            'INSERT INTO archiving_data_field_rule_sets (
                data_field_id,
                requires_search_terms,
                search_terms_json,
                value_pattern,
                normalization_type,
                normalization_chars,
                sort_order,
                created_at,
                updated_at
            ) VALUES (
                :data_field_id,
                :requires_search_terms,
                :search_terms_json,
                :value_pattern,
                :normalization_type,
                :normalization_chars,
                :sort_order,
                :created_at,
                :updated_at
            )'
        );

        $persistFields = function (array $fields, string $fieldType) use ($resolvedScope, $timestamp, $insertField, $insertRuleSet): void {
            $fieldOrder = 0;
            foreach ($fields as $field) {
                if (!is_array($field)) {
                    continue;
                }

                $fieldKey = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
                if ($fieldKey === '' && $fieldType === 'predefined' && is_string($field['predefinedFieldKey'] ?? null)) {
                    $fieldKey = trim((string) $field['predefinedFieldKey']);
                }
                $name = is_string($field['name'] ?? null) ? trim((string) $field['name']) : '';
                if ($fieldKey === '' || $name === '') {
                    continue;
                }

                $insertField->execute([
                    ':rules_scope' => $resolvedScope,
                    ':field_type' => $fieldType,
                    ':field_key' => $fieldKey,
                    ':name' => $name,
                    ':sort_order' => $fieldOrder,
                    ':created_at' => $timestamp,
                    ':updated_at' => $timestamp,
                ]);
                $fieldId = (int) $this->pdo->lastInsertId();
                if ($fieldId < 1) {
                    throw new RuntimeException('Could not persist extraction field.');
                }

                $ruleSets = is_array($field['ruleSets'] ?? null) ? $field['ruleSets'] : [];
                if ($ruleSets === []) {
                    $ruleSets = [[
                        'requiresSearchTerms' => true,
                        'searchTerms' => [],
                        'valuePattern' => '',
                        'normalizationType' => 'none',
                        'normalizationChars' => '',
                    ]];
                }

                $ruleOrder = 0;
                foreach ($ruleSets as $ruleSet) {
                    if (!is_array($ruleSet)) {
                        continue;
                    }

                    $searchTerms = is_array($ruleSet['searchTerms'] ?? null) ? array_values($ruleSet['searchTerms']) : [];
                    $searchTermsJson = json_encode($searchTerms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    if (!is_string($searchTermsJson)) {
                        throw new RuntimeException('Could not encode field rule-set search terms.');
                    }

                    $insertRuleSet->execute([
                        ':data_field_id' => $fieldId,
                        ':requires_search_terms' => ($ruleSet['requiresSearchTerms'] ?? true) ? 1 : 0,
                        ':search_terms_json' => $searchTermsJson,
                        ':value_pattern' => is_string($ruleSet['valuePattern'] ?? null) ? trim((string) $ruleSet['valuePattern']) : '',
                        ':normalization_type' => is_string($ruleSet['normalizationType'] ?? null) ? trim((string) $ruleSet['normalizationType']) : 'none',
                        ':normalization_chars' => is_string($ruleSet['normalizationChars'] ?? null) ? (string) $ruleSet['normalizationChars'] : '',
                        ':sort_order' => $ruleOrder,
                        ':created_at' => $timestamp,
                        ':updated_at' => $timestamp,
                    ]);
                    $ruleOrder += 1;
                }

                $fieldOrder += 1;
            }
        };

        $persistFields($customFields, 'custom');
        $persistFields($predefinedFields, 'predefined');
    }

    private function loadRuleSetsForFieldIds(array $fieldIds): array
    {
        $resolvedIds = [];
        foreach ($fieldIds as $fieldId) {
            $numericId = (int) $fieldId;
            if ($numericId > 0) {
                $resolvedIds[$numericId] = true;
            }
        }
        if ($resolvedIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($resolvedIds), '?'));
        $statement = $this->pdo->prepare(
            'SELECT
                id,
                data_field_id,
                requires_search_terms,
                search_terms_json,
                value_pattern,
                normalization_type,
                normalization_chars,
                sort_order,
                created_at,
                updated_at
            FROM archiving_data_field_rule_sets
            WHERE data_field_id IN (' . $placeholders . ')
            ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute(array_keys($resolvedIds));
        $rows = $statement->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $byFieldId = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $fieldId = isset($row['data_field_id']) ? (int) $row['data_field_id'] : 0;
            if ($fieldId < 1) {
                continue;
            }
            $searchTerms = json_decode((string) ($row['search_terms_json'] ?? '[]'), true);
            $resolvedSearchTerms = [];
            if (is_array($searchTerms)) {
                foreach ($searchTerms as $searchTerm) {
                    if (is_array($searchTerm)) {
                        $text = is_string($searchTerm['text'] ?? null)
                            ? trim((string) $searchTerm['text'])
                            : (is_string($searchTerm['value'] ?? null) ? trim((string) $searchTerm['value']) : '');
                        if ($text === '') {
                            continue;
                        }
                        $resolvedSearchTerms[] = [
                            'text' => $text,
                            'isRegex' => ($searchTerm['isRegex'] ?? false) === true || ($searchTerm['isRegex'] ?? false) === 1 || ($searchTerm['isRegex'] ?? false) === '1',
                        ];
                        continue;
                    }

                    if (!is_string($searchTerm) && !is_numeric($searchTerm)) {
                        continue;
                    }
                    $text = trim((string) $searchTerm);
                    if ($text === '') {
                        continue;
                    }
                    $resolvedSearchTerms[] = $text;
                }
            }

            $byFieldId[$fieldId][] = [
                'requiresSearchTerms' => ((int) ($row['requires_search_terms'] ?? 1)) === 1,
                'searchTerms' => $resolvedSearchTerms,
                'valuePattern' => is_string($row['value_pattern'] ?? null) ? trim((string) $row['value_pattern']) : '',
                'normalizationType' => is_string($row['normalization_type'] ?? null) ? trim((string) $row['normalization_type']) : 'none',
                'normalizationChars' => is_string($row['normalization_chars'] ?? null) ? (string) $row['normalization_chars'] : '',
            ];
        }

        return $byFieldId;
    }

    private function normalizeScope(string $scope): string
    {
        $resolved = trim(strtolower($scope));
        if ($resolved !== 'active' && $resolved !== 'draft') {
            throw new RuntimeException('Invalid extraction field scope.');
        }
        return $resolved;
    }
}
