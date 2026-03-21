<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $data = load_labels_data();
    json_response([
        'labels' => is_array($data['labels'] ?? null) ? $data['labels'] : [],
    ]);
} catch (Throwable $e) {
    json_response([
        'labels' => [],
        'error' => $e->getMessage(),
    ], 500);
}
