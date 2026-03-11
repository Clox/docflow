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

function config_file_path(): string
{
    return DATA_DIR . '/config.json';
}

function load_raw_config(): array
{
    $configPath = config_file_path();
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

    return $config;
}

function save_raw_config(array $config): void
{
    write_json_file(config_file_path(), $config);
}

function load_config(): array
{
    $config = load_raw_config();

    $inboxDirectory = $config['inboxDirectory'] ?? '';
    $jobsDirectory = $config['jobsDirectory'] ?? '';
    $outputBaseDirectory = $config['outputBaseDirectory'] ?? '';

    if (!is_string($inboxDirectory) || $inboxDirectory === '') {
        throw new RuntimeException('config.json: inboxDirectory is required');
    }
    if (!is_string($jobsDirectory) || $jobsDirectory === '') {
        throw new RuntimeException('config.json: jobsDirectory is required');
    }
    if (!is_string($outputBaseDirectory)) {
        $outputBaseDirectory = '';
    }

    return [
        'inboxDirectory' => $inboxDirectory,
        'jobsDirectory' => $jobsDirectory,
        'outputBaseDirectory' => trim($outputBaseDirectory),
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

function positive_int(mixed $value, int $fallback = 1): int
{
    if (is_int($value)) {
        return $value < 1 ? 1 : $value;
    }

    if (is_float($value)) {
        $intValue = (int) floor($value);
        return $intValue < 1 ? 1 : $intValue;
    }

    if (is_string($value) && trim($value) !== '') {
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        if ($parsed !== false) {
            $intValue = (int) $parsed;
            return $intValue < 1 ? 1 : $intValue;
        }
    }

    return $fallback;
}

function load_categories(): array
{
    $categoriesPath = DATA_DIR . '/categories.json';
    if (!is_file($categoriesPath)) {
        return [];
    }

    $raw = file_get_contents($categoriesPath);
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }

    $categories = [];
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }

        $rulesIn = $row['rules'] ?? [];
        $rules = [];
        if (is_array($rulesIn)) {
            foreach ($rulesIn as $ruleIn) {
                if (!is_array($ruleIn)) {
                    continue;
                }

                $rules[] = [
                    'text' => is_string($ruleIn['text'] ?? null) ? trim((string) $ruleIn['text']) : '',
                    'score' => positive_int($ruleIn['score'] ?? 1, 1),
                ];
            }
        }

        $categories[] = [
            'name' => is_string($row['name'] ?? null) ? trim((string) $row['name']) : '',
            'path' => is_string($row['path'] ?? null) ? trim((string) $row['path']) : '',
            'minScore' => positive_int($row['minScore'] ?? 1, 1),
            'rules' => $rules,
        ];
    }

    return $categories;
}

