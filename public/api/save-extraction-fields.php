<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    json_response(['error' => 'Invalid request body'], 400);
    exit;
}

$payload = json_decode($raw, true);
if (
    !is_array($payload)
    || !array_key_exists('fields', $payload)
    || !is_array($payload['fields'])
    || !array_key_exists('predefinedFields', $payload)
    || !is_array($payload['predefinedFields'])
    || !array_key_exists('systemFields', $payload)
    || !is_array($payload['systemFields'])
) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$normalized = [
    'fields' => normalize_extraction_fields($payload['fields']),
    'predefinedFields' => normalize_predefined_extraction_fields($payload['predefinedFields']),
    'systemFields' => normalize_system_extraction_fields($payload['systemFields']),
];

try {
    write_json_file(DATA_DIR . '/extraction-fields.json', $normalized);
    $stored = load_extraction_fields_data();
    write_json_file(DATA_DIR . '/extraction-fields.json', $stored);
    json_response([
        'ok' => true,
        'fields' => is_array($stored['fields'] ?? null) ? $stored['fields'] : [],
        'predefinedFields' => is_array($stored['predefinedFields'] ?? null) ? $stored['predefinedFields'] : [],
        'systemFields' => is_array($stored['systemFields'] ?? null) ? $stored['systemFields'] : [],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
