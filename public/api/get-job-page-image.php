<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();

    $id = $_GET['id'] ?? '';
    $page = $_GET['page'] ?? '1';
    $dpi = $_GET['dpi'] ?? '150';
    $variant = $_GET['variant'] ?? 'review';

    if (!is_string($id) || !is_valid_job_id($id)) {
        http_response_code(404);
        exit;
    }

    $pageNumber = filter_var($page, FILTER_VALIDATE_INT);
    $dpiValue = filter_var($dpi, FILTER_VALIDATE_INT);
    if ($pageNumber === false || $pageNumber < 1) {
        http_response_code(400);
        exit;
    }
    if ($dpiValue === false) {
        $dpiValue = 150;
    }

    $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $id;
    $preferredPdf = ($variant === 'source' ? 'source.pdf' : 'review.pdf');
    $pdfPath = $jobDir . DIRECTORY_SEPARATOR . $preferredPdf;
    if (!is_file($pdfPath)) {
        $fallbackPdf = $jobDir . DIRECTORY_SEPARATOR . ($preferredPdf === 'review.pdf' ? 'source.pdf' : 'review.pdf');
        $pdfPath = is_file($fallbackPdf) ? $fallbackPdf : $pdfPath;
    }
    if (!is_file($pdfPath)) {
        http_response_code(404);
        exit;
    }

    $bytes = rasterize_pdf_page_to_png_bytes($pdfPath, (int) $pageNumber, (int) $dpiValue);
    if (!is_string($bytes) || $bytes === '') {
        http_response_code(500);
        exit;
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-store, max-age=0');
    echo $bytes;
} catch (Throwable $e) {
    http_response_code(500);
}
