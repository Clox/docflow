<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

$raw = file_get_contents('php://input');
if (!is_string($raw) || trim($raw) === '') {
    json_response(['error' => 'Invalid request body'], 400);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$jobId = is_string($payload['jobId'] ?? null) ? trim((string) $payload['jobId']) : '';
if ($jobId === '' || !is_valid_job_id($jobId)) {
    json_response(['error' => 'Invalid job id'], 400);
    exit;
}

try {
    $config = load_config();
    $updates = [];
    foreach (['selectedClientDirName', 'selectedSenderId', 'selectedFolderId', 'selectedLabelIds', 'filename'] as $fieldKey) {
        if (array_key_exists($fieldKey, $payload)) {
            $updates[$fieldKey] = $payload[$fieldKey];
        }
    }

    update_job_user_fields($config, $jobId, $updates);

    json_response([
        'ok' => true,
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
