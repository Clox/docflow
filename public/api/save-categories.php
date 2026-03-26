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
    $normalizedArchiveFolders = normalize_archive_structure($payload['archiveFolders']);
    $state['draftArchivingRules']['archiveFolders'] = $normalizedArchiveFolders;
    $stored = save_archiving_rules_state($state);
    maybe_advance_draft_archiving_review_session($config, 10);
    maybe_queue_archiving_rules_update_event($config);
    $draftRules = load_draft_archiving_rules();
    json_response([
        'ok' => true,
        'archiveFolders' => is_array($draftRules['archiveFolders'] ?? null)
            ? $draftRules['archiveFolders']
            : [],
        'archivingRules' => build_archiving_rules_state_payload($config),
        'activeArchivingRulesVersion' => (int) ($stored['activeArchivingRulesVersion'] ?? 1),
        'hasUnpublishedChanges' => archiving_rules_have_unpublished_changes(
            is_array($stored['activeArchivingRules'] ?? null) ? $stored['activeArchivingRules'] : [],
            is_array($draftRules ?? null) ? $draftRules : []
        ),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
