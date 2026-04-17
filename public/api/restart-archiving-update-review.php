<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

$raw = file_get_contents('php://input');
$payload = [];
if (is_string($raw) && trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        json_response(['error' => 'Invalid JSON payload'], 400);
        exit;
    }
    $payload = $decoded;
}

try {
    $config = load_config();
    $activeRules = load_active_archiving_rules();
    $activeVersion = active_archiving_rules_version();
    $currentState = load_archiving_rules_review_state();
    $currentSession = is_array($currentState['updateSession'] ?? null)
        ? $currentState['updateSession']
        : empty_archiving_update_session();

    restart_archiving_update_session($config, $activeRules, $activeRules, $activeVersion, [
        'reason' => is_string($currentSession['reason'] ?? null) ? (string) $currentSession['reason'] : '',
        'changedSections' => is_array($currentSession['changedSections'] ?? null) ? $currentSession['changedSections'] : [],
        'templateChanges' => is_array($currentSession['templateChanges'] ?? null) ? $currentSession['templateChanges'] : [],
        'ignoreDismissed' => ($payload['ignoreDismissed'] ?? false) === true,
    ]);
    collect_archiving_update_review($config, 500);
    $archivingRules = build_archiving_rules_state_payload($config);

    json_response([
        'ok' => true,
        'archivingRules' => $archivingRules,
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
