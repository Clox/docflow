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
        $jobId = trim((string) $payload['jobId']);
        $mode = is_string($payload['mode'] ?? null) ? trim((string) $payload['mode']) : '';
        $forceOcr = ($payload['forceOcr'] ?? false) === true;
        if ($mode === 'full' || $mode === 'post-ocr') {
            $result = reprocess_job_by_id($config, $jobId, $mode, $forceOcr);
        } else {
            $result = reset_job_by_id($config, $jobId);
        }
    } else {
        $result = reset_all_jobs($config);
    }

    json_response([
        'ok' => true,
        'restoredSources' => $result['restoredSources'] ?? 0,
        'removedJobFolders' => $result['removedJobFolders'] ?? 0,
        'resetJobIds' => array_values(array_filter(
            is_array($result['resetJobIds'] ?? null) ? $result['resetJobIds'] : [],
            static fn ($jobId) => is_string($jobId) && trim($jobId) !== ''
        )),
        'skippedArchivedJobFolders' => $result['skippedArchivedJobFolders'] ?? 0,
        'errors' => $result['errors'] ?? [],
        'jobId' => $result['jobId'] ?? null,
        'mode' => $result['mode'] ?? null,
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
