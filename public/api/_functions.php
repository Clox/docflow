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
    $ocrSkipExistingText = $config['ocrSkipExistingText'] ?? true;
    $ocrOptimizeLevel = $config['ocrOptimizeLevel'] ?? 1;
    $stateUpdateTransport = $config['stateUpdateTransport'] ?? 'polling';
    $ocrTextExtractionMethod = $config['ocrTextExtractionMethod'] ?? 'layout';
    $ocrPdfTextSubstitutions = sanitize_ocr_pdf_text_substitutions(
        $config['ocrPdfTextSubstitutions'] ?? []
    );

    if (!is_string($inboxDirectory) || $inboxDirectory === '') {
        throw new RuntimeException('config.json: inboxDirectory is required');
    }
    if (!is_string($jobsDirectory) || $jobsDirectory === '') {
        throw new RuntimeException('config.json: jobsDirectory is required');
    }
    if (!is_string($outputBaseDirectory)) {
        $outputBaseDirectory = '';
    }
    if (!is_bool($ocrSkipExistingText)) {
        $ocrSkipExistingText = filter_var($ocrSkipExistingText, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (!is_bool($ocrSkipExistingText)) {
            $ocrSkipExistingText = true;
        }
    }
    if (!is_int($ocrOptimizeLevel)) {
        $parsedOptimizeLevel = filter_var($ocrOptimizeLevel, FILTER_VALIDATE_INT);
        $ocrOptimizeLevel = $parsedOptimizeLevel !== false ? (int) $parsedOptimizeLevel : 1;
    }
    if ($ocrOptimizeLevel < 0 || $ocrOptimizeLevel > 3) {
        $ocrOptimizeLevel = 1;
    }
    if (!is_string($stateUpdateTransport)) {
        $stateUpdateTransport = 'polling';
    }
    $stateUpdateTransport = trim(strtolower($stateUpdateTransport));
    if ($stateUpdateTransport !== 'sse') {
        $stateUpdateTransport = 'polling';
    }
    if (!is_string($ocrTextExtractionMethod)) {
        $ocrTextExtractionMethod = 'layout';
    }
    $ocrTextExtractionMethod = trim($ocrTextExtractionMethod);
    if ($ocrTextExtractionMethod !== 'bbox') {
        $ocrTextExtractionMethod = 'layout';
    }

    return [
        'inboxDirectory' => $inboxDirectory,
        'jobsDirectory' => $jobsDirectory,
        'outputBaseDirectory' => trim($outputBaseDirectory),
        'ocrSkipExistingText' => $ocrSkipExistingText,
        'ocrOptimizeLevel' => $ocrOptimizeLevel,
        'stateUpdateTransport' => $stateUpdateTransport,
        'ocrTextExtractionMethod' => $ocrTextExtractionMethod,
        'ocrPdfTextSubstitutions' => $ocrPdfTextSubstitutions,
    ];
}

function sanitize_ocr_pdf_text_substitutions($rows): array
{
    if (!is_array($rows)) {
        return [];
    }

    $normalized = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $from = is_string($row['from'] ?? null) ? trim((string) $row['from']) : '';
        $to = is_string($row['to'] ?? null) ? trim((string) $row['to']) : '';
        if ($from === '' || $to === '') {
            continue;
        }

        $normalized[] = [
            'from' => $from,
            'to' => $to,
        ];
    }

    return $normalized;
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

function sender_repository_instance(): ?\Docflow\Senders\SenderRepository
{
    static $initialized = false;
    static $repository = null;

    if ($initialized) {
        return $repository;
    }

    $initialized = true;
    try {
        $pdo = \Docflow\Database\Connection::make();
        $repository = new \Docflow\Senders\SenderRepository($pdo);
    } catch (Throwable $e) {
        $repository = null;
    }

    return $repository;
}

function detect_org_number_from_ocr_text(string $ocrText): ?string
{
    $pattern = '/\b(?:\d{6}[-\s]?\d{4}|(?:19|20)\d{2}[-\s]?\d{2}[-\s]?\d{2}[-\s]?\d{4})\b/u';
    $labelPattern = '/organisations?(?:nummer|nr)|org\\.?\\s*nr/iu';
    $lines = preg_split('/\R/u', $ocrText);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            if (!is_string($line)) {
                continue;
            }

            if (@preg_match($labelPattern, $line) !== 1) {
                continue;
            }

            $matches = [];
            if (@preg_match_all($pattern, $line, $matches) < 1) {
                continue;
            }

            $candidates = is_array($matches[0] ?? null) ? $matches[0] : [];
            foreach ($candidates as $candidate) {
                if (!is_string($candidate)) {
                    continue;
                }
                $normalized = \Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber($candidate);
                if ($normalized !== null) {
                    return $normalized;
                }
            }
        }
    }

    $matches = [];
    if (@preg_match_all($pattern, $ocrText, $matches) < 1) {
        return null;
    }

    $candidates = is_array($matches[0] ?? null) ? $matches[0] : [];
    foreach ($candidates as $candidate) {
        if (!is_string($candidate)) {
            continue;
        }
        $normalized = \Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber($candidate);
        if ($normalized !== null) {
            return $normalized;
        }
    }

    return null;
}

function sender_lookup_result(?string $orgNumber, ?string $bankgiro, ?string $plusgiro): array
{
    $repository = sender_repository_instance();

    $query = [
        'orgNumber' => $orgNumber,
        'bankgiro' => $bankgiro,
        'plusgiro' => $plusgiro,
    ];

    if ($repository === null) {
        return [
            'query' => $query,
            'matched' => false,
            'matchedBy' => null,
            'matchedValue' => null,
            'sender' => null,
        ];
    }

    try {
        $match = $repository->findByDocumentIdentifiers($orgNumber, $bankgiro, $plusgiro);
    } catch (Throwable $e) {
        return [
            'query' => $query,
            'matched' => false,
            'matchedBy' => null,
            'matchedValue' => null,
            'sender' => null,
            'error' => $e->getMessage(),
        ];
    }

    if (!is_array($match)) {
        return [
            'query' => $query,
            'matched' => false,
            'matchedBy' => null,
            'matchedValue' => null,
            'sender' => null,
        ];
    }

    $sender = [
        'id' => isset($match['id']) ? (int) $match['id'] : 0,
        'name' => is_string($match['name'] ?? null) ? $match['name'] : '',
        'orgNumber' => is_string($match['org_number'] ?? null) ? $match['org_number'] : null,
        'domain' => is_string($match['domain'] ?? null) ? $match['domain'] : null,
        'kind' => is_string($match['kind'] ?? null) ? $match['kind'] : null,
        'confidence' => isset($match['confidence']) ? (float) $match['confidence'] : 1.0,
    ];

    if (array_key_exists('payment_requires_ocr', $match)) {
        $sender['paymentRequiresOcr'] = ((int) $match['payment_requires_ocr']) === 1;
    }
    if (is_string($match['payment_source'] ?? null)) {
        $sender['paymentSource'] = $match['payment_source'];
    }
    if (array_key_exists('payment_confidence', $match)) {
        $sender['paymentConfidence'] = (float) $match['payment_confidence'];
    }

    return [
        'query' => $query,
        'matched' => true,
        'matchedBy' => is_string($match['matchedBy'] ?? null) ? $match['matchedBy'] : null,
        'matchedValue' => is_string($match['matchedValue'] ?? null) ? $match['matchedValue'] : null,
        'sender' => $sender,
    ];
}

function load_senders(): array
{
    $repository = sender_repository_instance();
    if ($repository === null) {
        return [];
    }

    try {
        $rows = $repository->listAll();
    } catch (Throwable $e) {
        return [];
    }

    $senders = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $id = isset($row['id']) ? (int) $row['id'] : 0;
        $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
        if ($id < 1 || $name === '') {
            continue;
        }

        $senders[] = [
            'id' => $id,
            'name' => $name,
        ];
    }

    return $senders;
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

function system_archive_categories_definitions(): array
{
    return [
        'invoice' => [
            'name' => 'Faktura',
            'minScore' => 2,
            'rules' => [
                ['text' => 'faktura', 'score' => 4],
                ['text' => 'förfallodatum', 'score' => 3],
                ['text' => 'faktura', 'score' => 2],
                ['text' => 'förfallodatum', 'score' => 3],
                ['text' => 'bankgiro', 'score' => 5],
                ['text' => 'plusgiro', 'score' => 5],
                ['text' => 'ocr', 'score' => 5],
                ['text' => 'ocr-nummer', 'score' => 5],
                ['text' => 'fakturanummer', 'score' => 5],
                ['text' => 'autogiro', 'score' => 3],
                ['text' => 'e-faktura', 'score' => 4],
                ['text' => 'betalningsmottagare', 'score' => 2],
            ],
        ],
    ];
}

function normalize_system_archive_category_with_defaults(mixed $input, array $defaults): array
{
    $category = is_array($input) ? $input : [];
    $name = is_string($category['name'] ?? null) ? trim((string) $category['name']) : '';
    if ($name === '') {
        $name = is_string($defaults['name'] ?? null) ? trim((string) $defaults['name']) : '';
    }

    $defaultMinScore = positive_int($defaults['minScore'] ?? 1, 1);
    $defaultRules = [];
    if (is_array($defaults['rules'] ?? null)) {
        foreach ($defaults['rules'] as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $defaultRules[] = [
                'text' => is_string($rule['text'] ?? null) ? trim((string) $rule['text']) : '',
                'score' => positive_int($rule['score'] ?? 1, 1),
            ];
        }
    }

    $rawRules = $category['rules'] ?? [];
    $rules = [];
    if (is_array($rawRules)) {
        foreach ($rawRules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $rules[] = [
                'text' => is_string($rule['text'] ?? null) ? trim((string) $rule['text']) : '',
                'score' => positive_int($rule['score'] ?? 1, 1),
            ];
        }
    }
    if (count($rules) === 0) {
        $rules = $defaultRules;
    }

    return [
        'name' => $name,
        'isSystemCategory' => true,
        'minScore' => positive_int($category['minScore'] ?? $defaultMinScore, $defaultMinScore),
        'rules' => $rules,
    ];
}

function system_archive_categories_template(): array
{
    $definitions = system_archive_categories_definitions();
    $categories = [];
    foreach ($definitions as $key => $defaults) {
        $categories[$key] = normalize_system_archive_category_with_defaults([], $defaults);
    }
    return $categories;
}

function normalize_system_archive_categories(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];
    $definitions = system_archive_categories_definitions();
    $categories = [];
    foreach ($definitions as $key => $defaults) {
        $categories[$key] = normalize_system_archive_category_with_defaults($decoded[$key] ?? [], $defaults);
    }
    return $categories;
}

function load_categories(): array
{
    $structure = load_archive_structure_data();
    $archiveFolders = $structure['archiveFolders'];
    $systemCategories = is_array($structure['systemCategories'] ?? null)
        ? $structure['systemCategories']
        : system_archive_categories_template();
    $categories = [];

    foreach ($archiveFolders as $archiveFolderIndex => $archiveFolder) {
        if (!is_array($archiveFolder)) {
            continue;
        }

        $archiveFolderName = is_string($archiveFolder['name'] ?? null) ? trim((string) $archiveFolder['name']) : '';
        $archiveFolderPath = is_string($archiveFolder['path'] ?? null) ? trim((string) $archiveFolder['path']) : '';
        $archiveFolderCategories = $archiveFolder['categories'] ?? [];
        if (!is_array($archiveFolderCategories)) {
            continue;
        }

        foreach ($archiveFolderCategories as $categoryIndex => $category) {
            if (!is_array($category)) {
                continue;
            }

            $rulesIn = $category['rules'] ?? [];
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
                'id' => 'f' . $archiveFolderIndex . '_c' . $categoryIndex,
                'name' => is_string($category['name'] ?? null) ? trim((string) $category['name']) : '',
                'path' => $archiveFolderPath,
                'archiveFolderName' => $archiveFolderName,
                'isSystemCategory' => false,
                'minScore' => positive_int($category['minScore'] ?? 1, 1),
                'rules' => $rules,
            ];
        }
    }

    foreach ($systemCategories as $systemKey => $systemCategory) {
        if (!is_array($systemCategory)) {
            continue;
        }

        $systemRules = [];
        $rawSystemRules = $systemCategory['rules'] ?? [];
        if (is_array($rawSystemRules)) {
            foreach ($rawSystemRules as $rule) {
                if (!is_array($rule)) {
                    continue;
                }
                $systemRules[] = [
                    'text' => is_string($rule['text'] ?? null) ? trim((string) $rule['text']) : '',
                    'score' => positive_int($rule['score'] ?? 1, 1),
                ];
            }
        }

        $categories[] = [
            'id' => 'system_' . $systemKey,
            'name' => is_string($systemCategory['name'] ?? null) ? trim((string) $systemCategory['name']) : '',
            'path' => '',
            'archiveFolderName' => 'Systemkategorier',
            'isSystemCategory' => true,
            'systemCategoryKey' => (string) $systemKey,
            'minScore' => positive_int($systemCategory['minScore'] ?? 1, 1),
            'rules' => $systemRules,
        ];
    }

    return $categories;
}

