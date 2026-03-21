<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $payload = load_matching_settings_payload();
    json_response([
        'replacements' => is_array($payload['replacements'] ?? null) ? $payload['replacements'] : [],
    ]);
} catch (Throwable $e) {
    json_response([
        'replacements' => [],
    ], 500);
}
