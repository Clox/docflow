<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

// Developer/admin-only snapshot endpoint for analysis comparison.
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
if (!is_array($payload)) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$jobIds = array_key_exists('jobIds', $payload) && is_array($payload['jobIds']) ? $payload['jobIds'] : [];
$scope = array_key_exists('scope', $payload) && is_string($payload['scope']) ? trim($payload['scope']) : 'jobs';
$requiresReanalysis = !array_key_exists('requiresReanalysis', $payload) || ($payload['requiresReanalysis'] ?? true) === true;

try {
    $config = load_config();
    $run = create_ocr_debug_snapshot_run($config, $jobIds, $scope, $requiresReanalysis);
    $snapshot = is_array($run['snapshot'] ?? null) ? $run['snapshot'] : [];
    json_response([
        'ok' => true,
        'snapshot' => $snapshot,
        'exportDirectory' => is_string($snapshot['exportDirectory'] ?? null) ? (string) $snapshot['exportDirectory'] : '',
        'folderName' => is_string($snapshot['folderName'] ?? null) ? (string) $snapshot['folderName'] : '',
        'exportedCount' => isset($snapshot['completedJobs']) ? (int) $snapshot['completedJobs'] : (int) ($snapshot['exportedCount'] ?? 0),
        'skippedCount' => isset($snapshot['failedJobs']) ? (int) $snapshot['failedJobs'] : (int) ($snapshot['skippedCount'] ?? 0),
        'skippedJobIds' => is_array($snapshot['skippedJobIds'] ?? null) ? $snapshot['skippedJobIds'] : [],
        'scope' => is_string($snapshot['scope'] ?? null) ? (string) $snapshot['scope'] : $scope,
        'scopeLabel' => is_string($snapshot['scopeLabel'] ?? null) ? (string) $snapshot['scopeLabel'] : (string) ($snapshot['filterLabel'] ?? ''),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
