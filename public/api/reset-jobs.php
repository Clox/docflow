<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

try {
    $config = load_config();
    $result = reset_all_jobs($config);

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
