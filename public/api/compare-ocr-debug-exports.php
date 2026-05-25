<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

// Developer/admin-only compare endpoint for existing snapshots.
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

$leftFolderName = is_string($payload['leftFolderName'] ?? null) ? trim((string) $payload['leftFolderName']) : '';
$rightFolderName = is_string($payload['rightFolderName'] ?? null) ? trim((string) $payload['rightFolderName']) : '';
if ($leftFolderName === '' || $rightFolderName === '') {
    json_response(['error' => 'Välj två snapshots att jämföra.'], 400);
    exit;
}
$jobIds = array_key_exists('jobIds', $payload) && is_array($payload['jobIds']) ? $payload['jobIds'] : [];
$scope = array_key_exists('scope', $payload) && is_string($payload['scope']) ? trim((string) $payload['scope']) : 'jobs';

try {
    $config = load_config();
    json_response([
        'ok' => true,
        'comparison' => compare_ocr_debug_exports_with_live($config, $leftFolderName, $rightFolderName, $jobIds, $scope),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
