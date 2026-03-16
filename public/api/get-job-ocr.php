<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();

    $id = $_GET['id'] ?? '';
    $source = $_GET['source'] ?? 'merged';
    if (!is_string($id) || !is_valid_job_id($id)) {
        http_response_code(404);
        exit;
    }
    if (!is_string($source)) {
        $source = 'merged';
    }

    $normalizedSource = trim($source);
    $filenameBySource = [
        'merged' => 'ocr.txt',
        'tesseract' => 'tesseract.txt',
        'rapidocr' => 'rapidocr.txt',
    ];
    $ocrFilename = $filenameBySource[$normalizedSource] ?? 'ocr.txt';

    $ocrPath = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . $id
        . DIRECTORY_SEPARATOR . 'ocr.txt';
    $ocrPath = dirname($ocrPath) . DIRECTORY_SEPARATOR . $ocrFilename;

    if (!is_file($ocrPath)) {
        http_response_code(404);
        exit;
    }

    $text = file_get_contents($ocrPath);
    if ($text === false) {
        json_response(['text' => ''], 500);
        exit;
    }

    json_response([
        'source' => $normalizedSource,
        'text' => $text,
    ]);
} catch (Throwable $e) {
    json_response(['text' => ''], 500);
}