function load_matching_settings(): array
{
    $path = DATA_DIR . '/matching.json';
    if (!is_file($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    $rows = $decoded['replacements'] ?? $decoded;
    if (!is_array($rows)) {
        return [];
    }

    $replacements = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $from = is_string($row['from'] ?? null) ? trim((string) $row['from']) : '';
        $to = is_string($row['to'] ?? null) ? trim((string) $row['to']) : '';
        if ($from === '' || $to === '') {
            continue;
        }

        $replacements[] = [
            'from' => $from,
            'to' => $to,
        ];
    }

    return $replacements;
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

function fallback_ocr_text_from_path(?string $txtPath): string
{
    if (!is_string($txtPath) || $txtPath === '' || !is_file($txtPath)) {
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

function lowercase_text(string $text): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($text, 'UTF-8');
    }

    return strtolower($text);
}

function utf8_chars(string $text): array
{
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($chars) ? $chars : [];
}

function build_inverse_single_char_map(array $replacementMap): array
{
    $inverse = [];
    foreach ($replacementMap as $from => $to) {
        if (!is_string($from) || !is_string($to) || $from === '' || $to === '') {
            continue;
        }

        if (count(utf8_chars($from)) !== 1 || count(utf8_chars($to)) !== 1) {
            continue;
        }

        if (!isset($inverse[$to])) {
            $inverse[$to] = [];
        }

        if (!in_array($from, $inverse[$to], true)) {
            $inverse[$to][] = $from;
        }
    }

    return $inverse;
}

function build_rule_match_pattern(string $ruleText, array $inverseMap): ?string
{
    $chars = utf8_chars($ruleText);
    if (count($chars) === 0) {
        return null;
    }

    $parts = [];
    foreach ($chars as $char) {
        if (preg_match('/\s/u', $char) === 1) {
            $parts[] = '\s+';
            continue;
        }

        $choices = [$char];
        $lower = lowercase_text($char);
        if (isset($inverseMap[$lower]) && is_array($inverseMap[$lower])) {
            foreach ($inverseMap[$lower] as $fromChar) {
                if (is_string($fromChar) && $fromChar !== '') {
                    $choices[] = $fromChar;
                }
            }
        }

        $choices = array_values(array_unique($choices));
        if (count($choices) === 1) {
            $parts[] = preg_quote($choices[0], '/');
            continue;
        }

        $charClass = '';
        foreach ($choices as $choice) {
            $charClass .= preg_quote($choice, '/');
        }
        $parts[] = '[' . $charClass . ']';
    }

    return '/' . implode('', $parts) . '/iu';
}

function find_source_text_for_rule(string $ocrText, string $ruleText, array $inverseMap): string
{
    if ($ruleText === '') {
        return '';
    }

    $pattern = build_rule_match_pattern($ruleText, $inverseMap);
    if (is_string($pattern) && @preg_match($pattern, $ocrText, $matches) === 1) {
        $match = $matches[0] ?? '';
        return is_string($match) && $match !== '' ? $match : $ruleText;
    }

    $literal = '/' . preg_quote($ruleText, '/') . '/iu';
    if (@preg_match($literal, $ocrText, $matches) === 1) {
        $match = $matches[0] ?? '';
        return is_string($match) && $match !== '' ? $match : $ruleText;
    }

    return $ruleText;
}

function replacement_map(array $matchingSettings): array
{
    $map = [];
    foreach ($matchingSettings as $row) {
        if (!is_array($row)) {
            continue;
        }

        $from = is_string($row['from'] ?? null) ? trim((string) $row['from']) : '';
        $to = is_string($row['to'] ?? null) ? trim((string) $row['to']) : '';
        if ($from === '' || $to === '') {
            continue;
        }

        $map[lowercase_text($from)] = lowercase_text($to);
    }

    return $map;
}

function normalize_for_matching(string $text, array $replacementMap): string
{
    $normalized = lowercase_text($text);
    if (count($replacementMap) === 0) {
        return $normalized;
    }

    return strtr($normalized, $replacementMap);
}

function find_category_matches(string $ocrText, array $categories, array $replacementMap): array
{
    $normalizedOcr = normalize_for_matching($ocrText, $replacementMap);
    $inverseMap = build_inverse_single_char_map($replacementMap);
    $matches = [];

    foreach ($categories as $categoryIndex => $category) {
        if (!is_array($category)) {
            continue;
        }

        $rules = $category['rules'] ?? [];
        if (!is_array($rules) || count($rules) === 0) {
            continue;
        }

        $minScore = positive_int($category['minScore'] ?? 1, 1);
        $categoryName = is_string($category['name'] ?? null) ? trim((string) $category['name']) : '';
        $categoryPath = is_string($category['path'] ?? null) ? trim((string) $category['path']) : '';
        if ($categoryName === '') {
            $categoryName = $categoryPath !== '' ? $categoryPath : 'Unnamed category';
        }

        $score = 0;
        $matchedRules = [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $ruleText = is_string($rule['text'] ?? null) ? trim((string) $rule['text']) : '';
            if ($ruleText === '') {
                continue;
            }

            $ruleScore = positive_int($rule['score'] ?? 1, 1);
            $ruleTextLower = normalize_for_matching($ruleText, $replacementMap);

            if (!str_contains($normalizedOcr, $ruleTextLower)) {
                continue;
            }

            $sourceText = find_source_text_for_rule($ocrText, $ruleText, $inverseMap);
            $score += $ruleScore;
            $matchedRules[] = [
                'text' => $ruleText,
                'sourceText' => $sourceText,
                'score' => $ruleScore,
            ];
        }

        if ($score < $minScore || count($matchedRules) === 0) {
            continue;
        }

        $matches[] = [
            'name' => $categoryName,
            'path' => $categoryPath,
            'minScore' => $minScore,
            'score' => $score,
            'matchedRules' => $matchedRules,
            '_categoryOrder' => is_int($categoryIndex) ? $categoryIndex : 0,
        ];
    }

    usort($matches, static function (array $a, array $b): int {
        $scoreCompare = (int) ($b['score'] ?? 0) <=> (int) ($a['score'] ?? 0);
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }
        return (int) ($a['_categoryOrder'] ?? 0) <=> (int) ($b['_categoryOrder'] ?? 0);
    });

    foreach ($matches as &$match) {
        unset($match['_categoryOrder']);
    }
    unset($match);

    return $matches;
}

function initial_job_data(string $jobId, string $originalFilename, ?string $fallbackTxtPath = null): array
{
    $now = now_iso();

    $jobData = [
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

    if ($fallbackTxtPath !== null && $fallbackTxtPath !== '') {
        $jobData['fallbackTxtPath'] = $fallbackTxtPath;
    }

    return $jobData;
}

function process_claimed_job(string $jobDir, string $sourcePdfPath, ?string $fallbackTxtPath, array $clients, array $categories, array $replacementMap): array
{
    $ocrText = '';
    $extracted = extract_text_from_pdf($sourcePdfPath);

    if ($extracted === null) {
        $ocrText = fallback_ocr_text_from_path($fallbackTxtPath);
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
    $categoryMatches = find_category_matches($ocrText, $categories, $replacementMap);
    $extractedData = [
        'matchedClientDirName' => $matched,
        'categoryMatches' => $categoryMatches,
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
        $fallbackTxtPath = preg_replace('/\.pdf$/i', '.txt', $inboxPdfPath);
        if (!is_string($fallbackTxtPath) || !is_file($fallbackTxtPath)) {
            $fallbackTxtPath = null;
        }

        $jobId = generate_job_id();
        $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $jobId;
        while (is_dir($jobDir)) {
            $jobId = generate_job_id();
            $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $jobId;
        }

        $jobData = initial_job_data($jobId, $originalFilename, $fallbackTxtPath);

        try {
            ensure_directory($jobDir);

            $sourcePdfPath = $jobDir . '/source.pdf';
            if (!rename($inboxPdfPath, $sourcePdfPath)) {
                throw new RuntimeException('Could not claim inbox PDF');
            }

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

function next_processing_job_id(array $config): ?string
{
    $jobsDir = $config['jobsDirectory'];
    ensure_directory($jobsDir);

    $entries = scandir($jobsDir);
    if ($entries === false) {
        return null;
    }

    $processingJobs = [];
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

        if (($job['status'] ?? '') !== 'processing') {
            continue;
        }

        $processingJobs[] = [
            'id' => is_string($job['id'] ?? null) ? $job['id'] : $entry,
            'createdAt' => is_string($job['createdAt'] ?? null) ? $job['createdAt'] : '',
        ];
    }

    if (count($processingJobs) === 0) {
        return null;
    }

    usort($processingJobs, static function (array $a, array $b): int {
        return strcmp((string) $a['createdAt'], (string) $b['createdAt']);
    });

    return (string) $processingJobs[0]['id'];
}

function process_job_by_id(array $config, array $clients, array $categories, array $matchingSettings, string $jobId): void
{
    if (!is_valid_job_id($jobId)) {
        return;
    }

    $jobsDir = $config['jobsDirectory'];
    $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $jobId;
    $jobJsonPath = $jobDir . '/job.json';
    $sourcePdfPath = $jobDir . '/source.pdf';

    $jobData = load_json_file($jobJsonPath);
    if (!is_array($jobData)) {
        return;
    }

    if (($jobData['status'] ?? '') !== 'processing') {
        return;
    }

    try {
        if (!is_file($sourcePdfPath)) {
            throw new RuntimeException('Missing source.pdf');
        }

        $fallbackTxtPath = is_string($jobData['fallbackTxtPath'] ?? null)
            ? (string) $jobData['fallbackTxtPath']
            : null;

        $replacementMap = replacement_map($matchingSettings);
        process_claimed_job($jobDir, $sourcePdfPath, $fallbackTxtPath, $clients, $categories, $replacementMap);

        $jobData['status'] = 'ready';
        $jobData['updatedAt'] = now_iso();
        write_json_file($jobJsonPath, $jobData);
    } catch (Throwable $e) {
        $jobData['status'] = 'failed';
        $jobData['updatedAt'] = now_iso();
        $jobData['error'] = $e->getMessage();
        write_json_file($jobJsonPath, $jobData);
    }
}

function run_processing_worker(array $config): void
{
    $jobsDir = $config['jobsDirectory'];
    ensure_directory($jobsDir);

    $lockPath = $jobsDir . DIRECTORY_SEPARATOR . '.worker.lock';
    $lockHandle = fopen($lockPath, 'c+');
    if ($lockHandle === false) {
        return;
    }

    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        fclose($lockHandle);
        return;
    }

    try {
        $clients = load_clients();
        $categories = load_categories();
        $matchingSettings = load_matching_settings();

        while (true) {
            $jobId = next_processing_job_id($config);
            if ($jobId === null) {
                break;
            }

            process_job_by_id($config, $clients, $categories, $matchingSettings, $jobId);
        }
    } finally {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

function trigger_processing_worker(): void
{
    $scriptPath = PROJECT_ROOT . 'scripts/process-jobs.php';
    if (!is_file($scriptPath)) {
        return;
    }

    $command = escapeshellarg(PHP_BINARY)
        . ' '
        . escapeshellarg($scriptPath)
        . ' > /dev/null 2>&1 &';

    exec($command);
}

function read_jobs_state(array $config): array
{
    $jobsDir = $config['jobsDirectory'];
    ensure_directory($jobsDir);
    $categories = load_categories();
    $categoryOrderByName = [];
    foreach ($categories as $index => $category) {
        if (!is_array($category)) {
            continue;
        }

        $name = is_string($category['name'] ?? null) ? trim((string) $category['name']) : '';
        $path = is_string($category['path'] ?? null) ? trim((string) $category['path']) : '';
        $displayName = $name !== '' ? $name : $path;
        if ($displayName === '' || isset($categoryOrderByName[$displayName])) {
            continue;
        }

        $categoryOrderByName[$displayName] = is_int($index) ? $index : 999999;
    }

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
            $topMatchedCategoryName = null;
            $topMatchedCategoryScore = null;
            $extracted = load_json_file($jobDir . '/extracted.json');
            if (is_array($extracted) && array_key_exists('matchedClientDirName', $extracted)) {
                $value = $extracted['matchedClientDirName'];
                if (is_string($value) || $value === null) {
                    $matchedClientDirName = $value;
                }
            }

            if (is_array($extracted) && isset($extracted['categoryMatches']) && is_array($extracted['categoryMatches'])) {
                $bestOrder = 999999;
                foreach ($extracted['categoryMatches'] as $categoryMatch) {
                    if (!is_array($categoryMatch)) {
                        continue;
                    }

                    $name = is_string($categoryMatch['name'] ?? null) ? trim((string) $categoryMatch['name']) : '';
                    $score = $categoryMatch['score'] ?? null;
                    $numericScore = is_int($score) || is_float($score) || (is_string($score) && is_numeric($score))
                        ? (float) $score
                        : null;

                    if ($name === '' || $numericScore === null || $numericScore <= 0) {
                        continue;
                    }

                    $order = isset($categoryOrderByName[$name]) ? (int) $categoryOrderByName[$name] : 999999;
                    if ($topMatchedCategoryScore === null || $numericScore > $topMatchedCategoryScore) {
                        $topMatchedCategoryName = $name;
                        $topMatchedCategoryScore = $numericScore;
                        $bestOrder = $order;
                        continue;
                    }

                    if ($numericScore === $topMatchedCategoryScore && $order < $bestOrder) {
                        $topMatchedCategoryName = $name;
                        $topMatchedCategoryScore = $numericScore;
                        $bestOrder = $order;
                    }
                }
            }

            $ready[] = [
                'id' => $id,
                'originalFilename' => $originalFilename,
                'status' => 'ready',
                'createdAt' => $createdAt,
                'matchedClientDirName' => $matchedClientDirName,
                'topMatchedCategoryName' => $topMatchedCategoryName,
                'topMatchedCategoryScore' => $topMatchedCategoryScore,
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
