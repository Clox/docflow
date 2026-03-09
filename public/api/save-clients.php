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
if (!is_array($payload) || !isset($payload['text']) || !is_string($payload['text'])) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$text = $payload['text'];
$decoded = json_decode($text, true);
if (!is_array($decoded)) {
    json_response(['error' => 'clients.json must be a JSON array'], 400);
    exit;
}

$path = DATA_DIR . '/clients.json';
if (file_put_contents($path, $text, LOCK_EX) === false) {
    json_response(['error' => 'Could not save clients file'], 500);
    exit;
}

json_response(['ok' => true]);
