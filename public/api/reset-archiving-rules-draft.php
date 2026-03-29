<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

try {
    $config = load_config();
    ensure_job_dispatcher_running($config);
    $state = reset_draft_archiving_rules_to_active();
    maybe_queue_archiving_rules_update_event($config);
    json_response([
        'ok' => true,
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => (int) ($state['activeArchivingRulesVersion'] ?? 1),
        'hasUnpublishedChanges' => false,
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
