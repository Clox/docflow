<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$config = load_config();

$id = $_GET['id'] ?? '';
if (!is_string($id) || !is_valid_job_id($id)) {
    http_response_code(404);
    exit;
}

$reviewPath = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'review.pdf';

if (!is_file($reviewPath)) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($reviewPath));
header('Content-Disposition: inline; filename="review.pdf"');
readfile($reviewPath);
