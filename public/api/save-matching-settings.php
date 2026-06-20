<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    json_response(['error' => 'Invalid request body'], 400);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload) || !array_key_exists('replacements', $payload) || !is_array($payload['replacements'])) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$normalized = [];
foreach ($payload['replacements'] as $row) {
    if (!is_array($row)) {
        continue;
    }

    $from = is_string($row['from'] ?? null) ? trim((string) $row['from']) : '';
    $to = is_string($row['to'] ?? null) ? trim((string) $row['to']) : '';
    if ($from === '' || $to === '') {
        continue;
    }

    $normalized[] = [
        'from' => $from,
        'to' => $to,
    ];
}

if (array_key_exists('positionAdjustment', $payload) && !is_array($payload['positionAdjustment'])) {
    json_response(['error' => 'Invalid position adjustment payload'], 400);
    exit;
}
if (array_key_exists('bboxSpanBuilding', $payload) && !is_array($payload['bboxSpanBuilding'])) {
    json_response(['error' => 'Invalid bbox span payload'], 400);
    exit;
}
if (array_key_exists('multiLineTextBlocks', $payload) && !is_array($payload['multiLineTextBlocks'])) {
    json_response(['error' => 'Invalid multiline text block payload'], 400);
    exit;
}
if (array_key_exists('layoutAnalysis', $payload) && !is_array($payload['layoutAnalysis'])) {
    json_response(['error' => 'Invalid layout analysis payload'], 400);
    exit;
}

$positionAdjustment = normalize_matching_position_adjustment_settings(
    is_array($payload['positionAdjustment'] ?? null) ? $payload['positionAdjustment'] : []
);
$bboxSpanBuilding = normalize_matching_bbox_span_building_settings(
    is_array($payload['bboxSpanBuilding'] ?? null) ? $payload['bboxSpanBuilding'] : []
);

$dataFieldAcceptanceThreshold = is_numeric($payload['dataFieldAcceptanceThreshold'] ?? null)
    ? clamp_confidence((float) $payload['dataFieldAcceptanceThreshold'])
    : 0.5;
$multiLineTextBlocks = normalize_multiline_text_block_settings(
    is_array($payload['multiLineTextBlocks'] ?? null) ? $payload['multiLineTextBlocks'] : []
);
$layoutAnalysis = normalize_layout_analysis_settings(
    is_array($payload['layoutAnalysis'] ?? null) ? $payload['layoutAnalysis'] : []
);

try {
    $config = load_config();
    $previousPayload = load_matching_settings_payload();
    $previousMultiLineTextBlocks = normalize_multiline_text_block_settings($config['multiLineTextBlocks'] ?? []);
    $previousLayoutAnalysis = normalize_layout_analysis_settings($config['layoutAnalysis'] ?? []);
    write_json_file(DATA_DIR . '/matching.json', [
        'replacements' => $normalized,
        'positionAdjustment' => $positionAdjustment,
        'bboxSpanBuilding' => $bboxSpanBuilding,
        'dataFieldAcceptanceThreshold' => $dataFieldAcceptanceThreshold,
    ]);
    $rawConfig = load_raw_config();
    $rawConfig['multiLineTextBlocks'] = $multiLineTextBlocks;
    $rawConfig['layoutAnalysis'] = $layoutAnalysis;
    save_raw_config($rawConfig);

    $multiLineTextBlocksChanged = $previousMultiLineTextBlocks !== $multiLineTextBlocks;
    $layoutAnalysisChanged = $previousLayoutAnalysis !== $layoutAnalysis;
    $reprocessedJobs = empty_reprocessed_jobs_payload($multiLineTextBlocksChanged ? 'full' : 'post-ocr');
    $markedOutdatedJobs = [
        'markedJobIds' => [],
        'markedCount' => 0,
    ];
    if (
        json_encode($previousPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        !== json_encode([
            'replacements' => $normalized,
            'positionAdjustment' => $positionAdjustment,
            'bboxSpanBuilding' => $bboxSpanBuilding,
            'dataFieldAcceptanceThreshold' => $dataFieldAcceptanceThreshold,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ) {
        $markedOutdatedJobs = mark_ready_jobs_analysis_outdated_for_analysis_change(load_config());
    } elseif ($multiLineTextBlocksChanged || $layoutAnalysisChanged) {
        $markedOutdatedJobs = mark_ready_jobs_analysis_outdated_for_analysis_change(load_config());
    }

    json_response([
        'ok' => true,
        'replacements' => $normalized,
        'positionAdjustment' => $positionAdjustment,
        'bboxSpanBuilding' => $bboxSpanBuilding,
        'dataFieldAcceptanceThreshold' => $dataFieldAcceptanceThreshold,
        'multiLineTextBlocks' => $multiLineTextBlocks,
        'layoutAnalysis' => $layoutAnalysis,
        'reprocessedJobs' => $reprocessedJobs,
        'markedOutdatedJobs' => $markedOutdatedJobs,
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
