<?php

// Change this to your PDF folder path.
const PDF_DIR = '/home/oscar/projects/docflow/pdfs';

$file = $_GET['file'] ?? '';
$file = basename($file);

if ($file === '' || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf') {
    http_response_code(404);
    exit;
}

$fullPath = PDF_DIR . DIRECTORY_SEPARATOR . $file;

if (!is_file($fullPath)) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . addslashes($file) . '"');

readfile($fullPath);
