<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $rules = load_draft_archiving_rules();
    json_response([
        'fields' => is_array($rules['fields'] ?? null) ? $rules['fields'] : [],
        'predefinedFields' => is_array($rules['predefinedFields'] ?? null) ? $rules['predefinedFields'] : [],
        'systemFields' => is_array($rules['systemFields'] ?? null) ? $rules['systemFields'] : [],
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => active_archiving_rules_version(),
        'hasUnpublishedChanges' => archiving_rules_have_unpublished_changes(),
    ]);
} catch (Throwable $e) {
    json_response([
        'fields' => [],
        'predefinedFields' => [],
        'systemFields' => [],
        'error' => $e->getMessage(),
    ], 500);
}
