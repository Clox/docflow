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
    $archiveStructure = normalize_archive_structure_data($payload);
    $state = load_archiving_rules_state();
    $nextRules = normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);
    $nextRules['archiveFolders'] = $archiveStructure['archiveFolders'];
    $result = persist_active_archiving_rules_change($config, $nextRules, ['reason' => 'rules']);
    $stored = is_array($result['stored'] ?? null) ? $result['stored'] : $state;
    $activeRules = load_active_archiving_rules();
    json_response([
        'ok' => true,
        'archiveFolders' => is_array($activeRules['archiveFolders'] ?? null)
            ? $activeRules['archiveFolders']
            : [],
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => (int) ($stored['activeArchivingRulesVersion'] ?? 1),
        'reprocessedJobs' => is_array($result['reprocessedJobs'] ?? null) ? $result['reprocessedJobs'] : ['reprocessedJobIds' => [], 'reprocessedCount' => 0],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
