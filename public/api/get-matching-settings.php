<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $payload = load_matching_settings_payload();
    $config = load_config();
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
        'multiLineTextBlocks' => normalize_multiline_text_block_settings($config['multiLineTextBlocks'] ?? []),
        'layoutAnalysis' => normalize_layout_analysis_settings($config['layoutAnalysis'] ?? []),
    ]);
} catch (Throwable $e) {
    json_response([
        'replacements' => [],
        'positionAdjustment' => default_matching_position_adjustment_settings(),
        'bboxSpanBuilding' => default_matching_bbox_span_building_settings(),
        'dataFieldAcceptanceThreshold' => 0.5,
        'multiLineTextBlocks' => default_multiline_text_block_settings(),
        'layoutAnalysis' => default_layout_analysis_settings(),
    ], 500);
}
