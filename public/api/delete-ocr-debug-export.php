<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

// Developer/admin-only delete endpoint for snapshots.
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

$folderName = '';
if (is_string($payload['folderName'] ?? null)) {
    $folderName = trim((string) $payload['folderName']);
} elseif (is_string($payload['filename'] ?? null)) {
    $folderName = trim((string) $payload['filename']);
}

try {
    $config = load_config();
    $normalizedFolderName = ocr_debug_export_normalize_folder_name($folderName);
    if ($normalizedFolderName === null) {
        json_response(['error' => 'Snapshot folder not found'], 404);
        exit;
    }

    $exportDirectory = ocr_debug_export_directory_path_from_name($config, $normalizedFolderName);
    $folderExisted = $exportDirectory !== null && is_dir($exportDirectory);
    if ($folderExisted && !delete_directory_recursive($exportDirectory)) {
        throw new RuntimeException('Could not delete snapshot folder.');
    }

    $pdo = ocr_debug_runs_pdo();
    $pdo->beginTransaction();
    try {
        $comparisonIdsStatement = $pdo->prepare(
            'SELECT id FROM comparison_runs WHERE left_folder_name = :folder_name OR right_folder_name = :folder_name'
        );
        $comparisonIdsStatement->execute([':folder_name' => $normalizedFolderName]);
        $comparisonIds = array_values(array_filter(array_map(
            static fn ($value): int => (int) $value,
            $comparisonIdsStatement->fetchAll(PDO::FETCH_COLUMN) ?: []
        ), static fn (int $value): bool => $value > 0));

        if ($comparisonIds !== []) {
            $placeholders = implode(',', array_fill(0, count($comparisonIds), '?'));
            $deleteComparisonJobs = $pdo->prepare(
                "DELETE FROM comparison_run_jobs WHERE comparison_run_id IN ($placeholders)"
            );
            $deleteComparisonJobs->execute($comparisonIds);

            $deleteComparisons = $pdo->prepare(
                "DELETE FROM comparison_runs WHERE id IN ($placeholders)"
            );
            $deleteComparisons->execute($comparisonIds);
        }

        $snapshotIdsStatement = $pdo->prepare('SELECT id FROM analysis_snapshots WHERE folder_name = :folder_name');
        $snapshotIdsStatement->execute([':folder_name' => $normalizedFolderName]);
        $snapshotIds = array_values(array_filter(array_map(
            static fn ($value): int => (int) $value,
            $snapshotIdsStatement->fetchAll(PDO::FETCH_COLUMN) ?: []
        ), static fn (int $value): bool => $value > 0));

        if ($snapshotIds !== []) {
            $placeholders = implode(',', array_fill(0, count($snapshotIds), '?'));
            $deleteSnapshotJobs = $pdo->prepare(
                "DELETE FROM analysis_snapshot_jobs WHERE snapshot_id IN ($placeholders)"
            );
            $deleteSnapshotJobs->execute($snapshotIds);

            $deleteSnapshots = $pdo->prepare(
                "DELETE FROM analysis_snapshots WHERE id IN ($placeholders)"
            );
            $deleteSnapshots->execute($snapshotIds);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    if (!$folderExisted && $snapshotIds === [] && $comparisonIds === []) {
        json_response(['error' => 'Snapshot folder not found'], 404);
        exit;
    }

    json_response([
        'ok' => true,
        'deleted' => $normalizedFolderName,
        'exportDirectory' => $exportDirectory ?? '',
        'folderDeleted' => $folderExisted,
        'snapshotRowsDeleted' => count($snapshotIds),
        'comparisonRowsDeleted' => count($comparisonIds),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
