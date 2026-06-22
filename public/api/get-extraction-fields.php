<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $rules = load_active_archiving_rules();
    json_response([
        'fields' => is_array($rules['fields'] ?? null) ? $rules['fields'] : [],
        'predefinedFields' => is_array($rules['predefinedFields'] ?? null) ? $rules['predefinedFields'] : [],
        'systemFields' => is_array($rules['systemFields'] ?? null) ? $rules['systemFields'] : [],
        'zones' => is_array($rules['zones'] ?? null) ? $rules['zones'] : [],
        'systemZones' => normalize_system_zones($rules['systemZones'] ?? []),
        'valuePatterns' => is_array($rules['valuePatterns'] ?? null) ? $rules['valuePatterns'] : [],
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => active_archiving_rules_version(),
    ]);
} catch (Throwable $e) {
    json_response([
        'fields' => [],
        'predefinedFields' => [],
        'systemFields' => [],
        'zones' => [],
        'systemZones' => default_system_zones(),
        'valuePatterns' => [],
        'error' => $e->getMessage(),
    ], 500);
}
