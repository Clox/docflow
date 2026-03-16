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
if (!is_array($payload)) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$tool = is_string($payload['tool'] ?? null) ? trim((string) $payload['tool']) : '';
if ($tool !== 'rapidocr') {
    json_response(['error' => 'Local installation is not supported for this tool'], 400);
    exit;
}

try {
    start_local_rapidocr_install();
    json_response([
        'ok' => true,
        'tool' => 'rapidocr',
    ]);
} catch (Throwable $e) {
    json_response([
        'error' => $e->getMessage(),
    ], 500);
}
