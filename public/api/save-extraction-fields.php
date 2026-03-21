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
    || !array_key_exists('systemFields', $payload)
    || !is_array($payload['systemFields'])
) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$normalized = [
    'fields' => normalize_extraction_fields($payload['fields']),
    'systemFields' => normalize_extraction_fields($payload['systemFields']),
];

try {
    write_json_file(DATA_DIR . '/extraction-fields.json', $normalized);
    json_response([
        'ok' => true,
        'fields' => $normalized['fields'],
        'systemFields' => $normalized['systemFields'],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