function normalize_archive_structure(array $input): array
{
    $archiveFolders = [];
    foreach ($input as $row) {
        if (!is_array($row)) {
            continue;
        }

        $archiveFolders[] = [
            'name' => is_string($row['name'] ?? null) ? trim((string) $row['name']) : '',
            'path' => is_string($row['path'] ?? null) ? trim((string) $row['path']) : '',
            'categories' => normalize_archive_categories($row['categories'] ?? []),
        ];
    }

    return $archiveFolders;
}

function normalize_archive_structure_data(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];

    $rawArchiveFolders = is_array($decoded['archiveFolders'] ?? null) ? $decoded['archiveFolders'] : [];
    $rawSystemCategories = is_array($decoded['systemCategories'] ?? null) ? $decoded['systemCategories'] : [];

    $archiveFolders = normalize_archive_structure($rawArchiveFolders);
    $systemCategories = normalize_system_archive_categories($rawSystemCategories);

    return [
        'archiveFolders' => $archiveFolders,
        'systemCategories' => $systemCategories,
    ];
}

function load_archive_structure_data(): array
{
    $archivePath = DATA_DIR . '/archive-structure.json';
    if (!is_file($archivePath)) {
        $initial = normalize_archive_structure_data([]);
        try {
            write_json_file($archivePath, $initial);
        } catch (Throwable $ignored) {
            // Keep runtime resilient if file cannot be created right now.
        }
        return $initial;
    }

    $raw = file_get_contents($archivePath);
    if ($raw === false || trim($raw) === '') {
        $initial = normalize_archive_structure_data([]);
        try {
            write_json_file($archivePath, $initial);
        } catch (Throwable $ignored) {
            // Keep runtime resilient if file cannot be repaired right now.
        }
        return $initial;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $initial = normalize_archive_structure_data([]);
        try {
            write_json_file($archivePath, $initial);
        } catch (Throwable $ignored) {
            // Keep runtime resilient if file cannot be repaired right now.
        }
        return $initial;
    }

    $normalized = normalize_archive_structure_data($decoded);
    if (json_encode($decoded) !== json_encode($normalized)) {
        try {
            write_json_file($archivePath, $normalized);
        } catch (Throwable $ignored) {
            // Keep runtime resilient if file cannot be updated right now.
        }
    }

    return $normalized;
}

function load_archive_structure(): array
{
    $structure = load_archive_structure_data();
    return is_array($structure['archiveFolders'] ?? null) ? $structure['archiveFolders'] : [];
}

function load_system_archive_categories(): array
{
    $structure = load_archive_structure_data();
    return is_array($structure['systemCategories'] ?? null)
        ? $structure['systemCategories']
        : system_archive_categories_template();
}

function normalize_archive_categories(mixed $input): array
{
    if (!is_array($input)) {
        return [];
    }

    $categories = [];
    foreach ($input as $row) {
        if (!is_array($row)) {
            continue;
        }
        $categories[] = normalize_archive_category($row);
    }

    return $categories;
}

function normalize_archive_category(array $input): array
{
    $rulesIn = $input['rules'] ?? [];
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

    if (count($rules) === 0) {
        $rules[] = [
            'text' => '',
            'score' => 1,
        ];
    }

    return [
        'name' => is_string($input['name'] ?? null) ? trim((string) $input['name']) : '',
        'minScore' => positive_int($input['minScore'] ?? 1, 1),
        'rules' => $rules,
    ];
}

function default_invoice_field_min_confidence(): float
{
    return 0.70;
}

function sanitize_invoice_field_min_confidence($value, ?float $fallback = null): float
{
    $base = is_float($fallback) ? $fallback : default_invoice_field_min_confidence();
    if (!(is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)))) {
        return $base;
    }

    $parsed = (float) $value;
    if ($parsed < 0.0) {
        return 0.0;
    }
    if ($parsed > 1.0) {
        return 1.0;
    }

    return round($parsed, 3);
}

function load_matching_settings_payload(): array
{
    $defaultThreshold = default_invoice_field_min_confidence();
    $defaultPayload = [
        'replacements' => [],
        'invoiceFieldMinConfidence' => $defaultThreshold,
    ];

    $path = DATA_DIR . '/matching.json';
    if (!is_file($path)) {
        return $defaultPayload;
    }

    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return $defaultPayload;
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return $defaultPayload;
    }

    $rows = $decoded['replacements'] ?? $decoded;
    if (!is_array($rows)) {
        $rows = [];
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

    return [
        'replacements' => $replacements,
        'invoiceFieldMinConfidence' => sanitize_invoice_field_min_confidence(
            $decoded['invoiceFieldMinConfidence'] ?? null,
            $defaultThreshold
        ),
    ];
}

function load_matching_settings(): array
{
    $payload = load_matching_settings_payload();
    $rows = $payload['replacements'] ?? [];
    return is_array($rows) ? $rows : [];
}

