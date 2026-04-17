<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

$raw = file_get_contents('php://input');
if (!is_string($raw) || trim($raw) === '') {
    json_response(['error' => 'Invalid request body'], 400);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload)) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$fieldKey = is_string($payload['fieldKey'] ?? null) ? trim((string) $payload['fieldKey']) : '';
if ($fieldKey === '') {
    json_response(['error' => 'Invalid field key'], 400);
    exit;
}

try {
    $config = load_config();
    $activeRules = load_active_archiving_rules();
    $fieldName = $fieldKey;
    foreach (is_array($activeRules['fields'] ?? null) ? $activeRules['fields'] : [] as $field) {
        if (!is_array($field)) {
            continue;
        }
        $candidateKey = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        if ($candidateKey !== $fieldKey) {
            continue;
        }
        $fieldName = is_string($field['name'] ?? null) && trim((string) $field['name']) !== ''
            ? trim((string) $field['name'])
            : $fieldKey;
        break;
    }

    $usageCounts = count_archived_documents_using_field_keys($config, [$fieldKey]);
    $usageCount = (int) ($usageCounts[$fieldKey] ?? 0);
    if ($usageCount > 0) {
        throw new RuntimeException(sprintf(
            'Går inte att ta bort datafältet "%s" eftersom det används i %d arkiverade dokument.',
            $fieldName,
            $usageCount
        ));
    }

    json_response([
        'ok' => true,
        'fieldKey' => $fieldKey,
        'usageCount' => 0,
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 400);
}
