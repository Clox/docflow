<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$path = DATA_DIR . '/clients.json';

if (!is_file($path)) {
    json_response(['text' => '']);
    exit;
}

$text = file_get_contents($path);
if ($text === false) {
    json_response(['error' => 'Could not read clients file'], 500);
    exit;
}

json_response(['text' => $text]);