function load_invoice_field_min_confidence(): float
{
    $payload = load_matching_settings_payload();
    return sanitize_invoice_field_min_confidence($payload['invoiceFieldMinConfidence'] ?? null);
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

function pdftoppm_path(): ?string
{
    static $cached = null;
    static $loaded = false;

    if ($loaded) {
        return $cached;
    }

    $loaded = true;
    $path = trim((string) shell_exec('command -v pdftoppm 2>/dev/null'));
    $cached = $path !== '' ? $path : null;
    return $cached;
}

function ocrmypdf_path(): ?string
{
    static $cached = null;
    static $loaded = false;

    if ($loaded) {
        return $cached;
    }

    $loaded = true;
    $path = trim((string) shell_exec('command -v ocrmypdf 2>/dev/null'));
    $cached = $path !== '' ? $path : null;
    return $cached;
}

function tesseract_path(): ?string
{
    static $cached = null;
    static $loaded = false;

    if ($loaded) {
        return $cached;
    }

    $loaded = true;
    $path = trim((string) shell_exec('command -v tesseract 2>/dev/null'));
    $cached = $path !== '' ? $path : null;
    return $cached;
}

function jbig2_path(): ?string
{
    static $cached = null;
    static $loaded = false;

    if ($loaded) {
        return $cached;
    }

    $loaded = true;
    $path = trim((string) shell_exec('command -v jbig2 2>/dev/null'));
    $cached = $path !== '' ? $path : null;
    return $cached;
}

function jbig2_status_payload(): array
{
    $installed = jbig2_path() !== null;

    return [
        'installed' => $installed,
        'installCommand' => 'sudo apt install jbig2',
        'binary' => $installed ? 'jbig2' : null,
    ];
}

function docflow_ocrmypdf_plugin_path(): ?string
{
    $path = PROJECT_ROOT . '/docflow-ocrmypdf-plugin/docflow_ocrmypdf_plugin.py';
    return is_file($path) ? $path : null;
}

function docflow_generated_transform_script_path(): string
{
    return DATA_DIR . '/docflow_ocr_pdf_transform.py';
}

function write_docflow_ocr_transform_script(array $substitutions): ?string
{
    $normalized = sanitize_ocr_pdf_text_substitutions($substitutions);
    if ($normalized === []) {
        $path = docflow_generated_transform_script_path();
        if (is_file($path)) {
            @unlink($path);
        }
        return null;
    }

    $scriptPath = docflow_generated_transform_script_path();
    $jsonPayload = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($jsonPayload)) {
        return null;
    }
    $jsonLiteral = var_export($jsonPayload, true);
    $script = <<<'PY'
"""Auto-generated by Docflow.

This file is rewritten from OCR settings. Edit the substitutions in Docflow,
not here.
"""

import json

SUBSTITUTIONS = json.loads(
PY;
    $script .= $jsonLiteral . ")\n\n";
    $script .= <<<'PY'
def _apply(text):
    if not isinstance(text, str):
        return text
    for row in SUBSTITUTIONS:
        source = row.get('from', '')
        replacement = row.get('to', '')
        if not source or not replacement:
            continue
        text = text.replace(source, replacement)
    return text


def transform_word(text, *, page_number, bbox, title, options):
    return _apply(text)


def transform_sidecar(text, *, page_number, options):
    return _apply(text)
PY;

    if (file_put_contents($scriptPath, $script) === false) {
        return null;
    }

    return $scriptPath;
}

function run_ocrmypdf(
    string $inputPdfPath,
    string $outputPdfPath,
    string $sidecarTextPath,
    bool $skipExistingText,
    int $optimizeLevel,
    array $ocrPdfTextSubstitutions = []
): bool
{
    $GLOBALS['docflow_last_ocrmypdf_error'] = null;

    $binary = ocrmypdf_path();
    if ($binary === null || !is_file($inputPdfPath)) {
        return false;
    }

    if (is_file($outputPdfPath)) {
        @unlink($outputPdfPath);
    }
    if (is_file($sidecarTextPath)) {
        @unlink($sidecarTextPath);
    }

    $pluginSegment = '';
    $normalizedSubstitutions = sanitize_ocr_pdf_text_substitutions($ocrPdfTextSubstitutions);
    if ($normalizedSubstitutions !== []) {
        $pluginPath = docflow_ocrmypdf_plugin_path();
        $transformScriptPath = write_docflow_ocr_transform_script($normalizedSubstitutions);
        if ($pluginPath === null || $transformScriptPath === null || !is_file($transformScriptPath)) {
            $GLOBALS['docflow_last_ocrmypdf_error'] = 'Docflow OCR-transform plugin could not be prepared';
            return false;
        }

        $pluginSegment = '--plugin '
            . escapeshellarg($pluginPath)
            . ' --docflow-transform-script '
            . escapeshellarg($transformScriptPath)
            . ' ';
    }

    $modeFlag = $skipExistingText ? '--mode skip' : '--mode redo';
    $safeOptimizeLevel = $optimizeLevel < 0 || $optimizeLevel > 3 ? 1 : $optimizeLevel;
    $deskewFlag = $skipExistingText ? '--deskew ' : '';
    $command = escapeshellarg($binary)
        . ' '
        . $pluginSegment
        . ' -l swe '
        . $deskewFlag
        . '--oversample 400 --tesseract-thresholding sauvola --tesseract-pagesegmode 6 --output-type pdf '
        . '-O' . $safeOptimizeLevel
        . ' '
        . $modeFlag
        . ' --sidecar '
        . escapeshellarg($sidecarTextPath)
        . ' '
        . escapeshellarg($inputPdfPath)
        . ' '
        . escapeshellarg($outputPdfPath)
        . ' 2>&1';

    exec($command, $output, $code);
    if ($code !== 0) {
        $lastErrorOutput = trim(implode("\n", $output));
        $GLOBALS['docflow_last_ocrmypdf_error'] = $lastErrorOutput !== ''
            ? $lastErrorOutput
            : 'ocrmypdf exited with code ' . $code;
        return false;
    }

    return $code === 0 && is_file($outputPdfPath);
}

function last_ocrmypdf_error(): ?string
{
    $value = $GLOBALS['docflow_last_ocrmypdf_error'] ?? null;
    return is_string($value) && trim($value) !== '' ? trim($value) : null;
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

function utf8_strlen_safe(string $text): int
{
    if (function_exists('mb_strlen')) {
        return (int) mb_strlen($text, 'UTF-8');
    }

    return count(utf8_chars($text));
}

function median_float(array $values, float $fallback): float
{
    $filtered = [];
    foreach ($values as $value) {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            $numeric = (float) $value;
            if ($numeric > 0) {
                $filtered[] = $numeric;
            }
        }
    }

    if ($filtered === []) {
        return $fallback;
    }

    sort($filtered, SORT_NUMERIC);
    $count = count($filtered);
    $middle = intdiv($count, 2);
    if ($count % 2 === 1) {
        return $filtered[$middle];
    }

    return ($filtered[$middle - 1] + $filtered[$middle]) / 2.0;
}

function extract_bbox_layout_xml_from_pdf(string $pdfPath): ?string
{
    $binary = pdftotext_path();
    if ($binary === null || !is_file($pdfPath)) {
        return null;
    }

    $command = escapeshellarg($binary)
        . ' -bbox-layout '
        . escapeshellarg($pdfPath)
        . ' - 2>/dev/null';
    $xml = shell_exec($command);
    if (!is_string($xml) || trim($xml) === '') {
        return '';
    }

    return $xml;
}

function bbox_entries_are_near(array $left, array $right, float $xTolerance, float $yTolerance): bool
{
    $leftText = is_string($left['text'] ?? null) ? trim((string) $left['text']) : '';
    $rightText = is_string($right['text'] ?? null) ? trim((string) $right['text']) : '';
    if ($leftText === '' || $leftText !== $rightText) {
        return false;
    }

    return abs(((float) ($left['x0'] ?? 0.0)) - ((float) ($right['x0'] ?? 0.0))) <= $xTolerance
        && abs(((float) ($left['x1'] ?? 0.0)) - ((float) ($right['x1'] ?? 0.0))) <= $xTolerance
        && abs(((float) ($left['y0'] ?? 0.0)) - ((float) ($right['y0'] ?? 0.0))) <= $yTolerance
        && abs(((float) ($left['y1'] ?? 0.0)) - ((float) ($right['y1'] ?? 0.0))) <= $yTolerance;
}

function dedupe_bbox_words(array $words): array
{
    if ($words === []) {
        return [];
    }

    usort($words, static function (array $a, array $b): int {
        $yCompare = ((float) ($a['y0'] ?? 0.0)) <=> ((float) ($b['y0'] ?? 0.0));
        if ($yCompare !== 0) {
            return $yCompare;
        }

        $xCompare = ((float) ($a['x0'] ?? 0.0)) <=> ((float) ($b['x0'] ?? 0.0));
        if ($xCompare !== 0) {
            return $xCompare;
        }

        $leftWidth = ((float) ($a['x1'] ?? 0.0)) - ((float) ($a['x0'] ?? 0.0));
        $rightWidth = ((float) ($b['x1'] ?? 0.0)) - ((float) ($b['x0'] ?? 0.0));
        return $rightWidth <=> $leftWidth;
    });

    $deduped = [];
    foreach ($words as $word) {
        $isDuplicate = false;
        foreach ($deduped as $existing) {
            if (bbox_entries_are_near($existing, $word, 1.5, 1.5)) {
                $isDuplicate = true;
                break;
            }
        }
        if (!$isDuplicate) {
            $deduped[] = $word;
        }
    }

    return $deduped;
}

function dedupe_bbox_lines(array $lines): array
{
    if ($lines === []) {
        return [];
    }

    $normalized = [];
    foreach ($lines as $line) {
        $words = dedupe_bbox_words(is_array($line['words'] ?? null) ? $line['words'] : []);
        if ($words === []) {
            continue;
        }

        usort($words, static function (array $a, array $b): int {
            return ((float) ($a['x0'] ?? 0.0)) <=> ((float) ($b['x0'] ?? 0.0));
        });

        $parts = [];
        $x0 = null;
        $y0 = null;
        $x1 = null;
        $y1 = null;
        foreach ($words as $word) {
            $parts[] = (string) ($word['text'] ?? '');
            $wordX0 = (float) ($word['x0'] ?? 0.0);
            $wordY0 = (float) ($word['y0'] ?? 0.0);
            $wordX1 = (float) ($word['x1'] ?? 0.0);
            $wordY1 = (float) ($word['y1'] ?? 0.0);
            $x0 = $x0 === null ? $wordX0 : min($x0, $wordX0);
            $y0 = $y0 === null ? $wordY0 : min($y0, $wordY0);
            $x1 = $x1 === null ? $wordX1 : max($x1, $wordX1);
            $y1 = $y1 === null ? $wordY1 : max($y1, $wordY1);
        }

        $normalized[] = [
            'x0' => $x0 ?? 0.0,
            'y0' => $y0 ?? 0.0,
            'x1' => $x1 ?? 0.0,
            'y1' => $y1 ?? 0.0,
            'text' => implode(' ', $parts),
            'words' => $words,
        ];
    }

    usort($normalized, static function (array $a, array $b): int {
        $yCompare = ((float) ($a['y0'] ?? 0.0)) <=> ((float) ($b['y0'] ?? 0.0));
        if ($yCompare !== 0) {
            return $yCompare;
        }
        return ((float) ($a['x0'] ?? 0.0)) <=> ((float) ($b['x0'] ?? 0.0));
    });

    $deduped = [];
    foreach ($normalized as $line) {
        $isDuplicate = false;
        foreach ($deduped as $existing) {
            if (bbox_entries_are_near($existing, $line, 2.0, 2.0)) {
                $isDuplicate = true;
                break;
            }
        }
        if (!$isDuplicate) {
            $deduped[] = $line;
        }
    }

    return $deduped;
}

function parse_bbox_layout_objects(string $xml): array
{
    if (trim($xml) === '') {
        return [];
    }

    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if ($loaded !== true) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('x', 'http://www.w3.org/1999/xhtml');

    $pageNodes = $xpath->query('//x:page');
    if ($pageNodes === false) {
        return [];
    }

    $pages = [];
    foreach ($pageNodes as $pageNode) {
        if (!$pageNode instanceof DOMElement) {
            continue;
        }

        $page = [
            'width' => (float) $pageNode->getAttribute('width'),
            'height' => (float) $pageNode->getAttribute('height'),
            'lines' => [],
        ];

        $lineNodes = $xpath->query('.//x:line', $pageNode);
        if ($lineNodes === false) {
            $pages[] = $page;
            continue;
        }

        foreach ($lineNodes as $lineNode) {
            if (!$lineNode instanceof DOMElement) {
                continue;
            }

            $line = [
                'x0' => (float) $lineNode->getAttribute('xMin'),
                'y0' => (float) $lineNode->getAttribute('yMin'),
                'x1' => (float) $lineNode->getAttribute('xMax'),
                'y1' => (float) $lineNode->getAttribute('yMax'),
                'text' => '',
                'words' => [],
            ];

            $wordNodes = $xpath->query('./x:word', $lineNode);
            if ($wordNodes === false) {
                continue;
            }

            $parts = [];
            foreach ($wordNodes as $wordNode) {
                if (!$wordNode instanceof DOMElement) {
                    continue;
                }

                $text = trim($wordNode->textContent);
                if ($text === '') {
                    continue;
                }

                $line['words'][] = [
                    'x0' => (float) $wordNode->getAttribute('xMin'),
                    'y0' => (float) $wordNode->getAttribute('yMin'),
                    'x1' => (float) $wordNode->getAttribute('xMax'),
                    'y1' => (float) $wordNode->getAttribute('yMax'),
                    'text' => $text,
                ];
                $parts[] = $text;
            }

            if ($line['words'] === []) {
                continue;
            }

            $line['text'] = implode(' ', $parts);
            $page['lines'][] = $line;
        }

        $page['lines'] = dedupe_bbox_lines($page['lines']);
        $pages[] = $page;
    }

    return $pages;
}

function render_grid_text_from_bbox_objects(array $pages): string
{
    if ($pages === []) {
        return '';
    }

    $wordWidths = [];
    $lineHeights = [];

    foreach ($pages as $page) {
        $lines = is_array($page['lines'] ?? null) ? $page['lines'] : [];
        foreach ($lines as $line) {
            $height = ((float) ($line['y1'] ?? 0.0)) - ((float) ($line['y0'] ?? 0.0));
            if ($height > 0) {
                $lineHeights[] = $height;
            }

            $words = is_array($line['words'] ?? null) ? $line['words'] : [];
            foreach ($words as $word) {
                $text = is_string($word['text'] ?? null) ? (string) $word['text'] : '';
                $charCount = utf8_strlen_safe($text);
                $width = ((float) ($word['x1'] ?? 0.0)) - ((float) ($word['x0'] ?? 0.0));
                if ($charCount > 0 && $width > 0) {
                    $wordWidths[] = $width / $charCount;
                }
            }
        }
    }

    $charWidth = median_float($wordWidths, 6.0);
    $lineHeight = median_float($lineHeights, 10.0);
    $renderedPages = [];

    foreach ($pages as $page) {
        $lines = is_array($page['lines'] ?? null) ? $page['lines'] : [];
        if ($lines === []) {
            $renderedPages[] = '';
            continue;
        }

        $grid = [];
        $rowTops = [];
        usort($lines, static function (array $a, array $b): int {
            $yCompare = ((float) ($a['y0'] ?? 0.0)) <=> ((float) ($b['y0'] ?? 0.0));
            if ($yCompare !== 0) {
                return $yCompare;
            }
            return ((float) ($a['x0'] ?? 0.0)) <=> ((float) ($b['x0'] ?? 0.0));
        });

        foreach ($lines as $line) {
            $words = is_array($line['words'] ?? null) ? $line['words'] : [];
            if ($words === []) {
                continue;
            }

            usort($words, static function (array $a, array $b): int {
                return ((float) ($a['x0'] ?? 0.0)) <=> ((float) ($b['x0'] ?? 0.0));
            });

            $lineTop = (float) ($line['y0'] ?? 0.0);
            $candidateRow = (int) round($lineTop / max($lineHeight, 1.0));
            while (isset($rowTops[$candidateRow]) && abs($rowTops[$candidateRow] - $lineTop) > ($lineHeight * 0.35)) {
                $candidateRow++;
            }
            if (!isset($grid[$candidateRow])) {
                $grid[$candidateRow] = [];
                $rowTops[$candidateRow] = $lineTop;
            }

            $buffer = $grid[$candidateRow];
            $cursor = count($buffer);
            foreach ($words as $word) {
                $targetCol = (int) round(((float) ($word['x0'] ?? 0.0)) / max($charWidth, 1.0));
                if ($targetCol <= $cursor) {
                    $targetCol = $cursor > 0 ? $cursor + 1 : 0;
                }
                while (count($buffer) < $targetCol) {
                    $buffer[] = ' ';
                }
                foreach (utf8_chars((string) ($word['text'] ?? '')) as $char) {
                    $buffer[] = $char;
                }
                $cursor = count($buffer);
            }
            $grid[$candidateRow] = $buffer;
        }

        if ($grid === []) {
            $renderedPages[] = '';
            continue;
        }

        ksort($grid, SORT_NUMERIC);
        $pageLines = [];
        $previousRow = null;
        foreach ($grid as $rowIndex => $buffer) {
            if ($previousRow !== null) {
                $gap = max(0, $rowIndex - $previousRow - 1);
                for ($i = 0; $i < $gap; $i++) {
                    $pageLines[] = '';
                }
            }
            $pageLines[] = rtrim(implode('', $buffer));
            $previousRow = $rowIndex;
        }

        $renderedPages[] = rtrim(implode("\n", $pageLines));
    }

    return trim(implode("\n\n", $renderedPages));
}

function extract_bbox_layout_objects_from_pdf(string $pdfPath): ?array
{
    $xml = extract_bbox_layout_xml_from_pdf($pdfPath);
    if ($xml === null) {
        return null;
    }

    $pages = parse_bbox_layout_objects($xml);
    return is_array($pages) ? $pages : [];
}

function extract_grid_ocr_text_from_bbox_pdf(string $pdfPath): ?string
{
    $pages = extract_bbox_layout_objects_from_pdf($pdfPath);
    if ($pages === null) {
        return null;
    }

    return render_grid_text_from_bbox_objects($pages);
}

function parse_tesseract_tsv(string $tsv): array
{
    $lines = preg_split('/\R/u', trim($tsv));
    if (!is_array($lines) || count($lines) < 2) {
        return [];
    }

    $header = str_getcsv((string) array_shift($lines), "\t");
    if (!is_array($header) || $header === []) {
        return [];
    }

    $rows = [];
    foreach ($lines as $line) {
        if (!is_string($line) || $line === '') {
            continue;
        }

        $values = str_getcsv($line, "\t");
        if (!is_array($values) || count($values) < count($header)) {
            continue;
        }

        $row = [];
        foreach ($header as $index => $column) {
            if (!is_string($column) || $column === '') {
                continue;
            }
            $row[$column] = isset($values[$index]) ? (string) $values[$index] : '';
        }
        $rows[] = $row;
    }

    return $rows;
}

function render_grid_text_from_tesseract_tsv(string $tsv): string
{
    $rows = parse_tesseract_tsv($tsv);
    if ($rows === []) {
        return '';
    }

    $pages = [];
    $wordWidths = [];
    $lineHeights = [];

    foreach ($rows as $row) {
        $pageNum = isset($row['page_num']) ? (int) $row['page_num'] : 0;
        if ($pageNum < 1) {
            continue;
        }

        if (!isset($pages[$pageNum])) {
            $pages[$pageNum] = [
                'lines' => [],
            ];
        }

        $level = isset($row['level']) ? (int) $row['level'] : 0;
        $blockNum = isset($row['block_num']) ? (int) $row['block_num'] : 0;
        $parNum = isset($row['par_num']) ? (int) $row['par_num'] : 0;
        $lineNum = isset($row['line_num']) ? (int) $row['line_num'] : 0;
        $lineKey = $blockNum . ':' . $parNum . ':' . $lineNum;

        if ($level === 4) {
            $top = isset($row['top']) ? (int) $row['top'] : 0;
            $height = isset($row['height']) ? (int) $row['height'] : 0;
            $pages[$pageNum]['lines'][$lineKey] = [
                'top' => $top,
                'height' => $height,
                'words' => [],
            ];
            if ($height > 0) {
                $lineHeights[] = (float) $height;
            }
            continue;
        }

        if ($level !== 5) {
            continue;
        }

        $text = isset($row['text']) ? trim((string) $row['text']) : '';
        if ($text === '') {
            continue;
        }

        if (!isset($pages[$pageNum]['lines'][$lineKey])) {
            $pages[$pageNum]['lines'][$lineKey] = [
                'top' => isset($row['top']) ? (int) $row['top'] : 0,
                'height' => isset($row['height']) ? (int) $row['height'] : 0,
                'words' => [],
            ];
        }

        $word = [
            'left' => isset($row['left']) ? (int) $row['left'] : 0,
            'top' => isset($row['top']) ? (int) $row['top'] : 0,
            'width' => isset($row['width']) ? (int) $row['width'] : 0,
            'height' => isset($row['height']) ? (int) $row['height'] : 0,
            'text' => $text,
        ];
        $pages[$pageNum]['lines'][$lineKey]['words'][] = $word;

        $charCount = utf8_strlen_safe($text);
        if ($charCount > 0 && $word['width'] > 0) {
            $wordWidths[] = $word['width'] / $charCount;
        }
        if ($word['height'] > 0) {
            $lineHeights[] = (float) $word['height'];
        }
    }

    $charWidth = median_float($wordWidths, 18.0);
    $lineHeight = median_float($lineHeights, 40.0);
    $renderedPages = [];

    ksort($pages, SORT_NUMERIC);
    foreach ($pages as $page) {
        $grid = [];
        $rowTops = [];
        $lines = array_values($page['lines']);
        usort($lines, static function (array $a, array $b): int {
            return ($a['top'] ?? 0) <=> ($b['top'] ?? 0);
        });

        foreach ($lines as $line) {
            $words = is_array($line['words'] ?? null) ? $line['words'] : [];
            if ($words === []) {
                continue;
            }

            usort($words, static function (array $a, array $b): int {
                return ($a['left'] ?? 0) <=> ($b['left'] ?? 0);
            });

            $candidateRow = (int) round(((int) ($line['top'] ?? 0)) / max($lineHeight, 1.0));
            while (isset($rowTops[$candidateRow]) && abs($rowTops[$candidateRow] - (int) ($line['top'] ?? 0)) > ($lineHeight * 0.35)) {
                $candidateRow++;
            }
            if (!isset($grid[$candidateRow])) {
                $grid[$candidateRow] = [];
                $rowTops[$candidateRow] = (int) ($line['top'] ?? 0);
            }

            $buffer = $grid[$candidateRow];
            $cursor = count($buffer);
            foreach ($words as $word) {
                $targetCol = (int) round(((int) ($word['left'] ?? 0)) / max($charWidth, 1.0));
                if ($targetCol <= $cursor) {
                    $targetCol = $cursor > 0 ? $cursor + 1 : 0;
                }
                while (count($buffer) < $targetCol) {
                    $buffer[] = ' ';
                }
                foreach (utf8_chars((string) ($word['text'] ?? '')) as $char) {
                    $buffer[] = $char;
                }
                $cursor = count($buffer);
            }
            $grid[$candidateRow] = $buffer;
        }

        if ($grid === []) {
            $renderedPages[] = '';
            continue;
        }

        ksort($grid, SORT_NUMERIC);
        $pageLines = [];
        $previousRow = null;
        foreach ($grid as $rowIndex => $buffer) {
            if ($previousRow !== null) {
                $gap = max(0, $rowIndex - $previousRow - 1);
                for ($i = 0; $i < $gap; $i++) {
                    $pageLines[] = '';
                }
            }
            $pageLines[] = rtrim(implode('', $buffer));
            $previousRow = $rowIndex;
        }

        $renderedPages[] = rtrim(implode("\n", $pageLines));
    }

    return trim(implode("\n\n", $renderedPages));
}

function extract_grid_ocr_text_from_pdf(string $pdfPath): ?string
{
    $pdftoppmBinary = pdftoppm_path();
    $tesseractBinary = tesseract_path();
    if ($pdftoppmBinary === null || $tesseractBinary === null || !is_file($pdfPath)) {
        return null;
    }

    $tempDir = sys_get_temp_dir() . '/docflow_grid_' . bin2hex(random_bytes(8));
    if (!mkdir($tempDir, 0700) && !is_dir($tempDir)) {
        return null;
    }

    try {
        $imagePrefix = $tempDir . '/page';
        $renderCommand = escapeshellarg($pdftoppmBinary)
            . ' -r 300 -png '
            . escapeshellarg($pdfPath)
            . ' '
            . escapeshellarg($imagePrefix)
            . ' 2>/dev/null';
        exec($renderCommand, $renderOutput, $renderCode);
        if ($renderCode !== 0) {
            return '';
        }

        $images = glob($imagePrefix . '-*.png');
        if (!is_array($images) || $images === []) {
            return '';
        }
        sort($images, SORT_NATURAL);

        $pages = [];
        foreach ($images as $imagePath) {
            $command = escapeshellarg($tesseractBinary)
                . ' '
                . escapeshellarg($imagePath)
                . ' stdout -l swe --psm 6 tsv 2>/dev/null';
            $tsv = shell_exec($command);
            if (!is_string($tsv) || trim($tsv) === '') {
                $pages[] = '';
                continue;
            }
            $pages[] = render_grid_text_from_tesseract_tsv($tsv);
        }

        return trim(implode("\n\n", $pages));
    } finally {
        delete_directory_recursive($tempDir);
    }
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

function find_category_signal_matches(string $ocrText, array $categories, array $replacementMap): array
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
        $categoryId = is_string($category['id'] ?? null) ? trim((string) $category['id']) : '';
        $categoryName = is_string($category['name'] ?? null) ? trim((string) $category['name']) : '';
        $categoryPath = is_string($category['path'] ?? null) ? trim((string) $category['path']) : '';
        $archiveFolderName = is_string($category['archiveFolderName'] ?? null) ? trim((string) $category['archiveFolderName']) : '';
        if ($categoryId === '') {
            continue;
        }
        if ($categoryName === '') {
            $categoryName = 'Unnamed category';
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

        if (count($matchedRules) === 0) {
            continue;
        }

        $matches[] = [
            'id' => $categoryId,
            'name' => $categoryName,
            'path' => $categoryPath,
            'archiveFolderName' => $archiveFolderName,
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

function filter_category_matches_by_threshold(array $matches): array
{
    $filtered = [];
    foreach ($matches as $match) {
        if (!is_array($match)) {
            continue;
        }

        $score = $match['score'] ?? null;
        $numericScore = is_int($score) || is_float($score) || (is_string($score) && is_numeric($score))
            ? (float) $score
            : null;
        $minScore = positive_int($match['minScore'] ?? 1, 1);
        if ($numericScore === null || $numericScore < $minScore) {
            continue;
        }

        $filtered[] = $match;
    }

    return $filtered;
}

function find_category_matches(string $ocrText, array $categories, array $replacementMap): array
{
    $signalMatches = find_category_signal_matches($ocrText, $categories, $replacementMap);
    return filter_category_matches_by_threshold($signalMatches);
}

function split_categories_for_processing(array $categories): array
{
    $normalCategories = [];
    $invoiceSystemCategories = [];

    foreach ($categories as $category) {
        if (!is_array($category)) {
            continue;
        }

        $isSystemCategory = ($category['isSystemCategory'] ?? false) === true;
        $systemKey = is_string($category['systemCategoryKey'] ?? null)
            ? trim((string) $category['systemCategoryKey'])
            : '';

        if ($isSystemCategory) {
            if ($systemKey === 'invoice') {
                $invoiceSystemCategories[] = $category;
            }
            continue;
        }

        $normalCategories[] = $category;
    }

    return [
        'normalCategories' => $normalCategories,
        'invoiceSystemCategories' => $invoiceSystemCategories,
    ];
}

function empty_invoice_fields(): array
{
    return [
        'amount' => null,
        'dueDate' => null,
        'bankgiro' => null,
        'plusgiro' => null,
        'supplier' => null,
        'payee' => null,
        'iban' => null,
        'swift' => null,
        'ocr' => null,
        'autogiro' => false,
    ];
}

function invoice_ocr_lines(string $ocrText): array
{
    $lines = preg_split('/\R/u', $ocrText);
    return is_array($lines) ? $lines : [];
}

function build_tolerant_label_regex(string $label, array $replacementMap): ?string
{
    $trimmed = trim($label);
    if ($trimmed === '') {
        return null;
    }

    $inverseMap = build_inverse_single_char_map($replacementMap);
    $basePattern = build_rule_match_pattern($trimmed, $inverseMap);
    if (!is_string($basePattern) || strlen($basePattern) < 4) {
        return null;
    }

    $body = substr($basePattern, 1, -3);
    if (!is_string($body) || $body === '') {
        return null;
    }

    return '/\b' . $body . '\b/iu';
}

function find_label_hits(array $lines, array $labels, array $replacementMap): array
{
    $compiled = [];
    foreach ($labels as $label) {
        if (!is_string($label)) {
            continue;
        }

        $pattern = build_tolerant_label_regex($label, $replacementMap);
        if (!is_string($pattern)) {
            continue;
        }

        $compiled[] = [
            'label' => $label,
            'pattern' => $pattern,
        ];
    }

    if (count($compiled) === 0) {
        return [];
    }

    $hits = [];
    foreach ($lines as $index => $line) {
        if (!is_string($line) || trim($line) === '') {
            continue;
        }

        foreach ($compiled as $item) {
            $pattern = $item['pattern'];
            $labelMatches = [];
            if (@preg_match($pattern, $line, $labelMatches, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }

            $matched = $labelMatches[0] ?? null;
            $matchedText = is_array($matched) && is_string($matched[0] ?? null) ? (string) $matched[0] : '';
            $labelStart = is_array($matched) && is_int($matched[1] ?? null) ? (int) $matched[1] : 0;
            $labelEnd = $labelStart + strlen($matchedText);

            $hits[] = [
                'index' => is_int($index) ? $index : 0,
                'line' => $line,
                'pattern' => $pattern,
                'label' => $item['label'],
                'labelStart' => $labelStart,
                'labelEnd' => $labelEnd,
            ];
            break;
        }
    }

    return $hits;
}

function extract_label_tail_info_from_line(string $line, string $pattern): array
{
    $matches = [];
    if (@preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE) !== 1) {
        return [
            'text' => '',
            'offset' => 0,
        ];
    }

    $matched = $matches[0] ?? null;
    if (!is_array($matched) || count($matched) < 2) {
        return [
            'text' => '',
            'offset' => 0,
        ];
    }

    $matchText = is_string($matched[0] ?? null) ? (string) $matched[0] : '';
    $offset = is_int($matched[1] ?? null) ? (int) $matched[1] : -1;
    if ($offset < 0) {
        return [
            'text' => '',
            'offset' => 0,
        ];
    }

    $tailOffset = $offset + strlen($matchText);
    $tail = substr($line, $tailOffset);
    return [
        'text' => is_string($tail) ? $tail : '',
        'offset' => $tailOffset,
    ];
}

function extract_label_tail_from_line(string $line, string $pattern): string
{
    $matches = [];
    if (@preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE) !== 1) {
        return '';
    }

    $matched = $matches[0] ?? null;
    if (!is_array($matched) || count($matched) < 2) {
        return '';
    }

    $matchText = is_string($matched[0] ?? null) ? (string) $matched[0] : '';
    $offset = is_int($matched[1] ?? null) ? (int) $matched[1] : -1;
    if ($offset < 0) {
        return '';
    }

    $tailOffset = $offset + strlen($matchText);
    $tail = substr($line, $tailOffset);
    return is_string($tail) ? trim($tail) : '';
}

function invoice_nearby_line_indexes(array $lines, int $index, int $distance = 1): array
{
    $indexes = [];
    for ($step = 1; $step <= $distance; $step++) {
        $next = $index + $step;
        if (array_key_exists($next, $lines)) {
            $indexes[] = $next;
        }

        $prev = $index - $step;
        if (array_key_exists($prev, $lines)) {
            $indexes[] = $prev;
        }
    }

    return $indexes;
}

function normalize_inline_whitespace(string $text): string
{
    $collapsed = preg_replace('/\s+/u', ' ', $text);
    if (!is_string($collapsed)) {
        return trim($text);
    }

    return trim($collapsed);
}

function clamp_confidence(float $value): float
{
    if ($value < 0.0) {
        return 0.0;
    }
    if ($value > 1.0) {
        return 1.0;
    }
    return $value;
}

function count_pattern_matches(string $pattern, string $text): int
{
    $count = @preg_match_all($pattern, $text, $matches);
    return is_int($count) && $count > 0 ? $count : 0;
}

function candidate_confidence_score(array $hit, string $candidateLine, int $candidateStart, int $candidateLineIndex, string $scope): float
{
    $hitIndex = is_int($hit['index'] ?? null) ? (int) $hit['index'] : 0;
    $labelStart = is_int($hit['labelStart'] ?? null) ? (int) $hit['labelStart'] : 0;
    $labelEnd = is_int($hit['labelEnd'] ?? null) ? (int) $hit['labelEnd'] : 0;
    $alignment = abs($candidateStart - $labelStart);

    $base = 0.68;
    if ($scope === 'tail') {
        $base = 1.00;
    } elseif ($scope === 'line') {
        $base = 0.86;
    }

    $distance = abs($candidateLineIndex - $hitIndex);
    if ($distance > 0) {
        $base -= min(0.24, 0.08 * $distance);
    }
    if ($candidateLineIndex < $hitIndex) {
        $base -= 0.10;
    }

    $betweenText = '';
    if ($scope === 'tail' || $scope === 'line') {
        $hitLine = is_string($hit['line'] ?? null) ? (string) $hit['line'] : '';
        $betweenLength = max(0, $candidateStart - $labelEnd);
        if ($betweenLength > 0) {
            $segment = substr($hitLine, $labelEnd, $betweenLength);
            $betweenText = is_string($segment) ? $segment : '';
        }
    } else {
        // For nearby-line extraction, measure noise relative to the label column,
        // not from start-of-line, to avoid punishing unrelated table columns.
        $segmentStart = max(0, $labelStart - 1);
        $segmentLength = max(0, $candidateStart - $segmentStart);
        if ($segmentLength > 0) {
            $segment = substr($candidateLine, $segmentStart, $segmentLength);
            $betweenText = is_string($segment) ? $segment : '';
        }

        if ($candidateLineIndex > $hitIndex && $alignment <= 2) {
            $base = max($base, 0.98);
        } elseif ($candidateLineIndex > $hitIndex && $alignment <= 8) {
            $base = max($base, 0.94);
        } else {
            $base -= min(0.16, $alignment * 0.0035);
        }

        if ($candidateStart < ($labelStart - 2)) {
            $base -= 0.16;
        }
    }

    $nonWhitespace = count_pattern_matches('/\S/u', $betweenText);
    $letters = count_pattern_matches('/\pL/u', $betweenText);
    $digits = count_pattern_matches('/\d/u', $betweenText);
    $noisePenalty = min(0.38, ($letters * 0.004) + ($nonWhitespace * 0.002) + ($digits * 0.0015));
    if ($scope === 'nearby' && $candidateLineIndex > $hitIndex && $alignment <= 8) {
        $noisePenalty *= 0.25;
    }
    $base -= $noisePenalty;

    if (@preg_match('/(https?:\/\/|www\.|@[A-Za-z0-9._-]+)/iu', $betweenText) === 1) {
        $base -= 0.15;
    }

    return clamp_confidence($base);
}

function empty_invoice_field_confidence(): array
{
    return [
        'amount' => 0.0,
        'dueDate' => 0.0,
        'bankgiro' => 0.0,
        'plusgiro' => 0.0,
        'supplier' => 0.0,
        'payee' => 0.0,
        'iban' => 0.0,
        'swift' => 0.0,
        'ocr' => 0.0,
        'autogiro' => 0.0,
    ];
}

function apply_invoice_field_confidence_threshold(array $fields, array $confidence, float $minConfidence): array
{
    $threshold = sanitize_invoice_field_min_confidence($minConfidence);
    $filtered = $fields;
    foreach ($filtered as $key => $value) {
        if ($key === 'autogiro') {
            continue;
        }

        if ($value === null) {
            continue;
        }

        $score = clamp_confidence((float) ($confidence[$key] ?? 0.0));
        if ($score < $threshold) {
            $filtered[$key] = null;
        }
    }

    return $filtered;
}

function select_best_labeled_candidate(array $lines, array $labels, array $replacementMap, callable $candidateExtractor, int $nearbyDistance = 1): array
{
    $best = [
        'value' => null,
        'confidence' => 0.0,
        'lineIndex' => null,
        'source' => 'none',
        'raw' => null,
    ];

    $hits = find_label_hits($lines, $labels, $replacementMap);
    foreach ($hits as $hit) {
        $line = is_string($hit['line'] ?? null) ? (string) $hit['line'] : '';
        $pattern = is_string($hit['pattern'] ?? null) ? (string) $hit['pattern'] : '';
        $hitIndex = is_int($hit['index'] ?? null) ? (int) $hit['index'] : 0;
        $labelEnd = is_int($hit['labelEnd'] ?? null) ? (int) $hit['labelEnd'] : 0;

        $tailInfo = extract_label_tail_info_from_line($line, $pattern);
        $tailText = is_string($tailInfo['text'] ?? null) ? (string) $tailInfo['text'] : '';
        $tailOffset = is_int($tailInfo['offset'] ?? null) ? (int) $tailInfo['offset'] : 0;
        if ($tailText !== '') {
            $tailCandidates = $candidateExtractor($tailText, $tailOffset);
            if (is_array($tailCandidates)) {
                foreach ($tailCandidates as $candidate) {
                    if (!is_array($candidate)) {
                        continue;
                    }

                    $value = $candidate['value'] ?? null;
                    $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
                    $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null;
                    if ($start < 0 || $value === null) {
                        continue;
                    }

                    $confidence = candidate_confidence_score($hit, $line, $start, $hitIndex, 'tail');
                    if ($confidence > (float) $best['confidence']) {
                        $best = [
                            'value' => $value,
                            'confidence' => $confidence,
                            'lineIndex' => $hitIndex,
                            'source' => 'tail',
                            'raw' => $raw,
                        ];
                    }
                }
            }
        }

        $lineCandidates = $candidateExtractor($line, 0);
        if (is_array($lineCandidates)) {
            foreach ($lineCandidates as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }

                $value = $candidate['value'] ?? null;
                $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
                $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null;
                if ($start < $labelEnd || $value === null) {
                    continue;
                }

                $confidence = candidate_confidence_score($hit, $line, $start, $hitIndex, 'line');
                if ($confidence > (float) $best['confidence']) {
                    $best = [
                        'value' => $value,
                        'confidence' => $confidence,
                        'lineIndex' => $hitIndex,
                        'source' => 'line',
                        'raw' => $raw,
                    ];
                }
            }
        }

        $nearIndexes = invoice_nearby_line_indexes($lines, $hitIndex, $nearbyDistance);
        foreach ($nearIndexes as $nearIndex) {
            $nearLine = is_string($lines[$nearIndex] ?? null) ? (string) $lines[$nearIndex] : '';
            if ($nearLine === '') {
                continue;
            }

            $nearCandidates = $candidateExtractor($nearLine, 0);
            if (!is_array($nearCandidates)) {
                continue;
            }

            foreach ($nearCandidates as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }

                $value = $candidate['value'] ?? null;
                $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
                $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null;
                if ($start < 0 || $value === null) {
                    continue;
                }

                $confidence = candidate_confidence_score($hit, $nearLine, $start, $nearIndex, 'nearby');
                if ($confidence > (float) $best['confidence']) {
                    $best = [
                        'value' => $value,
                        'confidence' => $confidence,
                        'lineIndex' => $nearIndex,
                        'source' => 'nearby',
                        'raw' => $raw,
                    ];
                }
            }
        }
    }

    return $best;
}

function invoice_bankgiro_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $matches = [];
    if (@preg_match_all('/\b(\d{2,4}\s*-\s*\d{4})\b/u', $text, $matches, PREG_OFFSET_CAPTURE) < 1) {
        return [];
    }

    $candidates = [];
    $groups = is_array($matches[1] ?? null) ? $matches[1] : [];
    foreach ($groups as $group) {
        if (!is_array($group) || count($group) < 2) {
            continue;
        }

        $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
        $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
        if ($raw === '' || $start < 0) {
            continue;
        }

        $value = preg_replace('/\s+/u', '', $raw);
        if (!is_string($value) || $value === '') {
            continue;
        }

        $candidates[] = [
            'value' => $value,
            'raw' => $raw,
            'start' => $offsetBase + $start,
        ];
    }

    return $candidates;
}

