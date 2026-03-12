<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $structure = load_archive_structure_data();
    json_response([
        'archiveFolders' => is_array($structure['archiveFolders'] ?? null) ? $structure['archiveFolders'] : [],
        'systemCategories' => is_array($structure['systemCategories'] ?? null) ? $structure['systemCategories'] : system_archive_categories_template(),
    ]);
} catch (Throwable $e) {
    json_response([
        'archiveFolders' => [],
        'systemCategories' => system_archive_categories_template(),
        'error' => $e->getMessage(),
    ], 500);
}
