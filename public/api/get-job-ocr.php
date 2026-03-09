<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();

    $id = $_GET['id'] ?? '';
    if (!is_string($id) || !is_valid_job_id($id)) {
        http_response_code(404);
        exit;
    }

    $ocrPath = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . $id
        . DIRECTORY_SEPARATOR . 'ocr.txt';

    if (!is_file($ocrPath)) {
        http_response_code(404);
        exit;
    }

    $text = file_get_contents($ocrPath);
    if ($text === false) {
        json_response(['text' => ''], 500);
        exit;
    }

    json_response(['text' => $text]);
} catch (Throwable $e) {
    json_response(['text' => ''], 500);
}
