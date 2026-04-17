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

$labelId = is_string($payload['labelId'] ?? null) ? trim((string) $payload['labelId']) : '';
if ($labelId === '') {
    json_response(['error' => 'Invalid label id'], 400);
    exit;
}

try {
    $config = load_config();
    $activeRules = load_active_archiving_rules();
    $labelName = $labelId;
    foreach (array_merge(
        is_array($activeRules['systemLabels'] ?? null) ? array_values($activeRules['systemLabels']) : [],
        is_array($activeRules['labels'] ?? null) ? $activeRules['labels'] : []
    ) as $label) {
        if (!is_array($label)) {
            continue;
        }
        $candidateId = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
        if ($candidateId !== $labelId) {
            continue;
        }
        $labelName = is_string($label['name'] ?? null) && trim((string) $label['name']) !== ''
            ? trim((string) $label['name'])
            : $labelId;
        break;
    }

    $usageCounts = count_archived_documents_using_label_ids($config, [$labelId]);
    $usageCount = (int) ($usageCounts[$labelId] ?? 0);
    if ($usageCount > 0) {
        throw new RuntimeException(sprintf(
            'Går inte att ta bort etiketten "%s" eftersom den används i %d arkiverade dokument.',
            $labelName,
            $usageCount
        ));
    }

    json_response([
        'ok' => true,
        'labelId' => $labelId,
        'usageCount' => 0,
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 400);
}
