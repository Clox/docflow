<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    json_response(['error' => 'Invalid request body'], 400);
    exit;
}

$payload = json_decode($raw, true);
if (
    !is_array($payload)
    || !array_key_exists('fields', $payload)
    || !is_array($payload['fields'])
    || !array_key_exists('predefinedFields', $payload)
    || !is_array($payload['predefinedFields'])
    || !array_key_exists('systemFields', $payload)
    || !is_array($payload['systemFields'])
) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

try {
    $config = load_config();
    ensure_job_dispatcher_running($config);
    $currentRules = load_active_archiving_rules();
    $currentFieldNamesByKey = [];
    foreach (is_array($currentRules['fields'] ?? null) ? $currentRules['fields'] : [] as $field) {
        if (!is_array($field)) {
            continue;
        }
        $fieldKey = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        if ($fieldKey === '') {
            continue;
        }
        $currentFieldNamesByKey[$fieldKey] = is_string($field['name'] ?? null) && trim((string) $field['name']) !== ''
            ? trim((string) $field['name'])
            : $fieldKey;
    }
    $state = load_archiving_rules_state();
    $nextRules = normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);
    $nextRules['fields'] = normalize_extraction_fields($payload['fields']);
    $nextRules['predefinedFields'] = normalize_predefined_extraction_fields($payload['predefinedFields']);
    $nextRules['systemFields'] = normalize_system_extraction_fields($payload['systemFields']);
    $nextFieldKeys = [];
    foreach (is_array($nextRules['fields'] ?? null) ? $nextRules['fields'] : [] as $field) {
        if (!is_array($field)) {
            continue;
        }
        $fieldKey = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        if ($fieldKey !== '') {
            $nextFieldKeys[$fieldKey] = true;
        }
    }
    $removedFieldKeys = array_values(array_filter(
        array_keys($currentFieldNamesByKey),
        static fn (string $fieldKey): bool => !isset($nextFieldKeys[$fieldKey])
    ));
    if ($removedFieldKeys !== []) {
        $usageCounts = count_archived_documents_using_field_keys($config, $removedFieldKeys);
        foreach ($removedFieldKeys as $fieldKey) {
            $usageCount = (int) ($usageCounts[$fieldKey] ?? 0);
            if ($usageCount < 1) {
                continue;
            }
            $fieldName = $currentFieldNamesByKey[$fieldKey] ?? $fieldKey;
            throw new RuntimeException(sprintf(
                'Går inte att ta bort datafältet "%s" eftersom det används i %d arkiverade dokument.',
                $fieldName,
                $usageCount
            ));
        }
        cleanup_unarchived_jobs_after_field_removal($config, $removedFieldKeys);
    }
    $result = persist_active_archiving_rules_change($config, $nextRules, ['reason' => 'rules']);
    $stored = is_array($result['stored'] ?? null) ? $result['stored'] : $state;
    $activeRules = load_active_archiving_rules();
    json_response([
        'ok' => true,
        'fields' => is_array($activeRules['fields'] ?? null) ? $activeRules['fields'] : [],
        'predefinedFields' => is_array($activeRules['predefinedFields'] ?? null) ? $activeRules['predefinedFields'] : [],
        'systemFields' => is_array($activeRules['systemFields'] ?? null) ? $activeRules['systemFields'] : [],
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => (int) ($stored['activeArchivingRulesVersion'] ?? 1),
        'reprocessedJobs' => is_array($result['reprocessedJobs'] ?? null) ? $result['reprocessedJobs'] : ['reprocessedJobIds' => [], 'reprocessedCount' => 0],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
