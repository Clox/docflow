<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    json_response([
        'sections' => load_archive_structure(),
    ]);
} catch (Throwable $e) {
    json_response([
        'sections' => [],
        'error' => $e->getMessage(),
    ], 500);
}