function invoice_plusgiro_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $matches = [];
    if (@preg_match_all('/\b(\d{1,8}\s*-\s*\d{1,5})\b/u', $text, $matches, PREG_OFFSET_CAPTURE) < 1) {
        return [];
    }

    $candidates = [];
    $groups = is_array($matches[1] ?? null) ? $matches[1] : [];
    foreach ($groups as $group) {
        if (!is_array($group) || count($group) < 2) {
            continue;
        }

        $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
        $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
        if ($raw === '' || $start < 0) {
            continue;
        }

        $value = preg_replace('/\s+/u', '', $raw);
        if (!is_string($value) || $value === '') {
            continue;
        }

        $candidates[] = [
            'value' => $value,
            'raw' => $raw,
            'start' => $offsetBase + $start,
        ];
    }

    return $candidates;
}

function invoice_ocr_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $matches = [];
    if (@preg_match_all('/\b(\d[\d\s]{7,40}\d)\b/u', $text, $matches, PREG_OFFSET_CAPTURE) < 1) {
        return [];
    }

    $candidates = [];
    $groups = is_array($matches[1] ?? null) ? $matches[1] : [];
    foreach ($groups as $group) {
        if (!is_array($group) || count($group) < 2) {
            continue;
        }

        $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
        $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
        if ($raw === '' || $start < 0) {
            continue;
        }

        $digits = preg_replace('/\D+/u', '', $raw);
        if (!is_string($digits) || strlen($digits) < 8) {
            continue;
        }

        $candidates[] = [
            'value' => $digits,
            'raw' => $raw,
            'start' => $offsetBase + $start,
        ];
    }

    return $candidates;
}

