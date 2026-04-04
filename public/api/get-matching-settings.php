<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $payload = load_matching_settings_payload();
    json_response([
        'replacements' => is_array($payload['replacements'] ?? null) ? $payload['replacements'] : [],
        'positionAdjustment' => normalize_matching_position_adjustment_settings(
            is_array($payload['positionAdjustment'] ?? null) ? $payload['positionAdjustment'] : []
        ),
    ]);
} catch (Throwable $e) {
    json_response([
        'replacements' => [],
        'positionAdjustment' => default_matching_position_adjustment_settings(),
    ], 500);
}
