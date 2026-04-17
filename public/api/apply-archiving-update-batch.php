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

$jobIds = array_values(array_filter(
    array_map(
        static fn ($value): string => is_string($value) ? trim($value) : '',
        is_array($payload['jobIds'] ?? null) ? $payload['jobIds'] : []
    ),
    static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
));

if ($jobIds === []) {
    json_response(['error' => 'No archived job ids supplied'], 400);
    exit;
}

try {
    $config = load_config();
    $updatedJobIds = [];
    foreach ($jobIds as $jobId) {
        save_archived_job_review($config, $jobId, 'use-new');
        $updatedJobIds[] = $jobId;
    }

    json_response([
        'ok' => true,
        'updatedJobIds' => $updatedJobIds,
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