function invoice_date_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $candidates = [];

    $matches = [];
    if (@preg_match_all('/\b(20\d{2}[-\/]\d{2}[-\/]\d{2})\b/u', $text, $matches, PREG_OFFSET_CAPTURE) === 1) {
        $groups = is_array($matches[1] ?? null) ? $matches[1] : [];
        foreach ($groups as $group) {
            if (!is_array($group) || count($group) < 2) {
                continue;
            }
            $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
            $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
            if ($raw === '' || $start < 0) {
                continue;
            }
            $value = extract_date_from_text($raw);
            if ($value === null) {
                continue;
            }
            $candidates[] = [
                'value' => $value,
                'raw' => $raw,
                'start' => $offsetBase + $start,
            ];
        }
    }

    $matches = [];
    if (@preg_match_all('/\b(\d{2}[-\/]\d{2}[-\/]20\d{2})\b/u', $text, $matches, PREG_OFFSET_CAPTURE) === 1) {
        $groups = is_array($matches[1] ?? null) ? $matches[1] : [];
        foreach ($groups as $group) {
            if (!is_array($group) || count($group) < 2) {
                continue;
            }
            $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
            $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
            if ($raw === '' || $start < 0) {
                continue;
            }
            $value = extract_date_from_text($raw);
            if ($value === null) {
                continue;
            }
            $candidates[] = [
                'value' => $value,
                'raw' => $raw,
                'start' => $offsetBase + $start,
            ];
        }
    }

    return $candidates;
}

function invoice_amount_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $matches = [];
    if (@preg_match_all('/\b(\d{1,3}(?:[ .]\d{3})*(?:,\d{2})?)\b|\b(\d+(?:,\d{2}))\b/u', $text, $matches, PREG_OFFSET_CAPTURE) < 1) {
        return [];
    }

    $candidates = [];
    $all = is_array($matches[0] ?? null) ? $matches[0] : [];
    foreach ($all as $group) {
        if (!is_array($group) || count($group) < 2) {
            continue;
        }
        $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
        $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
        if ($raw === '' || $start < 0) {
            continue;
        }

        $value = normalize_swedish_amount($raw);
        if (!is_float($value)) {
            continue;
        }

        if (!str_contains($raw, ',') && !str_contains($raw, '.') && $value >= 1900 && $value <= 2100) {
            continue;
        }

        $candidates[] = [
            'value' => $value,
            'raw' => $raw,
            'start' => $offsetBase + $start,
        ];
    }

    return $candidates;
}

