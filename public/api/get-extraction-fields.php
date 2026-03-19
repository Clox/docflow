<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $data = load_extraction_fields_data();
    json_response([
        'fields' => is_array($data['fields'] ?? null) ? $data['fields'] : [],
    ]);
} catch (Throwable $e) {
    json_response([
        'fields' => [],
        'error' => $e->getMessage(),
    ], 500);
}
