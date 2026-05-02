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
if (!is_array($payload)) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$filename = is_string($payload['filename'] ?? null) ? trim((string) $payload['filename']) : '';
$path = $filename !== '' ? configuration_backup_path($filename) : null;
if ($path === null || !is_file($path)) {
    json_response(['error' => 'Backup file not found'], 404);
    exit;
}

$backupPayload = load_json_file($path);
if (!is_array($backupPayload)) {
    json_response(['error' => 'Backup file is invalid'], 400);
    exit;
}

try {
    $result = apply_configuration_import_payload($backupPayload);
    json_response([
        'ok' => true,
        'restoredFrom' => basename($path),
        'backupFile' => is_string($result['backupFile'] ?? null) ? $result['backupFile'] : null,
        'clients' => is_array($result['clients'] ?? null) ? $result['clients'] : [],
        'senders' => is_array($result['senders'] ?? null) ? $result['senders'] : [],
        'labels' => is_array($result['labels'] ?? null) ? $result['labels'] : [],
        'systemLabels' => is_array($result['systemLabels'] ?? null) ? $result['systemLabels'] : system_labels_template(),
        'archiveStructure' => is_array($result['archiveStructure'] ?? null)
            ? $result['archiveStructure']
            : ['archiveFolders' => []],
        'dataFields' => is_array($result['dataFields'] ?? null)
            ? $result['dataFields']
            : ['fields' => [], 'predefinedFields' => [], 'systemFields' => []],
        'matching' => is_array($result['matching'] ?? null) ? $result['matching'] : [],
        'ocr' => is_array($result['ocr'] ?? null)
            ? $result['ocr']
            : [
                'ocrSkipExistingText' => true,
                'ocrOptimizeLevel' => 1,
                'ocrTextExtractionMethod' => 'layout',
                'ocrPdfTextSubstitutions' => [],
            ],
        'system' => is_array($result['system'] ?? null)
            ? $result['system']
            : [
                'stateUpdateTransport' => 'polling',
                'chromeExtensionSuppressMissingNotice' => false,
            ],
        'reprocessedJobs' => is_array($result['reprocessedJobs'] ?? null)
            ? $result['reprocessedJobs']
            : ['reprocessedJobIds' => [], 'reprocessedCount' => 0, 'mode' => 'full'],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 400);
}