function invoice_iban_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $matches = [];
    if (@preg_match_all('/\b([A-Z]{2}\d{2}(?:\s?[A-Z0-9]{4}){2,7})\b/iu', $text, $matches, PREG_OFFSET_CAPTURE) < 1) {
        return [];
    }

    $candidates = [];
    $groups = is_array($matches[1] ?? null) ? $matches[1] : [];
    foreach ($groups as $group) {
        if (!is_array($group) || count($group) < 2) {
            continue;
        }
        $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
        $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
        if ($raw === '' || $start < 0) {
            continue;
        }

        $value = normalize_inline_whitespace(strtoupper($raw));
        if ($value === '') {
            continue;
        }

        $candidates[] = [
            'value' => $value,
            'raw' => $raw,
            'start' => $offsetBase + $start,
        ];
    }

    return $candidates;
}

function invoice_swift_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $matches = [];
    if (@preg_match_all('/\b([A-Z]{6}[A-Z0-9]{2}(?:[A-Z0-9]{3})?)\b/iu', $text, $matches, PREG_OFFSET_CAPTURE) < 1) {
        return [];
    }

    $candidates = [];
    $groups = is_array($matches[1] ?? null) ? $matches[1] : [];
    foreach ($groups as $group) {
        if (!is_array($group) || count($group) < 2) {
            continue;
        }
        $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
        $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
        if ($raw === '' || $start < 0) {
            continue;
        }

        $value = strtoupper($raw);
        if ($value === '') {
            continue;
        }

        $candidates[] = [
            'value' => $value,
            'raw' => $raw,
            'start' => $offsetBase + $start,
        ];
    }

    return $candidates;
}

function invoice_payee_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $matches = [];
    if (@preg_match_all('/([^\s].*?)(?=\s{2,}|$)/u', $text, $matches, PREG_OFFSET_CAPTURE) < 1) {
        return [];
    }

    $candidates = [];
    $groups = is_array($matches[1] ?? null) ? $matches[1] : [];
    foreach ($groups as $group) {
        if (!is_array($group) || count($group) < 2) {
            continue;
        }

        $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
        $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
        if ($raw === '' || $start < 0) {
            continue;
        }

        $value = normalize_inline_whitespace($raw);
        if ($value === '' || @preg_match('/^(https?:\/\/|www\.)/iu', $value) === 1) {
            continue;
        }
        if (count_pattern_matches('/\pL/u', $value) < 2) {
            continue;
        }
        if (@preg_match('/^[^\pL\d]+$/u', $value) === 1) {
            continue;
        }

        $candidates[] = [
            'value' => $value,
            'raw' => $raw,
            'start' => $offsetBase + $start,
        ];
    }

    return $candidates;
}

function extract_ocr_number_from_text(string $text): ?string
{
    $matches = [];
    if (@preg_match_all('/\b\d[\d\s]{6,40}\d\b/u', $text, $matches) < 1) {
        return null;
    }

    $candidates = is_array($matches[0] ?? null) ? $matches[0] : [];
    $best = '';
    foreach ($candidates as $candidate) {
        if (!is_string($candidate)) {
            continue;
        }

        $digits = preg_replace('/\D+/u', '', $candidate);
        if (!is_string($digits) || strlen($digits) < 8) {
            continue;
        }

        if (strlen($digits) > strlen($best)) {
            $best = $digits;
        }
    }

    return $best !== '' ? $best : null;
}

function extract_bankgiro_from_text(string $text): ?string
{
    $matches = [];
    if (@preg_match('/\b(\d{2,4}\s*-\s*\d{4})\b/u', $text, $matches) !== 1) {
        return null;
    }

    $value = is_string($matches[1] ?? null) ? (string) $matches[1] : '';
    if ($value === '') {
        return null;
    }

    $normalized = preg_replace('/\s+/u', '', $value);
    return is_string($normalized) && $normalized !== '' ? $normalized : null;
}

function extract_plusgiro_from_text(string $text): ?string
{
    $matches = [];
    if (@preg_match('/\b(\d{1,8}\s*-\s*\d{1,8})\b/u', $text, $matches) !== 1) {
        return null;
    }

    $value = is_string($matches[1] ?? null) ? (string) $matches[1] : '';
    if ($value === '') {
        return null;
    }

    $normalized = preg_replace('/\s+/u', '', $value);
    return is_string($normalized) && $normalized !== '' ? $normalized : null;
}

function extract_date_from_text(string $text): ?string
{
    $matches = [];
    if (@preg_match('/\b(20\d{2})[-\/](\d{2})[-\/](\d{2})\b/u', $text, $matches) === 1) {
        $year = (int) ($matches[1] ?? 0);
        $month = (int) ($matches[2] ?? 0);
        $day = (int) ($matches[3] ?? 0);
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    $matches = [];
    if (@preg_match('/\b(\d{2})[-\/](\d{2})[-\/](20\d{2})\b/u', $text, $matches) === 1) {
        $day = (int) ($matches[1] ?? 0);
        $month = (int) ($matches[2] ?? 0);
        $year = (int) ($matches[3] ?? 0);
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    return null;
}

function normalize_swedish_amount(string $value): ?float
{
    $clean = str_replace("\xC2\xA0", ' ', $value);
    $clean = trim($clean);
    if ($clean === '') {
        return null;
    }

    $clean = preg_replace('/[^\d,.\s]/u', '', $clean);
    if (!is_string($clean) || trim($clean) === '') {
        return null;
    }

    $clean = str_replace(' ', '', $clean);
    if (str_contains($clean, ',')) {
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);
    }

    if (!is_numeric($clean)) {
        return null;
    }

    $amount = (float) $clean;
    if ($amount <= 0) {
        return null;
    }

    return round($amount, 2);
}

function extract_amount_from_text(string $text): ?float
{
    $matches = [];
    if (@preg_match_all('/\b\d{1,3}(?:[ .]\d{3})*(?:,\d{2})?\b|\b\d+(?:,\d{2})\b/u', $text, $matches) < 1) {
        return null;
    }

    $candidates = is_array($matches[0] ?? null) ? $matches[0] : [];
    for ($i = count($candidates) - 1; $i >= 0; $i--) {
        $candidate = $candidates[$i];
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }

        $normalized = normalize_swedish_amount($candidate);
        if (!is_float($normalized)) {
            continue;
        }

        // Avoid capturing plain years from labeled lines that may include dates.
        if (!str_contains($candidate, ',') && !str_contains($candidate, '.') && $normalized >= 1900 && $normalized <= 2100) {
            continue;
        }

        return $normalized;
    }

    return null;
}

function extract_iban_from_text(string $text): ?string
{
    $matches = [];
    if (@preg_match('/\b([A-Z]{2}\d{2}(?:\s?[A-Z0-9]{4}){2,7})\b/iu', $text, $matches) !== 1) {
        return null;
    }

    $iban = is_string($matches[1] ?? null) ? strtoupper((string) $matches[1]) : '';
    if ($iban === '') {
        return null;
    }

    return normalize_inline_whitespace($iban);
}

function extract_swift_from_text(string $text): ?string
{
    $matches = [];
    if (@preg_match('/\b([A-Z]{6}[A-Z0-9]{2}(?:[A-Z0-9]{3})?)\b/iu', $text, $matches) !== 1) {
        return null;
    }

    $swift = is_string($matches[1] ?? null) ? strtoupper((string) $matches[1]) : '';
    return $swift !== '' ? $swift : null;
}

function extract_payee_from_text(string $text): ?string
{
    $candidate = preg_replace('/^[\s:;\-]+/u', '', $text);
    if (!is_string($candidate)) {
        return null;
    }

    $candidate = trim($candidate);
    if ($candidate === '') {
        return null;
    }

    $parts = preg_split('/\s{2,}/u', $candidate);
    if (is_array($parts)) {
        foreach ($parts as $part) {
            if (!is_string($part)) {
                continue;
            }

            $value = normalize_inline_whitespace($part);
            if ($value === '') {
                continue;
            }
            if (@preg_match('/^(https?:\/\/|www\.)/iu', $value) === 1) {
                continue;
            }

            return $value;
        }
    }

    $normalized = normalize_inline_whitespace($candidate);
    if ($normalized === '' || @preg_match('/^(https?:\/\/|www\.)/iu', $normalized) === 1) {
        return null;
    }

    return $normalized;
}

function empty_invoice_field_result(): array
{
    return [
        'value' => null,
        'confidence' => 0.0,
        'lineIndex' => null,
        'source' => 'none',
        'raw' => null,
    ];
}

function extract_invoice_ocr_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['ocr-nummer', 'ocr nummer', 'ocr'], $replacementMap, 'invoice_ocr_candidates_from_text', 1);
    if (($result['value'] ?? null) === null) {
        return empty_invoice_field_result();
    }

    $lineIndex = is_int($result['lineIndex'] ?? null) ? (int) $result['lineIndex'] : -1;
    $line = $lineIndex >= 0 ? (string) ($lines[$lineIndex] ?? '') : '';
    $normalizedLine = normalize_for_matching($line, $replacementMap);
    if (str_contains($normalizedLine, 'iban') || str_contains($normalizedLine, 'swift')) {
        $result['confidence'] = clamp_confidence(((float) ($result['confidence'] ?? 0.0)) - 0.35);
    }

    return $result;
}

function extract_invoice_bankgiro_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['bankgiro', 'bg'], $replacementMap, 'invoice_bankgiro_candidates_from_text', 2);
    return ($result['value'] ?? null) !== null ? $result : empty_invoice_field_result();
}

function extract_invoice_plusgiro_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['plusgiro', 'pg'], $replacementMap, 'invoice_plusgiro_candidates_from_text', 2);
    return ($result['value'] ?? null) !== null ? $result : empty_invoice_field_result();
}

function extract_invoice_due_date_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate(
        $lines,
        ['förfallodatum', 'forfallodatum', 'förfaller', 'forfaller', 'att betala senast'],
        $replacementMap,
        'invoice_date_candidates_from_text',
        2
    );
    return ($result['value'] ?? null) !== null ? $result : empty_invoice_field_result();
}

function extract_invoice_amount_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate(
        $lines,
        ['fakturabelopp', 'att betala', 'summa att betala', 'belopp att betala', 'total att betala'],
        $replacementMap,
        'invoice_amount_candidates_from_text',
        2
    );
    return ($result['value'] ?? null) !== null ? $result : empty_invoice_field_result();
}

function extract_invoice_payee_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['betalningsmottagare', 'mottagare'], $replacementMap, 'invoice_payee_candidates_from_text', 1);
    return ($result['value'] ?? null) !== null ? $result : empty_invoice_field_result();
}

function extract_invoice_supplier_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate(
        $lines,
        ['leverantör', 'leverantor'],
        $replacementMap,
        'invoice_payee_candidates_from_text',
        1
    );
    return ($result['value'] ?? null) !== null ? $result : empty_invoice_field_result();
}

function extract_invoice_iban_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['iban'], $replacementMap, 'invoice_iban_candidates_from_text', 1);
    return ($result['value'] ?? null) !== null ? $result : empty_invoice_field_result();
}

function extract_invoice_swift_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['swift'], $replacementMap, 'invoice_swift_candidates_from_text', 1);
    return ($result['value'] ?? null) !== null ? $result : empty_invoice_field_result();
}

function extract_invoice_ocr(array $lines, array $replacementMap): ?string
{
    $result = extract_invoice_ocr_result($lines, $replacementMap);
    return is_string($result['value'] ?? null) ? (string) $result['value'] : null;
}

function extract_invoice_bankgiro(array $lines, array $replacementMap): ?string
{
    $result = extract_invoice_bankgiro_result($lines, $replacementMap);
    return is_string($result['value'] ?? null) ? (string) $result['value'] : null;
}

function extract_invoice_plusgiro(array $lines, array $replacementMap): ?string
{
    $result = extract_invoice_plusgiro_result($lines, $replacementMap);
    return is_string($result['value'] ?? null) ? (string) $result['value'] : null;
}

function extract_invoice_due_date(array $lines, array $replacementMap): ?string
{
    $result = extract_invoice_due_date_result($lines, $replacementMap);
    return is_string($result['value'] ?? null) ? (string) $result['value'] : null;
}

