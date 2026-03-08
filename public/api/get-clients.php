<?php

const CLIENTS_FILE = __DIR__ . '/../../clients.json';

header('Content-Type: application/json; charset=utf-8');

if (!is_file(CLIENTS_FILE)) {
    echo json_encode(['text' => '']);
    exit;
}

$content = file_get_contents(CLIENTS_FILE);
if ($content === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not read clients file']);
    exit;
}
echo json_encode(['text' => $content]);
