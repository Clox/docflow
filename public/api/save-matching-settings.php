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

$positionAdjustment = normalize_matching_position_adjustment_settings(
    is_array($payload['positionAdjustment'] ?? null) ? $payload['positionAdjustment'] : []
);

$dataFieldAcceptanceThreshold = is_numeric($payload['dataFieldAcceptanceThreshold'] ?? null)
    ? clamp_confidence((float) $payload['dataFieldAcceptanceThreshold'])
    : 0.5;

try {
    $config = load_config();
    ensure_job_dispatcher_running($config);
    $previousPayload = load_matching_settings_payload();
    write_json_file(DATA_DIR . '/matching.json', [
        'replacements' => $normalized,
        'positionAdjustment' => $positionAdjustment,
        'dataFieldAcceptanceThreshold' => $dataFieldAcceptanceThreshold,
    ]);

    $reprocessedJobs = [
        'reprocessedJobIds' => [],
        'reprocessedCount' => 0,
        'mode' => 'post-ocr',
    ];
    if (
        json_encode($previousPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        !== json_encode([
            'replacements' => $normalized,
            'positionAdjustment' => $positionAdjustment,
            'dataFieldAcceptanceThreshold' => $dataFieldAcceptanceThreshold,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ) {
        $reprocessedJobs = reprocess_unarchived_jobs_for_analysis_change($config, 'post-ocr', false);
    }

    json_response([
        'ok' => true,
        'replacements' => $normalized,
        'positionAdjustment' => $positionAdjustment,
        'dataFieldAcceptanceThreshold' => $dataFieldAcceptanceThreshold,
        'reprocessedJobs' => $reprocessedJobs,
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