function extract_invoice_amount(array $lines, array $replacementMap): ?float
{
    $result = extract_invoice_amount_result($lines, $replacementMap);
    $value = $result['value'] ?? null;
    return is_float($value) ? $value : null;
}

function extract_invoice_payee(array $lines, array $replacementMap): ?string
{
    $result = extract_invoice_payee_result($lines, $replacementMap);
    return is_string($result['value'] ?? null) ? (string) $result['value'] : null;
}

function extract_invoice_supplier(array $lines, array $replacementMap): ?string
{
    $result = extract_invoice_supplier_result($lines, $replacementMap);
    return is_string($result['value'] ?? null) ? (string) $result['value'] : null;
}

function extract_invoice_iban(array $lines, array $replacementMap): ?string
{
    $result = extract_invoice_iban_result($lines, $replacementMap);
    return is_string($result['value'] ?? null) ? (string) $result['value'] : null;
}

function extract_invoice_swift(array $lines, array $replacementMap): ?string
{
    $result = extract_invoice_swift_result($lines, $replacementMap);
    return is_string($result['value'] ?? null) ? (string) $result['value'] : null;
}

function extract_invoice_data_with_confidence(string $ocrText, array $replacementMap, ?float $minConfidence = null): array
{
    $lines = invoice_ocr_lines($ocrText);
    $fieldMinConfidence = sanitize_invoice_field_min_confidence($minConfidence, default_invoice_field_min_confidence());
    if (count($lines) === 0) {
        return [
            'fields' => empty_invoice_fields(),
            'confidence' => empty_invoice_field_confidence(),
            'fieldMinConfidence' => $fieldMinConfidence,
        ];
    }

    $amount = extract_invoice_amount_result($lines, $replacementMap);
    $dueDate = extract_invoice_due_date_result($lines, $replacementMap);
    $bankgiro = extract_invoice_bankgiro_result($lines, $replacementMap);
    $plusgiro = extract_invoice_plusgiro_result($lines, $replacementMap);
    $supplier = extract_invoice_supplier_result($lines, $replacementMap);
    $payee = extract_invoice_payee_result($lines, $replacementMap);
    $iban = extract_invoice_iban_result($lines, $replacementMap);
    $swift = extract_invoice_swift_result($lines, $replacementMap);
    $ocr = extract_invoice_ocr_result($lines, $replacementMap);

    $normalizedOcr = normalize_for_matching($ocrText, $replacementMap);
    $hasAutogiro = str_contains($normalizedOcr, normalize_for_matching('autogiro', $replacementMap));

    $fields = [
        'amount' => is_float($amount['value'] ?? null) ? (float) $amount['value'] : null,
        'dueDate' => is_string($dueDate['value'] ?? null) ? (string) $dueDate['value'] : null,
        'bankgiro' => is_string($bankgiro['value'] ?? null) ? (string) $bankgiro['value'] : null,
        'plusgiro' => is_string($plusgiro['value'] ?? null) ? (string) $plusgiro['value'] : null,
        'supplier' => is_string($supplier['value'] ?? null) ? (string) $supplier['value'] : null,
        'payee' => is_string($payee['value'] ?? null) ? (string) $payee['value'] : null,
        'iban' => is_string($iban['value'] ?? null) ? (string) $iban['value'] : null,
        'swift' => is_string($swift['value'] ?? null) ? (string) $swift['value'] : null,
        'ocr' => is_string($ocr['value'] ?? null) ? (string) $ocr['value'] : null,
        'autogiro' => $hasAutogiro,
    ];
    $confidence = [
        'amount' => clamp_confidence((float) ($amount['confidence'] ?? 0.0)),
        'dueDate' => clamp_confidence((float) ($dueDate['confidence'] ?? 0.0)),
        'bankgiro' => clamp_confidence((float) ($bankgiro['confidence'] ?? 0.0)),
        'plusgiro' => clamp_confidence((float) ($plusgiro['confidence'] ?? 0.0)),
        'supplier' => clamp_confidence((float) ($supplier['confidence'] ?? 0.0)),
        'payee' => clamp_confidence((float) ($payee['confidence'] ?? 0.0)),
        'iban' => clamp_confidence((float) ($iban['confidence'] ?? 0.0)),
        'swift' => clamp_confidence((float) ($swift['confidence'] ?? 0.0)),
        'ocr' => clamp_confidence((float) ($ocr['confidence'] ?? 0.0)),
        'autogiro' => $hasAutogiro ? 1.0 : 0.0,
    ];

    return [
        'fields' => apply_invoice_field_confidence_threshold($fields, $confidence, $fieldMinConfidence),
        'confidence' => $confidence,
        'fieldMinConfidence' => $fieldMinConfidence,
    ];
}

function extract_invoice_data(string $ocrText, array $replacementMap, ?float $minConfidence = null): array
{
    $result = extract_invoice_data_with_confidence($ocrText, $replacementMap, $minConfidence);
    $fields = $result['fields'] ?? [];
    return is_array($fields) ? $fields : empty_invoice_fields();
}

function build_invoice_detection(array $invoiceMatches, array $replacementMap): array
{
    $detection = [
        'matched' => false,
        'score' => 0,
        'minScore' => 0,
        'matchedSignals' => [],
    ];

    $bestMatch = (count($invoiceMatches) > 0 && is_array($invoiceMatches[0])) ? $invoiceMatches[0] : null;
    if (!is_array($bestMatch)) {
        return $detection;
    }

    $score = $bestMatch['score'] ?? 0;
    if (is_int($score) || is_float($score) || (is_string($score) && is_numeric($score))) {
        $detection['score'] = max(0, (int) round((float) $score));
    }

    $minScore = $bestMatch['minScore'] ?? 0;
    if (is_int($minScore) || is_float($minScore) || (is_string($minScore) && is_numeric($minScore))) {
        $detection['minScore'] = max(0, (int) round((float) $minScore));
    }

    $matchedSignals = [];
    $matchedRules = is_array($bestMatch['matchedRules'] ?? null) ? $bestMatch['matchedRules'] : [];
    foreach ($matchedRules as $rule) {
        if (!is_array($rule)) {
            continue;
        }

        $ruleText = is_string($rule['text'] ?? null) ? trim((string) $rule['text']) : '';
        if ($ruleText === '') {
            continue;
        }

        $matchedSignals[] = 'label:' . normalize_for_matching($ruleText, $replacementMap);
    }

    $detection['matchedSignals'] = array_values(array_unique($matchedSignals));
    $detection['matched'] = $detection['score'] >= $detection['minScore'] && count($detection['matchedSignals']) > 0;
    return $detection;
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
        'analysis' => [
            'invoiceDetection' => [
                'matched' => false,
                'score' => 0,
                'minScore' => 0,
                'matchedSignals' => [],
            ],
            'invoice' => null,
            'invoiceConfidence' => empty_invoice_field_confidence(),
            'preselectedClient' => null,
            'preselectedSender' => null,
            'senderLookup' => [
                'query' => [
                    'orgNumber' => null,
                    'bankgiro' => null,
                    'plusgiro' => null,
                ],
                'matched' => false,
                'matchedBy' => null,
                'matchedValue' => null,
                'sender' => null,
            ],
        ],
        'files' => [
            'sourcePdf' => 'source.pdf',
            'reviewPdf' => 'review.pdf',
            'ocrText' => 'ocr.txt',
            'ocrObjects' => 'ocr-objects.json',
            'extracted' => 'extracted.json',
        ],
    ];

    if ($fallbackTxtPath !== null && $fallbackTxtPath !== '') {
        $jobData['fallbackTxtPath'] = $fallbackTxtPath;
    }

    return $jobData;
}

