<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $currentPayload = build_configuration_export_payload();
    $currentFingerprint = configuration_backup_payload_fingerprint($currentPayload);
    $entries = list_configuration_backups();

    if (!is_array($entries) || $entries === []) {
        json_response([
            'ok' => true,
            'hasBackups' => false,
            'canCreate' => true,
            'disabledReason' => '',
            'latestBackupFile' => null,
            'latestBackupKind' => null,
            'latestBackupAt' => null,
        ]);
        return;
    }

    $latestEntry = is_array($entries[0]) ? $entries[0] : null;
    $latestFilename = is_array($latestEntry) && is_string($latestEntry['filename'] ?? null)
        ? (string) $latestEntry['filename']
        : '';
    $duplicateFound = false;
    $latestMatches = false;

    foreach ($entries as $entry) {
        if (!is_array($entry) || !is_string($entry['filename'] ?? null)) {
            continue;
        }

        $entryPath = configuration_backup_path((string) $entry['filename']);
        if ($entryPath === null || !is_file($entryPath)) {
            continue;
        }

        $entryPayload = load_json_file($entryPath);
        if (!is_array($entryPayload)) {
            continue;
        }

        $entryFingerprint = configuration_backup_payload_fingerprint($entryPayload);
        if ($entryFingerprint === $currentFingerprint) {
            $duplicateFound = true;
            if ($latestFilename !== '' && basename($entryPath) === $latestFilename) {
                $latestMatches = true;
            }
        }
    }

    $canCreate = !$duplicateFound;
    $disabledReason = '';
    if (!$canCreate) {
        $disabledReason = $latestMatches
            ? 'Ingen förändring sedan senaste inställningsbackupen'
            : 'Konfigurationen finns redan som inställningsbackup';
    }

    json_response([
        'ok' => true,
        'hasBackups' => true,
        'canCreate' => $canCreate,
        'disabledReason' => $disabledReason,
        'latestBackupFile' => is_array($latestEntry) && is_string($latestEntry['filename'] ?? null) ? (string) $latestEntry['filename'] : null,
        'latestBackupKind' => is_array($latestEntry) && is_string($latestEntry['kind'] ?? null) ? (string) $latestEntry['kind'] : null,
        'latestBackupAt' => is_array($latestEntry) && is_string($latestEntry['snapshotAt'] ?? null)
            ? (string) $latestEntry['snapshotAt']
            : (is_array($latestEntry) && is_string($latestEntry['exportedAt'] ?? null) ? (string) $latestEntry['exportedAt'] : null),
    ]);
} catch (Throwable $e) {
    json_response([
        'error' => $e->getMessage(),
    ], 500);
}
