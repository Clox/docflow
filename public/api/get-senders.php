<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $repository = sender_repository_instance();
    if ($repository === null) {
        throw new RuntimeException('Sender repository is unavailable.');
    }

    json_response([
        'senders' => $repository->listEditorRows(),
    ]);
} catch (Throwable $e) {
    json_response([
        'senders' => [],
        'error' => $e->getMessage(),
    ], 500);
}
