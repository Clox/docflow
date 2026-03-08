<?php

// Change this to your PDF folder path.
const PDF_DIR = '/home/oscar/projects/docflow/pdfs';

header('Content-Type: application/json; charset=utf-8');

$file = $_GET['file'] ?? '';
$file = basename($file);

if ($file === '' || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf') {
    http_response_code(404);
    echo json_encode(['text' => 'Invalid file']);
    exit;
}

$pdfPath = PDF_DIR . DIRECTORY_SEPARATOR . $file;
if (!is_file($pdfPath)) {
    http_response_code(404);
    echo json_encode(['text' => 'File not found']);
    exit;
}

$pdftotext = trim((string) shell_exec('command -v pdftotext 2>/dev/null'));
if ($pdftotext === '') {
    http_response_code(501);
    echo json_encode([
        'text' => 'OCR extraction is unavailable: pdftotext is not installed on the server.'
    ]);
    exit;
}

$tmpOutput = tempnam(sys_get_temp_dir(), 'ocr_');
if ($tmpOutput === false) {
    http_response_code(500);
    echo json_encode(['text' => 'Could not create temp file']);
    exit;
}

$command = escapeshellarg($pdftotext)
    . ' -layout '
    . escapeshellarg($pdfPath)
    . ' '
    . escapeshellarg($tmpOutput)
    . ' 2>/dev/null';

exec($command, $output, $exitCode);

if ($exitCode !== 0) {
    @unlink($tmpOutput);
    http_response_code(500);
    echo json_encode(['text' => 'Failed to extract OCR text from PDF']);
    exit;
}

$text = file_get_contents($tmpOutput);
@unlink($tmpOutput);

if ($text === false) {
    http_response_code(500);
    echo json_encode(['text' => 'Could not read OCR output']);
    exit;
}

echo json_encode(['text' => $text]);
