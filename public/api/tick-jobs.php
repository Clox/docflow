<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    start_job_dispatcher();

    json_response([
        'ok' => true,
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
