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
if (!is_array($payload) || !array_key_exists('labels', $payload) || !is_array($payload['labels'])) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

try {
    $normalized = [
        'labels' => normalize_labels($payload['labels']),
    ];

    write_json_file(DATA_DIR . '/labels.json', $normalized);
    json_response([
        'ok' => true,
        'labels' => $normalized['labels'],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 400);
}
