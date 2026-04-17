<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $archivingRules = build_archiving_rules_state_payload($config);
    json_response(is_array($archivingRules['updateReview'] ?? null) ? $archivingRules['updateReview'] : [
        'activeArchivingRulesVersion' => 1,
        'changedSections' => [],
        'templateChanges' => [],
        'summary' => empty_archiving_review_summary(),
        'jobs' => [],
        'session' => [
            'status' => 'idle',
            'ignoreDismissed' => false,
            'analyzedCount' => 0,
            'totalCount' => 0,
            'foundCount' => 0,
            'remainingCount' => 0,
        ],
        'reason' => '',
        'signature' => '',
    ]);
} catch (Throwable $e) {
    json_response([
        'activeArchivingRulesVersion' => 1,
        'changedSections' => [],
        'templateChanges' => [],
        'summary' => [],
        'jobs' => [],
        'session' => [
            'status' => 'idle',
            'ignoreDismissed' => false,
            'analyzedCount' => 0,
            'totalCount' => 0,
            'foundCount' => 0,
            'remainingCount' => 0,
        ],
        'reason' => '',
        'error' => $e->getMessage(),
    ], 500);
}
