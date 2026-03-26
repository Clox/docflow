<?php
declare(strict_types=1);

namespace Docflow\Archiving;

use PDO;
use RuntimeException;

final class ArchivingRulesStateRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findSingleton(): ?array
    {
        $statement = $this->pdo->query(
            'SELECT id, active_archiving_rules_version, active_archiving_rules_json, draft_archiving_rules_json, created_at, updated_at
            FROM archiving_rules_state
            WHERE id = 1
            LIMIT 1'
        );
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function replaceState(int $version, array $activeRules, array $draftRules): array
    {
        if ($version < 1) {
            throw new RuntimeException('Active archiving rules version must be >= 1.');
        }

        $activeJson = json_encode(
            $activeRules,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $draftJson = json_encode(
            $draftRules,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if (!is_string($activeJson) || !is_string($draftJson)) {
            throw new RuntimeException('Could not encode archiving rules state.');
        }

        $timestamp = date(DATE_ATOM);
        $statement = $this->pdo->prepare(
            'INSERT INTO archiving_rules_state (
                id,
                active_archiving_rules_version,
                active_archiving_rules_json,
                draft_archiving_rules_json,
                created_at,
                updated_at
            ) VALUES (
                1,
                :version,
                :active_json,
                :draft_json,
                :created_at,
                :updated_at
            )
            ON CONFLICT(id) DO UPDATE SET
                active_archiving_rules_version = excluded.active_archiving_rules_version,
                active_archiving_rules_json = excluded.active_archiving_rules_json,
                draft_archiving_rules_json = excluded.draft_archiving_rules_json,
                updated_at = excluded.updated_at'
        );
        $statement->execute([
            ':version' => $version,
            ':active_json' => $activeJson,
            ':draft_json' => $draftJson,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);

        $row = $this->findSingleton();
        if (!is_array($row)) {
            throw new RuntimeException('Could not reload archiving rules state after save.');
        }

        return $row;
    }
}
