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

try {
    $result = store_imported_configuration_snapshot($payload);
    json_response([
        'ok' => true,
        'created' => ($result['created'] ?? false) === true,
        'backupFile' => is_string($result['backupFile'] ?? null) ? $result['backupFile'] : null,
        'snapshotType' => is_string($result['snapshotType'] ?? null) ? $result['snapshotType'] : 'imported',
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 400);
}
