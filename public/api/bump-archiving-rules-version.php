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
    $result = bump_active_archiving_rules_version($config, 'manual');
    json_response([
        'ok' => true,
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => (int) ($result['activeArchivingRulesVersion'] ?? 1),
        'reprocessedJobs' => $result['reprocessedJobs'] ?? ['reprocessedJobIds' => [], 'reprocessedCount' => 0],
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
