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

    $extractedPath = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . $id
        . DIRECTORY_SEPARATOR . 'extracted.json';

    if (!is_file($extractedPath)) {
        http_response_code(404);
        exit;
    }

    $extracted = load_json_file($extractedPath);
    if (!is_array($extracted)) {
        json_response(['categories' => []]);
        exit;
    }

    $categories = $extracted['categoryMatches'] ?? [];
    if (!is_array($categories)) {
        $categories = [];
    }

    $systemCategories = $extracted['systemCategoryMatches'] ?? [];
    if (!is_array($systemCategories)) {
        $systemCategories = [];
    }

    $labels = $extracted['labelMatches'] ?? [];
    if (!is_array($labels)) {
        $labels = [];
    }

    json_response([
        'categories' => $categories,
        'systemCategories' => $systemCategories,
        'labels' => $labels,
    ]);
} catch (Throwable $e) {
    json_response([
        'categories' => [],
        'systemCategories' => [],
        'labels' => [],
    ], 500);
}
