<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    json_response([
        'ok' => true,
        'backups' => list_configuration_backups(),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
