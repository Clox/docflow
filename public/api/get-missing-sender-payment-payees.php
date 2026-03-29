<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

try {
    $repository = sender_repository_instance();
    if ($repository === null) {
        throw new RuntimeException('Sender repository is unavailable.');
    }

    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
    if (!is_int($limit) || $limit < 1) {
        $limit = 10;
    }

    json_response([
        'ok' => true,
        'items' => $repository->listPaymentNumbersMissingPayeeName($limit),
        'remainingCount' => $repository->countPaymentNumbersMissingPayeeName(),
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'items' => [],
        'remainingCount' => 0,
        'error' => $e->getMessage(),
    ], 500);
}
