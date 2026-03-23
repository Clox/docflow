<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $rules = load_draft_archiving_rules();
    json_response([
        'labels' => is_array($rules['labels'] ?? null) ? $rules['labels'] : [],
        'systemLabels' => is_array($rules['systemLabels'] ?? null) ? $rules['systemLabels'] : system_labels_template(),
        'activeArchivingRulesVersion' => active_archiving_rules_version(),
        'hasUnpublishedChanges' => archiving_rules_have_unpublished_changes(),
    ]);
} catch (Throwable $e) {
    json_response([
        'labels' => [],
        'systemLabels' => system_labels_template(),
        'error' => $e->getMessage(),
    ], 500);
}
