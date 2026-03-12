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
if (!is_array($payload) || !array_key_exists('archiveFolders', $payload) || !is_array($payload['archiveFolders'])) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$normalized = normalize_archive_structure($payload['archiveFolders']);

try {
    write_json_file(DATA_DIR . '/archive-structure.json', $normalized);
    json_response([
        'ok' => true,
        'archiveFolders' => $normalized,
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
