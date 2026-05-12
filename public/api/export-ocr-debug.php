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

try {
    $config = load_config();
    $result = export_ocr_debug_data($config, $jobIds, $scope);
    json_response([
        'ok' => true,
        'exportDirectory' => $result['exportDirectory'],
        'folderName' => $result['folderName'],
        'exportedCount' => $result['exportedCount'],
        'skippedCount' => $result['skippedCount'],
        'skippedJobIds' => $result['skippedJobIds'],
        'scope' => $result['scope'],
        'scopeLabel' => $result['scopeLabel'],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
