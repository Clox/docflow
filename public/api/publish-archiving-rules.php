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
    $result = publish_draft_archiving_rules($config);
    maybe_queue_archiving_rules_update_event($config);
    json_response([
        'ok' => true,
        'activeArchivingRulesVersion' => (int) ($result['activeArchivingRulesVersion'] ?? 1),
        'hasUnpublishedChanges' => false,
        'reprocessedJobs' => $result['reprocessedJobs'] ?? ['reprocessedJobIds' => [], 'reprocessedCount' => 0],
        'flaggedArchivedJobs' => $result['flaggedArchivedJobs'] ?? ['flaggedJobIds' => [], 'flaggedCount' => 0],
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
