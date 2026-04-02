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

try {
    $repository = sender_repository_instance();
    if ($repository === null) {
        throw new RuntimeException('Sender repository is unavailable.');
    }

    $repository->updateOrganizationName($organizationId, $organizationName);

    json_response([
        'ok' => true,
        'organizationId' => $organizationId,
        'organizationName' => is_string($organizationName) ? trim($organizationName) : null,
        'remainingCount' => $repository->countOrganizationNumbersMissingName(),
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
