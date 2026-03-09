<?php
declare(strict_types=1);

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function now_iso(): string
{
    return date(DATE_ATOM);
}

function load_config(): array
{
    $configPath = DATA_DIR . '/config.json';
    if (!is_file($configPath)) {
        throw new RuntimeException('Missing data/config.json');
    }

    $raw = file_get_contents($configPath);
    if ($raw === false) {
        throw new RuntimeException('Could not read config.json');
    }

    $config = json_decode($raw, true);
    if (!is_array($config)) {
        throw new RuntimeException('Invalid config.json');
    }

    $inboxDirectory = $config['inboxDirectory'] ?? '';
    $jobsDirectory = $config['jobsDirectory'] ?? '';

    if (!is_string($inboxDirectory) || $inboxDirectory === '') {
        throw new RuntimeException('config.json: inboxDirectory is required');
    }
    if (!is_string($jobsDirectory) || $jobsDirectory === '') {
        throw new RuntimeException('config.json: jobsDirectory is required');
    }

    return [
        'inboxDirectory' => $inboxDirectory,
        'jobsDirectory' => $jobsDirectory,
    ];
}

function load_clients(): array
{
    $clientsPath = DATA_DIR . '/clients.json';
    if (!is_file($clientsPath)) {
        return [];
    }

    $raw = file_get_contents($clientsPath);
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }

    $clients = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }

        $name = '';
        if (is_string($row['name'] ?? null)) {
            $name = trim((string) $row['name']);
        } elseif (is_string($row['firstName'] ?? null) || is_string($row['lastName'] ?? null)) {
            $firstName = is_string($row['firstName'] ?? null) ? trim((string) $row['firstName']) : '';
            $lastName = is_string($row['lastName'] ?? null) ? trim((string) $row['lastName']) : '';
            $name = trim($firstName . ' ' . $lastName);
        }

        $dirName = '';
        if (is_string($row['dirName'] ?? null)) {
            $dirName = trim((string) $row['dirName']);
        } elseif (is_string($row['folderName'] ?? null)) {
            $dirName = trim((string) $row['folderName']);
        } elseif ($name !== '') {
            $dirName = $name;
        }

        $pinRaw = $row['personalIdentityNumber'] ?? '';
        if (!is_string($pinRaw) && !is_int($pinRaw) && !is_float($pinRaw)) {
            continue;
        }
        $pin = trim((string) $pinRaw);

        if ($name === '' || $dirName === '' || $pin === '') {
            continue;
        }

        $clients[] = [
            'name' => $name,
            'dirName' => $dirName,
            'personalIdentityNumber' => $pin,
        ];
    }

    return $clients;
}

function ensure_directory(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0777, true) && !is_dir($path)) {
        throw new RuntimeException('Could not create directory: ' . $path);
    }
}

function is_pdf_filename(string $filename): bool
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'pdf';
}

function is_stable_file(string $path, int $minAgeSeconds = 2): bool
{
    $mtime = filemtime($path);
    if ($mtime === false) {
        return false;
    }

    return (time() - $mtime) >= $minAgeSeconds;
}

function generate_job_id(): string
{
    return date('Ymd_His') . '_' . bin2hex(random_bytes(3));
}

function write_json_file(string $path, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Could not encode JSON for: ' . $path);
    }

    if (file_put_contents($path, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Could not write file: ' . $path);
    }
}

