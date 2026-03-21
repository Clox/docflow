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
if (!is_array($payload) || !array_key_exists('replacements', $payload) || !is_array($payload['replacements'])) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$normalized = [];
foreach ($payload['replacements'] as $row) {
    if (!is_array($row)) {
        continue;
    }

    $from = is_string($row['from'] ?? null) ? trim((string) $row['from']) : '';
    $to = is_string($row['to'] ?? null) ? trim((string) $row['to']) : '';
    if ($from === '' || $to === '') {
        continue;
    }

    $normalized[] = [
        'from' => $from,
        'to' => $to,
    ];
}

try {
    write_json_file(DATA_DIR . '/matching.json', [
        'replacements' => $normalized,
    ]);

    json_response([
        'ok' => true,
        'replacements' => $normalized,
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
