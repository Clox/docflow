<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

try {
    $config = load_config();
    $payload = [];
    $raw = file_get_contents('php://input');
    if (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            json_response(['ok' => false, 'error' => 'Invalid JSON payload'], 400);
            exit;
        }
        $payload = $decoded;
    }

    $jobIds = is_array($payload['jobIds'] ?? null) ? $payload['jobIds'] : null;
    $mode = is_string($payload['mode'] ?? null) ? trim((string) $payload['mode']) : 'post-ocr';
    $forceOcr = ($payload['forceOcr'] ?? false) === true;

    json_response(reanalyze_all_documents($config, $jobIds, $mode, $forceOcr));
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
