<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$path = DATA_DIR . '/categories.json';
if (!is_file($path)) {
    json_response(['categories' => []]);
    exit;
}

$raw = file_get_contents($path);
if ($raw === false || trim($raw) === '') {
    json_response(['categories' => []]);
    exit;
}

$decoded = json_decode($raw, true);
if (!is_array($decoded)) {
    json_response(['error' => 'categories.json must be a JSON array'], 500);
    exit;
}

json_response(['categories' => $decoded]);
