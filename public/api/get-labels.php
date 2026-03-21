<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $data = load_labels_data();
    json_response([
        'labels' => is_array($data['labels'] ?? null) ? $data['labels'] : [],
        'systemLabels' => is_array($data['systemLabels'] ?? null) ? $data['systemLabels'] : system_labels_template(),
    ]);
} catch (Throwable $e) {
    json_response([
        'labels' => [],
        'systemLabels' => system_labels_template(),
        'error' => $e->getMessage(),
    ], 500);
}
