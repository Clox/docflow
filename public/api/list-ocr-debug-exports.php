<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

// Developer/admin-only list endpoint for OCR debug export comparison.
try {
    $config = load_config();
    json_response([
        'ok' => true,
        'exports' => list_ocr_debug_exports($config),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
