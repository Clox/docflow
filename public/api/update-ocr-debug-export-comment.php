<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

// Developer/admin-only metadata endpoint for snapshots.
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

$folderName = is_string($payload['folderName'] ?? null) ? trim((string) $payload['folderName']) : '';
$comment = is_string($payload['comment'] ?? null) ? (string) $payload['comment'] : '';
if ($folderName === '') {
    json_response(['error' => 'Snapshot folder not found'], 404);
    exit;
}

try {
    $config = load_config();
    $manifest = update_ocr_debug_export_comment($config, $folderName, $comment);
    json_response([
        'ok' => true,
        'folderName' => is_string($manifest['folderName'] ?? null) ? (string) $manifest['folderName'] : $folderName,
        'comment' => is_string($manifest['comment'] ?? null) ? (string) $manifest['comment'] : '',
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
