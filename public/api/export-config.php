<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $payload = build_configuration_export_payload();
    $backupResult = write_configuration_backup_if_changed($payload);

    json_response([
        'ok' => true,
        'backupFile' => is_string($backupResult['backupFile'] ?? null) ? $backupResult['backupFile'] : null,
        'created' => ($backupResult['created'] ?? false) === true,
        'snapshotType' => 'manual',
    ]);
} catch (Throwable $e) {
    json_response([
        'error' => $e->getMessage(),
    ], 500);
}
