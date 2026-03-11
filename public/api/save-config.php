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
if (!is_array($payload) || !array_key_exists('outputBaseDirectory', $payload) || !is_string($payload['outputBaseDirectory'])) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$outputBaseDirectory = trim((string) $payload['outputBaseDirectory']);
if ($outputBaseDirectory === '') {
    json_response(['error' => 'Base output path is required'], 400);
    exit;
}

if ($outputBaseDirectory[0] !== DIRECTORY_SEPARATOR) {
    json_response(['error' => 'Base output path must be absolute'], 400);
    exit;
}

if (!is_dir($outputBaseDirectory)) {
    json_response(['error' => 'Base output path does not exist'], 400);
    exit;
}

if (!is_writable($outputBaseDirectory)) {
    json_response(['error' => 'Base output path is not writable'], 400);
    exit;
}

try {
    $config = load_raw_config();
    $config['outputBaseDirectory'] = $outputBaseDirectory;
    save_raw_config($config);
    json_response(['ok' => true]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
