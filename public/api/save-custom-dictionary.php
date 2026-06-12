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
if (!is_array($payload) || !is_string($payload['text'] ?? null)) {
    json_response(['error' => 'Invalid dictionary payload'], 400);
    exit;
}

try {
    $text = save_docflow_custom_dictionary_text((string) $payload['text']);
    json_response([
        'ok' => true,
        'text' => $text,
        'path' => docflow_custom_dictionary_path(),
    ]);
} catch (Throwable $e) {
    json_response([
        'error' => $e->getMessage(),
    ], 500);
}
