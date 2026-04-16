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
    || !array_key_exists('labels', $payload)
    || !is_array($payload['labels'])
    || !array_key_exists('systemLabels', $payload)
    || !is_array($payload['systemLabels'])
) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

try {
    $config = load_config();
    ensure_job_dispatcher_running($config);
    $normalizedSystemLabels = normalize_system_labels($payload['systemLabels']);
    $systemLabelIds = [];
    foreach ($normalizedSystemLabels as $systemLabel) {
        if (!is_array($systemLabel)) {
            continue;
        }
        $id = is_string($systemLabel['id'] ?? null) ? trim((string) $systemLabel['id']) : '';
        if ($id !== '') {
            $systemLabelIds[$id] = true;
        }
    }

    $normalizedLabels = normalize_labels($payload['labels']);
    foreach ($normalizedLabels as $label) {
        $id = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
        if ($id !== '' && isset($systemLabelIds[$id])) {
            throw new RuntimeException('Etikett-id krockar med systemetikett: ' . $id);
        }
    }

    $state = load_archiving_rules_state();
    $nextRules = normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);
    $nextRules['labels'] = $normalizedLabels;
    $nextRules['systemLabels'] = $normalizedSystemLabels;
    $result = persist_active_archiving_rules_change($config, $nextRules, ['reason' => 'rules']);
    $stored = is_array($result['stored'] ?? null) ? $result['stored'] : $state;
    $activeRules = load_active_archiving_rules();
    json_response([
        'ok' => true,
        'labels' => is_array($activeRules['labels'] ?? null) ? $activeRules['labels'] : [],
        'systemLabels' => is_array($activeRules['systemLabels'] ?? null) ? $activeRules['systemLabels'] : system_labels_template(),
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => (int) ($stored['activeArchivingRulesVersion'] ?? 1),
        'reprocessedJobs' => is_array($result['reprocessedJobs'] ?? null) ? $result['reprocessedJobs'] : ['reprocessedJobIds' => [], 'reprocessedCount' => 0],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 400);
}