function load_json_file(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return null;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function pdftotext_path(): ?string
{
    static $cached = null;
    static $loaded = false;

    if ($loaded) {
        return $cached;
    }

    $loaded = true;
    $path = trim((string) shell_exec('command -v pdftotext 2>/dev/null'));
    $cached = $path !== '' ? $path : null;
    return $cached;
}

function extract_text_from_pdf(string $pdfPath): ?string
{
    $binary = pdftotext_path();
    if ($binary === null) {
        return null;
    }

    $tmp = tempnam(sys_get_temp_dir(), 'docflow_ocr_');
    if ($tmp === false) {
        return '';
    }

    $command = escapeshellarg($binary)
        . ' -layout '
        . escapeshellarg($pdfPath)
        . ' '
        . escapeshellarg($tmp)
        . ' 2>/dev/null';

    exec($command, $output, $code);
    if ($code !== 0) {
        @unlink($tmp);
        return '';
    }

    $text = file_get_contents($tmp);
    @unlink($tmp);

    return $text === false ? '' : $text;
}

function fallback_ocr_text_for_inbox_pdf(string $inboxPdfPath): string
{
    $txtPath = preg_replace('/\.pdf$/i', '.txt', $inboxPdfPath);
    if (!is_string($txtPath) || !is_file($txtPath)) {
        return '';
    }

    $text = file_get_contents($txtPath);
    return $text === false ? '' : $text;
}

function match_client_dir_name(string $ocrText, array $clients): ?string
{
    $normalizedText = preg_replace('/\D+/', '', $ocrText);
    if (!is_string($normalizedText)) {
        $normalizedText = '';
    }

    foreach ($clients as $client) {
        $pin = $client['personalIdentityNumber'] ?? '';
        $dirName = $client['dirName'] ?? '';

        if (!is_string($pin) || !is_string($dirName) || $dirName === '') {
            continue;
        }

        $pinNoHyphen = str_replace('-', '', $pin);
        $pinDigits = preg_replace('/\D+/', '', $pinNoHyphen);
        if (!is_string($pinDigits) || $pinDigits === '') {
            continue;
        }

        if (str_contains($ocrText, $pin) || str_contains($ocrText, $pinNoHyphen) || str_contains($normalizedText, $pinDigits)) {
            return $dirName;
        }
    }

    return null;
}

function initial_job_data(string $jobId, string $originalFilename): array
{
    $now = now_iso();

    return [
        'id' => $jobId,
        'status' => 'processing',
        'originalFilename' => $originalFilename,
        'createdAt' => $now,
        'updatedAt' => $now,
        'files' => [
            'sourcePdf' => 'source.pdf',
            'reviewPdf' => 'review.pdf',
            'ocrText' => 'ocr.txt',
            'extracted' => 'extracted.json',
        ],
    ];
}

function process_claimed_job(string $jobDir, string $sourcePdfPath, string $inboxPdfPath, array $clients): array
{
    $ocrText = '';
    $extracted = extract_text_from_pdf($sourcePdfPath);

    if ($extracted === null) {
        $ocrText = fallback_ocr_text_for_inbox_pdf($inboxPdfPath);
    } else {
        $ocrText = $extracted;
    }

    $reviewPdfPath = $jobDir . '/review.pdf';
    if (!copy($sourcePdfPath, $reviewPdfPath)) {
        throw new RuntimeException('Could not create review.pdf');
    }

    $ocrPath = $jobDir . '/ocr.txt';
    if (file_put_contents($ocrPath, $ocrText) === false) {
        throw new RuntimeException('Could not write ocr.txt');
    }

    $matched = match_client_dir_name($ocrText, $clients);
    $extractedData = [
        'matchedClientDirName' => $matched,
    ];

    write_json_file($jobDir . '/extracted.json', $extractedData);

    return $extractedData;
}

function claim_and_process_inbox(array $config, array $clients): void
{
    $inboxDir = $config['inboxDirectory'];
    $jobsDir = $config['jobsDirectory'];

    if (!is_dir($inboxDir)) {
        return;
    }

    ensure_directory($jobsDir);

    $entries = scandir($inboxDir);
    if ($entries === false) {
        return;
    }

    $pdfPaths = [];
    foreach ($entries as $entry) {
        $path = $inboxDir . DIRECTORY_SEPARATOR . $entry;
        if (!is_file($path)) {
            continue;
        }
        if (!is_pdf_filename($entry)) {
            continue;
        }
        if (!is_stable_file($path, 2)) {
            continue;
        }

        $pdfPaths[] = $path;
    }

    sort($pdfPaths, SORT_STRING);

    foreach ($pdfPaths as $inboxPdfPath) {
        $originalFilename = basename($inboxPdfPath);

        $jobId = generate_job_id();
        $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $jobId;
        while (is_dir($jobDir)) {
            $jobId = generate_job_id();
            $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $jobId;
        }

        $jobData = initial_job_data($jobId, $originalFilename);

        try {
            ensure_directory($jobDir);

            $sourcePdfPath = $jobDir . '/source.pdf';
            if (!rename($inboxPdfPath, $sourcePdfPath)) {
                throw new RuntimeException('Could not claim inbox PDF');
            }

            write_json_file($jobDir . '/job.json', $jobData);

            // Deliberate delay per file to make processing state visible in the UI.
            sleep(4);

            process_claimed_job($jobDir, $sourcePdfPath, $inboxPdfPath, $clients);

            $jobData['status'] = 'ready';
            $jobData['updatedAt'] = now_iso();
            write_json_file($jobDir . '/job.json', $jobData);
        } catch (Throwable $e) {
            $jobData['status'] = 'failed';
            $jobData['updatedAt'] = now_iso();
            $jobData['error'] = $e->getMessage();
            try {
                write_json_file($jobDir . '/job.json', $jobData);
            } catch (Throwable $ignored) {
                // Ignore secondary failure while writing failed state.
            }
        }
    }
}

function read_jobs_state(array $config): array
{
    $jobsDir = $config['jobsDirectory'];
    ensure_directory($jobsDir);

    $entries = scandir($jobsDir);
    if ($entries === false) {
        return [
            'processingJobs' => [],
            'readyJobs' => [],
            'failedJobs' => [],
        ];
    }

    $processing = [];
    $ready = [];
    $failed = [];

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($jobDir)) {
            continue;
        }

        $job = load_json_file($jobDir . '/job.json');
        if (!is_array($job)) {
            continue;
        }

        $status = $job['status'] ?? '';
        $id = is_string($job['id'] ?? null) ? $job['id'] : $entry;
        $originalFilename = is_string($job['originalFilename'] ?? null) ? $job['originalFilename'] : 'unknown.pdf';
        $createdAt = is_string($job['createdAt'] ?? null) ? $job['createdAt'] : '';

        if ($status === 'ready') {
            $matchedClientDirName = null;
            $extracted = load_json_file($jobDir . '/extracted.json');
            if (is_array($extracted) && array_key_exists('matchedClientDirName', $extracted)) {
                $value = $extracted['matchedClientDirName'];
                if (is_string($value) || $value === null) {
                    $matchedClientDirName = $value;
                }
            }

            $ready[] = [
                'id' => $id,
                'originalFilename' => $originalFilename,
                'status' => 'ready',
                'createdAt' => $createdAt,
                'matchedClientDirName' => $matchedClientDirName,
            ];
        } elseif ($status === 'processing') {
            $processing[] = [
                'id' => $id,
                'originalFilename' => $originalFilename,
                'status' => 'processing',
                'createdAt' => $createdAt,
            ];
        } elseif ($status === 'failed') {
            $failed[] = [
                'id' => $id,
                'originalFilename' => $originalFilename,
                'status' => 'failed',
                'createdAt' => $createdAt,
                'error' => is_string($job['error'] ?? null) ? $job['error'] : null,
            ];
        }
    }

    $sortByCreatedDesc = static function (array $a, array $b): int {
        return strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? ''));
    };

    usort($ready, $sortByCreatedDesc);
    usort($processing, $sortByCreatedDesc);
    usort($failed, $sortByCreatedDesc);

    return [
        'processingJobs' => $processing,
        'readyJobs' => $ready,
        'failedJobs' => $failed,
    ];
}

