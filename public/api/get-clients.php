<?php

const CLIENTS_FILE = __DIR__ . '/../../clients.json';

header('Content-Type: application/json; charset=utf-8');

if (!is_file(CLIENTS_FILE)) {
    echo json_encode([]);
    exit;
}

$content = file_get_contents(CLIENTS_FILE);
if ($content === false || trim($content) === '') {
    echo json_encode([]);
    exit;
}

$data = json_decode($content, true);
if (!is_array($data)) {
    echo json_encode([]);
    exit;
}

$clients = [];
foreach ($data as $item) {
    if (!is_string($item)) {
        continue;
    }

    $value = trim($item);
    if ($value !== '') {
        $clients[] = $value;
    }
}

echo json_encode($clients);
