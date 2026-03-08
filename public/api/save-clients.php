<?php

const CLIENTS_FILE = __DIR__ . '/../../clients.json';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body']);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload) || !isset($payload['clients']) || !is_array($payload['clients'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$clients = [];
foreach ($payload['clients'] as $item) {
    if (!is_string($item)) {
        continue;
    }

    $value = trim($item);
    if ($value !== '') {
        $clients[] = $value;
    }
}

$json = json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not encode clients']);
    exit;
}

if (file_put_contents(CLIENTS_FILE, $json . "\n", LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save clients']);
    exit;
}

echo json_encode(['ok' => true]);