function is_valid_job_id(string $id): bool
{
    return preg_match('/^[A-Za-z0-9_-]+$/', $id) === 1;
}

function delete_directory_recursive(string $path): bool
{
    if (!is_dir($path)) {
        return true;
    }

    $entries = scandir($path);
    if ($entries === false) {
        return false;
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $item = $path . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($item)) {
            if (!delete_directory_recursive($item)) {
                return false;
            }
            continue;
        }

        if (!unlink($item)) {
            return false;
        }
    }

    return rmdir($path);
}

function unique_inbox_target_path(string $inboxDir, string $filename): string
{
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $suffix = '';
    $counter = 1;

    while (true) {
        $candidateName = $base . $suffix . ($ext !== '' ? '.' . $ext : '');
        $candidatePath = $inboxDir . DIRECTORY_SEPARATOR . $candidateName;
        if (!file_exists($candidatePath)) {
            return $candidatePath;
        }

        $suffix = '_restored_' . $counter;
        $counter++;
    }
}

function reset_all_jobs(array $config): array
{
    $jobsDir = $config['jobsDirectory'];
    $inboxDir = $config['inboxDirectory'];

    ensure_directory($jobsDir);
    ensure_directory($inboxDir);

    $entries = scandir($jobsDir);
    if ($entries === false) {
        throw new RuntimeException('Could not read jobs directory');
    }

    $restoredSources = 0;
    $removedJobFolders = 0;
    $errors = [];

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($jobDir)) {
            continue;
        }

        $job = load_json_file($jobDir . '/job.json');
        $originalFilename = is_array($job) && is_string($job['originalFilename'] ?? null)
            ? trim((string) $job['originalFilename'])
            : '';
        if ($originalFilename === '') {
            $originalFilename = $entry . '.pdf';
        }

        $sourcePath = $jobDir . '/source.pdf';
        if (is_file($sourcePath)) {
            $targetPath = unique_inbox_target_path($inboxDir, $originalFilename);
            if (!rename($sourcePath, $targetPath)) {
                $errors[] = 'Could not restore source.pdf for job ' . $entry;
                continue;
            }
            $restoredSources++;
        }

        if (!delete_directory_recursive($jobDir)) {
            $errors[] = 'Could not remove job directory ' . $entry;
            continue;
        }

        $removedJobFolders++;
    }

    return [
        'restoredSources' => $restoredSources,
        'removedJobFolders' => $removedJobFolders,
        'errors' => $errors,
    ];
}
