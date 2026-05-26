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
                value_type,
                normalization_type,
                normalization_chars,
                normalization_replacements_json,
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
            $valueType = $this->normalizeValueType($row['value_type'] ?? null);
            if ($fieldId < 1 || $fieldKey === '' || $name === '') {
                continue;
            }

            $field = [
                'key' => $fieldKey,
                'name' => $name,
                'type' => $this->legacyRuleTypeForValueType($valueType),
                'valueType' => $valueType,
                'normalizationType' => $this->normalizeNormalizationType($row['normalization_type'] ?? null),
                'normalizationChars' => is_string($row['normalization_chars'] ?? null) ? (string) $row['normalization_chars'] : '',
                'normalizationReplacements' => $this->normalizeNormalizationReplacementsForStorage(json_decode((string) ($row['normalization_replacements_json'] ?? '[]'), true)),
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
        $timestamp = date(DATE_ATOM);
        $existingFields = $this->existingFieldIdsByTypeAndKey($resolvedScope);
        $seenFieldKeys = [];
        $insertField = $this->pdo->prepare(
            'INSERT INTO archiving_data_fields (
                rules_scope,
                field_type,
                field_key,
                name,
                value_type,
                normalization_type,
                normalization_chars,
                normalization_replacements_json,
                sort_order,
                created_at,
                updated_at
            ) VALUES (
                :rules_scope,
                :field_type,
                :field_key,
                :name,
                :value_type,
                :normalization_type,
                :normalization_chars,
                :normalization_replacements_json,
                :sort_order,
                :created_at,
                :updated_at
            )'
        );
        $updateField = $this->pdo->prepare(
            'UPDATE archiving_data_fields
            SET name = :name,
                value_type = :value_type,
                normalization_type = :normalization_type,
                normalization_chars = :normalization_chars,
                normalization_replacements_json = :normalization_replacements_json,
                sort_order = :sort_order,
                updated_at = :updated_at
            WHERE id = :id'
        );
        $deleteRuleSets = $this->pdo->prepare(
            'DELETE FROM archiving_data_field_rule_sets WHERE data_field_id = :data_field_id'
        );
        $insertRuleSet = $this->pdo->prepare(
            'INSERT INTO archiving_data_field_rule_sets (
                data_field_id,
                requires_search_terms,
                search_terms_json,
                use_value_pattern,
                value_pattern,
                scope_json,
                normalization_type,
                normalization_chars,
                normalization_replacements_json,
                capture_group,
                amount_whole_group,
                amount_fraction_group,
                date_position,
                amount_position,
                sort_order,
                created_at,
                updated_at
            ) VALUES (
                :data_field_id,
                :requires_search_terms,
                :search_terms_json,
                :use_value_pattern,
                :value_pattern,
                :scope_json,
                :normalization_type,
                :normalization_chars,
                :normalization_replacements_json,
                :capture_group,
                :amount_whole_group,
                :amount_fraction_group,
                :date_position,
                :amount_position,
                :sort_order,
                :created_at,
                :updated_at
            )'
        );

        $persistFields = function (array $fields, string $fieldType) use (
            $resolvedScope,
            $timestamp,
            $existingFields,
            &$seenFieldKeys,
            $insertField,
            $updateField,
            $deleteRuleSets,
            $insertRuleSet
        ): void {
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
                $valueType = $this->normalizeValueType($field['valueType'] ?? null, $field);
                if ($fieldKey === '' || $name === '') {
                    continue;
                }
                $fieldNormalizationReplacementsJson = json_encode(
                    $this->normalizeNormalizationReplacementsForStorage($field['normalizationReplacements'] ?? null),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                if (!is_string($fieldNormalizationReplacementsJson)) {
                    throw new RuntimeException('Could not encode extraction field normalization replacements.');
                }
                $lookupKey = $fieldType . ':' . $fieldKey;
                $seenFieldKeys[$lookupKey] = true;

                $fieldId = isset($existingFields[$lookupKey]) ? (int) $existingFields[$lookupKey] : 0;
                if ($fieldId > 0) {
                    $updateField->execute([
                        ':id' => $fieldId,
                        ':name' => $name,
                        ':value_type' => $valueType,
                        ':normalization_type' => $this->normalizeNormalizationType($field['normalizationType'] ?? null),
                        ':normalization_chars' => is_string($field['normalizationChars'] ?? null) ? (string) $field['normalizationChars'] : '',
                        ':normalization_replacements_json' => $fieldNormalizationReplacementsJson,
                        ':sort_order' => $fieldOrder,
                        ':updated_at' => $timestamp,
                    ]);
                } else {
                    $insertField->execute([
                        ':rules_scope' => $resolvedScope,
                        ':field_type' => $fieldType,
                        ':field_key' => $fieldKey,
                        ':name' => $name,
                        ':value_type' => $valueType,
                        ':normalization_type' => $this->normalizeNormalizationType($field['normalizationType'] ?? null),
                        ':normalization_chars' => is_string($field['normalizationChars'] ?? null) ? (string) $field['normalizationChars'] : '',
                        ':normalization_replacements_json' => $fieldNormalizationReplacementsJson,
                        ':sort_order' => $fieldOrder,
                        ':created_at' => $timestamp,
                        ':updated_at' => $timestamp,
                    ]);
                    $fieldId = (int) $this->pdo->lastInsertId();
                }
                if ($fieldId < 1) {
                    throw new RuntimeException('Could not persist extraction field.');
                }
                $deleteRuleSets->execute([':data_field_id' => $fieldId]);

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
                    $scopeJson = json_encode(
                        $this->normalizeRuleSetScopeForStorage($ruleSet['scope'] ?? null),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );
                    if (!is_string($scopeJson)) {
                        throw new RuntimeException('Could not encode field rule-set scope.');
                    }
                    $normalizationReplacementsJson = json_encode(
                        $this->normalizeNormalizationReplacementsForStorage($ruleSet['normalizationReplacements'] ?? null),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );
                    if (!is_string($normalizationReplacementsJson)) {
                        throw new RuntimeException('Could not encode field rule-set normalization replacements.');
                    }

                    $insertRuleSet->execute([
                        ':data_field_id' => $fieldId,
                        ':requires_search_terms' => ($ruleSet['requiresSearchTerms'] ?? true) ? 1 : 0,
                        ':search_terms_json' => $searchTermsJson,
                        ':use_value_pattern' => ($ruleSet['useValuePattern'] ?? false) ? 1 : 0,
                        ':value_pattern' => is_string($ruleSet['valuePattern'] ?? null) ? trim((string) $ruleSet['valuePattern']) : '',
                        ':scope_json' => $scopeJson,
                        ':normalization_type' => is_string($ruleSet['normalizationType'] ?? null) ? trim((string) $ruleSet['normalizationType']) : 'none',
                        ':normalization_chars' => is_string($ruleSet['normalizationChars'] ?? null) ? (string) $ruleSet['normalizationChars'] : '',
                        ':normalization_replacements_json' => $normalizationReplacementsJson,
                        ':capture_group' => $this->normalizeCaptureGroupForStorage($ruleSet['captureGroup'] ?? null),
                        ':amount_whole_group' => $this->normalizeCaptureGroupForStorage($ruleSet['amountWholeGroup'] ?? null),
                        ':amount_fraction_group' => $this->normalizeCaptureGroupForStorage($ruleSet['amountFractionGroup'] ?? null),
                        ':date_position' => is_string($ruleSet['datePosition'] ?? null) && in_array(trim(strtolower((string) $ruleSet['datePosition'])), ['first', 'second', 'last'], true)
                            ? trim(strtolower((string) $ruleSet['datePosition']))
                            : 'first',
                        ':amount_position' => is_string($ruleSet['amountPosition'] ?? null) && in_array(trim(strtolower((string) $ruleSet['amountPosition'])), ['first', 'second', 'last'], true)
                            ? trim(strtolower((string) $ruleSet['amountPosition']))
                            : 'first',
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

        $deleteRemovedField = $this->pdo->prepare(
            'DELETE FROM archiving_data_fields WHERE id = :id'
        );
        foreach ($existingFields as $lookupKey => $fieldId) {
            if (!isset($seenFieldKeys[$lookupKey])) {
                $deleteRemovedField->execute([':id' => (int) $fieldId]);
            }
        }
    }

    private function existingFieldIdsByTypeAndKey(string $scope): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, field_type, field_key
            FROM archiving_data_fields
            WHERE rules_scope = :rules_scope'
        );
        $statement->execute([':rules_scope' => $scope]);
        $rows = $statement->fetchAll();
        $ids = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            $fieldType = is_string($row['field_type'] ?? null) ? trim((string) $row['field_type']) : '';
            $fieldKey = is_string($row['field_key'] ?? null) ? trim((string) $row['field_key']) : '';
            if ($id > 0 && $fieldType !== '' && $fieldKey !== '') {
                $ids[$fieldType . ':' . $fieldKey] = $id;
            }
        }

        return $ids;
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
                use_value_pattern,
                value_pattern,
                scope_json,
                normalization_type,
                normalization_chars,
                normalization_replacements_json,
                capture_group,
                amount_whole_group,
                amount_fraction_group,
                date_position,
                amount_position,
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
            $scope = json_decode((string) ($row['scope_json'] ?? '{}'), true);
            $normalizationReplacements = json_decode((string) ($row['normalization_replacements_json'] ?? '[]'), true);
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
                'useValuePattern' => ((int) ($row['use_value_pattern'] ?? 0)) === 1,
                'valuePattern' => is_string($row['value_pattern'] ?? null) ? trim((string) $row['value_pattern']) : '',
                'scope' => $this->normalizeRuleSetScopeForStorage(is_array($scope) ? $scope : null) ?: null,
                'normalizationType' => is_string($row['normalization_type'] ?? null) ? trim((string) $row['normalization_type']) : 'none',
                'normalizationChars' => is_string($row['normalization_chars'] ?? null) ? (string) $row['normalization_chars'] : '',
                'normalizationReplacements' => $this->normalizeNormalizationReplacementsForStorage($normalizationReplacements),
                'captureGroup' => $this->normalizeCaptureGroupForOutput($row['capture_group'] ?? null),
                'amountWholeGroup' => $this->normalizeCaptureGroupForOutput($row['amount_whole_group'] ?? null),
                'amountFractionGroup' => $this->normalizeCaptureGroupForOutput($row['amount_fraction_group'] ?? null),
                'datePosition' => is_string($row['date_position'] ?? null) ? trim(strtolower((string) $row['date_position'])) : 'first',
                'amountPosition' => is_string($row['amount_position'] ?? null) ? trim(strtolower((string) $row['amount_position'])) : 'first',
            ];
        }

        return $byFieldId;
    }

    private function normalizeRuleSetScopeForStorage(mixed $scope): array
    {
        if (!is_array($scope)) {
            return [];
        }

        $type = is_string($scope['type'] ?? null) ? trim(strtolower((string) $scope['type'])) : '';
        $text = is_string($scope['text'] ?? null) ? trim((string) $scope['text']) : '';
        if ($type !== 'after_text' || $text === '') {
            return [];
        }

        return [
            'type' => 'after_text',
            'text' => $text,
            'isRegex' => ($scope['isRegex'] ?? false) === true
                || ($scope['isRegex'] ?? false) === 1
                || ($scope['isRegex'] ?? false) === '1',
        ];
    }

    private function normalizeNormalizationType(mixed $value): string
    {
        $normalized = is_string($value) ? trim(strtolower($value)) : '';
        return in_array($normalized, ['whitelist', 'blacklist', 'replacements'], true) ? $normalized : 'none';
    }

    private function normalizeNormalizationReplacementsForStorage(mixed $replacements): array
    {
        $rows = is_array($replacements) ? $replacements : [];
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $find = is_string($row['find'] ?? null)
                ? (string) $row['find']
                : (is_string($row['from'] ?? null) ? (string) $row['from'] : '');
            if ($find === '') {
                continue;
            }

            $normalized[] = [
                'find' => $find,
                'replace' => is_string($row['replace'] ?? null)
                    ? (string) $row['replace']
                    : (is_string($row['to'] ?? null) ? (string) $row['to'] : ''),
                'isRegex' => ($row['isRegex'] ?? false) === true
                    || ($row['isRegex'] ?? false) === 1
                    || ($row['isRegex'] ?? false) === '1',
            ];
        }

        return $normalized;
    }

    private function normalizeValueType(mixed $value, ?array $legacyField = null): string
    {
        $normalized = is_string($value) ? trim(strtolower($value)) : '';
        if (in_array($normalized, ['text', 'date', 'amount'], true)) {
            return $normalized;
        }

        $legacy = is_array($legacyField) ? $legacyField : [];
        $legacyType = is_string($legacy['type'] ?? null) ? trim(strtolower((string) $legacy['type'])) : '';
        $legacyKey = is_string($legacy['predefinedFieldKey'] ?? null)
            ? trim((string) $legacy['predefinedFieldKey'])
            : (is_string($legacy['key'] ?? null) ? trim((string) $legacy['key']) : '');
        $legacyExtractor = is_string($legacy['extractor'] ?? null) ? trim(strtolower((string) $legacy['extractor'])) : '';

        if ($legacyType === 'amount' || $legacyKey === 'amount' || $legacyExtractor === 'amount') {
            return 'amount';
        }
        if ($legacyType === 'date' || in_array($legacyKey, ['due_date', 'document_date'], true) || in_array($legacyExtractor, ['due_date', 'document_date'], true)) {
            return 'date';
        }

        return 'text';
    }

    private function legacyRuleTypeForValueType(string $valueType): string
    {
        return match ($valueType) {
            'date' => 'date',
            'amount' => 'amount',
            default => 'regex',
        };
    }

    private function normalizeCaptureGroupForStorage(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        $group = (int) $value;
        return $group >= 0 ? $group : null;
    }

    private function normalizeCaptureGroupForOutput(mixed $value): ?int
    {
        return $this->normalizeCaptureGroupForStorage($value);
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
