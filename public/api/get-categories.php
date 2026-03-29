<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $rules = load_draft_archiving_rules();
    json_response([
        'archiveFolders' => is_array($rules['archiveFolders'] ?? null) ? $rules['archiveFolders'] : [],
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => active_archiving_rules_version(),
        'hasUnpublishedChanges' => archiving_rules_have_unpublished_changes(),
    ]);
} catch (Throwable $e) {
    json_response([
        'archiveFolders' => [],
        'error' => $e->getMessage(),
    ], 500);
}
