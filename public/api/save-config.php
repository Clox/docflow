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

if (!array_key_exists('outputBaseDirectory', $payload) && !array_key_exists('ocrSkipExistingText', $payload)) {
    json_response(['error' => 'No config values provided'], 400);
    exit;
}

$nextOutputBaseDirectory = null;
if (array_key_exists('outputBaseDirectory', $payload)) {
    if (!is_string($payload['outputBaseDirectory'])) {
        json_response(['error' => 'Base output path must be a string'], 400);
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

    $nextOutputBaseDirectory = $outputBaseDirectory;
}

$nextOcrSkipExistingText = null;
if (array_key_exists('ocrSkipExistingText', $payload)) {
    if (!is_bool($payload['ocrSkipExistingText'])) {
        json_response(['error' => 'OCR skip setting must be boolean'], 400);
        exit;
    }
    $nextOcrSkipExistingText = $payload['ocrSkipExistingText'];
}

try {
    $config = load_raw_config();
    if ($nextOutputBaseDirectory !== null) {
        $config['outputBaseDirectory'] = $nextOutputBaseDirectory;
    }
    if ($nextOcrSkipExistingText !== null) {
        $config['ocrSkipExistingText'] = $nextOcrSkipExistingText;
    }
    save_raw_config($config);
    json_response([
        'ok' => true,
        'outputBaseDirectory' => $config['outputBaseDirectory'] ?? '',
        'ocrSkipExistingText' => (bool) ($config['ocrSkipExistingText'] ?? true),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
