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
if (
    !is_array($payload)
    || !array_key_exists('labels', $payload)
    || !is_array($payload['labels'])
    || !array_key_exists('systemLabels', $payload)
    || !is_array($payload['systemLabels'])
) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

try {
    $normalizedSystemLabels = normalize_system_labels($payload['systemLabels']);
    $systemLabelIds = [];
    foreach ($normalizedSystemLabels as $systemLabel) {
        if (!is_array($systemLabel)) {
            continue;
        }
        $id = is_string($systemLabel['id'] ?? null) ? trim((string) $systemLabel['id']) : '';
        if ($id !== '') {
            $systemLabelIds[$id] = true;
        }
    }

    $normalizedLabels = normalize_labels($payload['labels']);
    foreach ($normalizedLabels as $label) {
        $id = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
        if ($id !== '' && isset($systemLabelIds[$id])) {
            throw new RuntimeException('Etikett-id krockar med systemetikett: ' . $id);
        }
    }

    $normalized = [
        'labels' => $normalizedLabels,
        'systemLabels' => $normalizedSystemLabels,
    ];

    write_json_file(DATA_DIR . '/labels.json', $normalized);
    json_response([
        'ok' => true,
        'labels' => $normalized['labels'],
        'systemLabels' => $normalized['systemLabels'],
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 400);
}
