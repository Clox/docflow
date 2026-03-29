<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    json_response(['error' => 'Invalid request body'], 400);
    exit;
}

$payload = json_decode($raw, true);
if (
    !is_array($payload)
    || !array_key_exists('fields', $payload)
    || !is_array($payload['fields'])
    || !array_key_exists('predefinedFields', $payload)
    || !is_array($payload['predefinedFields'])
    || !array_key_exists('systemFields', $payload)
    || !is_array($payload['systemFields'])
) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

try {
    $config = load_config();
    ensure_job_dispatcher_running($config);
    $state = load_archiving_rules_state();
    $state['draftArchivingRules']['fields'] = normalize_extraction_fields($payload['fields']);
    $state['draftArchivingRules']['predefinedFields'] = normalize_predefined_extraction_fields($payload['predefinedFields']);
    $state['draftArchivingRules']['systemFields'] = normalize_system_extraction_fields($payload['systemFields']);
    $stored = save_archiving_rules_state($state);
    maybe_advance_draft_archiving_review_session($config, 10);
    maybe_queue_archiving_rules_update_event($config);
    json_response([
        'ok' => true,
        'fields' => is_array($stored['draftArchivingRules']['fields'] ?? null) ? $stored['draftArchivingRules']['fields'] : [],
        'predefinedFields' => is_array($stored['draftArchivingRules']['predefinedFields'] ?? null) ? $stored['draftArchivingRules']['predefinedFields'] : [],
        'systemFields' => is_array($stored['draftArchivingRules']['systemFields'] ?? null) ? $stored['draftArchivingRules']['systemFields'] : [],
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => (int) ($stored['activeArchivingRulesVersion'] ?? 1),
        'hasUnpublishedChanges' => json_encode($stored['activeArchivingRules'] ?? null) !== json_encode($stored['draftArchivingRules'] ?? null),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
