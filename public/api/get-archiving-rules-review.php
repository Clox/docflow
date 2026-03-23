<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $review = collect_archiving_rules_review($config);
    $state = load_archiving_rules_state();
    $active = is_array($state['activeArchivingRules'] ?? null) ? $state['activeArchivingRules'] : [];
    $draft = is_array($state['draftArchivingRules'] ?? null) ? $state['draftArchivingRules'] : [];
    $changedSections = [];
    foreach ([
        'archiveFolders' => 'Arkivstruktur',
        'labels' => 'Etiketter',
        'systemLabels' => 'Fördefinerade etiketter',
        'fields' => 'Egna datafält',
        'predefinedFields' => 'Fördefinerade datafält',
        'systemFields' => 'Systemdatafält',
    ] as $key => $label) {
        if (json_encode($active[$key] ?? null) !== json_encode($draft[$key] ?? null)) {
            $changedSections[] = $label;
        }
    }
    json_response([
        'activeArchivingRulesVersion' => (int) ($state['activeArchivingRulesVersion'] ?? 1),
        'hasUnpublishedChanges' => json_encode($state['activeArchivingRules'] ?? null) !== json_encode($state['draftArchivingRules'] ?? null),
        'changedSections' => $changedSections,
        'summary' => is_array($review['summary'] ?? null) ? $review['summary'] : [],
        'jobs' => is_array($review['jobs'] ?? null) ? $review['jobs'] : [],
        'session' => is_array($review['session'] ?? null) ? $review['session'] : [
            'status' => 'idle',
            'analyzedCount' => 0,
            'totalCount' => 0,
            'foundCount' => 0,
            'remainingCount' => 0,
        ],
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
