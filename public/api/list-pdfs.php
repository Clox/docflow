<?php

// Change this to your PDF folder path.
const PDF_DIR = '/home/oscar/projects/docflow/pdfs';

header('Content-Type: application/json; charset=utf-8');

if (!is_dir(PDF_DIR)) {
    echo json_encode([]);
    exit;
}

$entries = scandir(PDF_DIR);
if ($entries === false) {
    echo json_encode([]);
    exit;
}

$pdfFiles = [];
foreach ($entries as $entry) {
    $fullPath = PDF_DIR . DIRECTORY_SEPARATOR . $entry;
    if (!is_file($fullPath)) {
        continue;
    }

    if (strtolower(pathinfo($entry, PATHINFO_EXTENSION)) !== 'pdf') {
        continue;
    }

    $pdfFiles[] = $entry;
}

sort($pdfFiles, SORT_STRING);
echo json_encode($pdfFiles);
