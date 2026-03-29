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

$paymentId = isset($payload['paymentId']) && is_numeric($payload['paymentId'])
    ? (int) $payload['paymentId']
    : 0;
if ($paymentId < 1) {
    json_response(['error' => 'Payment id is required'], 400);
    exit;
}

$payeeName = $payload['payeeName'] ?? null;
if ($payeeName !== null && !is_string($payeeName)) {
    json_response(['error' => 'payeeName must be a string or null'], 400);
    exit;
}

try {
    $repository = sender_repository_instance();
    if ($repository === null) {
        throw new RuntimeException('Sender repository is unavailable.');
    }

    $repository->updatePaymentPayeeName($paymentId, $payeeName);

    json_response([
        'ok' => true,
        'paymentId' => $paymentId,
        'payeeName' => is_string($payeeName) ? trim($payeeName) : null,
        'remainingCount' => $repository->countPaymentNumbersMissingPayeeName(),
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
