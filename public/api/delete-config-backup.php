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

$filename = is_string($payload['filename'] ?? null) ? trim((string) $payload['filename']) : '';
$path = $filename !== '' ? configuration_backup_path($filename) : null;
if ($path === null || !is_file($path)) {
    json_response(['error' => 'Backup file not found'], 404);
    exit;
}

try {
    if (!@unlink($path)) {
        throw new RuntimeException('Could not delete backup file.');
    }

    json_response([
        'ok' => true,
        'deleted' => basename($path),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
