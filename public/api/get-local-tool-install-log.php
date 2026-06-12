<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$tool = is_string($_GET['tool'] ?? null) ? trim((string) $_GET['tool']) : '';
if ($tool !== 'rapidocr' && $tool !== 'spylls') {
    json_response(['error' => 'Unknown tool'], 400);
    exit;
}

$path = $tool === 'rapidocr' ? rapidocr_install_log_path() : spylls_install_log_path();
if (!is_file($path)) {
    json_response([
        'tool' => $tool,
        'log' => '',
    ]);
    exit;
}

$log = file_get_contents($path);
if ($log === false) {
    json_response(['error' => 'Could not read install log'], 500);
    exit;
}

json_response([
    'tool' => $tool,
    'log' => $log,
]);
