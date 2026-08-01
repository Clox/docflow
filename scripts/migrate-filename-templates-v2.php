#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

use Docflow\Database\Connection;

function validate_filename_template_sequence_for_v2_migration(mixed $parts, string $location, int $depth = 0): void
{
    if ($depth > 12) {
        throw new RuntimeException("Filename template nesting is too deep at {$location}.");
    }
    if (!is_array($parts)) {
        throw new RuntimeException("Filename template sequence is invalid at {$location}.");
    }
    $supportedTypes = ['text', 'dataField', 'systemField', 'folder', 'labels', 'firstAvailable', 'ifLabels', 'sequence', 'prefix', 'suffix'];
    foreach ($parts as $index => $part) {
        $nodeLocation = "{$location}.parts[{$index}]";
        if (!is_array($part)) {
            throw new RuntimeException("Filename template node is invalid at {$nodeLocation}.");
        }
        $type = is_string($part['type'] ?? null) ? trim((string) $part['type']) : 'text';
        if (!in_array($type, $supportedTypes, true)) {
            throw new RuntimeException("Unknown filename template node type '{$type}' at {$nodeLocation}.");
        }
        foreach (['prefixParts', 'suffixParts'] as $legacyKey) {
            if (array_key_exists($legacyKey, $part)) {
                validate_filename_template_sequence_for_v2_migration($part[$legacyKey], "{$nodeLocation}.{$legacyKey}", $depth + 1);
            }
        }
        if ($type === 'ifLabels') {
            validate_filename_template_sequence_for_v2_migration($part['thenParts'] ?? [], "{$nodeLocation}.thenParts", $depth + 1);
            validate_filename_template_sequence_for_v2_migration($part['elseParts'] ?? [], "{$nodeLocation}.elseParts", $depth + 1);
        } elseif (in_array($type, ['firstAvailable', 'sequence'], true)) {
            validate_filename_template_sequence_for_v2_migration($part['parts'] ?? [], "{$nodeLocation}.parts", $depth + 1);
        } elseif (in_array($type, ['prefix', 'suffix'], true) && array_key_exists('parts', $part)) {
            validate_filename_template_sequence_for_v2_migration($part['parts'], "{$nodeLocation}.parts", $depth + 1);
        }
    }
}

function validate_archiving_rules_filename_templates_for_v2_migration(array $rules, string $stateName): void
{
    $folders = $rules['archiveFolders'] ?? [];
    if (!is_array($folders)) {
        throw new RuntimeException("Archive folder list is invalid in {$stateName} rules.");
    }
    foreach ($folders as $folderIndex => $folder) {
        if (!is_array($folder)) {
            throw new RuntimeException("Archive folder {$folderIndex} is invalid in {$stateName} rules.");
        }
        if (array_key_exists('pathTemplate', $folder)) {
            $template = $folder['pathTemplate'];
            if (is_string($template)) {
                continue;
            }
            if (!is_array($template)) {
                throw new RuntimeException("Path template is invalid at {$stateName}.archiveFolders[{$folderIndex}].pathTemplate.");
            }
            validate_filename_template_sequence_for_v2_migration(
                $template['parts'] ?? [],
                "{$stateName}.archiveFolders[{$folderIndex}].pathTemplate"
            );
        }
        $templates = $folder['filenameTemplates'] ?? [];
        if (!is_array($templates)) {
            throw new RuntimeException("Filename template list is invalid at {$stateName}.archiveFolders[{$folderIndex}].");
        }
        foreach ($templates as $templateIndex => $definition) {
            if (!is_array($definition)) {
                throw new RuntimeException("Filename template definition is invalid at {$stateName}.archiveFolders[{$folderIndex}].filenameTemplates[{$templateIndex}].");
            }
            $template = $definition['template'] ?? ($definition['filenameTemplate'] ?? null);
            if (is_string($template)) {
                continue;
            }
            if (!is_array($template)) {
                throw new RuntimeException("Filename template is invalid at {$stateName}.archiveFolders[{$folderIndex}].filenameTemplates[{$templateIndex}].");
            }
            validate_filename_template_sequence_for_v2_migration(
                $template['parts'] ?? [],
                "{$stateName}.archiveFolders[{$folderIndex}].filenameTemplates[{$templateIndex}].template"
            );
        }
    }
}

function migrate_stored_filename_templates_v2(?PDO $pdo = null): array
{
    $pdo ??= Connection::make();
    $migrationKey = 'filename_templates_v2';
    $already = $pdo->prepare('SELECT statistics_json FROM filename_template_migration_log WHERE migration_key = :key');
    $already->execute([':key' => $migrationKey]);
    $existing = $already->fetchColumn();
    if (is_string($existing) && $existing !== '') {
        $stats = json_decode($existing, true);
        return ['alreadyMigrated' => true, 'statistics' => is_array($stats) ? $stats : []];
    }

    $pdo->beginTransaction();
    try {
        $row = $pdo->query(
            'SELECT active_archiving_rules_json, draft_archiving_rules_json FROM archiving_rules_state WHERE id = 1'
        )->fetch();
        if (!is_array($row)) {
            throw new RuntimeException('Archiving rules state is missing.');
        }
        $activeRaw = (string) ($row['active_archiving_rules_json'] ?? '');
        $draftRaw = (string) ($row['draft_archiving_rules_json'] ?? '');
        $active = json_decode($activeRaw, true, 512, JSON_THROW_ON_ERROR);
        $draft = json_decode($draftRaw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($active) || !is_array($draft)) {
            throw new RuntimeException('Archiving rules state has an invalid JSON structure.');
        }
        validate_archiving_rules_filename_templates_for_v2_migration($active, 'active');
        validate_archiving_rules_filename_templates_for_v2_migration($draft, 'draft');
        $stats = [
            'migratedTemplates' => 0,
            'migratedPathTemplates' => 0,
            'migratedMainNodes' => 0,
            'createdPrefixNodes' => 0,
            'createdSuffixNodes' => 0,
        ];
        $active = migrate_archiving_rules_filename_templates_to_v2($active, $stats);
        $draft = migrate_archiving_rules_filename_templates_to_v2($draft, $stats);
        $activeJson = json_encode($active, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $draftJson = json_encode($draft, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $statement = $pdo->prepare(
            'UPDATE archiving_rules_state
             SET active_archiving_rules_json = :active, draft_archiving_rules_json = :draft, updated_at = :updated
             WHERE id = 1'
        );
        $statement->execute([
            ':active' => $activeJson,
            ':draft' => $draftJson,
            ':updated' => date(DATE_ATOM),
        ]);
        $log = $pdo->prepare(
            'INSERT INTO filename_template_migration_log (
                migration_key, migrated_at, active_rules_backup_json, draft_rules_backup_json, statistics_json
             ) VALUES (:key, :at, :active_backup, :draft_backup, :statistics)'
        );
        $log->execute([
            ':key' => $migrationKey,
            ':at' => date(DATE_ATOM),
            ':active_backup' => $activeRaw,
            ':draft_backup' => $draftRaw,
            ':statistics' => json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);
        $pdo->commit();
        return ['alreadyMigrated' => false, 'statistics' => $stats];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $result = migrate_stored_filename_templates_v2();
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
}
