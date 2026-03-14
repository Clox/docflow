<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $jbig2 = jbig2_status_payload();
    json_response([
        'inboxDirectory' => $config['inboxDirectory'],
        'jobsDirectory' => $config['jobsDirectory'],
        'outputBaseDirectory' => $config['outputBaseDirectory'],
        'ocrSkipExistingText' => (bool) $config['ocrSkipExistingText'],
        'jbig2' => $jbig2,
    ]);
} catch (Throwable $e) {
    json_response([
        'error' => $e->getMessage(),
    ], 500);
}
