<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $archivingRules = build_archiving_rules_state_payload($config);
    json_response(is_array($archivingRules['draftReview'] ?? null) ? $archivingRules['draftReview'] : [
        'activeArchivingRulesVersion' => 1,
        'hasUnpublishedChanges' => false,
        'changedSections' => [],
        'summary' => empty_archiving_review_summary(),
        'jobs' => [],
        'session' => [
            'status' => 'idle',
            'analyzedCount' => 0,
            'totalCount' => 0,
            'foundCount' => 0,
            'remainingCount' => 0,
        ],
        'signature' => '',
    ]);
} catch (Throwable $e) {
    json_response([
        'activeArchivingRulesVersion' => 1,
        'hasUnpublishedChanges' => false,
        'changedSections' => [],
        'summary' => [],
        'jobs' => [],
        'session' => [
            'status' => 'idle',
            'analyzedCount' => 0,
            'totalCount' => 0,
            'foundCount' => 0,
            'remainingCount' => 0,
        ],
        'error' => $e->getMessage(),
    ], 500);
}
