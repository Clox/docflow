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
if (!is_array($payload) || !array_key_exists('valuePatterns', $payload) || !is_array($payload['valuePatterns'])) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

try {
    $config = load_config();
    $state = load_archiving_rules_state();
    $nextRules = normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);
    $nextRules['valuePatterns'] = normalize_value_pattern_definitions($payload['valuePatterns']);
    $result = persist_active_archiving_rules_change($config, $nextRules, [
        'reason' => 'rules',
        'reprocessImmediately' => false,
    ]);
    $stored = is_array($result['stored'] ?? null) ? $result['stored'] : $state;
    $activeRules = load_active_archiving_rules();
    json_response([
        'ok' => true,
        'valuePatterns' => is_array($activeRules['valuePatterns'] ?? null) ? $activeRules['valuePatterns'] : [],
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'activeArchivingRulesVersion' => (int) ($stored['activeArchivingRulesVersion'] ?? 1),
        'reprocessedJobs' => is_array($result['reprocessedJobs'] ?? null) ? $result['reprocessedJobs'] : ['reprocessedJobIds' => [], 'reprocessedCount' => 0],
        'markedOutdatedJobs' => is_array($result['markedOutdatedJobs'] ?? null) ? $result['markedOutdatedJobs'] : ['markedJobIds' => [], 'markedCount' => 0],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
