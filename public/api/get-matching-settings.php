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
        'bboxSpanBuilding' => normalize_matching_bbox_span_building_settings(
            is_array($payload['bboxSpanBuilding'] ?? null) ? $payload['bboxSpanBuilding'] : []
        ),
        'dataFieldAcceptanceThreshold' => is_numeric($payload['dataFieldAcceptanceThreshold'] ?? null)
            ? clamp_confidence((float) $payload['dataFieldAcceptanceThreshold'])
            : 0.5,
    ]);
} catch (Throwable $e) {
    json_response([
        'replacements' => [],
        'positionAdjustment' => default_matching_position_adjustment_settings(),
        'bboxSpanBuilding' => default_matching_bbox_span_building_settings(),
        'dataFieldAcceptanceThreshold' => 0.5,
    ], 500);
}
