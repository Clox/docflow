<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

// Developer/admin-only delete endpoint for snapshots.
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

$folderName = '';
if (is_string($payload['folderName'] ?? null)) {
    $folderName = trim((string) $payload['folderName']);
} elseif (is_string($payload['filename'] ?? null)) {
    $folderName = trim((string) $payload['filename']);
}

try {
    $config = load_config();
    $exportDirectory = $folderName !== '' ? ocr_debug_export_directory_path_from_name($config, $folderName) : null;
    if ($exportDirectory === null || !is_dir($exportDirectory)) {
        json_response(['error' => 'Snapshot folder not found'], 404);
        exit;
    }

    if (!delete_directory_recursive($exportDirectory)) {
        throw new RuntimeException('Could not delete snapshot folder.');
    }

    json_response([
        'ok' => true,
        'deleted' => basename($exportDirectory),
        'exportDirectory' => $exportDirectory,
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
