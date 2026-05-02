<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$filename = isset($_GET['filename']) && is_string($_GET['filename']) ? trim((string) $_GET['filename']) : '';
$path = $filename !== '' ? configuration_backup_path($filename) : null;
if ($path === null || !is_file($path)) {
    json_response(['error' => 'Backup file not found'], 404);
    exit;
}

$contents = file_get_contents($path);
if ($contents === false) {
    json_response(['error' => 'Could not read backup file'], 500);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . strlen($contents));
echo $contents;
