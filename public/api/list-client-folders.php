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

$folders = [];
foreach ($data as $item) {
    if (!is_array($item) || !isset($item['folderName']) || !is_string($item['folderName'])) {
        continue;
    }

    $folderName = trim($item['folderName']);
    if ($folderName !== '') {
        $folders[] = $folderName;
    }
}

echo json_encode($folders);
