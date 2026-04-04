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

$organizationId = isset($payload['organizationId']) && is_numeric($payload['organizationId'])
    ? (int) $payload['organizationId']
    : 0;
if ($organizationId < 1) {
    json_response(['error' => 'Organization id is required'], 400);
    exit;
}

$organizationName = $payload['organizationName'] ?? null;
if ($organizationName !== null && !is_string($organizationName)) {
    json_response(['error' => 'organizationName must be a string or null'], 400);
    exit;
}

$alternativeNames = [];
if (array_key_exists('alternativeNames', $payload)) {
    if (!is_array($payload['alternativeNames'])) {
        json_response(['error' => 'alternativeNames must be an array when provided'], 400);
        exit;
    }
    foreach ($payload['alternativeNames'] as $value) {
        if (!is_string($value)) {
            json_response(['error' => 'alternativeNames must only contain strings'], 400);
            exit;
        }
        $alternativeNames[] = trim($value);
    }
}

$currentSelectedJobId = is_string($payload['currentSelectedJobId'] ?? null)
    ? trim((string) $payload['currentSelectedJobId'])
    : null;
if ($currentSelectedJobId === '') {
    $currentSelectedJobId = null;
}

try {
    $repository = sender_repository_instance();
    if ($repository === null) {
        throw new RuntimeException('Sender repository is unavailable.');
    }

    $resolved = $repository->resolveOrganizationName($organizationId, $organizationName, $alternativeNames);
    $config = load_config();
    $followup = [
        'affectedJobIds' => [],
        'markedOutdatedJobIds' => [],
        'autoReprocessedJobIds' => [],
    ];
    if (($resolved['senderId'] ?? null) !== null) {
        $followup = handle_resolved_sender_identifier_followups(
            $config,
            'organization_number',
            (string) ($resolved['organizationNumber'] ?? ''),
            $currentSelectedJobId
        );
    }

    json_response([
        'ok' => true,
        'organizationId' => $organizationId,
        'organizationName' => is_string($organizationName) ? trim($organizationName) : null,
        'alternativeNames' => array_values(array_filter($alternativeNames, static fn (string $name): bool => $name !== '')),
        'senderId' => isset($resolved['senderId']) ? $resolved['senderId'] : null,
        'followup' => $followup,
        'remainingCount' => $repository->countOrganizationNumbersMissingName(),
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
