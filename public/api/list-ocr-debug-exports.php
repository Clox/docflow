<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

// Developer/admin-only list endpoint for snapshot comparison.
try {
    $config = load_config();
    json_response([
        'ok' => true,
        'exports' => list_ocr_debug_exports_with_runs($config),
        'comparisonRuns' => list_ocr_debug_comparison_runs(),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
