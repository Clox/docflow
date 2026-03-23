<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $rules = load_draft_archiving_rules();
    json_response([
        'archiveFolders' => is_array($rules['archiveFolders'] ?? null) ? $rules['archiveFolders'] : [],
        'activeArchivingRulesVersion' => active_archiving_rules_version(),
        'hasUnpublishedChanges' => archiving_rules_have_unpublished_changes(),
    ]);
} catch (Throwable $e) {
    json_response([
        'archiveFolders' => [],
        'error' => $e->getMessage(),
    ], 500);
}
