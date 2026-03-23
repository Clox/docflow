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
    || !array_key_exists('archiveFolders', $payload)
    || !is_array($payload['archiveFolders'])
) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

try {
    $config = load_config();
    ensure_job_dispatcher_running($config);
    $state = load_archiving_rules_state();
    $state['draftArchivingRules']['archiveFolders'] = normalize_archive_structure($payload['archiveFolders']);
    $stored = save_archiving_rules_state($state);
    maybe_advance_draft_archiving_review_session($config, 10);
    maybe_queue_archiving_rules_update_event($config);
    json_response([
        'ok' => true,
        'archiveFolders' => is_array($stored['draftArchivingRules']['archiveFolders'] ?? null)
            ? $stored['draftArchivingRules']['archiveFolders']
            : [],
        'activeArchivingRulesVersion' => (int) ($stored['activeArchivingRulesVersion'] ?? 1),
        'hasUnpublishedChanges' => json_encode($stored['activeArchivingRules'] ?? null) !== json_encode($stored['draftArchivingRules'] ?? null),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
