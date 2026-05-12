<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

// Developer/admin-only endpoint for launching Meld against existing OCR debug exports.
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

$leftFolderName = is_string($payload['leftFolderName'] ?? null) ? trim((string) $payload['leftFolderName']) : '';
$rightFolderName = is_string($payload['rightFolderName'] ?? null) ? trim((string) $payload['rightFolderName']) : '';
$relativePath = is_string($payload['relativePath'] ?? null) ? trim((string) $payload['relativePath']) : null;
if ($leftFolderName === '' || $rightFolderName === '') {
    json_response(['error' => 'Välj två exporter att jämföra.'], 400);
    exit;
}

try {
    $config = load_config();
    $result = launch_ocr_debug_export_meld($config, $leftFolderName, $rightFolderName, $relativePath);
    json_response([
        'ok' => true,
        'leftPath' => $result['leftPath'],
        'rightPath' => $result['rightPath'],
        'pid' => $result['pid'],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
