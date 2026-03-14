<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

$payload = [];
$raw = file_get_contents('php://input');
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
    if (is_string($payload['jobId'] ?? null) && trim((string) $payload['jobId']) !== '') {
        $result = reset_job_by_id($config, trim((string) $payload['jobId']));
    } else {
        $result = reset_all_jobs($config);
    }

    json_response([
        'ok' => true,
        'restoredSources' => $result['restoredSources'],
        'removedJobFolders' => $result['removedJobFolders'],
        'errors' => $result['errors'],
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
