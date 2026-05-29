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
$comment = is_string($payload['comment'] ?? null) ? (string) $payload['comment'] : '';
if ($filename === '') {
    json_response(['error' => 'Backup file not found'], 404);
    exit;
}

try {
    $entry = update_configuration_backup_comment($filename, $comment);
    json_response([
        'ok' => true,
        'backup' => $entry,
        'comment' => is_string($entry['comment'] ?? null) ? (string) $entry['comment'] : '',
    ]);
} catch (Throwable $e) {
    $message = $e->getMessage();
    $status = $message === 'Backup file not found' ? 404 : 500;
    json_response(['error' => $message], $status);
}
