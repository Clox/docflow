<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        json_response(['error' => 'Method not allowed']);
    }

    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        http_response_code(400);
        json_response(['error' => 'Ogiltig JSON']);
    }

    $jobId = is_string($payload['jobId'] ?? null) ? trim((string) $payload['jobId']) : '';
    if (!is_valid_job_id($jobId)) {
        http_response_code(400);
        json_response(['error' => 'Ogiltigt jobb-id']);
    }

    $ruleSet = is_array($payload['ruleSet'] ?? null) ? $payload['ruleSet'] : null;
    if ($ruleSet === null) {
        http_response_code(400);
        json_response(['error' => 'ruleSet saknas']);
    }

    $config = load_config();
    $jobDir = rtrim((string) $config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
    $job = load_json_file($jobDir . DIRECTORY_SEPARATOR . 'job.json');
    if (!is_array($job)) {
        http_response_code(404);
        json_response(['error' => 'Jobbet kunde inte läsas']);
    }

    $analysisPdfPath = job_review_pdf_path($config, $jobId, $job);
    $ocrText = load_job_analysis_text($jobDir, $analysisPdfPath);
    $matchingPayload = load_matching_settings_payload();
    $replacementMap = replacement_map(is_array($matchingPayload['replacements'] ?? null) ? $matchingPayload['replacements'] : []);
    $positionSettings = normalize_matching_position_adjustment_settings(
        is_array($matchingPayload['positionAdjustment'] ?? null) ? $matchingPayload['positionAdjustment'] : []
    );
    $lineGeometries = build_matching_line_geometries_for_job($job, $ocrText);
    $acceptanceThreshold = is_numeric($matchingPayload['dataFieldAcceptanceThreshold'] ?? null)
        ? (float) $matchingPayload['dataFieldAcceptanceThreshold']
        : 0.5;

    $field = [
        'key' => 'test',
        'name' => 'Test',
        'extractor' => 'generic_label',
        'ruleSets' => [$ruleSet],
    ];
    $results = extract_configured_text_field_results(
        split_lines_for_matching($ocrText),
        $replacementMap,
        [$field],
        $positionSettings,
        $lineGeometries,
        $acceptanceThreshold
    );

    json_response([
        'field' => $results['test'] ?? null,
        'threshold' => $acceptanceThreshold,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    json_response(['error' => $e->getMessage()]);
}
