<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $rules = load_active_archiving_rules();
    json_response([
        'labels' => is_array($rules['labels'] ?? null) ? $rules['labels'] : [],
        'systemLabels' => is_array($rules['systemLabels'] ?? null) ? $rules['systemLabels'] : system_labels_template(),
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => active_archiving_rules_version(),
    ]);
} catch (Throwable $e) {
    json_response([
        'labels' => [],
        'systemLabels' => system_labels_template(),
        'error' => $e->getMessage(),
    ], 500);
}