function process_claimed_job(
    string $jobDir,
    string $sourcePdfPath,
    ?string $fallbackTxtPath,
    array $clients,
    array $categories,
    array $replacementMap,
    float $invoiceFieldMinConfidence,
    bool $ocrSkipExistingText,
    int $ocrOptimizeLevel,
    string $ocrTextExtractionMethod,
    array $ocrPdfTextSubstitutions,
    bool $runOcr = true
): array
{
    $reviewPdfPath = $jobDir . '/review.pdf';
    $ocrPath = $jobDir . '/ocr.txt';
    $ocrObjectsPath = $jobDir . '/ocr-objects.json';
    if (is_file($ocrPath)) {
        @unlink($ocrPath);
    }
    if (is_file($ocrObjectsPath)) {
        @unlink($ocrObjectsPath);
    }

    $ocrProcessedPdf = false;
    $textSourcePdfPath = $sourcePdfPath;
    if ($runOcr) {
        $ocrProcessedPdf = run_ocrmypdf(
            $sourcePdfPath,
            $reviewPdfPath,
            $ocrPath,
            $ocrSkipExistingText,
            $ocrOptimizeLevel,
            $ocrPdfTextSubstitutions
        );
        if (!$ocrProcessedPdf) {
            if (ocrmypdf_path() !== null) {
                $ocrError = last_ocrmypdf_error();
                throw new RuntimeException(
                    'OCRmyPDF failed'
                    . ($ocrError !== null ? ': ' . $ocrError : '')
                );
            }

            if (!copy($sourcePdfPath, $reviewPdfPath)) {
                throw new RuntimeException('Could not create review.pdf');
            }
        }

        $textSourcePdfPath = $ocrProcessedPdf ? $reviewPdfPath : $sourcePdfPath;
    } else {
        if (!is_file($reviewPdfPath)) {
            throw new RuntimeException('Missing review.pdf');
        }
        $textSourcePdfPath = $reviewPdfPath;
    }

    $ocrText = '';
    $extracted = null;
    if ($ocrTextExtractionMethod === 'bbox') {
        $bboxObjects = extract_bbox_layout_objects_from_pdf($textSourcePdfPath);
        if ($bboxObjects !== null && $bboxObjects !== []) {
            write_json_file($ocrObjectsPath, [
                'source' => 'pdftotext-bbox-layout',
                'pages' => $bboxObjects,
            ]);
            $extracted = render_grid_text_from_bbox_objects($bboxObjects);
        } elseif (is_file($ocrObjectsPath)) {
            @unlink($ocrObjectsPath);
        }
    } else {
        if (is_file($ocrObjectsPath)) {
            @unlink($ocrObjectsPath);
        }
    }

    if (!is_string($extracted) || $extracted === '') {
        $extracted = extract_text_from_pdf($textSourcePdfPath);
    }
    if ($extracted === null || $extracted === '') {
        $ocrText = fallback_ocr_text_from_path($fallbackTxtPath);
    } else {
        $ocrText = $extracted;
    }

    if (file_put_contents($ocrPath, $ocrText) === false) {
        throw new RuntimeException('Could not write ocr.txt');
    }

    $categoryGroups = split_categories_for_processing($categories);
    $invoiceSignalMatches = find_category_signal_matches($ocrText, $categoryGroups['invoiceSystemCategories'], $replacementMap);
    $invoiceDetection = build_invoice_detection($invoiceSignalMatches, $replacementMap);
    $invoiceExtraction = extract_invoice_data_with_confidence($ocrText, $replacementMap, $invoiceFieldMinConfidence);
    $invoiceData = is_array($invoiceExtraction['fields'] ?? null) ? $invoiceExtraction['fields'] : empty_invoice_fields();
    $invoiceConfidence = is_array($invoiceExtraction['confidence'] ?? null)
        ? $invoiceExtraction['confidence']
        : empty_invoice_field_confidence();
    $orgNumber = detect_org_number_from_ocr_text($ocrText);
    $senderLookup = sender_lookup_result(
        $orgNumber,
        is_string($invoiceData['bankgiro'] ?? null) ? (string) $invoiceData['bankgiro'] : null,
        is_string($invoiceData['plusgiro'] ?? null) ? (string) $invoiceData['plusgiro'] : null
    );

    $matched = match_client_dir_name($ocrText, $clients);
    $preselectedClient = null;
    if (is_string($matched) && trim($matched) !== '') {
        $preselectedClient = [
            'dirName' => trim($matched),
        ];
    }

    $preselectedSender = null;
    if (($senderLookup['matched'] ?? false) === true && is_array($senderLookup['sender'] ?? null)) {
        $sender = $senderLookup['sender'];
        $senderId = isset($sender['id']) ? (int) $sender['id'] : 0;
        if ($senderId > 0) {
            $preselectedSender = [
                'id' => $senderId,
                'name' => is_string($sender['name'] ?? null) ? trim((string) $sender['name']) : '',
                'matchedBy' => is_string($senderLookup['matchedBy'] ?? null) ? $senderLookup['matchedBy'] : null,
                'matchedValue' => is_string($senderLookup['matchedValue'] ?? null) ? $senderLookup['matchedValue'] : null,
            ];
        }
    }

    $categoryMatches = find_category_matches($ocrText, $categoryGroups['normalCategories'], $replacementMap);
    $extractedData = [
        'matchedClientDirName' => $matched,
        'categoryMatches' => $categoryMatches,
        'systemCategoryMatches' => $invoiceSignalMatches,
        'invoice' => $invoiceData,
        'invoiceConfidence' => $invoiceConfidence,
        'invoiceFieldMinConfidence' => $invoiceFieldMinConfidence,
        'invoiceDetection' => $invoiceDetection,
        'preselectedClient' => $preselectedClient,
        'preselectedSender' => $preselectedSender,
        'senderLookup' => $senderLookup,
    ];

    write_json_file($jobDir . '/extracted.json', $extractedData);

    return [
        'extractedData' => $extractedData,
        'analysis' => [
            'invoiceDetection' => $invoiceDetection,
            'invoice' => $invoiceData,
            'invoiceConfidence' => $invoiceConfidence,
            'invoiceFieldMinConfidence' => $invoiceFieldMinConfidence,
            'preselectedClient' => $preselectedClient,
            'preselectedSender' => $preselectedSender,
            'senderLookup' => $senderLookup,
        ],
    ];
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

function process_job_by_id(
    array $config,
    array $clients,
    array $categories,
    array $matchingSettings,
    float $invoiceFieldMinConfidence,
    string $jobId
): void
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
        $reprocessMode = is_string($jobData['reprocessMode'] ?? null)
            ? trim((string) $jobData['reprocessMode'])
            : 'full';
        if ($reprocessMode !== 'post-ocr') {
            $reprocessMode = 'full';
        }
        $result = process_claimed_job(
            $jobDir,
            $sourcePdfPath,
            $fallbackTxtPath,
            $clients,
            $categories,
            $replacementMap,
            $invoiceFieldMinConfidence,
            (bool) ($config['ocrSkipExistingText'] ?? true),
            (int) ($config['ocrOptimizeLevel'] ?? 1),
            (string) ($config['ocrTextExtractionMethod'] ?? 'layout'),
            is_array($config['ocrPdfTextSubstitutions'] ?? null) ? $config['ocrPdfTextSubstitutions'] : [],
            $reprocessMode !== 'post-ocr'
        );

        $analysis = $result['analysis'] ?? null;
        if (is_array($analysis)) {
            $jobData['analysis'] = $analysis;
        }

        $jobData['status'] = 'ready';
        $jobData['updatedAt'] = now_iso();
        unset($jobData['error'], $jobData['reprocessMode']);
        write_json_file($jobJsonPath, $jobData);
    } catch (Throwable $e) {
        $jobData['status'] = 'failed';
        $jobData['updatedAt'] = now_iso();
        $jobData['error'] = $e->getMessage();
        unset($jobData['reprocessMode']);
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
        $matchingPayload = load_matching_settings_payload();
        $matchingSettings = is_array($matchingPayload['replacements'] ?? null) ? $matchingPayload['replacements'] : [];
        $invoiceFieldMinConfidence = sanitize_invoice_field_min_confidence(
            $matchingPayload['invoiceFieldMinConfidence'] ?? null
        );

        while (true) {
            $jobId = next_processing_job_id($config);
            if ($jobId === null) {
                break;
            }

            process_job_by_id(
                $config,
                $clients,
                $categories,
                $matchingSettings,
                $invoiceFieldMinConfidence,
                $jobId
            );
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

function start_job_dispatcher(): void
{
    $scriptPath = PROJECT_ROOT . 'scripts/job-dispatcher.php';
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
    $categoryOrderById = [];
    $categoryNameById = [];
    foreach ($categories as $index => $category) {
        if (!is_array($category)) {
            continue;
        }

        $id = is_string($category['id'] ?? null) ? trim((string) $category['id']) : '';
        $order = is_int($index) ? $index : 999999;

        if ($id !== '' && !isset($categoryOrderById[$id])) {
            $categoryOrderById[$id] = $order;
            $categoryNameById[$id] = is_string($category['name'] ?? null) ? trim((string) $category['name']) : '';
        }
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
        $id = is_string($job['id'] ?? null) ? trim((string) $job['id']) : '';
        if ($id === '') {
            $id = $entry;
        }
        $originalFilename = is_string($job['originalFilename'] ?? null) ? $job['originalFilename'] : 'unknown.pdf';
        $createdAt = is_string($job['createdAt'] ?? null) ? $job['createdAt'] : '';
        $hasReviewPdf = is_file($jobDir . '/review.pdf');
        $hasSourcePdf = is_file($jobDir . '/source.pdf');

        if ($status === 'ready') {
            $matchedClientDirName = null;
            $matchedSenderId = null;
            $topMatchedCategoryId = null;
            $topMatchedCategoryName = null;
            $topMatchedCategoryScore = null;
            $analysis = is_array($job['analysis'] ?? null) ? $job['analysis'] : [];
            $extracted = load_json_file($jobDir . '/extracted.json');

            $preselectedClient = is_array($analysis['preselectedClient'] ?? null)
                ? $analysis['preselectedClient']
                : null;
            if (is_array($preselectedClient) && is_string($preselectedClient['dirName'] ?? null)) {
                $value = trim((string) $preselectedClient['dirName']);
                if ($value !== '') {
                    $matchedClientDirName = $value;
                }
            } elseif (is_array($extracted) && array_key_exists('matchedClientDirName', $extracted)) {
                $value = $extracted['matchedClientDirName'];
                if (is_string($value) || $value === null) {
                    $matchedClientDirName = $value;
                }
            }

            $preselectedSender = is_array($analysis['preselectedSender'] ?? null)
                ? $analysis['preselectedSender']
                : null;
            if (is_array($preselectedSender) && isset($preselectedSender['id'])) {
                $senderId = (int) $preselectedSender['id'];
                if ($senderId > 0) {
                    $matchedSenderId = $senderId;
                }
            } elseif (is_array($extracted) && is_array($extracted['senderLookup'] ?? null)) {
                $senderLookup = $extracted['senderLookup'];
                $sender = is_array($senderLookup['sender'] ?? null) ? $senderLookup['sender'] : null;
                $senderId = is_array($sender) && isset($sender['id']) ? (int) $sender['id'] : 0;
                if ($senderId > 0) {
                    $matchedSenderId = $senderId;
                }
            }

            if (is_array($extracted) && isset($extracted['categoryMatches']) && is_array($extracted['categoryMatches'])) {
                $bestOrder = 999999;
                foreach ($extracted['categoryMatches'] as $categoryMatch) {
                    if (!is_array($categoryMatch)) {
                        continue;
                    }

                    $categoryId = is_string($categoryMatch['id'] ?? null) ? trim((string) $categoryMatch['id']) : '';
                    $score = $categoryMatch['score'] ?? null;
                    $numericScore = is_int($score) || is_float($score) || (is_string($score) && is_numeric($score))
                        ? (float) $score
                        : null;

                    if ($categoryId === '' || $numericScore === null || $numericScore <= 0) {
                        continue;
                    }

                    $order = isset($categoryOrderById[$categoryId]) ? (int) $categoryOrderById[$categoryId] : 999999;
                    $resolvedName = isset($categoryNameById[$categoryId]) ? (string) $categoryNameById[$categoryId] : '';

                    if ($topMatchedCategoryScore === null || $numericScore > $topMatchedCategoryScore) {
                        $topMatchedCategoryId = $categoryId;
                        $topMatchedCategoryName = $resolvedName;
                        $topMatchedCategoryScore = $numericScore;
                        $bestOrder = $order;
                        continue;
                    }

                    if ($numericScore === $topMatchedCategoryScore && $order < $bestOrder) {
                        $topMatchedCategoryId = $categoryId;
                        $topMatchedCategoryName = $resolvedName;
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
                'hasReviewPdf' => $hasReviewPdf,
                'hasSourcePdf' => $hasSourcePdf,
                'matchedClientDirName' => $matchedClientDirName,
                'matchedSenderId' => $matchedSenderId,
                'topMatchedCategoryId' => $topMatchedCategoryId,
                'topMatchedCategoryName' => $topMatchedCategoryName,
                'topMatchedCategoryScore' => $topMatchedCategoryScore,
            ];
        } elseif ($status === 'processing') {
            $processing[] = [
                'id' => $id,
                'originalFilename' => $originalFilename,
                'status' => 'processing',
                'createdAt' => $createdAt,
                'hasReviewPdf' => $hasReviewPdf,
                'hasSourcePdf' => $hasSourcePdf,
            ];
        } elseif ($status === 'failed') {
            $failed[] = [
                'id' => $id,
                'originalFilename' => $originalFilename,
                'status' => 'failed',
                'createdAt' => $createdAt,
                'hasReviewPdf' => $hasReviewPdf,
                'hasSourcePdf' => $hasSourcePdf,
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

function build_jobs_state_payload(array $config): array
{
    $jobsState = read_jobs_state($config);
    return [
        'processingJobs' => $jobsState['processingJobs'],
        'readyJobs' => $jobsState['readyJobs'],
        'failedJobs' => $jobsState['failedJobs'],
    ];
}

function encode_jobs_state_payload(array $payload): string
{
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        throw new RuntimeException('Kunde inte serialisera state');
    }
    return $encoded;
}

function jobs_state_signature(string $encodedPayload): string
{
    return sha1($encodedPayload);
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

function reset_job_by_id(array $config, string $jobId): array
{
    if (!is_valid_job_id($jobId)) {
        throw new RuntimeException('Invalid job id');
    }

    $jobsDir = $config['jobsDirectory'];
    $inboxDir = $config['inboxDirectory'];

    ensure_directory($jobsDir);
    ensure_directory($inboxDir);

    $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $jobId;
    if (!is_dir($jobDir)) {
        throw new RuntimeException('Job not found');
    }

    $job = load_json_file($jobDir . '/job.json');
    $originalFilename = is_array($job) && is_string($job['originalFilename'] ?? null)
        ? trim((string) $job['originalFilename'])
        : '';
    if ($originalFilename === '') {
        $originalFilename = $jobId . '.pdf';
    }

    $restoredSources = 0;
    $removedJobFolders = 0;
    $errors = [];

    $sourcePath = $jobDir . '/source.pdf';
    if (is_file($sourcePath)) {
        $targetPath = unique_inbox_target_path($inboxDir, $originalFilename);
        if (!rename($sourcePath, $targetPath)) {
            $errors[] = 'Could not restore source.pdf for job ' . $jobId;
        } else {
            $restoredSources = 1;
        }
    }

    if (!delete_directory_recursive($jobDir)) {
        $errors[] = 'Could not remove job directory ' . $jobId;
    } else {
        $removedJobFolders = 1;
    }

    return [
        'restoredSources' => $restoredSources,
        'removedJobFolders' => $removedJobFolders,
        'errors' => $errors,
    ];
}

function reprocess_job_by_id(array $config, string $jobId, string $mode = 'post-ocr'): array
{
    if (!is_valid_job_id($jobId)) {
        throw new RuntimeException('Invalid job id');
    }

    $normalizedMode = trim($mode);
    if ($normalizedMode !== 'full' && $normalizedMode !== 'post-ocr') {
        throw new RuntimeException('Invalid reprocess mode');
    }

    $jobsDir = $config['jobsDirectory'];
    ensure_directory($jobsDir);

    $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $jobId;
    if (!is_dir($jobDir)) {
        throw new RuntimeException('Job not found');
    }

    $jobJsonPath = $jobDir . '/job.json';
    $job = load_json_file($jobJsonPath);
    if (!is_array($job)) {
        throw new RuntimeException('Job metadata missing');
    }

    $sourcePath = $jobDir . '/source.pdf';
    if (!is_file($sourcePath)) {
        throw new RuntimeException('Missing source.pdf');
    }

    if ($normalizedMode === 'post-ocr' && !is_file($jobDir . '/review.pdf')) {
        throw new RuntimeException('Missing review.pdf');
    }

    $artifactPaths = [
        $jobDir . '/ocr.txt',
        $jobDir . '/ocr-objects.json',
        $jobDir . '/extracted.json',
    ];
    if ($normalizedMode === 'full') {
        $artifactPaths[] = $jobDir . '/review.pdf';
    }

    foreach ($artifactPaths as $artifactPath) {
        if (is_file($artifactPath)) {
            @unlink($artifactPath);
        }
    }

    unset($job['error'], $job['analysis']);
    $job['status'] = 'processing';
    $job['updatedAt'] = now_iso();
    $job['reprocessMode'] = $normalizedMode;
    write_json_file($jobJsonPath, $job);

    trigger_processing_worker();

    return [
        'jobId' => $jobId,
        'mode' => $normalizedMode,
    ];
}
