<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    json_response([
        'archiveFolders' => load_archive_structure(),
    ]);
} catch (Throwable $e) {
    json_response([
        'archiveFolders' => [],
        'error' => $e->getMessage(),
    ], 500);
}
