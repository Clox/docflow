<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $data = load_extraction_fields_data();
    json_response([
        'fields' => is_array($data['fields'] ?? null) ? $data['fields'] : [],
        'predefinedFields' => is_array($data['predefinedFields'] ?? null) ? $data['predefinedFields'] : [],
        'systemFields' => is_array($data['systemFields'] ?? null) ? $data['systemFields'] : [],
    ]);
} catch (Throwable $e) {
    json_response([
        'fields' => [],
        'predefinedFields' => [],
        'systemFields' => [],
        'error' => $e->getMessage(),
    ], 500);
}
