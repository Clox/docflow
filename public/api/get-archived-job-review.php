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

    $payload = archived_job_review_payload($config, $id);
    json_response($payload);
} catch (Throwable $e) {
    json_response([
        'error' => $e->getMessage(),
    ], 500);
}
