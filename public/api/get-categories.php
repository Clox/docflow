<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $structure = load_archive_structure_data();
    json_response([
        'archiveFolders' => is_array($structure['archiveFolders'] ?? null) ? $structure['archiveFolders'] : [],
    ]);
} catch (Throwable $e) {
    json_response([
        'archiveFolders' => [],
        'error' => $e->getMessage(),
    ], 500);
}
