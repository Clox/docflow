<?php
declare(strict_types=1);

namespace Docflow\Archiving;

use PDO;
use RuntimeException;

final class ZoneRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function hasAnyRows(): bool
    {
        $statement = $this->pdo->query('SELECT 1 FROM archiving_zones LIMIT 1');
        return $statement->fetchColumn() !== false;
    }

    public function loadScope(string $scope): array
    {
        $resolvedScope = $this->normalizeScope($scope);
        $statement = $this->pdo->prepare(
            'SELECT zone_key, name, enabled, pattern, is_regex, pattern_source, value_pattern_id
            FROM archiving_zones
            WHERE rules_scope = :rules_scope
            ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute([':rules_scope' => $resolvedScope]);

        $zones = [];
        foreach ($statement->fetchAll() ?: [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = is_string($row['zone_key'] ?? null) ? trim((string) $row['zone_key']) : '';
            $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
            $pattern = is_string($row['pattern'] ?? null) ? trim((string) $row['pattern']) : '';
            $patternSource = $this->normalizePatternSource($row['pattern_source'] ?? null);
            $valuePatternId = is_string($row['value_pattern_id'] ?? null) ? trim((string) $row['value_pattern_id']) : '';
            if ($id === '' || $name === '' || ($patternSource === 'manual' && $pattern === '') || ($patternSource === 'reference' && $valuePatternId === '')) {
                continue;
            }
            $zones[] = [
                'id' => $id,
                'name' => $name,
                'enabled' => ((int) ($row['enabled'] ?? 1)) === 1,
                'pattern' => $pattern,
                'isRegex' => true,
                'patternSource' => $patternSource,
                'valuePatternId' => $valuePatternId,
            ];
        }

        return $zones;
    }

    public function replaceScopes(array $activeZones, array $draftZones): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->replaceScopeInTransaction('active', $activeZones);
            $this->replaceScopeInTransaction('draft', $draftZones);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function replaceScopeInTransaction(string $scope, array $zones): void
    {
        $resolvedScope = $this->normalizeScope($scope);
        $timestamp = date(DATE_ATOM);
        $this->pdo->prepare('DELETE FROM archiving_zones WHERE rules_scope = :rules_scope')
            ->execute([':rules_scope' => $resolvedScope]);

        $insert = $this->pdo->prepare(
            'INSERT INTO archiving_zones (
                rules_scope,
                zone_key,
                name,
                enabled,
                pattern,
                is_regex,
                pattern_source,
                value_pattern_id,
                sort_order,
                created_at,
                updated_at
            ) VALUES (
                :rules_scope,
                :zone_key,
                :name,
                :enabled,
                :pattern,
                :is_regex,
                :pattern_source,
                :value_pattern_id,
                :sort_order,
                :created_at,
                :updated_at
            )'
        );

        $sortOrder = 0;
        foreach ($zones as $zone) {
            if (!is_array($zone)) {
                continue;
            }
            $id = is_string($zone['id'] ?? null) ? trim((string) $zone['id']) : '';
            $name = is_string($zone['name'] ?? null) ? trim((string) $zone['name']) : '';
            $pattern = is_string($zone['pattern'] ?? null) ? trim((string) $zone['pattern']) : '';
            $patternSource = $this->normalizePatternSource($zone['patternSource'] ?? null);
            $valuePatternId = is_string($zone['valuePatternId'] ?? null) ? trim((string) $zone['valuePatternId']) : '';
            if ($id === '' || $name === '' || ($patternSource === 'manual' && $pattern === '') || ($patternSource === 'reference' && $valuePatternId === '')) {
                continue;
            }
            if (!$insert->execute([
                ':rules_scope' => $resolvedScope,
                ':zone_key' => $id,
                ':name' => $name,
                ':enabled' => ($zone['enabled'] ?? true) ? 1 : 0,
                ':pattern' => $pattern,
                ':is_regex' => ($zone['isRegex'] ?? false) ? 1 : 0,
                ':pattern_source' => $patternSource,
                ':value_pattern_id' => $valuePatternId,
                ':sort_order' => $sortOrder,
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ])) {
                throw new RuntimeException('Could not persist zone.');
            }
            $sortOrder++;
        }
    }

    private function normalizeScope(string $scope): string
    {
        $resolved = trim(strtolower($scope));
        if ($resolved !== 'active' && $resolved !== 'draft') {
            throw new RuntimeException('Invalid zone scope.');
        }
        return $resolved;
    }

    private function normalizePatternSource(mixed $value): string
    {
        return is_string($value) && trim(strtolower($value)) === 'reference' ? 'reference' : 'manual';
    }
}
