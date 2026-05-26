<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $rules = load_active_archiving_rules();
    json_response([
        'valuePatterns' => is_array($rules['valuePatterns'] ?? null) ? $rules['valuePatterns'] : [],
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => active_archiving_rules_version(),
    ]);
} catch (Throwable $e) {
    json_response([
        'valuePatterns' => [],
        'error' => $e->getMessage(),
    ], 500);
}
