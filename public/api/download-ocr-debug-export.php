<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

function ocr_debug_export_add_directory_to_zip(ZipArchive $zip, string $baseDirectory): void
{
    $baseDirectory = rtrim($baseDirectory, DIRECTORY_SEPARATOR);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDirectory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $itemPath = $item->getPathname();
        $relativePath = substr($itemPath, strlen($baseDirectory) + 1);
        if (!is_string($relativePath) || $relativePath === '') {
            continue;
        }

        $zipPath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
        if ($item->isDir()) {
            $zip->addEmptyDir($zipPath);
            continue;
        }

        $zip->addFile($itemPath, $zipPath);
    }
}

// Developer/admin-only download endpoint for snapshots.
$folderName = isset($_GET['folderName']) && is_string($_GET['folderName'])
    ? trim((string) $_GET['folderName'])
    : (isset($_GET['filename']) && is_string($_GET['filename']) ? trim((string) $_GET['filename']) : '');
if ($folderName === '') {
    json_response(['error' => 'Export folder not found'], 404);
    exit;
}

$zipPath = null;
try {
    $config = load_config();
    $exportDirectory = ocr_debug_export_directory_path_from_name($config, $folderName);
    if ($exportDirectory === null || !is_dir($exportDirectory)) {
        json_response(['error' => 'Export folder not found'], 404);
        exit;
    }

    $zipPath = tempnam(sys_get_temp_dir(), 'docflow-ocr-debug-');
    if ($zipPath === false) {
        throw new RuntimeException('Kunde inte skapa temporärt zip-arkiv.');
    }

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Kunde inte öppna zip-arkiv.');
        }
        ocr_debug_export_add_directory_to_zip($zip, $exportDirectory);
        $zip->close();
    } else {
        $zipBinary = trim((string) shell_exec('command -v zip 2>/dev/null'));
        if ($zipBinary === '') {
            throw new RuntimeException('Varken ZipArchive eller zip-kommandot finns tillgängligt.');
        }

        $command = sprintf(
            'cd %s && %s -qr %s . 2>&1',
            escapeshellarg($exportDirectory),
            escapeshellarg($zipBinary),
            escapeshellarg($zipPath)
        );
        exec($command, $output, $exitCode);
        if ($exitCode !== 0 || !is_file($zipPath)) {
            throw new RuntimeException('Kunde inte skapa zip-arkiv.');
        }
    }

    $downloadName = 'snapshot-' . basename($exportDirectory) . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . (string) filesize($zipPath));
    readfile($zipPath);
} catch (Throwable $e) {
    if (!headers_sent()) {
        json_response(['error' => $e->getMessage()], 500);
    } else {
        throw $e;
    }
} finally {
    if (is_string($zipPath) && $zipPath !== '' && is_file($zipPath)) {
        @unlink($zipPath);
    }
}
