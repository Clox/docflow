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

    $reprocessedJobs = reprocess_unarchived_jobs_for_analysis_change($config, 'post-ocr', false);

    $activeRules = load_active_archiving_rules();
    $activeVersion = active_archiving_rules_version();
    $currentState = load_archiving_rules_review_state();
    $currentSession = is_array($currentState['updateSession'] ?? null)
        ? $currentState['updateSession']
        : empty_archiving_update_session();

    restart_archiving_update_session($config, $activeRules, $activeRules, $activeVersion, [
        'reason' => is_string($currentSession['reason'] ?? null) ? (string) $currentSession['reason'] : 'manual-reanalysis',
        'changedSections' => is_array($currentSession['changedSections'] ?? null) ? $currentSession['changedSections'] : [],
        'templateChanges' => is_array($currentSession['templateChanges'] ?? null) ? $currentSession['templateChanges'] : [],
        'ignoreDismissed' => false,
    ]);
    collect_archiving_update_review($config, 500);

    json_response([
        'ok' => true,
        'archivingRules' => build_archiving_rules_state_payload($config),
        'reprocessedJobs' => $reprocessedJobs,
        'lastEventId' => latest_job_event_id(),
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
