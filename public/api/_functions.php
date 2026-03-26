<?php
declare(strict_types=1);

const DOCFLOW_OCR_VERSION = 1;
const DOCFLOW_OCR_METADATA_KEY = 'docflow-ocr-version';

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

function docflow_ocr_version(): int
{
    return DOCFLOW_OCR_VERSION;
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
    $repository = client_repository_instance();
    if ($repository === null) {
        return [];
    }

    try {
        $rows = $repository->listAll();
    } catch (Throwable $e) {
        return [];
    }

    $clients = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $firstName = is_string($row['first_name'] ?? null) ? trim((string) $row['first_name']) : '';
        $lastName = is_string($row['last_name'] ?? null) ? trim((string) $row['last_name']) : '';
        $name = trim($firstName . ' ' . $lastName);
        $dirName = is_string($row['folder_name'] ?? null) ? trim((string) $row['folder_name']) : '';
        $pin = is_string($row['personal_identity_number'] ?? null)
            ? trim((string) $row['personal_identity_number'])
            : '';

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

function client_repository_instance(): ?\Docflow\Clients\ClientRepository
{
    static $initialized = false;
    static $repository = null;

    if ($initialized) {
        return $repository;
    }

    $initialized = true;
    try {
        $pdo = \Docflow\Database\Connection::make();
        $repository = new \Docflow\Clients\ClientRepository($pdo);
    } catch (Throwable $e) {
        $repository = null;
    }

    return $repository;
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

function job_repository_instance(): ?\Docflow\Jobs\JobRepository
{
    static $initialized = false;
    static $repository = null;

    if ($initialized) {
        return $repository;
    }

    $initialized = true;
    try {
        $pdo = \Docflow\Database\Connection::make();
        $repository = new \Docflow\Jobs\JobRepository($pdo);
    } catch (Throwable $e) {
        $repository = null;
    }

    return $repository;
}

function archiving_rules_state_repository_instance(): ?\Docflow\Archiving\ArchivingRulesStateRepository
{
    static $initialized = false;
    static $repository = null;

    if ($initialized) {
        return $repository;
    }

    $initialized = true;
    try {
        $pdo = \Docflow\Database\Connection::make();
        $repository = new \Docflow\Archiving\ArchivingRulesStateRepository($pdo);
    } catch (Throwable $e) {
        $repository = null;
    }

    return $repository;
}

function job_sender_snapshot_ids(string $jobId): ?array
{
    $normalizedId = trim($jobId);
    if ($normalizedId === '') {
        return null;
    }

    $repository = job_repository_instance();
    if ($repository === null) {
        return null;
    }

    try {
        $row = $repository->findById($normalizedId);
    } catch (Throwable $e) {
        return null;
    }

    if (!is_array($row)) {
        return null;
    }

    $senderId = isset($row['sender_id']) && (int) $row['sender_id'] > 0 ? (int) $row['sender_id'] : null;
    $autoSenderId = isset($row['auto_sender_id']) && (int) $row['auto_sender_id'] > 0 ? (int) $row['auto_sender_id'] : null;

    return [
        'senderId' => resolve_active_sender_id($senderId),
        'autoSenderId' => resolve_active_sender_id($autoSenderId),
    ];
}

function sync_job_sender_snapshot_ids(string $jobId, ?int $senderId, ?int $autoSenderId): void
{
    $normalizedId = trim($jobId);
    if ($normalizedId === '') {
        return;
    }

    $repository = job_repository_instance();
    if ($repository === null) {
        return;
    }

    try {
        $repository->upsertSenderSnapshotIds(
            $normalizedId,
            $senderId !== null && $senderId > 0 ? $senderId : null,
            $autoSenderId !== null && $autoSenderId > 0 ? $autoSenderId : null
        );
    } catch (Throwable $e) {
        // Best effort for now. job.json remains the fallback while this migration lands.
    }
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

function resolve_active_sender_id(?int $senderId): ?int
{
    $normalizedId = is_int($senderId) ? $senderId : (int) $senderId;
    if ($normalizedId < 1) {
        return null;
    }

    $repository = sender_repository_instance();
    if ($repository === null) {
        return $normalizedId;
    }

    try {
        $resolvedId = $repository->resolveActiveSenderId($normalizedId);
        return $resolvedId !== null && $resolvedId > 0 ? $resolvedId : null;
    } catch (Throwable $e) {
        return $normalizedId;
    }
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

function system_label_definitions(): array
{
    return [
        'invoice' => [
            'name' => 'Faktura',
            'minScore' => 15,
            'rules' => [
                ['type' => 'text', 'text' => 'faktura', 'score' => 4],
                ['type' => 'text', 'text' => 'förfallodatum', 'score' => 3],
                ['type' => 'text', 'text' => 'faktura', 'score' => 2],
                ['type' => 'text', 'text' => 'förfallodatum', 'score' => 3],
                ['type' => 'text', 'text' => 'bankgiro', 'score' => 5],
                ['type' => 'text', 'text' => 'plusgiro', 'score' => 5],
                ['type' => 'text', 'text' => 'ocr', 'score' => 5],
                ['type' => 'text', 'text' => 'ocr-nummer', 'score' => 5],
                ['type' => 'text', 'text' => 'fakturanummer', 'score' => 5],
                ['type' => 'text', 'text' => 'e-faktura', 'score' => 4],
                ['type' => 'text', 'text' => 'betalningsmottagare', 'score' => 2],
            ],
        ],
        'autogiro' => [
            'name' => 'Autogiro',
            'minScore' => 3,
            'rules' => [
                ['type' => 'text', 'text' => 'autogiro', 'score' => 3],
            ],
        ],
    ];
}

function normalize_archive_rule(mixed $input, array $options = []): array
{
    $rule = is_array($input) ? $input : [];
    $allowLabel = ($options['allowLabel'] ?? false) === true;
    $type = is_string($rule['type'] ?? null) ? trim(strtolower((string) $rule['type'])) : 'text';
    if ($type === 'label' && !$allowLabel) {
        $type = 'text';
    } elseif ($type !== 'label') {
        $type = 'text';
    }

    return [
        'type' => $type,
        'text' => is_string($rule['text'] ?? null) ? trim((string) $rule['text']) : '',
        'labelId' => is_string($rule['labelId'] ?? null) ? trim((string) $rule['labelId']) : '',
        'score' => positive_int($rule['score'] ?? 1, 1),
    ];
}

function normalize_system_label_rule(mixed $input): array
{
    return normalize_archive_rule($input, [
        'allowLabel' => true,
    ]);
}

function normalize_editable_label_rule(mixed $input): array
{
    return normalize_archive_rule($input, [
        'allowLabel' => true,
    ]);
}

function normalize_category_rule(mixed $input): array
{
    return normalize_archive_rule($input, [
        'allowLabel' => true,
    ]);
}

function slugify_text(string $value, string $separator = '-', string $fallback = ''): string
{
    $safeSeparator = $separator === '_' ? '_' : '-';
    $normalized = lowercase_text(trim($value));
    $normalized = strtr($normalized, [
        'å' => 'a',
        'ä' => 'a',
        'ö' => 'o',
        'á' => 'a',
        'à' => 'a',
        'â' => 'a',
        'ã' => 'a',
        'æ' => 'ae',
        'ç' => 'c',
        'č' => 'c',
        'ď' => 'd',
        'ð' => 'd',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ě' => 'e',
        'í' => 'i',
        'ì' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ľ' => 'l',
        'ĺ' => 'l',
        'ł' => 'l',
        'ñ' => 'n',
        'ň' => 'n',
        'ó' => 'o',
        'ò' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ø' => 'o',
        'œ' => 'oe',
        'ŕ' => 'r',
        'ř' => 'r',
        'š' => 's',
        'ß' => 'ss',
        'ť' => 't',
        'þ' => 'th',
        'ú' => 'u',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ů' => 'u',
        'ý' => 'y',
        'ÿ' => 'y',
        'ž' => 'z',
    ]);
    $normalized = preg_replace('/[^a-z0-9]+/u', $safeSeparator, $normalized);
    if (!is_string($normalized)) {
        $normalized = '';
    }
    $normalized = trim($normalized, $safeSeparator);
    return $normalized !== '' ? $normalized : $fallback;
}

function normalize_config_key(string $value, string $fallback = 'field'): string
{
    return slugify_text($value, '_', $fallback);
}

function ensure_unique_config_key(string $baseKey, array &$usedKeys): string
{
    $candidate = $baseKey;
    $suffix = 2;
    while (isset($usedKeys[$candidate])) {
        $candidate = $baseKey . '_' . $suffix;
        $suffix++;
    }
    $usedKeys[$candidate] = true;
    return $candidate;
}

function normalize_filename_template_parts(mixed $input, int $depth = 0): array
{
    if (!is_array($input) || $depth > 6) {
        return [];
    }

    $parts = [];
    foreach ($input as $part) {
        $normalized = normalize_filename_template_part($part, $depth + 1);
        if ($normalized !== null) {
            $parts[] = $normalized;
        }
    }

    return $parts;
}

function normalize_filename_template_candidate_parts(mixed $input, int $depth = 0): array
{
    return array_values(array_filter(
        normalize_filename_template_parts($input, $depth),
        static fn (array $part): bool => ($part['type'] ?? 'text') !== 'text'
    ));
}

function normalize_filename_template_part(mixed $input, int $depth = 0): ?array
{
    if (!is_array($input) || $depth > 6) {
        return null;
    }

    $type = is_string($input['type'] ?? null) ? trim((string) $input['type']) : 'text';
    $prefixParts = normalize_filename_template_parts($input['prefixParts'] ?? [], $depth + 1);
    $suffixParts = normalize_filename_template_parts($input['suffixParts'] ?? [], $depth + 1);
    if ($type === 'dataField' || $type === 'systemField') {
        $key = is_string($input['key'] ?? null) ? trim((string) $input['key']) : '';
        if ($key === '') {
            return null;
        }

        return [
            'type' => $type,
            'key' => $key,
            'prefixParts' => $prefixParts,
            'suffixParts' => $suffixParts,
        ];
    }

    if ($type === 'category') {
        return [
            'type' => 'category',
            'prefixParts' => $prefixParts,
            'suffixParts' => $suffixParts,
        ];
    }

    if ($type === 'labels') {
        return [
            'type' => 'labels',
            'separator' => is_string($input['separator'] ?? null) ? (string) $input['separator'] : ', ',
            'prefixParts' => $prefixParts,
            'suffixParts' => $suffixParts,
        ];
    }

    if ($type === 'firstAvailable') {
        return [
            'type' => 'firstAvailable',
            'parts' => normalize_filename_template_candidate_parts($input['parts'] ?? [], $depth + 1),
            'prefixParts' => $prefixParts,
            'suffixParts' => $suffixParts,
        ];
    }

    $value = is_string($input['value'] ?? null) ? (string) $input['value'] : '';
    return [
        'type' => 'text',
        'value' => $value,
    ];
}

function normalize_filename_template(mixed $input): array
{
    if (is_string($input)) {
        $trimmed = trim($input);
        return [
            'parts' => $trimmed === ''
                ? []
                : [[
                    'type' => 'text',
                    'value' => $trimmed,
                ]],
        ];
    }

    if (!is_array($input)) {
        return ['parts' => []];
    }

    return [
        'parts' => normalize_filename_template_parts($input['parts'] ?? [], 0),
    ];
}

function valid_extraction_field_extractor(string $extractor, string $fallback = 'generic_label'): string
{
    $normalized = trim(strtolower($extractor));
    $valid = [
        'generic_label',
        'amount',
        'due_date',
        'document_date',
        'bankgiro',
        'plusgiro',
        'supplier',
        'payment_receiver',
        'iban',
        'swift',
        'ocr',
    ];
    return in_array($normalized, $valid, true) ? $normalized : $fallback;
}

function predefined_extraction_field_definitions(): array
{
    return [
        'amount' => [
            'name' => 'Belopp',
            'searchString' => 'att betala',
            'extractor' => 'amount',
        ],
        'due_date' => [
            'name' => 'Förfallodatum',
            'searchString' => 'förfallodatum',
            'extractor' => 'due_date',
        ],
        'bankgiro' => [
            'name' => 'Bankgiro',
            'searchString' => 'bankgiro',
            'extractor' => 'bankgiro',
        ],
        'plusgiro' => [
            'name' => 'Plusgiro',
            'searchString' => 'plusgiro',
            'extractor' => 'plusgiro',
        ],
        'supplier' => [
            'name' => 'Leverantör',
            'searchString' => 'leverantör',
            'extractor' => 'supplier',
        ],
        'payment_receiver' => [
            'name' => 'Betalningsmottagare',
            'searchString' => 'betalningsmottagare',
            'extractor' => 'payment_receiver',
        ],
        'iban' => [
            'name' => 'IBAN',
            'searchString' => 'iban',
            'extractor' => 'iban',
        ],
        'swift' => [
            'name' => 'SWIFT',
            'searchString' => 'swift',
            'extractor' => 'swift',
        ],
        'ocr' => [
            'name' => 'OCR',
            'searchString' => 'ocr',
            'extractor' => 'ocr',
        ],
    ];
}

function system_extraction_field_definitions(): array
{
    return [
        'document_date' => [
            'name' => 'Dokumentdatum',
            'searchString' => '',
            'extractor' => 'document_date',
        ],
    ];
}

function normalize_extraction_fields(mixed $input): array
{
    if (!is_array($input)) {
        return [];
    }

    $fields = [];
    $usedKeys = [];

    foreach ($input as $row) {
        if (!is_array($row)) {
            continue;
        }

        $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
        $searchString = is_string($row['searchString'] ?? null)
            ? trim((string) $row['searchString'])
            : (is_string($row['query'] ?? null) ? trim((string) $row['query']) : '');
        $extractor = valid_extraction_field_extractor(
            is_string($row['extractor'] ?? null) ? (string) $row['extractor'] : 'generic_label'
        );

        if ($name === '' || ($searchString === '' && $extractor === 'generic_label')) {
            continue;
        }

        $keySource = is_string($row['key'] ?? null) ? trim((string) $row['key']) : $name;
        $baseKey = normalize_config_key($keySource, 'field');
        $key = ensure_unique_config_key($baseKey, $usedKeys);

        $fields[] = [
            'key' => $key,
            'name' => $name,
            'searchString' => $searchString,
            'extractor' => $extractor,
        ];
    }

    return $fields;
}

function normalize_predefined_extraction_field_with_defaults(string $key, mixed $input, array $defaults): array
{
    $field = is_array($input) ? $input : [];
    $name = is_string($field['name'] ?? null) ? trim((string) $field['name']) : '';
    if ($name === '') {
        $name = is_string($defaults['name'] ?? null) ? trim((string) $defaults['name']) : '';
    }

    $searchString = is_string($field['searchString'] ?? null)
        ? trim((string) $field['searchString'])
        : (is_string($field['query'] ?? null) ? trim((string) $field['query']) : '');
    if ($searchString === '') {
        $searchString = is_string($defaults['searchString'] ?? null) ? trim((string) $defaults['searchString']) : '';
    }

    return [
        'key' => $key,
        'name' => $name,
        'searchString' => $searchString,
        'extractor' => valid_extraction_field_extractor(
            is_string($field['extractor'] ?? null) ? (string) $field['extractor'] : (string) ($defaults['extractor'] ?? 'generic_label'),
            valid_extraction_field_extractor((string) ($defaults['extractor'] ?? 'generic_label'))
        ),
        'predefinedFieldKey' => $key,
        'isPredefinedField' => true,
    ];
}

function predefined_extraction_fields_template(): array
{
    $definitions = predefined_extraction_field_definitions();
    $fields = [];
    foreach ($definitions as $key => $defaults) {
        $fields[] = normalize_predefined_extraction_field_with_defaults($key, [], $defaults);
    }
    return $fields;
}

function normalize_predefined_extraction_fields(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];
    $byKey = [];
    foreach ($decoded as $field) {
        if (!is_array($field)) {
            continue;
        }
        $predefinedKey = is_string($field['predefinedFieldKey'] ?? null) ? trim((string) $field['predefinedFieldKey']) : '';
        $key = $predefinedKey !== ''
            ? $predefinedKey
            : (is_string($field['key'] ?? null) ? trim((string) $field['key']) : '');
        if ($key !== '') {
            $byKey[$key] = $field;
        }
    }

    $definitions = predefined_extraction_field_definitions();
    $fields = [];
    foreach ($definitions as $key => $defaults) {
        $fields[] = normalize_predefined_extraction_field_with_defaults($key, $byKey[$key] ?? [], $defaults);
    }
    return $fields;
}

function system_extraction_fields_template(): array
{
    $definitions = system_extraction_field_definitions();
    $fields = [];
    foreach ($definitions as $key => $defaults) {
        $fields[] = normalize_system_extraction_field_with_defaults($key, [], $defaults);
    }
    return $fields;
}

function normalize_system_extraction_fields(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];
    $byKey = [];
    foreach ($decoded as $field) {
        if (!is_array($field)) {
            continue;
        }
        $systemKey = is_string($field['systemFieldKey'] ?? null) ? trim((string) $field['systemFieldKey']) : '';
        $key = $systemKey !== ''
            ? $systemKey
            : (is_string($field['key'] ?? null) ? trim((string) $field['key']) : '');
        if ($key !== '') {
            $byKey[$key] = $field;
        }
    }

    $definitions = system_extraction_field_definitions();
    $fields = [];
    foreach ($definitions as $key => $defaults) {
        $fields[] = normalize_system_extraction_field_with_defaults($key, $byKey[$key] ?? [], $defaults);
    }
    return $fields;
}

function normalize_system_extraction_field_with_defaults(string $key, mixed $input, array $defaults): array
{
    $field = is_array($input) ? $input : [];
    $name = is_string($field['name'] ?? null) ? trim((string) $field['name']) : '';
    if ($name === '') {
        $name = is_string($defaults['name'] ?? null) ? trim((string) $defaults['name']) : '';
    }

    $searchString = is_string($field['searchString'] ?? null)
        ? trim((string) $field['searchString'])
        : (is_string($field['query'] ?? null) ? trim((string) $field['query']) : '');
    if ($searchString === '') {
        $searchString = is_string($defaults['searchString'] ?? null) ? trim((string) $defaults['searchString']) : '';
    }

    return [
        'key' => $key,
        'name' => $name,
        'searchString' => $searchString,
        'extractor' => valid_extraction_field_extractor(
            is_string($field['extractor'] ?? null) ? (string) $field['extractor'] : (string) ($defaults['extractor'] ?? 'generic_label'),
            valid_extraction_field_extractor((string) ($defaults['extractor'] ?? 'generic_label'))
        ),
        'systemFieldKey' => $key,
        'isSystemField' => true,
    ];
}

function load_extraction_fields(): array
{
    $rules = load_active_archiving_rules();
    $fields = is_array($rules['fields'] ?? null) ? $rules['fields'] : [];
    $predefinedFields = is_array($rules['predefinedFields'] ?? null) ? $rules['predefinedFields'] : [];
    $systemFields = is_array($rules['systemFields'] ?? null) ? $rules['systemFields'] : [];
    return array_values(array_merge($predefinedFields, $systemFields, $fields));
}

function normalize_system_label_with_defaults(string $key, mixed $input, array $defaults): array
{
    $label = is_array($input) ? $input : [];
    $name = is_string($label['name'] ?? null) ? trim((string) $label['name']) : '';
    if ($name === '') {
        $name = is_string($defaults['name'] ?? null) ? trim((string) $defaults['name']) : '';
    }

    $defaultMinScore = positive_int($defaults['minScore'] ?? 1, 1);
    $defaultRules = [];
    if (is_array($defaults['rules'] ?? null)) {
        foreach ($defaults['rules'] as $rule) {
            $defaultRules[] = normalize_system_label_rule($rule);
        }
    }

    $rawRules = $label['rules'] ?? [];
    $rules = [];
    if (is_array($rawRules)) {
        foreach ($rawRules as $rule) {
            $rules[] = normalize_system_label_rule($rule);
        }
    }
    if (count($rules) === 0) {
        $rules = $defaultRules;
    }
    if ($key === 'invoice') {
        $rules = array_values(array_filter($rules, static function ($rule): bool {
            if (!is_array($rule)) {
                return false;
            }
            if (($rule['type'] ?? 'text') !== 'text') {
                return true;
            }
            $text = is_string($rule['text'] ?? null) ? trim((string) $rule['text']) : '';
            return $text === '' || lowercase_text($text) !== 'autogiro';
        }));
    }

    return [
        'id' => slugify_text($name, '-', 'label'),
        'systemLabelKey' => $key,
        'name' => $name,
        'isSystemLabel' => true,
        'minScore' => positive_int($label['minScore'] ?? $defaultMinScore, $defaultMinScore),
        'rules' => $rules,
    ];
}

function system_labels_template(): array
{
    $definitions = system_label_definitions();
    $labels = [];
    foreach ($definitions as $key => $defaults) {
        $labels[$key] = normalize_system_label_with_defaults($key, [], $defaults);
    }
    return $labels;
}

function normalize_system_labels(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];
    $definitions = system_label_definitions();
    $labels = [];
    foreach ($definitions as $key => $defaults) {
        $labels[$key] = normalize_system_label_with_defaults($key, $decoded[$key] ?? [], $defaults);
    }
    return $labels;
}

function normalize_label_definition(array $input): ?array
{
    $name = is_string($input['name'] ?? null) ? trim((string) $input['name']) : '';
    if ($name === '') {
        return null;
    }

    $rulesIn = $input['rules'] ?? [];
    $rules = [];
    if (is_array($rulesIn)) {
        foreach ($rulesIn as $ruleIn) {
            $rules[] = normalize_editable_label_rule($ruleIn);
        }
    }

    if (count($rules) === 0) {
        $rules[] = normalize_editable_label_rule([]);
    }

    return [
        'id' => slugify_text($name, '-', 'label'),
        'name' => $name,
        'minScore' => positive_int($input['minScore'] ?? 1, 1),
        'rules' => $rules,
    ];
}

function normalize_labels(mixed $input): array
{
    if (!is_array($input)) {
        return [];
    }

    $labels = [];
    $usedIds = [];
    foreach ($input as $row) {
        if (!is_array($row)) {
            continue;
        }

        $normalized = normalize_label_definition($row);
        if (!is_array($normalized)) {
            continue;
        }

        $id = (string) ($normalized['id'] ?? '');
        if ($id === '' || isset($usedIds[$id])) {
            throw new RuntimeException('Etikett-id krockar: ' . $id);
        }
        $usedIds[$id] = true;
        $labels[] = $normalized;
    }

    return $labels;
}

function build_categories_from_archive_folders(array $archiveFolders): array
{
    $categories = [];

    foreach ($archiveFolders as $archiveFolderIndex => $archiveFolder) {
        if (!is_array($archiveFolder)) {
            continue;
        }

        $archiveFolderId = is_string($archiveFolder['id'] ?? null) ? trim((string) $archiveFolder['id']) : '';
        $archiveFolderName = is_string($archiveFolder['name'] ?? null) ? trim((string) $archiveFolder['name']) : '';
        $archiveFolderPath = is_string($archiveFolder['path'] ?? null) ? trim((string) $archiveFolder['path']) : '';
        $archiveFolderFilenameTemplate = normalize_filename_template($archiveFolder['filenameTemplate'] ?? null);
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

                    $rules[] = normalize_category_rule($ruleIn);
                }
            }

            $categories[] = [
                'id' => is_string($category['id'] ?? null) ? trim((string) $category['id']) : '',
                'name' => is_string($category['name'] ?? null) ? trim((string) $category['name']) : '',
                'archiveFolderId' => $archiveFolderId,
                'path' => $archiveFolderPath,
                'archiveFolderName' => $archiveFolderName,
                'minScore' => positive_int($category['minScore'] ?? 1, 1),
                'rules' => $rules,
                'filenameTemplate' => $archiveFolderFilenameTemplate,
            ];
        }
    }

    return $categories;
}

function load_labels(): array
{
    $rules = load_active_archiving_rules();
    return is_array($rules['labels'] ?? null) ? $rules['labels'] : [];
}

function load_system_labels(): array
{
    $rules = load_active_archiving_rules();
    return is_array($rules['systemLabels'] ?? null) ? $rules['systemLabels'] : system_labels_template();
}

function load_categories(): array
{
    $rules = load_active_archiving_rules();
    $archiveFolders = is_array($rules['archiveFolders'] ?? null) ? $rules['archiveFolders'] : [];
    return build_categories_from_archive_folders($archiveFolders);
}

function normalize_archive_structure(array $input): array
{
    $archiveFolders = [];
    $usedCategoryIds = [];
    foreach ($input as $row) {
        if (!is_array($row)) {
            continue;
        }

        $filenameTemplate = null;
        if (array_key_exists('filenameTemplate', $row)) {
            $filenameTemplate = normalize_filename_template($row['filenameTemplate']);
        } else {
            $rawCategories = is_array($row['categories'] ?? null) ? $row['categories'] : [];
            foreach ($rawCategories as $rawCategory) {
                if (!is_array($rawCategory) || !array_key_exists('filenameTemplate', $rawCategory)) {
                    continue;
                }
                $filenameTemplate = normalize_filename_template($rawCategory['filenameTemplate']);
                break;
            }
        }
        if (!is_array($filenameTemplate)) {
            $filenameTemplate = normalize_filename_template(null);
        }

        $archiveFolders[] = [
            'id' => slugify_text(
                is_string($row['path'] ?? null) && trim((string) $row['path']) !== ''
                    ? (string) $row['path']
                    : (is_string($row['name'] ?? null) ? (string) $row['name'] : ''),
                '-',
                'folder'
            ),
            'name' => is_string($row['name'] ?? null) ? trim((string) $row['name']) : '',
            'path' => is_string($row['path'] ?? null) ? trim((string) $row['path']) : '',
            'filenameTemplate' => $filenameTemplate,
            'categories' => normalize_archive_categories($row['categories'] ?? [], $usedCategoryIds),
        ];
    }

    return $archiveFolders;
}

function sync_active_filename_templates_from_archive_folders(array $activeArchiveFolders, array $savedArchiveFolders): array
{
    $normalizedActive = normalize_archive_structure($activeArchiveFolders);
    $normalizedSaved = normalize_archive_structure($savedArchiveFolders);
    $savedById = [];
    foreach ($normalizedSaved as $index => $archiveFolder) {
        if (!is_array($archiveFolder)) {
            continue;
        }
        $folderId = is_string($archiveFolder['id'] ?? null) ? trim((string) $archiveFolder['id']) : '';
        if ($folderId !== '') {
            $savedById[$folderId] = $archiveFolder;
        }
        $normalizedSaved[$index] = $archiveFolder;
    }

    foreach ($normalizedActive as $index => $activeArchiveFolder) {
        if (!is_array($activeArchiveFolder)) {
            continue;
        }
        $activeId = is_string($activeArchiveFolder['id'] ?? null) ? trim((string) $activeArchiveFolder['id']) : '';
        $sourceFolder = null;
        if ($activeId !== '' && isset($savedById[$activeId]) && is_array($savedById[$activeId])) {
            $sourceFolder = $savedById[$activeId];
        } elseif (isset($normalizedSaved[$index]) && is_array($normalizedSaved[$index])) {
            $sourceFolder = $normalizedSaved[$index];
        }
        if (!is_array($sourceFolder)) {
            continue;
        }
        $activeArchiveFolder['filenameTemplate'] = normalize_filename_template($sourceFolder['filenameTemplate'] ?? null);
        $normalizedActive[$index] = $activeArchiveFolder;
    }

    return $normalizedActive;
}

function archive_structure_without_filename_templates(array $archiveFolders): array
{
    $normalized = normalize_archive_structure($archiveFolders);
    foreach ($normalized as $index => $archiveFolder) {
        if (!is_array($archiveFolder)) {
            continue;
        }
        unset($archiveFolder['filenameTemplate']);
        $categories = is_array($archiveFolder['categories'] ?? null) ? $archiveFolder['categories'] : [];
        foreach ($categories as $categoryIndex => $category) {
            if (!is_array($category)) {
                continue;
            }
            unset($category['filenameTemplate']);
            $categories[$categoryIndex] = $category;
        }
        $archiveFolder['categories'] = $categories;
        $normalized[$index] = $archiveFolder;
    }

    return $normalized;
}

function normalize_archive_structure_data(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];

    $rawArchiveFolders = is_array($decoded['archiveFolders'] ?? null) ? $decoded['archiveFolders'] : [];
    $archiveFolders = normalize_archive_structure($rawArchiveFolders);

    return [
        'archiveFolders' => $archiveFolders,
    ];
}

function normalize_archiving_rules_set(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];

    return [
        'archiveFolders' => normalize_archive_structure(
            is_array($decoded['archiveFolders'] ?? null) ? $decoded['archiveFolders'] : []
        ),
        'labels' => normalize_labels(
            is_array($decoded['labels'] ?? null) ? $decoded['labels'] : []
        ),
        'systemLabels' => normalize_system_labels($decoded['systemLabels'] ?? []),
        'fields' => normalize_extraction_fields(
            is_array($decoded['fields'] ?? null) ? $decoded['fields'] : []
        ),
        'predefinedFields' => normalize_predefined_extraction_fields(
            is_array($decoded['predefinedFields'] ?? null) ? $decoded['predefinedFields'] : []
        ),
        'systemFields' => normalize_system_extraction_fields(
            is_array($decoded['systemFields'] ?? null) ? $decoded['systemFields'] : []
        ),
    ];
}

function normalize_archiving_rules_state(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];
    $defaults = normalize_archiving_rules_set([
        'archiveFolders' => [],
        'labels' => [],
        'systemLabels' => system_labels_template(),
        'fields' => [],
        'predefinedFields' => predefined_extraction_fields_template(),
        'systemFields' => system_extraction_fields_template(),
    ]);
    $active = array_key_exists('activeArchivingRules', $decoded)
        ? normalize_archiving_rules_set($decoded['activeArchivingRules'])
        : $defaults;
    $draft = array_key_exists('draftArchivingRules', $decoded)
        ? normalize_archiving_rules_set($decoded['draftArchivingRules'])
        : $active;
    $version = filter_var($decoded['activeArchivingRulesVersion'] ?? null, FILTER_VALIDATE_INT);
    if ($version === false || $version === null || $version < 1) {
        $version = 1;
    }

    return [
        'activeArchivingRulesVersion' => (int) $version,
        'activeArchivingRules' => $active,
        'draftArchivingRules' => $draft,
    ];
}

function load_archiving_rules_state(): array
{
    $repository = archiving_rules_state_repository_instance();
    if ($repository === null) {
        return normalize_archiving_rules_state([]);
    }

    $row = $repository->findSingleton();
    if (!is_array($row)) {
        $initial = normalize_archiving_rules_state([]);
        save_archiving_rules_state($initial);
        return $initial;
    }

    $decoded = [
        'activeArchivingRulesVersion' => (int) ($row['active_archiving_rules_version'] ?? 1),
        'activeArchivingRules' => json_decode((string) ($row['active_archiving_rules_json'] ?? '[]'), true),
        'draftArchivingRules' => json_decode((string) ($row['draft_archiving_rules_json'] ?? '[]'), true),
    ];

    $normalized = normalize_archiving_rules_state($decoded);
    $activeJson = json_encode(
        $normalized['activeArchivingRules'],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    $draftJson = json_encode(
        $normalized['draftArchivingRules'],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if (
        !is_string($activeJson)
        || !is_string($draftJson)
        || (int) ($row['active_archiving_rules_version'] ?? 1) !== (int) $normalized['activeArchivingRulesVersion']
        || trim((string) ($row['active_archiving_rules_json'] ?? '')) !== trim($activeJson)
        || trim((string) ($row['draft_archiving_rules_json'] ?? '')) !== trim($draftJson)
    ) {
        save_archiving_rules_state($normalized);
    }

    return $normalized;
}

function save_archiving_rules_state(array $state): array
{
    $normalized = normalize_archiving_rules_state($state);
    $repository = archiving_rules_state_repository_instance();
    if ($repository === null) {
        throw new RuntimeException('Archiving rules state repository is unavailable.');
    }

    $repository->replaceState(
        (int) ($normalized['activeArchivingRulesVersion'] ?? 1),
        is_array($normalized['activeArchivingRules'] ?? null) ? $normalized['activeArchivingRules'] : [],
        is_array($normalized['draftArchivingRules'] ?? null) ? $normalized['draftArchivingRules'] : []
    );
    return $normalized;
}

function load_active_archiving_rules(): array
{
    $state = load_archiving_rules_state();
    return normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);
}

function load_draft_archiving_rules(): array
{
    $state = load_archiving_rules_state();
    return normalize_archiving_rules_set($state['draftArchivingRules'] ?? []);
}

function active_archiving_rules_version(): int
{
    $state = load_archiving_rules_state();
    return (int) ($state['activeArchivingRulesVersion'] ?? 1);
}

function archiving_rules_unpublished_hash(array $rules): string
{
    $encoded = json_encode(
        normalize_archiving_rules_set($rules),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    return sha1(is_string($encoded) ? $encoded : '');
}

function archiving_rules_have_unpublished_changes(?array $activeRules = null, ?array $draftRules = null): bool
{
    $resolvedActiveRules = is_array($activeRules) ? $activeRules : load_active_archiving_rules();
    $resolvedDraftRules = is_array($draftRules) ? $draftRules : load_draft_archiving_rules();
    return archiving_rules_unpublished_hash($resolvedActiveRules) !== archiving_rules_unpublished_hash($resolvedDraftRules);
}

function archiving_rules_review_relevant_set(array $rules): array
{
    $normalized = normalize_archiving_rules_set($rules);
    $archiveFolders = is_array($normalized['archiveFolders'] ?? null) ? $normalized['archiveFolders'] : [];
    foreach ($archiveFolders as $index => $archiveFolder) {
        if (!is_array($archiveFolder)) {
            continue;
        }
        unset($archiveFolder['filenameTemplate']);
        $categories = is_array($archiveFolder['categories'] ?? null) ? $archiveFolder['categories'] : [];
        foreach ($categories as $categoryIndex => $category) {
            if (!is_array($category)) {
                continue;
            }
            unset($category['filenameTemplate']);
            $categories[$categoryIndex] = $category;
        }
        $archiveFolder['categories'] = $categories;
        $archiveFolders[$index] = $archiveFolder;
    }
    $normalized['archiveFolders'] = $archiveFolders;

    return $normalized;
}

function archiving_rules_review_relevant_hash(array $rules): string
{
    $encoded = json_encode(
        archiving_rules_review_relevant_set($rules),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    return sha1(is_string($encoded) ? $encoded : '');
}

function archiving_rules_have_review_relevant_changes(?array $activeRules = null, ?array $draftRules = null): bool
{
    $resolvedActiveRules = is_array($activeRules) ? $activeRules : load_active_archiving_rules();
    $resolvedDraftRules = is_array($draftRules) ? $draftRules : load_draft_archiving_rules();
    return archiving_rules_review_relevant_hash($resolvedActiveRules) !== archiving_rules_review_relevant_hash($resolvedDraftRules);
}

function archiving_rules_changed_sections(array $active, array $draft): array
{
    $changedSections = [];
    foreach ([
        'archiveFolders' => 'Arkivstruktur',
        'labels' => 'Etiketter',
        'systemLabels' => 'Fördefinerade etiketter',
        'fields' => 'Egna datafält',
        'predefinedFields' => 'Fördefinerade datafält',
        'systemFields' => 'Systemdatafält',
    ] as $key => $label) {
        $leftValue = $active[$key] ?? null;
        $rightValue = $draft[$key] ?? null;
        if (json_encode($leftValue) !== json_encode($rightValue)) {
            $changedSections[] = $label;
        }
    }

    return $changedSections;
}

function filename_template_part_review_label(array $part, array $nameMaps): string
{
    $type = is_string($part['type'] ?? null) ? (string) $part['type'] : 'text';
    if ($type === 'text') {
        return is_string($part['value'] ?? null) ? (string) $part['value'] : '';
    }

    $prefix = filename_template_parts_review_label(is_array($part['prefixParts'] ?? null) ? $part['prefixParts'] : [], $nameMaps);
    $suffix = filename_template_parts_review_label(is_array($part['suffixParts'] ?? null) ? $part['suffixParts'] : [], $nameMaps);
    $label = '';

    if ($type === 'systemField' || $type === 'dataField') {
        $key = is_string($part['key'] ?? null) ? trim((string) $part['key']) : '';
        $resolved = $nameMaps[$type][$key] ?? $key;
        $label = $resolved !== '' ? $resolved : ($type === 'systemField' ? 'Systemdatafält' : 'Datafält');
    } elseif ($type === 'category') {
        $label = 'Kategori';
    } elseif ($type === 'labels') {
        $separator = is_string($part['separator'] ?? null) ? (string) $part['separator'] : ', ';
        $label = $separator === ', '
            ? 'Etiketter'
            : sprintf('Etiketter (separator: %s)', $separator);
    } elseif ($type === 'firstAvailable') {
        $candidateLabels = [];
        foreach (is_array($part['parts'] ?? null) ? $part['parts'] : [] as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            $candidateLabel = trim(filename_template_part_review_label($candidate, $nameMaps));
            if ($candidateLabel !== '') {
                $candidateLabels[] = $candidateLabel;
            }
        }
        $label = 'Första tillgängliga';
        if ($candidateLabels !== []) {
            $label .= ': ' . implode(' / ', $candidateLabels);
        }
    }

    if ($label === '') {
        return $prefix . $suffix;
    }

    return $prefix . '[' . $label . ']' . $suffix;
}

function filename_template_parts_review_label(array $parts, array $nameMaps): string
{
    $result = '';
    foreach ($parts as $part) {
        if (!is_array($part)) {
            continue;
        }
        $result .= filename_template_part_review_label($part, $nameMaps);
    }

    return $result;
}

function filename_template_review_label(array $template, array $nameMaps): string
{
    $parts = is_array($template['parts'] ?? null) ? $template['parts'] : [];
    $rendered = trim(preg_replace('/\s+/u', ' ', filename_template_parts_review_label($parts, $nameMaps)) ?? '');
    return $rendered !== '' ? $rendered : 'Tom filnamnsmall';
}

function filename_template_review_name_maps(array $activeRules, array $draftRules): array
{
    $dataFieldNames = [];
    $systemFieldNames = [];
    foreach ([$activeRules, $draftRules] as $rules) {
        foreach (['fields', 'predefinedFields'] as $groupKey) {
            foreach (is_array($rules[$groupKey] ?? null) ? $rules[$groupKey] : [] as $field) {
                if (!is_array($field)) {
                    continue;
                }
                $key = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
                $name = is_string($field['name'] ?? null) ? trim((string) $field['name']) : '';
                if ($key !== '' && $name !== '' && !isset($dataFieldNames[$key])) {
                    $dataFieldNames[$key] = $name;
                }
            }
        }
        foreach (is_array($rules['systemFields'] ?? null) ? $rules['systemFields'] : [] as $field) {
            if (!is_array($field)) {
                continue;
            }
            $key = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
            $name = is_string($field['name'] ?? null) ? trim((string) $field['name']) : '';
            if ($key !== '' && $name !== '' && !isset($systemFieldNames[$key])) {
                $systemFieldNames[$key] = $name;
            }
        }
    }

    return [
        'dataField' => $dataFieldNames,
        'systemField' => $systemFieldNames,
    ];
}

function archiving_rules_filename_template_changes(array $activeRules, array $draftRules): array
{
    $activeFolders = normalize_archive_structure(is_array($activeRules['archiveFolders'] ?? null) ? $activeRules['archiveFolders'] : []);
    $draftFolders = normalize_archive_structure(is_array($draftRules['archiveFolders'] ?? null) ? $draftRules['archiveFolders'] : []);
    $nameMaps = filename_template_review_name_maps($activeRules, $draftRules);
    $activeByKey = [];
    $draftByKey = [];
    $orderedKeys = [];

    foreach ($activeFolders as $index => $archiveFolder) {
        if (!is_array($archiveFolder)) {
            continue;
        }
        $folderId = is_string($archiveFolder['id'] ?? null) ? trim((string) $archiveFolder['id']) : '';
        $key = $folderId !== '' ? $folderId : ('#' . $index);
        $activeByKey[$key] = $archiveFolder;
        $orderedKeys[] = $key;
    }

    foreach ($draftFolders as $index => $archiveFolder) {
        if (!is_array($archiveFolder)) {
            continue;
        }
        $folderId = is_string($archiveFolder['id'] ?? null) ? trim((string) $archiveFolder['id']) : '';
        $key = $folderId !== '' ? $folderId : ('#' . $index);
        $draftByKey[$key] = $archiveFolder;
        if (!in_array($key, $orderedKeys, true)) {
            $orderedKeys[] = $key;
        }
    }

    $changes = [];
    foreach ($orderedKeys as $key) {
        $activeFolder = is_array($activeByKey[$key] ?? null) ? $activeByKey[$key] : [];
        $draftFolder = is_array($draftByKey[$key] ?? null) ? $draftByKey[$key] : [];
        $before = normalize_filename_template($activeFolder['filenameTemplate'] ?? null);
        $after = normalize_filename_template($draftFolder['filenameTemplate'] ?? null);
        if (json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
            continue;
        }

        $folderName = '';
        foreach ([$draftFolder, $activeFolder] as $folder) {
            if (!is_array($folder)) {
                continue;
            }
            $candidateName = is_string($folder['name'] ?? null) ? trim((string) $folder['name']) : '';
            $candidatePath = is_string($folder['path'] ?? null) ? trim((string) $folder['path']) : '';
            $candidateId = is_string($folder['id'] ?? null) ? trim((string) $folder['id']) : '';
            $folderName = $candidateName !== '' ? $candidateName : ($candidatePath !== '' ? $candidatePath : $candidateId);
            if ($folderName !== '') {
                break;
            }
        }

        $changes[] = [
            'archiveFolderId' => is_string($draftFolder['id'] ?? $activeFolder['id'] ?? null) ? trim((string) ($draftFolder['id'] ?? $activeFolder['id'])) : '',
            'archiveFolderName' => $folderName,
            'before' => filename_template_review_label($before, $nameMaps),
            'after' => filename_template_review_label($after, $nameMaps),
        ];
    }

    return $changes;
}

function draft_archiving_review_response_from_session(
    array $activeRules,
    array $draftRules,
    int $activeVersion,
    array $session,
    bool $hasUnpublishedChanges,
    bool $hasReviewRelevantChanges
): array {
    $changedSections = archiving_rules_changed_sections($activeRules, $draftRules);
    $templateChanges = archiving_rules_filename_template_changes($activeRules, $draftRules);
    if (!$hasUnpublishedChanges) {
        $payload = [
            'activeArchivingRulesVersion' => $activeVersion,
            'hasUnpublishedChanges' => false,
            'hasReviewRelevantChanges' => false,
            'changedSections' => [],
            'templateChanges' => [],
            'summary' => empty_archiving_review_summary(),
            'jobs' => [],
            'session' => [
                'status' => 'idle',
                'analyzedCount' => 0,
                'totalCount' => 0,
                'foundCount' => 0,
                'remainingCount' => 0,
            ],
        ];
        $payload['signature'] = archiving_rules_state_payload_hash($payload);
        return $payload;
    }

    if (!$hasReviewRelevantChanges) {
        $payload = [
            'activeArchivingRulesVersion' => $activeVersion,
            'hasUnpublishedChanges' => true,
            'hasReviewRelevantChanges' => false,
            'changedSections' => $changedSections,
            'templateChanges' => $templateChanges,
            'summary' => empty_archiving_review_summary(),
            'jobs' => [],
            'session' => [
                'status' => 'idle',
                'analyzedCount' => 0,
                'totalCount' => 0,
                'foundCount' => 0,
                'remainingCount' => 0,
            ],
        ];
        $payload['signature'] = archiving_rules_state_payload_hash($payload);
        return $payload;
    }

    $processedJobs = is_array($session['processedJobs'] ?? null) ? $session['processedJobs'] : [];
    $changedItems = [];
    foreach ($processedJobs as $item) {
        if (!is_array($item)) {
            continue;
        }
        $type = is_string($item['classification']['type'] ?? null) ? (string) $item['classification']['type'] : 'unchanged';
        if ($type !== 'unchanged') {
            $changedItems[] = $item;
        }
    }

    $payload = [
        'activeArchivingRulesVersion' => $activeVersion,
        'hasUnpublishedChanges' => true,
        'hasReviewRelevantChanges' => true,
        'changedSections' => $changedSections,
        'templateChanges' => $templateChanges,
        'summary' => is_array($session['summary'] ?? null) ? $session['summary'] : empty_archiving_review_summary(),
        'jobs' => sort_archiving_review_items($changedItems),
        'session' => [
            'status' => is_string($session['status'] ?? null) ? (string) $session['status'] : 'idle',
            'analyzedCount' => (int) ($session['analyzedCount'] ?? 0),
            'totalCount' => (int) ($session['totalCount'] ?? 0),
            'foundCount' => count($changedItems),
            'remainingCount' => max(
                0,
                ((int) ($session['totalCount'] ?? 0))
                - ((int) ($session['analyzedCount'] ?? 0))
                + count(is_array($session['pendingJobIds'] ?? null) ? $session['pendingJobIds'] : [])
            ),
        ],
    ];
    $payload['signature'] = archiving_rules_state_payload_hash($payload);

    return $payload;
}

function build_archiving_rules_state_payload(array $config, ?int $needsRuleReviewCount = null): array
{
    $rulesState = load_archiving_rules_state();
    $reviewState = load_archiving_rules_review_state();
    $activeRules = normalize_archiving_rules_set($rulesState['activeArchivingRules'] ?? []);
    $draftRules = normalize_archiving_rules_set($rulesState['draftArchivingRules'] ?? []);
    $activeVersion = (int) ($rulesState['activeArchivingRulesVersion'] ?? 1);
    $hasUnpublishedChanges = archiving_rules_have_unpublished_changes($activeRules, $draftRules);
    $hasReviewRelevantChanges = archiving_rules_have_review_relevant_changes($activeRules, $draftRules);
    $draftSession = is_array($reviewState['draftSession'] ?? null) ? $reviewState['draftSession'] : empty_draft_archiving_review_session();
    $publishedSession = is_array($reviewState['publishedSession'] ?? null) ? $reviewState['publishedSession'] : empty_published_archiving_review_session();
    $draftReview = draft_archiving_review_response_from_session(
        $activeRules,
        $draftRules,
        $activeVersion,
        $draftSession,
        $hasUnpublishedChanges,
        $hasReviewRelevantChanges
    );

    return [
        'activeVersion' => $activeVersion,
        'hasUnpublishedChanges' => $hasUnpublishedChanges,
        'hasReviewRelevantChanges' => $hasReviewRelevantChanges,
        'needsRuleReviewCount' => $needsRuleReviewCount ?? count(is_array($publishedSession['affectedJobIds'] ?? null) ? $publishedSession['affectedJobIds'] : []),
        'publishedReview' => [
            'status' => is_string($publishedSession['status'] ?? null) ? (string) $publishedSession['status'] : 'idle',
            'analyzedCount' => (int) ($publishedSession['analyzedCount'] ?? 0),
            'totalCount' => (int) ($publishedSession['totalCount'] ?? 0),
        ],
        'draftReview' => $draftReview,
        'signature' => archiving_rules_state_payload_hash([
            'activeVersion' => $activeVersion,
            'hasUnpublishedChanges' => $hasUnpublishedChanges,
            'hasReviewRelevantChanges' => $hasReviewRelevantChanges,
            'needsRuleReviewCount' => $needsRuleReviewCount ?? count(is_array($publishedSession['affectedJobIds'] ?? null) ? $publishedSession['affectedJobIds'] : []),
            'publishedReview' => [
                'status' => is_string($publishedSession['status'] ?? null) ? (string) $publishedSession['status'] : 'idle',
                'analyzedCount' => (int) ($publishedSession['analyzedCount'] ?? 0),
                'totalCount' => (int) ($publishedSession['totalCount'] ?? 0),
            ],
            'draftReview' => $draftReview,
        ]),
    ];
}

function archiving_rules_state_payload_hash(array $payload): string
{
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return sha1(is_string($encoded) ? $encoded : '');
}

function maybe_queue_archiving_rules_update_event(array $config, ?array $payload = null): bool
{
    $resolvedPayload = is_array($payload) ? $payload : build_archiving_rules_state_payload($config);
    $payloadHash = archiving_rules_state_payload_hash($resolvedPayload);

    $shouldQueue = with_archiving_rules_review_lock(static function () use ($payloadHash): bool {
        $state = load_archiving_rules_review_state();
        $lastHash = is_string($state['lastStateEventHash'] ?? null) ? (string) $state['lastStateEventHash'] : '';
        if ($lastHash === $payloadHash) {
            return false;
        }
        $state['lastStateEventHash'] = $payloadHash;
        save_archiving_rules_review_state($state);
        return true;
    });

    if (!$shouldQueue) {
        return false;
    }

    append_job_event([
        'type' => 'archivingRules.update',
        'archivingRules' => $resolvedPayload,
    ]);

    return true;
}

function normalize_archiving_snapshot(mixed $input): ?array
{
    if (!is_array($input)) {
        return null;
    }

    $version = filter_var($input['approvedWithRulesVersion'] ?? null, FILTER_VALIDATE_INT);
    if ($version === false || $version === null || $version < 1) {
        $version = 1;
    }

    return [
        'approvedWithRulesVersion' => (int) $version,
        'autoDetectedAtApproval' => normalize_auto_archiving_result(
            is_array($input['autoDetectedAtApproval'] ?? null) ? $input['autoDetectedAtApproval'] : []
        ),
        'userApproved' => normalize_auto_archiving_result(
            is_array($input['userApproved'] ?? null) ? $input['userApproved'] : []
        ),
    ];
}

function set_job_archiving_snapshot(array &$job, int $rulesVersion, array $autoDetectedAtApproval, array $userApproved): void
{
    $normalizedAutoDetected = normalize_auto_archiving_result($autoDetectedAtApproval);
    $normalizedApproved = normalize_auto_archiving_result($userApproved);
    $jobId = is_string($job['id'] ?? null) ? trim((string) $job['id']) : '';
    sync_job_sender_snapshot_ids(
        $jobId,
        isset($normalizedApproved['senderId']) ? (int) $normalizedApproved['senderId'] : null,
        isset($normalizedAutoDetected['senderId']) ? (int) $normalizedAutoDetected['senderId'] : null
    );

    $snapshot = normalize_archiving_snapshot([
        'approvedWithRulesVersion' => max(1, $rulesVersion),
        'autoDetectedAtApproval' => $normalizedAutoDetected,
        'userApproved' => $normalizedApproved,
    ]);
    if (is_array($snapshot['autoDetectedAtApproval'] ?? null)) {
        unset($snapshot['autoDetectedAtApproval']['senderId']);
    }
    if (is_array($snapshot['userApproved'] ?? null)) {
        unset($snapshot['userApproved']['senderId']);
    }
    $job['archiveSnapshot'] = $snapshot;
    $job['approvedArchiving'] = $normalizedApproved;
}

function job_archiving_snapshot(array $job): ?array
{
    $snapshot = normalize_archiving_snapshot($job['archiveSnapshot'] ?? null);
    if (!is_array($snapshot)) {
        return null;
    }

    if (is_array($snapshot['autoDetectedAtApproval'] ?? null)) {
        $snapshot['autoDetectedAtApproval']['senderId'] = null;
    }
    if (is_array($snapshot['userApproved'] ?? null)) {
        $snapshot['userApproved']['senderId'] = null;
    }

    $jobId = is_string($job['id'] ?? null) ? trim((string) $job['id']) : '';
    $storedSenderIds = job_sender_snapshot_ids($jobId);
    if (is_array($storedSenderIds)) {
        if (is_array($snapshot['autoDetectedAtApproval'] ?? null)) {
            $snapshot['autoDetectedAtApproval']['senderId'] = $storedSenderIds['autoSenderId'] ?? null;
        }
        if (is_array($snapshot['userApproved'] ?? null)) {
            $snapshot['userApproved']['senderId'] = $storedSenderIds['senderId'] ?? null;
        }
    }
    return $snapshot;
}

function archived_job_active_result(array $config, string $jobId, array $job, array $activeRules, int $activeVersion): array
{
    $snapshot = job_archiving_snapshot($job);
    if (is_array($snapshot) && (int) ($snapshot['approvedWithRulesVersion'] ?? 0) === $activeVersion) {
        return normalize_auto_archiving_result(
            is_array($snapshot['autoDetectedAtApproval'] ?? null) ? $snapshot['autoDetectedAtApproval'] : []
        );
    }

    $active = calculate_auto_archiving_result_for_job($config, $jobId, $activeRules, $job);
    return normalize_auto_archiving_result(
        is_array($active['autoArchivingResult'] ?? null) ? $active['autoArchivingResult'] : []
    );
}

function normalize_archive_categories(mixed $input, array &$usedCategoryIds = []): array
{
    if (!is_array($input)) {
        return [];
    }

    $categories = [];
    foreach ($input as $row) {
        if (!is_array($row)) {
            continue;
        }
        $categories[] = normalize_archive_category($row, $usedCategoryIds);
    }

    return $categories;
}

function normalize_archive_category(array $input, array &$usedCategoryIds = []): array
{
    $name = is_string($input['name'] ?? null) ? trim((string) $input['name']) : '';
    $id = slugify_text($name, '-', '');
    if ($id === '') {
        throw new RuntimeException('Kategori saknar giltigt namn');
    }
    if (isset($usedCategoryIds[$id])) {
        throw new RuntimeException('Kategori-id krockar: ' . $id);
    }
    $usedCategoryIds[$id] = true;

    $rulesIn = $input['rules'] ?? [];
    $rules = [];
    if (is_array($rulesIn)) {
        foreach ($rulesIn as $ruleIn) {
            $rules[] = normalize_category_rule($ruleIn);
        }
    }

    if (count($rules) === 0) {
        $rules[] = normalize_category_rule([]);
    }

    return [
        'id' => $id,
        'name' => $name,
        'minScore' => positive_int($input['minScore'] ?? 1, 1),
        'rules' => $rules,
    ];
}

function load_matching_settings_payload(): array
{
    $defaultPayload = [
        'replacements' => [],
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
    ];
}

function load_matching_settings(): array
{
    $payload = load_matching_settings_payload();
    $rows = $payload['replacements'] ?? [];
    return is_array($rows) ? $rows : [];
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

function sanitize_pdf_filename(string $value): string
{
    $basename = trim(str_replace(["\0", '/', '\\'], ' ', $value));
    $basename = preg_replace('/\s+/u', ' ', $basename);
    if (!is_string($basename)) {
        $basename = '';
    }

    $basename = trim($basename, " \t\n\r\0\x0B.");
    if ($basename === '') {
        $basename = 'dokument';
    }

    if (@preg_match('/\.pdf$/iu', $basename) !== 1) {
        $basename .= '.pdf';
    }

    return $basename;
}

function job_review_pdf_path(array $config, string $jobId, ?array $job = null): ?string
{
    if (!is_valid_job_id($jobId)) {
        return null;
    }

    $jobDir = $config['jobsDirectory'] . DIRECTORY_SEPARATOR . $jobId;
    $reviewPath = $jobDir . DIRECTORY_SEPARATOR . 'review.pdf';
    if (is_file($reviewPath)) {
        return $reviewPath;
    }

    if (!is_array($job)) {
        $job = load_json_file($jobDir . '/job.json');
    }

    $archivedPdfPath = is_array($job) && is_string($job['archivedPdfPath'] ?? null)
        ? trim((string) $job['archivedPdfPath'])
        : '';
    if ($archivedPdfPath !== '' && is_file($archivedPdfPath)) {
        return $archivedPdfPath;
    }

    return null;
}

function find_loaded_category_by_id(array $categories, string $categoryId): ?array
{
    foreach ($categories as $category) {
        if (!is_array($category)) {
            continue;
        }
        $id = is_string($category['id'] ?? null) ? trim((string) $category['id']) : '';
        if ($id === $categoryId) {
            return $category;
        }
    }

    return null;
}

function sender_exists_by_id(array $senders, int $senderId): bool
{
    foreach ($senders as $sender) {
        if (!is_array($sender)) {
            continue;
        }
        if ((int) ($sender['id'] ?? 0) === $senderId) {
            return true;
        }
    }

    return false;
}

function update_job_user_fields(array $config, string $jobId, array $payload): array
{
    if (!is_valid_job_id($jobId)) {
        throw new RuntimeException('Invalid job id');
    }

    $jobDir = $config['jobsDirectory'] . DIRECTORY_SEPARATOR . $jobId;
    $jobPath = $jobDir . DIRECTORY_SEPARATOR . 'job.json';
    $job = load_json_file($jobPath);
    if (!is_array($job)) {
        throw new RuntimeException('Job not found');
    }

    $clients = load_clients();
    $senders = load_senders();
    $categories = load_categories();

    if (array_key_exists('selectedClientDirName', $payload)) {
        $value = $payload['selectedClientDirName'];
        if ($value === null || $value === '') {
            unset($job['selectedClientDirName']);
        } else {
            $clientDirName = is_string($value) ? trim($value) : '';
            $clientExists = false;
            foreach ($clients as $client) {
                if (!is_array($client)) {
                    continue;
                }
                if ($clientDirName !== '' && $clientDirName === (string) ($client['dirName'] ?? '')) {
                    $clientExists = true;
                    break;
                }
            }
            if (!$clientExists) {
                throw new RuntimeException('Ogiltig huvudman');
            }
            $job['selectedClientDirName'] = $clientDirName;
        }
    }

    if (array_key_exists('selectedSenderId', $payload)) {
        $value = $payload['selectedSenderId'];
        if ($value === null || $value === '') {
            unset($job['selectedSenderId']);
        } else {
            $senderId = (int) $value;
            if ($senderId < 1 || !sender_exists_by_id($senders, $senderId)) {
                throw new RuntimeException('Ogiltig avsändare');
            }
            $job['selectedSenderId'] = $senderId;
        }
    }

    if (array_key_exists('selectedCategoryId', $payload)) {
        $value = $payload['selectedCategoryId'];
        if ($value === null || $value === '') {
            unset($job['selectedCategoryId']);
        } else {
            $categoryId = is_string($value) ? trim($value) : '';
            $category = find_loaded_category_by_id($categories, $categoryId);
            if ($categoryId === '' || !is_array($category)) {
                throw new RuntimeException('Ogiltig kategori');
            }
            $job['selectedCategoryId'] = $categoryId;
        }
    }

    if (array_key_exists('filename', $payload)) {
        $value = $payload['filename'];
        if ($value === null || trim((string) $value) === '') {
            unset($job['filename']);
        } else {
            $job['filename'] = sanitize_pdf_filename((string) $value);
        }
    }

    $job['updatedAt'] = now_iso();
    write_json_file($jobPath, $job);
    if (($job['archived'] ?? false) === true && (
        array_key_exists('selectedClientDirName', $payload)
        || array_key_exists('selectedSenderId', $payload)
        || array_key_exists('selectedCategoryId', $payload)
        || array_key_exists('filename', $payload)
    )) {
        invalidate_archiving_review_job($config, $jobId, true);
    }
    queue_job_upsert_event($config, $jobId);
    maybe_queue_archiving_rules_update_event($config);
    return $job;
}

function archive_job_by_id(array $config, string $jobId, bool $restore = false, array $payload = []): array
{
    if (!is_valid_job_id($jobId)) {
        throw new RuntimeException('Invalid job id');
    }

    $jobDir = $config['jobsDirectory'] . DIRECTORY_SEPARATOR . $jobId;
    $jobPath = $jobDir . DIRECTORY_SEPARATOR . 'job.json';
    $job = load_json_file($jobPath);
    if (!is_array($job)) {
        throw new RuntimeException('Job not found');
    }

    if (($job['status'] ?? '') !== 'ready') {
        throw new RuntimeException('Bara klara jobb kan arkiveras eller återställas');
    }

    if ($restore) {
        $archivedPdfPath = is_string($job['archivedPdfPath'] ?? null) ? trim((string) $job['archivedPdfPath']) : '';
        if ($archivedPdfPath === '' || !is_file($archivedPdfPath)) {
            throw new RuntimeException('Arkiverad PDF saknas');
        }
        $reviewPath = $jobDir . DIRECTORY_SEPARATOR . 'review.pdf';
        if (is_file($reviewPath)) {
            throw new RuntimeException('review.pdf finns redan i jobbet');
        }
        if (!rename($archivedPdfPath, $reviewPath)) {
            throw new RuntimeException('Kunde inte återställa review.pdf');
        }

        $job['archived'] = false;
        $job['needsRuleReview'] = false;
        $job['updatedAt'] = now_iso();
        unset($job['archivedAt'], $job['archivedPdfPath']);
        write_json_file($jobPath, $job);
        invalidate_archiving_review_job($config, $jobId, false);
        queue_job_upsert_event($config, $jobId);
        maybe_queue_archiving_rules_update_event($config);
        return $job;
    }

    $outputBaseDirectory = trim((string) ($config['outputBaseDirectory'] ?? ''));
    if ($outputBaseDirectory === '' || !is_dir($outputBaseDirectory)) {
        throw new RuntimeException('Bas-sökväg för utdata är inte konfigurerad');
    }

    $reviewPath = $jobDir . DIRECTORY_SEPARATOR . 'review.pdf';
    if (!is_file($reviewPath)) {
        throw new RuntimeException('review.pdf saknas');
    }

    $clients = load_clients();
    $senders = load_senders();
    $categories = load_categories();

    $selectedClientDirName = array_key_exists('selectedClientDirName', $payload)
        ? (is_string($payload['selectedClientDirName'] ?? null) ? trim((string) $payload['selectedClientDirName']) : '')
        : (is_string($job['selectedClientDirName'] ?? null) ? trim((string) $job['selectedClientDirName']) : '');
    $selectedSenderId = array_key_exists('selectedSenderId', $payload)
        ? (int) ($payload['selectedSenderId'] ?? 0)
        : (int) ($job['selectedSenderId'] ?? 0);
    $selectedCategoryId = array_key_exists('selectedCategoryId', $payload)
        ? (is_string($payload['selectedCategoryId'] ?? null) ? trim((string) $payload['selectedCategoryId']) : '')
        : (is_string($job['selectedCategoryId'] ?? null) ? trim((string) $job['selectedCategoryId']) : '');
    $filenameInput = array_key_exists('filename', $payload)
        ? (is_string($payload['filename'] ?? null) ? (string) $payload['filename'] : '')
        : (is_string($job['filename'] ?? null) ? (string) $job['filename'] : '');

    $clientExists = false;
    foreach ($clients as $client) {
        if (!is_array($client)) {
            continue;
        }
        if ($selectedClientDirName !== '' && $selectedClientDirName === (string) ($client['dirName'] ?? '')) {
            $clientExists = true;
            break;
        }
    }
    if (!$clientExists) {
        throw new RuntimeException('Ogiltig huvudman');
    }
    if ($selectedSenderId < 1 || !sender_exists_by_id($senders, $selectedSenderId)) {
        throw new RuntimeException('Ogiltig avsändare');
    }

    $category = find_loaded_category_by_id($categories, $selectedCategoryId);
    if (!is_array($category)) {
        throw new RuntimeException('Ogiltig kategori');
    }

    $activeRules = load_active_archiving_rules();
    $activeVersion = active_archiving_rules_version();
    $autoAnalysis = calculate_auto_archiving_result_for_job($config, $jobId, $activeRules, $job);
    $autoDetectedAtApproval = normalize_auto_archiving_result(
        is_array($autoAnalysis['autoArchivingResult'] ?? null) ? $autoAnalysis['autoArchivingResult'] : []
    );
    $userApprovedAtApproval = approved_archiving_from_archive_request($job, $autoDetectedAtApproval, $payload, $categories);

    $filename = sanitize_pdf_filename(
        $filenameInput !== ''
            ? $filenameInput
            : (is_string($userApprovedAtApproval['filename'] ?? null) ? (string) $userApprovedAtApproval['filename'] : (string) ($job['originalFilename'] ?? 'dokument.pdf'))
    );
    $categoryPath = is_string($category['path'] ?? null) ? trim((string) $category['path']) : '';
    $targetDirectory = rtrim($outputBaseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $selectedClientDirName;
    if ($categoryPath !== '') {
        $targetDirectory .= DIRECTORY_SEPARATOR . $categoryPath;
    }
    ensure_directory($targetDirectory);

    $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;
    if (is_file($targetPath)) {
        throw new RuntimeException('Det finns redan en fil med det filnamnet i mål-mappen');
    }

    if (!rename($reviewPath, $targetPath)) {
        throw new RuntimeException('Kunde inte flytta review.pdf till arkivet');
    }

    $job['selectedClientDirName'] = $selectedClientDirName;
    $job['selectedSenderId'] = $selectedSenderId;
    $job['selectedCategoryId'] = $selectedCategoryId;
    $job['filename'] = $filename;
    $approvedSnapshotValue = normalize_auto_archiving_result(array_merge($userApprovedAtApproval, [
        'filename' => $filename,
        'principalId' => $selectedClientDirName,
        'senderId' => $selectedSenderId,
        'categoryId' => $selectedCategoryId,
        'archiveFolderId' => is_string($category['archiveFolderId'] ?? null) ? trim((string) $category['archiveFolderId']) : null,
        'archiveFolderPath' => $categoryPath,
    ]));
    set_job_archiving_snapshot($job, $activeVersion, $autoDetectedAtApproval, $approvedSnapshotValue);
    $job['archived'] = true;
    $job['needsRuleReview'] = false;
    $job['archivedAt'] = now_iso();
    $job['archivedPdfPath'] = $targetPath;
    $job['updatedAt'] = now_iso();
    write_json_file($jobPath, $job);
    invalidate_archiving_review_job($config, $jobId, true);
    queue_job_upsert_event($config, $jobId);
    maybe_queue_archiving_rules_update_event($config);

    return $job;
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

function rasterize_pdf_page_to_png_bytes(string $pdfPath, int $pageNumber, int $dpi = 150): ?string
{
    $binary = pdftoppm_path();
    if ($binary === null || !is_file($pdfPath) || $pageNumber < 1) {
        return null;
    }

    $safeDpi = max(72, min(300, $dpi));
    $tempDir = sys_get_temp_dir() . '/docflow_page_' . bin2hex(random_bytes(8));
    if (!mkdir($tempDir, 0700) && !is_dir($tempDir)) {
        return null;
    }

    try {
        $outputPrefix = $tempDir . '/page';
        $command = escapeshellarg($binary)
            . ' -r ' . $safeDpi
            . ' -png -singlefile'
            . ' -f ' . $pageNumber
            . ' -l ' . $pageNumber
            . ' '
            . escapeshellarg($pdfPath)
            . ' '
            . escapeshellarg($outputPrefix)
            . ' 2>/dev/null';
        exec($command, $output, $code);
        if ($code !== 0) {
            return null;
        }

        $imagePath = $outputPrefix . '.png';
        if (!is_file($imagePath)) {
            return null;
        }

        $bytes = file_get_contents($imagePath);
        return is_string($bytes) ? $bytes : null;
    } finally {
        delete_directory_recursive($tempDir);
    }
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
        'installScope' => $installed ? 'global' : null,
        'isInstalling' => false,
        'installCommand' => 'sudo apt install jbig2',
        'binary' => $installed ? 'jbig2' : null,
        'localInstallSupported' => false,
        'localInstallReason' => 'Lokal installation stöds inte ännu för JBIG2',
    ];
}

function python_command_path(): ?string
{
    static $cached = null;
    static $loaded = false;

    if ($loaded) {
        return $cached;
    }

    $loaded = true;
    $python3 = trim((string) shell_exec('command -v python3 2>/dev/null'));
    if ($python3 !== '') {
        $cached = $python3;
        return $cached;
    }

    $python = trim((string) shell_exec('command -v python 2>/dev/null'));
    $cached = $python !== '' ? $python : null;
    return $cached;
}

function pdf_docflow_ocr_version(string $pdfPath): ?int
{
    $python = python_command_path();
    $scriptPath = docflow_pdf_metadata_script_path();
    if ($python === null || $scriptPath === null || !is_file($pdfPath)) {
        return null;
    }

    $command = escapeshellarg($python)
        . ' '
        . escapeshellarg($scriptPath)
        . ' read '
        . escapeshellarg($pdfPath)
        . ' '
        . escapeshellarg(DOCFLOW_OCR_METADATA_KEY)
        . ' 2>/dev/null';
    $output = shell_exec($command);
    if (!is_string($output) || trim($output) === '') {
        return null;
    }

    $payload = json_decode($output, true);
    if (!is_array($payload)) {
        return null;
    }

    $value = $payload['value'] ?? null;
    if (!is_string($value) && !is_int($value) && !is_float($value)) {
        return null;
    }

    $parsed = filter_var($value, FILTER_VALIDATE_INT);
    if ($parsed === false || (int) $parsed <= 0) {
        return null;
    }

    return (int) $parsed;
}

function write_pdf_docflow_ocr_version(string $pdfPath, ?int $version): bool
{
    $python = python_command_path();
    $scriptPath = docflow_pdf_metadata_script_path();
    if ($python === null || $scriptPath === null || !is_file($pdfPath)) {
        return false;
    }

    $command = escapeshellarg($python)
        . ' '
        . escapeshellarg($scriptPath)
        . ' ';
    if (is_int($version) && $version > 0) {
        $command .= 'write '
            . escapeshellarg($pdfPath)
            . ' '
            . escapeshellarg(DOCFLOW_OCR_METADATA_KEY)
            . ' '
            . escapeshellarg((string) $version);
    } else {
        $command .= 'remove '
            . escapeshellarg($pdfPath)
            . ' '
            . escapeshellarg(DOCFLOW_OCR_METADATA_KEY);
    }
    $command .= ' 2>&1';

    $output = [];
    $exitCode = 0;
    exec($command, $output, $exitCode);
    return $exitCode === 0;
}

function job_docflow_ocr_version(array $job): ?int
{
    $value = $job[DOCFLOW_OCR_METADATA_KEY] ?? null;
    $parsed = filter_var($value, FILTER_VALIDATE_INT);
    if ($parsed === false || (int) $parsed <= 0) {
        return null;
    }
    return (int) $parsed;
}

function python_status_payload(): array
{
    $python = python_command_path();

    return [
        'installed' => $python !== null,
        'installScope' => $python !== null ? 'global' : null,
        'isInstalling' => false,
        'installCommand' => 'sudo apt install python3 python3-pip python3-venv',
        'binary' => $python,
        'localInstallSupported' => false,
        'localInstallReason' => 'Lokal installation stöds inte ännu för Python 3',
    ];
}

function python_can_create_venv(?string $python = null): bool
{
    $binary = $python !== null ? trim($python) : (python_command_path() ?? '');
    if ($binary === '') {
        return false;
    }

    $command = escapeshellarg($binary)
        . ' -c '
        . escapeshellarg('import venv')
        . ' 2>/dev/null';
    exec($command, $output, $exitCode);
    return $exitCode === 0;
}

function rapidocr_local_venv_dir(): string
{
    return PROJECT_ROOT . '.docflow-tools/rapidocr-venv';
}

function rapidocr_local_python_path(): string
{
    return rapidocr_local_venv_dir() . '/bin/python';
}

function rapidocr_install_status_path(): string
{
    return DATA_DIR . '/rapidocr-install-status.json';
}

function rapidocr_install_lock_path(): string
{
    return DATA_DIR . '/rapidocr-install.lock';
}

function rapidocr_install_log_path(): string
{
    return DATA_DIR . '/rapidocr-install.log';
}

function rapidocr_install_pid_path(): string
{
    return DATA_DIR . '/rapidocr-install.pid';
}

function rapidocr_detect_module(?string $python): ?string
{
    $binary = is_string($python) ? trim($python) : '';
    if ($binary === '' || !is_file($binary)) {
        return null;
    }

    $checkRapidocr = escapeshellarg($binary)
        . ' -c '
        . escapeshellarg('import importlib.util,sys; sys.exit(0 if importlib.util.find_spec("rapidocr") else 1)')
        . ' 2>/dev/null';
    exec($checkRapidocr, $output, $exitCode);
    if ($exitCode === 0) {
        return 'rapidocr';
    }

    $checkLegacy = escapeshellarg($binary)
        . ' -c '
        . escapeshellarg('import importlib.util,sys; sys.exit(0 if importlib.util.find_spec("rapidocr_onnxruntime") else 1)')
        . ' 2>/dev/null';
    exec($checkLegacy, $legacyOutput, $legacyExitCode);
    if ($legacyExitCode === 0) {
        return 'rapidocr_onnxruntime';
    }

    return null;
}

function rapidocr_install_runtime_status(): array
{
    $status = load_json_file(rapidocr_install_status_path()) ?? [];
    $state = is_string($status['state'] ?? null) ? trim((string) $status['state']) : '';
    $message = is_string($status['message'] ?? null) ? trim((string) $status['message']) : '';
    $startedAt = is_string($status['startedAt'] ?? null) ? trim((string) $status['startedAt']) : '';
    $updatedAt = is_string($status['updatedAt'] ?? null) ? trim((string) $status['updatedAt']) : '';
    $finishedAt = is_string($status['finishedAt'] ?? null) ? trim((string) $status['finishedAt']) : '';

    $lockHandle = @fopen(rapidocr_install_lock_path(), 'c+');
    $isInstalling = false;
    if ($lockHandle !== false) {
        $hasLock = @flock($lockHandle, LOCK_EX | LOCK_NB);
        if ($hasLock) {
            @flock($lockHandle, LOCK_UN);
        } else {
            $isInstalling = true;
        }
        @fclose($lockHandle);
    }

    if ($isInstalling) {
        $state = 'installing';
    } elseif ($state === 'installing') {
        $state = '';
    }

    return [
        'state' => $state,
        'isInstalling' => $isInstalling,
        'message' => $message,
        'startedAt' => $startedAt,
        'updatedAt' => $updatedAt,
        'finishedAt' => $finishedAt,
        'hasLog' => is_file(rapidocr_install_log_path()) && filesize(rapidocr_install_log_path()) > 0,
    ];
}

function rapidocr_status_payload(): array
{
    $globalPython = python_command_path();
    $localPython = rapidocr_local_python_path();
    $localModule = rapidocr_detect_module($localPython);
    $globalModule = $localModule === null ? rapidocr_detect_module($globalPython) : null;
    $installStatus = rapidocr_install_runtime_status();
    $venvSupported = python_can_create_venv($globalPython);

    $installScope = null;
    $activePython = null;
    $module = null;
    if ($localModule !== null) {
        $installScope = 'local';
        $activePython = $localPython;
        $module = $localModule;
    } elseif ($globalModule !== null) {
        $installScope = 'global';
        $activePython = $globalPython;
        $module = $globalModule;
    }

    return [
        'installed' => $installScope !== null,
        'installScope' => $installScope,
        'isInstalling' => $installStatus['isInstalling'],
        'installState' => $installStatus['state'],
        'installStatusMessage' => $installStatus['message'],
        'hasInstallLog' => $installStatus['hasLog'],
        'installCommand' => 'python3 -m pip install --break-system-packages rapidocr onnxruntime',
        'python' => $activePython,
        'module' => $module,
        'globalPython' => $globalPython,
        'localPython' => $localModule !== null ? $localPython : null,
        'localInstallSupported' => $globalPython !== null && $venvSupported,
        'localInstallReason' => $globalPython === null
            ? 'Kräver Python 3'
            : (!$venvSupported ? 'Kräver python3-venv' : ''),
    ];
}

function rapidocr_runtime_python_path(): ?string
{
    $status = rapidocr_status_payload();
    $python = $status['python'] ?? null;
    return is_string($python) && trim($python) !== '' ? trim($python) : null;
}

function docflow_ocrmypdf_plugin_path(): ?string
{
    $path = PROJECT_ROOT . '/docflow-ocrmypdf-plugin/docflow_ocrmypdf_plugin.py';
    return is_file($path) ? $path : null;
}

function docflow_pdf_metadata_script_path(): ?string
{
    $path = PROJECT_ROOT . '/docflow-ocrmypdf-plugin/docflow_pdf_metadata.py';
    return is_file($path) ? $path : null;
}

function docflow_ocr_transform_runtime_script_path(): ?string
{
    $path = PROJECT_ROOT . '/docflow-ocrmypdf-plugin/docflow_transform_runtime.py';
    return is_file($path) ? $path : null;
}

function docflow_generated_transform_config_path(): string
{
    return DATA_DIR . '/docflow_ocr_transform_config.json';
}

function docflow_legacy_generated_transform_script_path(): string
{
    return DATA_DIR . '/docflow_ocr_pdf_transform.py';
}

function write_docflow_ocr_transform_config(array $substitutions): ?string
{
    $normalized = sanitize_ocr_pdf_text_substitutions($substitutions);
    $legacyScriptPath = docflow_legacy_generated_transform_script_path();
    if (is_file($legacyScriptPath)) {
        @unlink($legacyScriptPath);
    }

    if ($normalized === []) {
        $path = docflow_generated_transform_config_path();
        if (is_file($path)) {
            @unlink($path);
        }
        return null;
    }

    $configPath = docflow_generated_transform_config_path();
    $payload = json_encode(
        ['substitutions' => $normalized],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if (!is_string($payload)) {
        return null;
    }

    if (file_put_contents($configPath, $payload) === false) {
        return null;
    }

    return $configPath;
}

function run_ocrmypdf(
    string $inputPdfPath,
    string $outputPdfPath,
    string $sidecarTextPath,
    ?string $debugOutputDir,
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
    $pluginPath = docflow_ocrmypdf_plugin_path();
    if ($pluginPath !== null) {
        $pluginSegment = '--plugin ' . escapeshellarg($pluginPath) . ' ';
        if (is_string($debugOutputDir) && trim($debugOutputDir) !== '') {
            $pluginSegment .= '--docflow-debug-output-dir '
                . escapeshellarg(trim($debugOutputDir))
                . ' ';
        }
        $rapidocrPython = rapidocr_runtime_python_path();
        if ($rapidocrPython !== null) {
            $pluginSegment .= '--docflow-rapidocr-python '
                . escapeshellarg($rapidocrPython)
                . ' ';
        }
    }

    $normalizedSubstitutions = sanitize_ocr_pdf_text_substitutions($ocrPdfTextSubstitutions);
    if ($normalizedSubstitutions !== []) {
        $runtimeScriptPath = docflow_ocr_transform_runtime_script_path();
        $transformConfigPath = write_docflow_ocr_transform_config($normalizedSubstitutions);
        if (
            $pluginPath === null
            || $runtimeScriptPath === null
            || $transformConfigPath === null
            || !is_file($runtimeScriptPath)
            || !is_file($transformConfigPath)
        ) {
            $GLOBALS['docflow_last_ocrmypdf_error'] = 'Docflow OCR-transform plugin could not be prepared';
            return false;
        }

        $pluginSegment .= '--docflow-transform-script '
            . escapeshellarg($runtimeScriptPath)
            . ' --docflow-transform-config '
            . escapeshellarg($transformConfigPath)
            . ' ';
    }

    $modeFlag = $skipExistingText ? '--mode skip' : '--mode redo';
    $safeOptimizeLevel = $optimizeLevel < 0 || $optimizeLevel > 3 ? 1 : $optimizeLevel;
    $deskewFlag = $skipExistingText ? '--deskew ' : '';
    $command = escapeshellarg($binary)
        . ' '
        . $pluginSegment
        . ' -j 1'
        . ' -l swe '
        . $deskewFlag
        . '--tesseract-thresholding sauvola --tesseract-pagesegmode 6 --output-type pdf '
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

function write_combined_page_debug_text(
    string $jobDir,
    string $engine,
    string $targetFilename
): void
{
    $pagePaths = glob($jobDir . '/' . $engine . '_page_*.txt') ?: [];
    sort($pagePaths, SORT_NATURAL);

    if ($pagePaths === []) {
        $targetPath = $jobDir . '/' . $targetFilename;
        if (is_file($targetPath)) {
            @unlink($targetPath);
        }
        return;
    }

    $chunks = [];
    foreach ($pagePaths as $index => $path) {
        $text = file_get_contents($path);
        if (!is_string($text)) {
            continue;
        }
        $pageNumber = $index + 1;
        $chunks[] = '=== PAGE ' . $pageNumber . " ===\n" . rtrim($text, "\r\n");
    }

    $combined = implode("\n\n", $chunks);
    file_put_contents($jobDir . '/' . $targetFilename, $combined === '' ? '' : $combined . "\n");
}

function regenerate_debug_text_files_from_json(string $jobDir, string $engine): void
{
    $jsonPaths = glob($jobDir . '/' . $engine . '_page_*.json') ?: [];
    sort($jsonPaths, SORT_NATURAL);

    foreach ($jsonPaths as $jsonPath) {
        $payload = load_json_file($jsonPath);
        if (!is_array($payload)) {
            continue;
        }

        $text = render_grid_text_from_debug_payload($payload);
        $textPath = preg_replace('/\.json$/', '.txt', $jsonPath);
        if (!is_string($textPath) || $textPath === '') {
            continue;
        }
        file_put_contents($textPath, $text === '' ? '' : $text . "\n");
    }

    write_combined_page_debug_text($jobDir, $engine, $engine . '.txt');
}

function load_job_engine_debug_pages(string $jobDir, string $engine): array
{
    $jsonPaths = glob($jobDir . '/' . $engine . '_page_*.json') ?: [];
    sort($jsonPaths, SORT_NATURAL);

    $pages = [];
    foreach ($jsonPaths as $jsonPath) {
        $payload = load_json_file($jsonPath);
        if (!is_array($payload)) {
            continue;
        }
        $pages[] = $payload;
    }

    return $pages;
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

function pdf_has_extractable_text(string $pdfPath): bool
{
    $text = extract_text_from_pdf($pdfPath);
    return is_string($text) && trim($text) !== '';
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
            $words = is_array($line['words'] ?? null) ? $line['words'] : [];
            foreach ($words as $word) {
                $text = is_string($word['text'] ?? null) ? (string) $word['text'] : '';
                $charCount = utf8_strlen_safe($text);
                $width = ((float) ($word['x1'] ?? 0.0)) - ((float) ($word['x0'] ?? 0.0));
                $height = ((float) ($word['y1'] ?? 0.0)) - ((float) ($word['y0'] ?? 0.0));
                if ($charCount > 0 && $width > 0) {
                    $wordWidths[] = $width / $charCount;
                }
                if ($height > 0) {
                    $lineHeights[] = $height;
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

        $pageWords = [];
        foreach ($lines as $line) {
            foreach (is_array($line['words'] ?? null) ? $line['words'] : [] as $word) {
                if (!is_array($word)) {
                    continue;
                }
                $text = is_string($word['text'] ?? null) ? trim((string) $word['text']) : '';
                if ($text === '') {
                    continue;
                }
                $pageWords[] = [
                    'text' => $text,
                    'x0' => (float) ($word['x0'] ?? 0.0),
                    'y0' => (float) ($word['y0'] ?? 0.0),
                    'x1' => (float) ($word['x1'] ?? 0.0),
                    'y1' => (float) ($word['y1'] ?? 0.0),
                ];
            }
        }
        if ($pageWords === []) {
            $renderedPages[] = '';
            continue;
        }

        $grid = [];
        $rowTops = [];
        $rows = ocr_layout_group_words_into_rows($pageWords);

        foreach ($rows as $rowWords) {
            $lineTop = min(array_map(static fn(array $word): float => (float) ($word['y0'] ?? 0.0), $rowWords));
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
            foreach ($rowWords as $word) {
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

function normalize_debug_word_bbox($bbox): ?array
{
    if (!is_array($bbox) || $bbox === []) {
        return null;
    }

    if (
        array_key_exists('x0', $bbox)
        && array_key_exists('y0', $bbox)
        && array_key_exists('x1', $bbox)
        && array_key_exists('y1', $bbox)
    ) {
        $values = [
            $bbox['x0'],
            $bbox['y0'],
            $bbox['x1'],
            $bbox['y1'],
        ];
        foreach ($values as $value) {
            if (!(is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)))) {
                return null;
            }
        }
        return [
            'x0' => (float) $bbox['x0'],
            'y0' => (float) $bbox['y0'],
            'x1' => (float) $bbox['x1'],
            'y1' => (float) $bbox['y1'],
        ];
    }

    if (count($bbox) >= 4 && !is_array($bbox[0] ?? null)) {
        $values = array_slice($bbox, 0, 4);
        foreach ($values as $value) {
            if (!(is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)))) {
                return null;
            }
        }
        return [
            'x0' => (float) $values[0],
            'y0' => (float) $values[1],
            'x1' => (float) $values[2],
            'y1' => (float) $values[3],
        ];
    }

    $points = [];
    foreach ($bbox as $point) {
        if (!is_array($point) || count($point) < 2) {
            continue;
        }
        $x = $point[0] ?? null;
        $y = $point[1] ?? null;
        if (!(is_int($x) || is_float($x) || (is_string($x) && is_numeric($x)))) {
            continue;
        }
        if (!(is_int($y) || is_float($y) || (is_string($y) && is_numeric($y)))) {
            continue;
        }
        $points[] = [(float) $x, (float) $y];
    }

    if ($points === []) {
        return null;
    }

    $xs = array_column($points, 0);
    $ys = array_column($points, 1);
    return [
        'x0' => min($xs),
        'y0' => min($ys),
        'x1' => max($xs),
        'y1' => max($ys),
    ];
}

function ocr_layout_group_words_into_rows(array $words): array
{
    if ($words === []) {
        return [];
    }

    $orderedWords = array_values($words);
    usort($orderedWords, static function (array $left, array $right): int {
        $leftCenterY = (((float) ($left['y0'] ?? 0.0)) + ((float) ($left['y1'] ?? 0.0))) / 2.0;
        $rightCenterY = (((float) ($right['y0'] ?? 0.0)) + ((float) ($right['y1'] ?? 0.0))) / 2.0;
        $centerCompare = $leftCenterY <=> $rightCenterY;
        if ($centerCompare !== 0) {
            return $centerCompare;
        }
        return ((float) ($left['x0'] ?? 0.0)) <=> ((float) ($right['x0'] ?? 0.0));
    });

    $heights = array_values(array_filter(array_map(static function (array $word): float {
        return max(0.0, (float) ($word['y1'] ?? 0.0) - (float) ($word['y0'] ?? 0.0));
    }, $orderedWords), static fn(float $height): bool => $height > 0.0));
    $medianWordHeight = median_float($heights, 12.0);
    $baseTolerance = max(1.0, $medianWordHeight * 0.35);

    $rows = [];
    foreach ($orderedWords as $word) {
        $wordHeight = max(1.0, (float) ($word['y1'] ?? 0.0) - (float) ($word['y0'] ?? 0.0));
        $wordCenterY = (((float) ($word['y0'] ?? 0.0)) + ((float) ($word['y1'] ?? 0.0))) / 2.0;
        $bestIndex = null;
        $bestScore = null;

        foreach ($rows as $index => $row) {
            $rowHeight = max(1.0, (float) $row['maxY'] - (float) $row['minY']);
            $centerDiff = abs((float) $row['centerY'] - $wordCenterY);
            $verticalOverlap = max(
                0.0,
                min((float) $row['maxY'], (float) ($word['y1'] ?? 0.0)) - max((float) $row['minY'], (float) ($word['y0'] ?? 0.0))
            );
            $overlapRatio = $verticalOverlap / max(1.0, min($rowHeight, $wordHeight));
            $adaptiveTolerance = max($baseTolerance, min($rowHeight, $wordHeight) * 0.25);
            $matchesRow = $centerDiff <= $adaptiveTolerance
                || ($overlapRatio >= 0.45 && $centerDiff <= max($baseTolerance, $medianWordHeight * 0.6));
            if (!$matchesRow) {
                continue;
            }

            $score = $centerDiff - ($overlapRatio * $medianWordHeight * 0.15);
            if ($bestScore === null || $score < $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        if ($bestIndex === null) {
            $rows[] = [
                'words' => [$word],
                'centerY' => $wordCenterY,
                'minY' => (float) ($word['y0'] ?? 0.0),
                'maxY' => (float) ($word['y1'] ?? 0.0),
            ];
            continue;
        }

        $rows[$bestIndex]['words'][] = $word;
        $wordCount = count($rows[$bestIndex]['words']);
        $rows[$bestIndex]['centerY'] = (($rows[$bestIndex]['centerY'] * ($wordCount - 1)) + $wordCenterY) / $wordCount;
        $rows[$bestIndex]['minY'] = min((float) $rows[$bestIndex]['minY'], (float) ($word['y0'] ?? 0.0));
        $rows[$bestIndex]['maxY'] = max((float) $rows[$bestIndex]['maxY'], (float) ($word['y1'] ?? 0.0));
    }

    usort($rows, static function (array $left, array $right): int {
        $centerCompare = ((float) ($left['centerY'] ?? 0.0)) <=> ((float) ($right['centerY'] ?? 0.0));
        if ($centerCompare !== 0) {
            return $centerCompare;
        }
        return ((float) ($left['minY'] ?? 0.0)) <=> ((float) ($right['minY'] ?? 0.0));
    });

    $normalizedRows = [];
    foreach ($rows as $row) {
        $rowWords = is_array($row['words'] ?? null) ? $row['words'] : [];
        usort($rowWords, static function (array $a, array $b): int {
            return ((float) ($a['x0'] ?? 0.0)) <=> ((float) ($b['x0'] ?? 0.0));
        });
        if ($rowWords !== []) {
            $normalizedRows[] = $rowWords;
        }
    }

    return $normalizedRows;
}

function render_grid_text_from_debug_words(array $words): string
{
    if ($words === []) {
        return '';
    }

    $normalized = [];
    $wordWidths = [];
    $lineHeights = [];

    foreach ($words as $word) {
        if (!is_array($word)) {
            continue;
        }

        $text = is_string($word['text'] ?? null) ? trim((string) $word['text']) : '';
        if ($text === '') {
            continue;
        }

        $bbox = normalize_debug_word_bbox($word['bbox'] ?? null);
        if ($bbox === null) {
            continue;
        }

        $width = $bbox['x1'] - $bbox['x0'];
        $height = $bbox['y1'] - $bbox['y0'];
        $charCount = utf8_strlen_safe($text);
        if ($width > 0 && $charCount > 0) {
            $wordWidths[] = $width / $charCount;
        }
        if ($height > 0) {
            $lineHeights[] = $height;
        }

        $normalized[] = [
            'text' => $text,
            'x0' => $bbox['x0'],
            'y0' => $bbox['y0'],
            'x1' => $bbox['x1'],
            'y1' => $bbox['y1'],
        ];
    }

    if ($normalized === []) {
        return '';
    }

    $charWidth = median_float($wordWidths, 18.0);
    $lineHeight = median_float($lineHeights, 40.0);
    $rows = ocr_layout_group_words_into_rows($normalized);
    $rowTops = [];

    if ($rows === []) {
        return '';
    }

    $grid = [];
    foreach ($rows as $rowWords) {
        $wordTop = min(array_map(static fn(array $word): float => (float) ($word['y0'] ?? 0.0), $rowWords));
        $candidateRow = (int) round($wordTop / max($lineHeight, 1.0));
        while (isset($rowTops[$candidateRow]) && abs($rowTops[$candidateRow] - $wordTop) > ($lineHeight * 0.35)) {
            $candidateRow++;
        }
        if (!isset($grid[$candidateRow])) {
            $grid[$candidateRow] = [];
            $rowTops[$candidateRow] = $wordTop;
        }
        $buffer = [];
        $cursor = 0;
        foreach ($rowWords as $word) {
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

    $pageLines = [];
    $previousRow = null;
    ksort($grid, SORT_NUMERIC);
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

    return rtrim(implode("\n", $pageLines));
}

function render_grid_text_from_debug_payload(array $payload): string
{
    $words = is_array($payload['words'] ?? null) ? $payload['words'] : [];
    $rendered = render_grid_text_from_debug_words($words);
    if ($rendered !== '') {
        return $rendered;
    }

    $text = $payload['text'] ?? '';
    return is_string($text) ? trim($text) : '';
}

function normalize_debug_words_for_merge(array $payload, string $engine): array
{
    $words = is_array($payload['words'] ?? null) ? $payload['words'] : [];
    $normalized = [];

    foreach ($words as $index => $word) {
        if (!is_array($word)) {
            continue;
        }

        $text = is_string($word['text'] ?? null) ? trim((string) $word['text']) : '';
        if ($text === '') {
            continue;
        }

        $bbox = normalize_debug_word_bbox($word['bbox'] ?? null);
        if ($bbox === null) {
            continue;
        }

        $score = null;
        $candidateScore = $word['score'] ?? null;
        if (is_int($candidateScore) || is_float($candidateScore) || (is_string($candidateScore) && is_numeric($candidateScore))) {
            $score = max(0.0, min(1.0, (float) $candidateScore));
        }

        $normalized[] = [
            'engine' => $engine,
            'index' => (int) $index,
            'text' => $text,
            'bbox' => $bbox,
            'score' => $score,
        ];
    }

    return $normalized;
}

function normalize_text_for_segment_match(string $text): string
{
    $lowered = lowercase_text($text);
    $collapsed = preg_replace('/\s+/u', '', $lowered);
    return is_string($collapsed) ? $collapsed : '';
}

function is_swedish_diacritic_char(string $char): bool
{
    return in_array($char, ['å', 'ä', 'ö', 'Å', 'Ä', 'Ö'], true);
}

function fold_char_for_diacritic_match(string $char): string
{
    $lower = lowercase_text($char);
    return match ($lower) {
        'å', 'ä', 'à', 'á', 'â', 'ã', 'ā' => 'a',
        'ö', 'ò', 'ó', 'ô', 'õ', 'ø', 'ō' => 'o',
        'ü', 'ù', 'ú', 'û', 'ū' => 'u',
        'é', 'è', 'ê', 'ë', 'ē' => 'e',
        'í', 'ì', 'î', 'ï', 'ī' => 'i',
        default => $lower,
    };
}

function normalize_text_for_diacritic_match(string $text): string
{
    $normalized = [];
    foreach (utf8_chars($text) as $char) {
        if (preg_match('/\s/u', $char) === 1) {
            continue;
        }
        $normalized[] = fold_char_for_diacritic_match($char);
    }
    return implode('', $normalized);
}

function utf8_levenshtein_distance(string $left, string $right): int
{
    $leftChars = utf8_chars($left);
    $rightChars = utf8_chars($right);
    $leftCount = count($leftChars);
    $rightCount = count($rightChars);

    if ($leftCount === 0) {
        return $rightCount;
    }
    if ($rightCount === 0) {
        return $leftCount;
    }

    $previousRow = range(0, $rightCount);
    for ($i = 1; $i <= $leftCount; $i++) {
        $currentRow = [$i];
        for ($j = 1; $j <= $rightCount; $j++) {
            $cost = $leftChars[$i - 1] === $rightChars[$j - 1] ? 0 : 1;
            $currentRow[$j] = min(
                $previousRow[$j] + 1,
                $currentRow[$j - 1] + 1,
                $previousRow[$j - 1] + $cost
            );
        }
        $previousRow = $currentRow;
    }

    return (int) $previousRow[$rightCount];
}

function texts_are_diacritic_compatible(string $left, string $right): bool
{
    $normalizedLeft = normalize_text_for_diacritic_match($left);
    $normalizedRight = normalize_text_for_diacritic_match($right);
    if ($normalizedLeft === '' || $normalizedRight === '') {
        return false;
    }
    if ($normalizedLeft === $normalizedRight) {
        return true;
    }

    $maxLen = max(count(utf8_chars($normalizedLeft)), count(utf8_chars($normalizedRight)));
    if ($maxLen === 0) {
        return true;
    }

    $distance = utf8_levenshtein_distance($normalizedLeft, $normalizedRight);
    return $distance <= max(1, (int) floor($maxLen * 0.2));
}

function transfer_swedish_diacritics(string $sourceText, string $truthText): string
{
    $sourceChars = utf8_chars($sourceText);
    $truthChars = utf8_chars($truthText);
    $sourceCount = count($sourceChars);
    $truthCount = count($truthChars);
    if ($sourceCount === 0 || $truthCount === 0) {
        return $sourceText;
    }

    $sourceFolded = array_map('fold_char_for_diacritic_match', $sourceChars);
    $truthFolded = array_map('fold_char_for_diacritic_match', $truthChars);

    $dp = array_fill(0, $sourceCount + 1, array_fill(0, $truthCount + 1, 0));
    for ($i = 0; $i <= $sourceCount; $i++) {
        $dp[$i][0] = $i;
    }
    for ($j = 0; $j <= $truthCount; $j++) {
        $dp[0][$j] = $j;
    }

    for ($i = 1; $i <= $sourceCount; $i++) {
        for ($j = 1; $j <= $truthCount; $j++) {
            $cost = $sourceFolded[$i - 1] === $truthFolded[$j - 1] ? 0 : 1;
            $dp[$i][$j] = min(
                $dp[$i - 1][$j] + 1,
                $dp[$i][$j - 1] + 1,
                $dp[$i - 1][$j - 1] + $cost
            );
        }
    }

    $result = [];
    $i = $sourceCount;
    $j = $truthCount;
    while ($i > 0 || $j > 0) {
        if (
            $i > 0
            && $j > 0
            && $dp[$i][$j] === $dp[$i - 1][$j - 1] + ($sourceFolded[$i - 1] === $truthFolded[$j - 1] ? 0 : 1)
        ) {
            $sourceChar = $sourceChars[$i - 1];
            $truthChar = $truthChars[$j - 1];
            if (
                $sourceFolded[$i - 1] === $truthFolded[$j - 1]
                && is_swedish_diacritic_char($truthChar)
            ) {
                $result[] = $truthChar;
            } else {
                $result[] = $sourceChar;
            }
            $i--;
            $j--;
            continue;
        }

        if ($i > 0 && $dp[$i][$j] === $dp[$i - 1][$j] + 1) {
            $result[] = $sourceChars[$i - 1];
            $i--;
            continue;
        }

        if ($j > 0 && $dp[$i][$j] === $dp[$i][$j - 1] + 1) {
            $j--;
            continue;
        }

        if ($i > 0) {
            $result[] = $sourceChars[$i - 1];
            $i--;
        } elseif ($j > 0) {
            $j--;
        }
    }

    return implode('', array_reverse($result));
}

function build_debug_word_from_fragments(array $fragments, string $text, ?array $fallbackBbox = null, ?float $fallbackScore = null): ?array
{
    if ($fragments === []) {
        if ($fallbackBbox === null || trim($text) === '') {
            return null;
        }
        return [
            'text' => trim($text),
            'bbox' => $fallbackBbox,
            'score' => $fallbackScore,
        ];
    }

    $bbox = null;
    $scores = [];
    foreach ($fragments as $fragment) {
        if (!is_array($fragment)) {
            continue;
        }
        $fragmentBbox = normalize_debug_word_bbox($fragment['bbox'] ?? null);
        if ($fragmentBbox !== null) {
            if ($bbox === null) {
                $bbox = $fragmentBbox;
            } else {
                $bbox = [
                    'x0' => min($bbox['x0'], $fragmentBbox['x0']),
                    'y0' => min($bbox['y0'], $fragmentBbox['y0']),
                    'x1' => max($bbox['x1'], $fragmentBbox['x1']),
                    'y1' => max($bbox['y1'], $fragmentBbox['y1']),
                ];
            }
        }

        $candidateScore = $fragment['score'] ?? null;
        if (is_int($candidateScore) || is_float($candidateScore) || (is_string($candidateScore) && is_numeric($candidateScore))) {
            $scores[] = max(0.0, min(1.0, (float) $candidateScore));
        }
    }

    if ($bbox === null) {
        $bbox = $fallbackBbox;
    }
    if ($bbox === null || trim($text) === '') {
        return null;
    }

    $score = $scores !== []
        ? array_sum($scores) / count($scores)
        : $fallbackScore;

    return [
        'text' => trim($text),
        'bbox' => $bbox,
        'score' => $score,
    ];
}

function fallback_merge_rapidocr_line_fragments(array $fragments, ?array $lineBbox = null, ?float $lineScore = null): array
{
    if ($fragments === []) {
        return [];
    }

    usort($fragments, static function (array $a, array $b): int {
        $leftBbox = normalize_debug_word_bbox($a['bbox'] ?? null);
        $rightBbox = normalize_debug_word_bbox($b['bbox'] ?? null);
        if ($leftBbox === null || $rightBbox === null) {
            return 0;
        }
        $xCompare = $leftBbox['x0'] <=> $rightBbox['x0'];
        if ($xCompare !== 0) {
            return $xCompare;
        }
        return $leftBbox['y0'] <=> $rightBbox['y0'];
    });

    $groups = [];
    $currentGroup = [];
    $previousBbox = null;

    foreach ($fragments as $fragment) {
        $bbox = normalize_debug_word_bbox($fragment['bbox'] ?? null);
        if ($bbox === null) {
            continue;
        }

        if ($currentGroup === [] || $previousBbox === null) {
            $currentGroup[] = $fragment;
            $previousBbox = $bbox;
            continue;
        }

        $previousHeight = max(1.0, $previousBbox['y1'] - $previousBbox['y0']);
        $currentHeight = max(1.0, $bbox['y1'] - $bbox['y0']);
        $gap = $bbox['x0'] - $previousBbox['x1'];
        $mergeThreshold = max(4.0, min($previousHeight, $currentHeight) * 0.22);

        if ($gap <= $mergeThreshold) {
            $currentGroup[] = $fragment;
        } else {
            $groups[] = $currentGroup;
            $currentGroup = [$fragment];
        }
        $previousBbox = $bbox;
    }

    if ($currentGroup !== []) {
        $groups[] = $currentGroup;
    }

    $segments = [];
    foreach ($groups as $group) {
        $text = '';
        foreach ($group as $fragment) {
            $text .= is_string($fragment['text'] ?? null) ? (string) $fragment['text'] : '';
        }
        $segment = build_debug_word_from_fragments($group, $text, $lineBbox, $lineScore);
        if ($segment !== null) {
            $segments[] = $segment;
        }
    }

    return $segments;
}

function merge_rapidocr_line_into_segments(array $line): array
{
    $lineText = is_string($line['text'] ?? null) ? trim((string) $line['text']) : '';
    $lineScore = null;
    if (is_int($line['score'] ?? null) || is_float($line['score'] ?? null) || (is_string($line['score'] ?? null) && is_numeric($line['score'] ?? null))) {
        $lineScore = max(0.0, min(1.0, (float) $line['score']));
    }
    $lineBbox = normalize_debug_word_bbox($line['bbox'] ?? null);

    $fragments = [];
    foreach (is_array($line['words'] ?? null) ? $line['words'] : [] as $fragment) {
        if (!is_array($fragment)) {
            continue;
        }
        $text = is_string($fragment['text'] ?? null) ? trim((string) $fragment['text']) : '';
        $bbox = normalize_debug_word_bbox($fragment['bbox'] ?? null);
        if ($text === '' || $bbox === null) {
            continue;
        }
        $score = null;
        if (is_int($fragment['score'] ?? null) || is_float($fragment['score'] ?? null) || (is_string($fragment['score'] ?? null) && is_numeric($fragment['score'] ?? null))) {
            $score = max(0.0, min(1.0, (float) $fragment['score']));
        }
        $fragments[] = [
            'text' => $text,
            'bbox' => $bbox,
            'score' => $score,
        ];
    }

    if ($fragments === []) {
        return [];
    }

    usort($fragments, static function (array $a, array $b): int {
        $xCompare = $a['bbox']['x0'] <=> $b['bbox']['x0'];
        if ($xCompare !== 0) {
            return $xCompare;
        }
        return $a['bbox']['y0'] <=> $b['bbox']['y0'];
    });

    $tokens = preg_split('/\s+/u', $lineText, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($tokens) || $tokens === []) {
        return fallback_merge_rapidocr_line_fragments($fragments, $lineBbox, $lineScore);
    }

    $segments = [];
    $fragmentIndex = 0;
    foreach ($tokens as $token) {
        $normalizedToken = normalize_text_for_segment_match($token);
        if ($normalizedToken === '') {
            continue;
        }

        $segmentFragments = [];
        $candidateText = '';
        $matched = false;

        while ($fragmentIndex < count($fragments)) {
            $fragment = $fragments[$fragmentIndex];
            $fragmentIndex += 1;
            $normalizedFragment = normalize_text_for_segment_match($fragment['text']);
            if ($normalizedFragment === '') {
                continue;
            }

            $segmentFragments[] = $fragment;
            $candidateText .= $normalizedFragment;

            if ($candidateText === $normalizedToken) {
                $matched = true;
                break;
            }

            if (!str_starts_with($normalizedToken, $candidateText)) {
                $matched = false;
                break;
            }
        }

        if (!$matched) {
            return fallback_merge_rapidocr_line_fragments($fragments, $lineBbox, $lineScore);
        }

        $segment = build_debug_word_from_fragments($segmentFragments, $token, $lineBbox, $lineScore);
        if ($segment !== null) {
            $segments[] = $segment;
        }
    }

    if ($fragmentIndex < count($fragments)) {
        $remaining = array_slice($fragments, $fragmentIndex);
        foreach (fallback_merge_rapidocr_line_fragments($remaining, $lineBbox, $lineScore) as $segment) {
            $segments[] = $segment;
        }
    }

    return $segments !== [] ? $segments : fallback_merge_rapidocr_line_fragments($fragments, $lineBbox, $lineScore);
}

function apply_tesseract_swedish_truth_to_segments(array $segments, array $tesseractWords): array
{
    if ($segments === [] || $tesseractWords === []) {
        return $segments;
    }

    foreach ($segments as $index => $segment) {
        if (!is_array($segment)) {
            continue;
        }

        $segmentText = is_string($segment['text'] ?? null) ? trim((string) $segment['text']) : '';
        $segmentBbox = normalize_debug_word_bbox($segment['bbox'] ?? null);
        if ($segmentText === '' || $segmentBbox === null) {
            continue;
        }

        $bestCandidate = null;
        $bestMatchScore = null;
        foreach ($tesseractWords as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $candidateText = is_string($candidate['text'] ?? null) ? trim((string) $candidate['text']) : '';
            if ($candidateText === '' || !preg_match('/[åäöÅÄÖ]/u', $candidateText)) {
                continue;
            }

            $candidateBbox = normalize_debug_word_bbox($candidate['bbox'] ?? null);
            if ($candidateBbox === null) {
                continue;
            }

            $iou = bbox_iou($segmentBbox, $candidateBbox);
            $distanceRatio = bbox_center_distance_ratio($segmentBbox, $candidateBbox);
            if ($iou < 0.08 && $distanceRatio > 0.9) {
                continue;
            }

            if (!texts_are_diacritic_compatible($segmentText, $candidateText)) {
                continue;
            }

            $candidateScore = is_float($candidate['score'] ?? null) ? (float) $candidate['score'] : 0.0;
            $matchScore = ($iou * 4.0) + max(0.0, 1.0 - min($distanceRatio, 1.0)) + $candidateScore;
            if ($bestMatchScore === null || $matchScore > $bestMatchScore) {
                $bestMatchScore = $matchScore;
                $bestCandidate = $candidate;
            }
        }

        if ($bestCandidate === null) {
            continue;
        }

        $adjustedText = transfer_swedish_diacritics($segmentText, (string) $bestCandidate['text']);
        if ($adjustedText !== $segmentText) {
            $segments[$index]['text'] = $adjustedText;
        }
    }

    return $segments;
}

function build_merged_objects_payload_from_rapidocr_page(array $rapidocrPayload, int $pageNumber, array $tesseractPayload = []): array
{
    $pageWidth = is_numeric($rapidocrPayload['pageWidth'] ?? null) ? (float) $rapidocrPayload['pageWidth'] : null;
    $pageHeight = is_numeric($rapidocrPayload['pageHeight'] ?? null) ? (float) $rapidocrPayload['pageHeight'] : null;
    $sourceImage = is_string($rapidocrPayload['sourceImage'] ?? null) ? $rapidocrPayload['sourceImage'] : null;
    $tesseractWords = normalize_debug_words_for_merge($tesseractPayload, 'tesseract');

    $mergedLines = [];
    $mergedWords = [];
    foreach (is_array($rapidocrPayload['lines'] ?? null) ? $rapidocrPayload['lines'] : [] as $lineIndex => $line) {
        if (!is_array($line)) {
            continue;
        }
        $segments = merge_rapidocr_line_into_segments($line);
        $segments = apply_tesseract_swedish_truth_to_segments($segments, $tesseractWords);
        $lineText = implode(' ', array_map(static fn(array $segment): string => (string) ($segment['text'] ?? ''), $segments));
        $lineBbox = normalize_debug_word_bbox($line['bbox'] ?? null);
        $lineScore = null;
        if (is_int($line['score'] ?? null) || is_float($line['score'] ?? null) || (is_string($line['score'] ?? null) && is_numeric($line['score'] ?? null))) {
            $lineScore = max(0.0, min(1.0, (float) $line['score']));
        }

        $lineWords = [];
        foreach ($segments as $segmentIndex => $segment) {
            $lineWords[] = $segment;
            $mergedWords[] = $segment;
        }

        $mergedLines[] = [
            'index' => $lineIndex,
            'text' => $lineText,
            'bbox' => $lineBbox,
            'score' => $lineScore,
            'words' => $lineWords,
        ];
    }

    $pageText = implode("\n", array_values(array_filter(array_map(
        static fn(array $line): string => trim((string) ($line['text'] ?? '')),
        $mergedLines
    ), static fn(string $lineText): bool => $lineText !== '')));

    return [
        'engine' => 'merged_objects',
        'sourceEngine' => 'rapidocr',
        'pageNumber' => $pageNumber,
        'pageIndex' => max(0, $pageNumber - 1),
        'sourceImage' => $sourceImage,
        'pageWidth' => $pageWidth,
        'pageHeight' => $pageHeight,
        'lines' => $mergedLines,
        'words' => $mergedWords,
        'text' => $pageText,
    ];
}

function write_merged_object_debug_files_from_rapidocr(string $jobDir): void
{
    $rapidocrPages = load_job_engine_debug_pages($jobDir, 'rapidocr');
    $tesseractPages = load_job_engine_debug_pages($jobDir, 'tesseract');
    if ($rapidocrPages === []) {
        foreach (glob($jobDir . '/merged_objects_page_*.json') ?: [] as $path) {
            @unlink($path);
        }
        foreach (glob($jobDir . '/merged_objects_page_*.txt') ?: [] as $path) {
            @unlink($path);
        }
        if (is_file($jobDir . '/merged_objects.txt')) {
            @unlink($jobDir . '/merged_objects.txt');
        }
        return;
    }

    foreach ($rapidocrPages as $pageIndex => $rapidocrPage) {
        if (!is_array($rapidocrPage)) {
            continue;
        }
        $pageNumber = is_numeric($rapidocrPage['pageNumber'] ?? null) ? (int) $rapidocrPage['pageNumber'] : ($pageIndex + 1);
        $tesseractPage = is_array($tesseractPages[$pageIndex] ?? null) ? $tesseractPages[$pageIndex] : [];
        $payload = build_merged_objects_payload_from_rapidocr_page($rapidocrPage, $pageNumber, $tesseractPage);
        $path = $jobDir . '/merged_objects_page_' . str_pad((string) $pageNumber, 2, '0', STR_PAD_LEFT) . '.json';
        write_json_file($path, $payload);
    }

    regenerate_debug_text_files_from_json($jobDir, 'merged_objects');
}

function bbox_intersection_area(array $left, array $right): float
{
    $x0 = max((float) ($left['x0'] ?? 0.0), (float) ($right['x0'] ?? 0.0));
    $y0 = max((float) ($left['y0'] ?? 0.0), (float) ($right['y0'] ?? 0.0));
    $x1 = min((float) ($left['x1'] ?? 0.0), (float) ($right['x1'] ?? 0.0));
    $y1 = min((float) ($left['y1'] ?? 0.0), (float) ($right['y1'] ?? 0.0));
    if ($x1 <= $x0 || $y1 <= $y0) {
        return 0.0;
    }
    return ($x1 - $x0) * ($y1 - $y0);
}

function bbox_area(array $bbox): float
{
    $width = max(0.0, (float) ($bbox['x1'] ?? 0.0) - (float) ($bbox['x0'] ?? 0.0));
    $height = max(0.0, (float) ($bbox['y1'] ?? 0.0) - (float) ($bbox['y0'] ?? 0.0));
    return $width * $height;
}

function bbox_iou(array $left, array $right): float
{
    $intersection = bbox_intersection_area($left, $right);
    if ($intersection <= 0) {
        return 0.0;
    }

    $union = bbox_area($left) + bbox_area($right) - $intersection;
    if ($union <= 0) {
        return 0.0;
    }

    return $intersection / $union;
}

function bbox_center_distance_ratio(array $left, array $right): float
{
    $leftCenterX = (((float) ($left['x0'] ?? 0.0)) + ((float) ($left['x1'] ?? 0.0))) / 2.0;
    $leftCenterY = (((float) ($left['y0'] ?? 0.0)) + ((float) ($left['y1'] ?? 0.0))) / 2.0;
    $rightCenterX = (((float) ($right['x0'] ?? 0.0)) + ((float) ($right['x1'] ?? 0.0))) / 2.0;
    $rightCenterY = (((float) ($right['y0'] ?? 0.0)) + ((float) ($right['y1'] ?? 0.0))) / 2.0;

    $distance = sqrt((($leftCenterX - $rightCenterX) ** 2) + (($leftCenterY - $rightCenterY) ** 2));
    $scale = max(
        1.0,
        (float) ($left['x1'] ?? 0.0) - (float) ($left['x0'] ?? 0.0),
        (float) ($left['y1'] ?? 0.0) - (float) ($left['y0'] ?? 0.0),
        (float) ($right['x1'] ?? 0.0) - (float) ($right['x0'] ?? 0.0),
        (float) ($right['y1'] ?? 0.0) - (float) ($right['y0'] ?? 0.0)
    );

    return $distance / $scale;
}

function texts_are_similar_enough(string $left, string $right): bool
{
    if ($left === $right) {
        return true;
    }

    $normalizedLeft = preg_replace('/\s+/u', '', lowercase_text($left));
    $normalizedRight = preg_replace('/\s+/u', '', lowercase_text($right));
    if (!is_string($normalizedLeft) || !is_string($normalizedRight)) {
        return false;
    }
    if ($normalizedLeft === $normalizedRight) {
        return true;
    }

    if ($normalizedLeft === '' || $normalizedRight === '') {
        return false;
    }

    $maxLen = max(strlen($normalizedLeft), strlen($normalizedRight));
    if ($maxLen === 0) {
        return true;
    }

    $distance = levenshtein($normalizedLeft, $normalizedRight);
    return $distance <= max(1, (int) floor($maxLen * 0.2));
}

function choose_experiment_merged_word(array $tesseractWord, ?array $rapidocrWord): array
{
    if ($rapidocrWord === null) {
        return [
            'chosen' => $tesseractWord,
            'source' => 'tesseract-only',
            'rapidocrWord' => null,
        ];
    }

    $tesseractScore = is_float($tesseractWord['score']) ? $tesseractWord['score'] : 0.0;
    $rapidocrScore = is_float($rapidocrWord['score']) ? $rapidocrWord['score'] : 0.0;
    $sameText = texts_are_similar_enough($tesseractWord['text'], $rapidocrWord['text']);

    if ($sameText) {
        return [
            'chosen' => $rapidocrScore >= $tesseractScore ? $rapidocrWord : $tesseractWord,
            'source' => $rapidocrScore >= $tesseractScore ? 'rapidocr-agree' : 'tesseract-agree',
            'rapidocrWord' => $rapidocrWord,
        ];
    }

    if ($rapidocrScore >= ($tesseractScore + 0.08)) {
        return [
            'chosen' => $rapidocrWord,
            'source' => 'rapidocr-score',
            'rapidocrWord' => $rapidocrWord,
        ];
    }

    return [
        'chosen' => $tesseractWord,
        'source' => 'tesseract-score',
        'rapidocrWord' => $rapidocrWord,
    ];
}

function build_experiment_merge_for_pages(array $tesseractPages, array $rapidocrPages): array
{
    $pageCount = max(count($tesseractPages), count($rapidocrPages));
    $pages = [];

    for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
        $tesseractPayload = is_array($tesseractPages[$pageIndex] ?? null) ? $tesseractPages[$pageIndex] : [];
        $rapidocrPayload = is_array($rapidocrPages[$pageIndex] ?? null) ? $rapidocrPages[$pageIndex] : [];
        $tesseractWords = normalize_debug_words_for_merge($tesseractPayload, 'tesseract');
        $rapidocrWords = normalize_debug_words_for_merge($rapidocrPayload, 'rapidocr');
        $rapidocrUsed = [];
        $mergedWords = [];
        $decisions = [];

        foreach ($tesseractWords as $tesseractWord) {
            $bestIndex = null;
            $bestScore = -1.0;
            foreach ($rapidocrWords as $rapidocrIndex => $rapidocrWord) {
                if (isset($rapidocrUsed[$rapidocrIndex])) {
                    continue;
                }

                $iou = bbox_iou($tesseractWord['bbox'], $rapidocrWord['bbox']);
                $distanceRatio = bbox_center_distance_ratio($tesseractWord['bbox'], $rapidocrWord['bbox']);
                $score = $iou - ($distanceRatio * 0.2);
                if ($iou <= 0.02 && $distanceRatio > 1.2) {
                    continue;
                }
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestIndex = $rapidocrIndex;
                }
            }

            $matchedRapidocrWord = null;
            if ($bestIndex !== null && $bestScore > -0.05) {
                $matchedRapidocrWord = $rapidocrWords[$bestIndex];
                $rapidocrUsed[$bestIndex] = true;
            }

            $decision = choose_experiment_merged_word($tesseractWord, $matchedRapidocrWord);
            $chosen = $decision['chosen'];
            $mergedWords[] = [
                'text' => $chosen['text'],
                'bbox' => $chosen['bbox'],
                'score' => $chosen['score'],
                'source' => $decision['source'],
            ];
            $decisions[] = [
                'tesseract' => $tesseractWord,
                'rapidocr' => $decision['rapidocrWord'],
                'chosenSource' => $decision['source'],
                'chosenText' => $chosen['text'],
            ];
        }

        foreach ($rapidocrWords as $rapidocrIndex => $rapidocrWord) {
            if (isset($rapidocrUsed[$rapidocrIndex])) {
                continue;
            }
            $mergedWords[] = [
                'text' => $rapidocrWord['text'],
                'bbox' => $rapidocrWord['bbox'],
                'score' => $rapidocrWord['score'],
                'source' => 'rapidocr-unmatched',
            ];
            $decisions[] = [
                'tesseract' => null,
                'rapidocr' => $rapidocrWord,
                'chosenSource' => 'rapidocr-unmatched',
                'chosenText' => $rapidocrWord['text'],
            ];
        }

        $renderWords = array_map(static function (array $word): array {
            return [
                'text' => $word['text'],
                'bbox' => $word['bbox'],
                'score' => $word['score'],
            ];
        }, $mergedWords);

        $pages[] = [
            'pageNumber' => $pageIndex + 1,
            'tesseract' => $tesseractPayload,
            'rapidocr' => $rapidocrPayload,
            'mergedWords' => $mergedWords,
            'mergedText' => render_grid_text_from_debug_payload(['words' => $renderWords]),
            'decisions' => $decisions,
        ];
    }

    return $pages;
}

function run_job_merge_experiment(string $jobDir): array
{
    if (!is_dir($jobDir)) {
        throw new RuntimeException('Job directory not found');
    }

    $tesseractPages = load_job_engine_debug_pages($jobDir, 'tesseract');
    $rapidocrPages = load_job_engine_debug_pages($jobDir, 'rapidocr');
    if ($tesseractPages === [] && $rapidocrPages === []) {
        throw new RuntimeException('No OCR debug page JSON files found');
    }

    $pages = build_experiment_merge_for_pages($tesseractPages, $rapidocrPages);
    $payload = [
        'generatedAt' => now_iso(),
        'pageCount' => count($pages),
        'pages' => $pages,
    ];

    write_json_file($jobDir . '/merge-experiment.json', $payload);

    $pageTexts = [];
    foreach ($pages as $page) {
        $pageNumber = (int) ($page['pageNumber'] ?? 0);
        $mergedText = is_string($page['mergedText'] ?? null) ? rtrim((string) $page['mergedText']) : '';
        $pageTexts[] = '=== PAGE ' . $pageNumber . " ===\n" . $mergedText;
    }
    file_put_contents($jobDir . '/merge-experiment.txt', implode("\n\n", $pageTexts) . "\n");

    return $payload;
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

function find_category_signal_matches(string $ocrText, array $categories, array $replacementMap, array $context = []): array
{
    $normalizedOcr = normalize_for_matching($ocrText, $replacementMap);
    $inverseMap = build_inverse_single_char_map($replacementMap);
    $matches = [];
    $matchedLabelsById = is_array($context['matchedLabelsById'] ?? null) ? $context['matchedLabelsById'] : [];

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
        $systemLabelKey = is_string($category['systemLabelKey'] ?? null) ? trim((string) $category['systemLabelKey']) : '';
        $isSystemLabel = ($category['isSystemLabel'] ?? false) === true;
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

            $ruleType = is_string($rule['type'] ?? null) ? trim(strtolower((string) $rule['type'])) : 'text';
            $ruleText = is_string($rule['text'] ?? null) ? trim((string) $rule['text']) : '';
            $ruleLabelId = is_string($rule['labelId'] ?? null) ? trim((string) $rule['labelId']) : '';
            $ruleScore = positive_int($rule['score'] ?? 1, 1);
            $sourceText = '';
            $displayText = $ruleText;

            if ($ruleType === 'label') {
                if ($ruleLabelId === '' || !is_array($matchedLabelsById[$ruleLabelId] ?? null)) {
                    continue;
                }
                $matchedLabel = $matchedLabelsById[$ruleLabelId];
                $matchedLabelName = is_string($matchedLabel['name'] ?? null) ? trim((string) $matchedLabel['name']) : '';
                $sourceText = $matchedLabelName !== '' ? $matchedLabelName : $ruleLabelId;
                $displayText = 'Har etikett: ' . $sourceText;
            } else {
                if ($ruleText === '') {
                    continue;
                }
                $ruleTextLower = normalize_for_matching($ruleText, $replacementMap);

                if (!str_contains($normalizedOcr, $ruleTextLower)) {
                    continue;
                }

                $sourceText = find_source_text_for_rule($ocrText, $ruleText, $inverseMap);
            }
            $score += $ruleScore;
            $matchedRules[] = [
                'type' => $ruleType,
                'text' => $displayText,
                'labelId' => $ruleLabelId,
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
            'systemLabelKey' => $systemLabelKey,
            'isSystemLabel' => $isSystemLabel,
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

function find_category_matches(string $ocrText, array $categories, array $replacementMap, array $context = []): array
{
    $signalMatches = find_category_signal_matches($ocrText, $categories, $replacementMap, $context);
    return filter_category_matches_by_threshold($signalMatches);
}

function find_incremental_label_matches(string $ocrText, array $labels, array $replacementMap, array $context = []): array
{
    $matches = [];
    $matchedLabelsById = is_array($context['matchedLabelsById'] ?? null) ? $context['matchedLabelsById'] : [];
    $baseContext = $context;

    foreach ($labels as $label) {
        if (!is_array($label)) {
            continue;
        }

        $currentContext = $baseContext;
        $currentContext['matchedLabelsById'] = $matchedLabelsById;
        $labelMatches = find_category_matches($ocrText, [$label], $replacementMap, $currentContext);
        foreach ($labelMatches as $match) {
            if (!is_array($match)) {
                continue;
            }
            $matches[] = $match;
            $labelId = is_string($match['id'] ?? null) ? trim((string) $match['id']) : '';
            if ($labelId !== '') {
                $matchedLabelsById[$labelId] = $match;
            }
        }
    }

    return $matches;
}

function resolved_label_ids_from_matches(array ...$matchGroups): array
{
    $merged = [];
    $order = 0;
    foreach ($matchGroups as $matches) {
        if (!is_array($matches)) {
            continue;
        }
        foreach ($matches as $match) {
            if (!is_array($match)) {
                continue;
            }
            $labelId = is_string($match['id'] ?? null) ? trim((string) $match['id']) : '';
            if ($labelId === '') {
                continue;
            }
            $score = $match['score'] ?? 0;
            $numericScore = is_int($score) || is_float($score) || (is_string($score) && is_numeric($score))
                ? (float) $score
                : 0.0;
            if (!isset($merged[$labelId]) || $numericScore > $merged[$labelId]['score']) {
                $merged[$labelId] = [
                    'id' => $labelId,
                    'score' => $numericScore,
                    'order' => $order,
                ];
            }
            $order++;
        }
    }

    $items = array_values($merged);
    usort($items, static function (array $a, array $b): int {
        $scoreCompare = ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }
        return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
    });

    return array_values(array_map(
        static fn (array $item): string => (string) ($item['id'] ?? ''),
        array_filter($items, static fn (array $item): bool => is_string($item['id'] ?? null) && (string) $item['id'] !== '')
    ));
}

function matched_labels_by_id(array $matches): array
{
    $labelsById = [];
    foreach ($matches as $match) {
        if (!is_array($match)) {
            continue;
        }
        $labelId = is_string($match['id'] ?? null) ? trim((string) $match['id']) : '';
        if ($labelId === '') {
            continue;
        }
        $labelsById[$labelId] = $match;
    }
    return $labelsById;
}

function ocr_text_lines(string $ocrText): array
{
    $lines = preg_split('/\R/u', $ocrText);
    return is_array($lines) ? $lines : [];
}

function split_lines_for_matching(string $text): array
{
    return ocr_text_lines($text);
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

function nearby_line_indexes(array $lines, int $index, int $distance = 1): array
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

        $nearIndexes = nearby_line_indexes($lines, $hitIndex, $nearbyDistance);
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

function bankgiro_candidates_from_text(string $text, int $offsetBase = 0): array
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

function plusgiro_candidates_from_text(string $text, int $offsetBase = 0): array
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

function ocr_number_candidates_from_text(string $text, int $offsetBase = 0): array
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

function due_date_candidates_from_text(string $text, int $offsetBase = 0): array
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

function document_date_month_lookup(): array
{
    return [
        'jan' => 1,
        'januari' => 1,
        'january' => 1,
        'feb' => 2,
        'februari' => 2,
        'february' => 2,
        'mar' => 3,
        'mars' => 3,
        'march' => 3,
        'apr' => 4,
        'april' => 4,
        'maj' => 5,
        'may' => 5,
        'jun' => 6,
        'juni' => 6,
        'june' => 6,
        'jul' => 7,
        'juli' => 7,
        'july' => 7,
        'aug' => 8,
        'augusti' => 8,
        'august' => 8,
        'sep' => 9,
        'sept' => 9,
        'september' => 9,
        'okt' => 10,
        'oktober' => 10,
        'oct' => 10,
        'october' => 10,
        'nov' => 11,
        'november' => 11,
        'dec' => 12,
        'december' => 12,
    ];
}

function add_document_date_candidate(array &$candidates, array &$seen, ?string $value, string $raw, int $start, string $format): void
{
    $value = is_string($value) ? trim($value) : '';
    $raw = trim($raw);
    if ($value === '' || $raw === '' || $start < 0) {
        return;
    }

    $key = $start . '|' . $value . '|' . $raw;
    if (isset($seen[$key])) {
        return;
    }
    $seen[$key] = true;

    $candidates[] = [
        'value' => $value,
        'raw' => $raw,
        'start' => $start,
        'end' => $start + strlen($raw),
        'format' => $format,
    ];
}

function document_date_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $candidates = [];
    $seen = [];

    $matches = [];
    if (@preg_match_all('/\b(20\d{2})[.\-\/](\d{1,2})[.\-\/](\d{1,2})\b/u', $text, $matches, PREG_OFFSET_CAPTURE) >= 1) {
        $all = is_array($matches[0] ?? null) ? $matches[0] : [];
        foreach ($all as $index => $group) {
            if (!is_array($group) || count($group) < 2) {
                continue;
            }
            $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
            $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
            $year = isset($matches[1][$index][0]) ? (int) $matches[1][$index][0] : 0;
            $month = isset($matches[2][$index][0]) ? (int) $matches[2][$index][0] : 0;
            $day = isset($matches[3][$index][0]) ? (int) $matches[3][$index][0] : 0;
            $value = checkdate($month, $day, $year)
                ? sprintf('%04d-%02d-%02d', $year, $month, $day)
                : null;
            add_document_date_candidate($candidates, $seen, $value, $raw, $offsetBase + $start, 'ymd_numeric');
        }
    }

    $matches = [];
    if (@preg_match_all('/\b(\d{1,2})[.\-\/](\d{1,2})[.\-\/](20\d{2})\b/u', $text, $matches, PREG_OFFSET_CAPTURE) >= 1) {
        $all = is_array($matches[0] ?? null) ? $matches[0] : [];
        foreach ($all as $index => $group) {
            if (!is_array($group) || count($group) < 2) {
                continue;
            }
            $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
            $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
            $day = isset($matches[1][$index][0]) ? (int) $matches[1][$index][0] : 0;
            $month = isset($matches[2][$index][0]) ? (int) $matches[2][$index][0] : 0;
            $year = isset($matches[3][$index][0]) ? (int) $matches[3][$index][0] : 0;
            $value = checkdate($month, $day, $year)
                ? sprintf('%04d-%02d-%02d', $year, $month, $day)
                : null;
            add_document_date_candidate($candidates, $seen, $value, $raw, $offsetBase + $start, 'dmy_numeric');
        }
    }

    $monthPattern = 'jan(?:uari|uary)?|feb(?:ruari|ruary)?|mar(?:s|ch)?|apr(?:il)?|maj|may|jun(?:i|e)?|jul(?:i|y)?|aug(?:usti|ust)?|sep(?:t(?:ember)?)?|okt(?:ober)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?';
    $matches = [];
    if (@preg_match_all('/\b((?:den\s+)?(\d{1,2})\s+(' . $monthPattern . ')\s+(20\d{2}))\b/iu', $text, $matches, PREG_OFFSET_CAPTURE) >= 1) {
        $all = is_array($matches[1] ?? null) ? $matches[1] : [];
        $monthLookup = document_date_month_lookup();
        foreach ($all as $index => $group) {
            if (!is_array($group) || count($group) < 2) {
                continue;
            }
            $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
            $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
            $day = isset($matches[2][$index][0]) ? (int) $matches[2][$index][0] : 0;
            $monthKey = isset($matches[3][$index][0]) ? lowercase_text((string) $matches[3][$index][0]) : '';
            $year = isset($matches[4][$index][0]) ? (int) $matches[4][$index][0] : 0;
            $month = $monthLookup[$monthKey] ?? 0;
            $value = ($month > 0 && checkdate($month, $day, $year))
                ? sprintf('%04d-%02d-%02d', $year, $month, $day)
                : null;
            add_document_date_candidate($candidates, $seen, $value, $raw, $offsetBase + $start, 'd_mon_y');
        }
    }

    usort($candidates, static function (array $a, array $b): int {
        $startCompare = ((int) ($a['start'] ?? 0)) <=> ((int) ($b['start'] ?? 0));
        if ($startCompare !== 0) {
            return $startCompare;
        }
        return strcmp((string) ($a['value'] ?? ''), (string) ($b['value'] ?? ''));
    });

    return $candidates;
}

function normalize_document_date_lookup_text(string $text): string
{
    $normalized = lowercase_text(trim($text));
    $normalized = strtr($normalized, [
        'å' => 'a',
        'ä' => 'a',
        'ö' => 'o',
        'á' => 'a',
        'à' => 'a',
        'â' => 'a',
        'ã' => 'a',
        'æ' => 'ae',
        'ç' => 'c',
        'č' => 'c',
        'ď' => 'd',
        'ð' => 'd',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ě' => 'e',
        'í' => 'i',
        'ì' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ľ' => 'l',
        'ĺ' => 'l',
        'ł' => 'l',
        'ñ' => 'n',
        'ň' => 'n',
        'ó' => 'o',
        'ò' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ø' => 'o',
        'œ' => 'oe',
        'ŕ' => 'r',
        'ř' => 'r',
        'š' => 's',
        'ß' => 'ss',
        'ť' => 't',
        'þ' => 'th',
        'ú' => 'u',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ů' => 'u',
        'ý' => 'y',
        'ÿ' => 'y',
        'ž' => 'z',
    ]);
    $normalized = preg_replace('/[^a-z0-9]+/u', ' ', $normalized);
    if (!is_string($normalized)) {
        return '';
    }
    return normalize_inline_whitespace($normalized);
}

function document_date_reference_localities(): array
{
    static $cached = null;
    if (is_array($cached)) {
        return $cached;
    }

    $cached = [];
    $path = PROJECT_ROOT . '/public/assets/reference/svenska-tatorter-over-500-invanare-2023.txt';
    if (!is_file($path)) {
        return $cached;
    }

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $cached;
    }

    foreach ($lines as $line) {
        if (!is_string($line)) {
            continue;
        }
        $name = trim($line);
        if ($name === '') {
            continue;
        }
        $normalized = normalize_document_date_lookup_text($name);
        if ($normalized === '') {
            continue;
        }
        $cached[$normalized] = $name;
    }

    uksort($cached, static function (string $a, string $b): int {
        $lenCompare = strlen($b) <=> strlen($a);
        if ($lenCompare !== 0) {
            return $lenCompare;
        }
        return strcmp($a, $b);
    });

    return $cached;
}

function document_date_label_definitions(): array
{
    return [
        [
            'code' => 'invoice_date_label',
            'score' => -110,
            'detail' => 'fakturadatum',
            'patterns' => ['fakturadatum'],
        ],
        [
            'code' => 'due_date_label',
            'score' => -110,
            'detail' => 'förfallodatum/förfaller',
            'patterns' => ['forfallodatum', 'forfaller'],
        ],
        [
            'code' => 'decision_date_label',
            'score' => -110,
            'detail' => 'beslutsdatum',
            'patterns' => ['beslutsdatum'],
        ],
        [
            'code' => 'payment_date_label',
            'score' => -95,
            'detail' => 'betalningsdatum/betalas senast',
            'patterns' => ['betalningsdatum', 'betalas senast', 'att betala senast'],
        ],
        [
            'code' => 'payment_out_date_label',
            'score' => -100,
            'detail' => 'utbetalningsdatum',
            'patterns' => ['utbetalningsdatum'],
        ],
        [
            'code' => 'dispatch_date_label',
            'score' => -90,
            'detail' => 'utskicksdatum/utskriftsdatum',
            'patterns' => ['utskicksdatum', 'utskriftsdatum'],
        ],
        [
            'code' => 'accounting_date_label',
            'score' => -60,
            'detail' => 'bokföringsdatum/verifikationsdatum',
            'patterns' => ['bokforingsdatum', 'verifikationsdatum'],
        ],
        [
            'code' => 'period_label',
            'score' => -45,
            'detail' => 'period',
            'patterns' => ['period', 'galler for tiden'],
        ],
        [
            'code' => 'generic_date_label',
            'score' => -18,
            'detail' => 'datum',
            'patterns' => ['datum'],
        ],
    ];
}

function previous_nonempty_line_index(array $lines, int $index, int $distance = 2): ?int
{
    for ($step = 1; $step <= $distance; $step++) {
        $candidateIndex = $index - $step;
        if (!array_key_exists($candidateIndex, $lines)) {
            continue;
        }
        $line = is_string($lines[$candidateIndex] ?? null) ? trim((string) $lines[$candidateIndex]) : '';
        if ($line !== '') {
            return $candidateIndex;
        }
    }

    return null;
}

function document_date_context_label_signals(string $normalizedContext, string $contextKey): array
{
    if ($normalizedContext === '') {
        return [];
    }

    $matches = [];
    foreach (document_date_label_definitions() as $definition) {
        if (!is_array($definition)) {
            continue;
        }

        $patterns = is_array($definition['patterns'] ?? null) ? $definition['patterns'] : [];
        foreach ($patterns as $pattern) {
            if (!is_string($pattern) || $pattern === '') {
                continue;
            }
            $quoted = preg_quote($pattern, '/');
            if (@preg_match('/\b' . $quoted . '\b/u', $normalizedContext) !== 1) {
                continue;
            }

            $matches[] = [
                'code' => is_string($definition['code'] ?? null) ? (string) $definition['code'] : 'context_label',
                'score' => (int) ($definition['score'] ?? 0),
                'detail' => is_string($definition['detail'] ?? null) ? (string) $definition['detail'] : $pattern,
                'context' => $contextKey,
            ];
            break;
        }
    }

    return $matches;
}

function document_date_place_match(string $prefixText): ?array
{
    $normalizedPrefix = normalize_document_date_lookup_text($prefixText);
    if ($normalizedPrefix === '') {
        return null;
    }

    $best = null;
    foreach (document_date_reference_localities() as $normalizedPlace => $rawPlace) {
        $quoted = preg_quote($normalizedPlace, '/');
        if (@preg_match('/\b' . $quoted . '\b(?:\s+\w+){0,3}\s*$/u', $normalizedPrefix, $matches) !== 1) {
            continue;
        }
        $suffix = is_string($matches[0] ?? null) ? trim((string) $matches[0]) : '';
        if ($suffix === '') {
            continue;
        }

        $suffixWithoutPlace = trim(preg_replace('/\b' . $quoted . '\b/u', '', $suffix, 1));
        $gapWords = $suffixWithoutPlace === '' ? 0 : count(preg_split('/\s+/u', $suffixWithoutPlace));
        if ($gapWords < 0 || $gapWords > 3) {
            continue;
        }

        $candidate = [
            'name' => $rawPlace,
            'normalized' => $normalizedPlace,
            'gapWords' => $gapWords,
        ];
        if (!is_array($best)) {
            $best = $candidate;
            continue;
        }
        $bestGap = (int) ($best['gapWords'] ?? 99);
        if ($gapWords < $bestGap) {
            $best = $candidate;
            continue;
        }
        if ($gapWords === $bestGap && strlen($normalizedPlace) > strlen((string) ($best['normalized'] ?? ''))) {
            $best = $candidate;
        }
    }

    return $best;
}

function is_document_title_line(string $text): bool
{
    $normalized = normalize_inline_whitespace($text);
    $textLength = function_exists('mb_strlen') ? mb_strlen($normalized) : strlen($normalized);
    if ($normalized === '' || $textLength > 48) {
        return false;
    }
    if (@preg_match('/[:;]/u', $normalized) === 1) {
        return false;
    }
    if (count_pattern_matches('/\d/u', $normalized) > 0) {
        return false;
    }
    if (count_pattern_matches('/\pL/u', $normalized) < 3) {
        return false;
    }
    $lookup = normalize_document_date_lookup_text($normalized);
    $headerishPatterns = [
        'fakturanummer',
        'org nr',
        'pers nr',
        'referensnummer',
        'belopp',
        'moms',
        'datum',
        'handelse',
        'ocr',
        'forfallodatum',
    ];
    foreach ($headerishPatterns as $pattern) {
        if (@preg_match('/\b' . preg_quote($pattern, '/') . '\b/u', $lookup) === 1) {
            return false;
        }
    }
    return true;
}

function score_document_date_candidate(array $candidate, array $lines): array
{
    $lineIndex = is_int($candidate['lineIndex'] ?? null) ? (int) $candidate['lineIndex'] : -1;
    $line = is_string($candidate['line'] ?? null) ? (string) $candidate['line'] : '';
    $raw = is_string($candidate['raw'] ?? null) ? trim((string) $candidate['raw']) : '';
    $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;

    $result = $candidate;
    $result['signals'] = [];
    $result['excluded'] = false;
    $result['excludedReason'] = null;
    $result['score'] = 0;

    if ($lineIndex < 0 || $line === '' || $raw === '' || $start < 0) {
        return $result;
    }

    $prefix = substr($line, 0, $start);
    $prefix = is_string($prefix) ? $prefix : '';
    $suffix = substr($line, $start + strlen($raw));
    $suffix = is_string($suffix) ? $suffix : '';
    $previousLineIndex = previous_nonempty_line_index($lines, $lineIndex, 2);
    $previousLine = $previousLineIndex !== null && is_string($lines[$previousLineIndex] ?? null)
        ? (string) $lines[$previousLineIndex]
        : '';
    $normalizedPrefix = normalize_document_date_lookup_text($prefix);
    $normalizedLine = normalize_document_date_lookup_text($line);
    $normalizedSuffix = normalize_document_date_lookup_text($suffix);
    $previousNormalized = normalize_document_date_lookup_text($previousLine);
    $result['sameLinePrefix'] = normalize_inline_whitespace($prefix);
    $result['sameLineSuffix'] = normalize_inline_whitespace($suffix);
    $result['previousLine'] = normalize_inline_whitespace($previousLine);

    $score = 0;
    $signals = [];

    $placeMatch = document_date_place_match($prefix);
    if (is_array($placeMatch)) {
        $score += 100;
        $signals[] = [
            'type' => 'positive',
            'code' => 'place_before_date',
            'score' => 100,
            'detail' => $placeMatch['name'],
        ];
        $result['matchedPlace'] = $placeMatch['name'];
        $result['matchedPlaceGapWords'] = (int) ($placeMatch['gapWords'] ?? 0);
    }

    $placeAboveMatch = $previousLine !== '' ? document_date_place_match($previousLine) : null;
    if (is_array($placeAboveMatch)) {
        $score += 90;
        $signals[] = [
            'type' => 'positive',
            'code' => 'place_above_date',
            'score' => 90,
            'detail' => $placeAboveMatch['name'],
        ];
        $result['matchedPlaceAbove'] = $placeAboveMatch['name'];
        $result['matchedPlaceAboveGapWords'] = (int) ($placeAboveMatch['gapWords'] ?? 0);
    }

    if ($lineIndex <= 14) {
        $score += 50;
        $signals[] = [
            'type' => 'positive',
            'code' => 'high_in_document',
            'score' => 50,
            'detail' => 'line:' . ($lineIndex + 1),
        ];
    }

    if ($previousLine !== '' && $lineIndex <= 20) {
        $score += 10;
        $signals[] = [
            'type' => 'positive',
            'code' => 'context_above_date',
            'score' => 10,
            'detail' => 'line:' . ($previousLineIndex !== null ? ($previousLineIndex + 1) : $lineIndex),
        ];
    }

    $headerWindowStart = max(0, $lineIndex - 3);
    $headerWindowEnd = min(count($lines) - 1, $lineIndex + 1);
    $headerPatterns = ['till', 'fran', 'handlaggare', 'diarienummer', 'avdelning'];
    $headerMatched = null;
    for ($i = $headerWindowStart; $i <= $headerWindowEnd; $i++) {
        $windowLine = is_string($lines[$i] ?? null) ? (string) $lines[$i] : '';
        $normalizedWindowLine = normalize_document_date_lookup_text($windowLine);
        foreach ($headerPatterns as $pattern) {
            if (@preg_match('/\b' . preg_quote($pattern, '/') . '\b/u', $normalizedWindowLine) === 1) {
                $headerMatched = $pattern;
                break 2;
            }
        }
    }
    if ($headerMatched !== null && $lineIndex <= 24) {
        $score += 40;
        $signals[] = [
            'type' => 'positive',
            'code' => 'letterhead_context',
            'score' => 40,
            'detail' => $headerMatched,
        ];
    }

    $trimmedLine = normalize_inline_whitespace($line);
    if (
        $trimmedLine === $raw
        || $trimmedLine === ('den ' . $raw)
    ) {
        $score += 30;
        $signals[] = [
            'type' => 'positive',
            'code' => 'single_date_line',
            'score' => 30,
            'detail' => $trimmedLine,
        ];
    }

    if ($previousLine !== '' && is_document_title_line($previousLine)) {
        $score += 30;
        $signals[] = [
            'type' => 'positive',
            'code' => 'near_title',
            'score' => 30,
            'detail' => normalize_inline_whitespace($previousLine),
        ];
    }

    $contextSignals = [];
    foreach (document_date_context_label_signals($normalizedPrefix, 'same_line_prefix') as $signal) {
        $contextSignals[$signal['code'] . '::' . $signal['context']] = $signal;
    }
    foreach (document_date_context_label_signals($normalizedSuffix, 'same_line_suffix') as $signal) {
        $contextSignals[$signal['code'] . '::' . $signal['context']] = $signal;
    }
    foreach (document_date_context_label_signals($previousNormalized, 'line_above') as $signal) {
        $contextSignals[$signal['code'] . '::' . $signal['context']] = $signal;
    }
    foreach (document_date_context_label_signals($normalizedLine, 'same_line') as $signal) {
        $existingPrefix = $signal['code'] . '::same_line_prefix';
        $existingSuffix = $signal['code'] . '::same_line_suffix';
        $existingAbove = $signal['code'] . '::line_above';
        if (isset($contextSignals[$existingPrefix]) || isset($contextSignals[$existingSuffix]) || isset($contextSignals[$existingAbove])) {
            continue;
        }
        $contextSignals[$signal['code'] . '::' . $signal['context']] = $signal;
    }
    foreach (array_values($contextSignals) as $signal) {
        $score += (int) ($signal['score'] ?? 0);
        $signals[] = [
            'type' => ((int) ($signal['score'] ?? 0)) >= 0 ? 'positive' : 'negative',
            'code' => (string) ($signal['code'] ?? 'context_label'),
            'score' => (int) ($signal['score'] ?? 0),
            'detail' => (string) ($signal['detail'] ?? ''),
            'context' => (string) ($signal['context'] ?? ''),
        ];
    }

    $longIdentifierCount = count_pattern_matches('/\b\d{6,}\b/u', $line)
        + count_pattern_matches('/\b\d{2,6}-\d{4,}\b/u', $line);
    if (!is_array($placeMatch) && $headerMatched === null && $longIdentifierCount >= 2) {
        $score -= 60;
        $signals[] = [
            'type' => 'negative',
            'code' => 'identifier_row',
            'score' => -60,
            'detail' => 'long_identifiers:' . $longIdentifierCount,
        ];
    }

    $lineCount = count($lines);
    if ($lineIndex >= 100 || ($lineCount > 0 && $lineIndex >= max(100, $lineCount - 12))) {
        $score -= 30;
        $signals[] = [
            'type' => 'negative',
            'code' => 'late_in_document',
            'score' => -30,
            'detail' => 'line:' . ($lineIndex + 1),
        ];
    }

    $result['score'] = $score;
    $result['signals'] = $signals;
    return $result;
}

function extract_document_date_field_result(array $lines): array
{
    $candidates = [];
    foreach ($lines as $lineIndex => $line) {
        if (!is_string($line)) {
            continue;
        }
        $trimmedLine = trim($line);
        if ($trimmedLine === '') {
            continue;
        }
        $lineCandidates = document_date_candidates_from_text($line, 0);
        foreach ($lineCandidates as $candidate) {
            $candidate['lineIndex'] = is_int($lineIndex) ? $lineIndex : 0;
            $candidate['line'] = $line;
            $candidates[] = score_document_date_candidate($candidate, $lines);
        }
    }

    usort($candidates, static function (array $a, array $b): int {
        $excludedCompare = ((bool) ($a['excluded'] ?? false)) <=> ((bool) ($b['excluded'] ?? false));
        if ($excludedCompare !== 0) {
            return $excludedCompare;
        }
        $scoreCompare = ((int) ($b['score'] ?? 0)) <=> ((int) ($a['score'] ?? 0));
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }
        $lineCompare = ((int) ($a['lineIndex'] ?? 0)) <=> ((int) ($b['lineIndex'] ?? 0));
        if ($lineCompare !== 0) {
            return $lineCompare;
        }
        return ((int) ($a['start'] ?? 0)) <=> ((int) ($b['start'] ?? 0));
    });

    $selected = null;
    foreach ($candidates as $candidate) {
        if (($candidate['excluded'] ?? false) === true) {
            continue;
        }
        if ((int) ($candidate['score'] ?? 0) <= 0) {
            continue;
        }
        $selected = $candidate;
        break;
    }

    if (!is_array($selected)) {
        return [
            'value' => null,
            'confidence' => 0.0,
            'lineIndex' => null,
            'source' => 'document_date_heuristic',
            'raw' => null,
            'selectedValue' => null,
            'selectedCandidate' => null,
            'candidates' => $candidates,
        ];
    }

    $score = (int) ($selected['score'] ?? 0);
    return [
        'value' => is_string($selected['value'] ?? null) ? (string) $selected['value'] : null,
        'confidence' => clamp_confidence($score / 180),
        'lineIndex' => is_int($selected['lineIndex'] ?? null) ? (int) $selected['lineIndex'] : null,
        'source' => 'document_date_heuristic',
        'raw' => is_string($selected['raw'] ?? null) ? (string) $selected['raw'] : null,
        'selectedValue' => is_string($selected['value'] ?? null) ? (string) $selected['value'] : null,
        'selectedCandidate' => $selected,
        'candidates' => $candidates,
    ];
}

function amount_candidates_from_text(string $text, int $offsetBase = 0): array
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

function iban_candidates_from_text(string $text, int $offsetBase = 0): array
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

function swift_candidates_from_text(string $text, int $offsetBase = 0): array
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

function payment_receiver_candidates_from_text(string $text, int $offsetBase = 0): array
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

function generic_text_segment_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $trimmed = normalize_inline_whitespace($text);
    if ($trimmed === '') {
        return [];
    }

    $candidates = [];
    $matches = [];
    if (@preg_match_all('/([^\s].*?)(?=\s{2,}|$)/u', $text, $matches, PREG_OFFSET_CAPTURE) >= 1) {
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
            if ($value === '') {
                continue;
            }

            $candidates[] = [
                'value' => $value,
                'raw' => $raw,
                'start' => $offsetBase + $start,
            ];
        }
    }

    if ($candidates !== []) {
        return $candidates;
    }

    return [[
        'value' => $trimmed,
        'raw' => $trimmed,
        'start' => $offsetBase,
    ]];
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

function empty_extraction_field_result(): array
{
    return [
        'value' => null,
        'confidence' => 0.0,
        'lineIndex' => null,
        'source' => 'none',
        'raw' => null,
    ];
}

function extract_ocr_number_field_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['ocr-nummer', 'ocr nummer', 'ocr'], $replacementMap, 'ocr_number_candidates_from_text', 1);
    if (($result['value'] ?? null) === null) {
        return empty_extraction_field_result();
    }

    $lineIndex = is_int($result['lineIndex'] ?? null) ? (int) $result['lineIndex'] : -1;
    $line = $lineIndex >= 0 ? (string) ($lines[$lineIndex] ?? '') : '';
    $normalizedLine = normalize_for_matching($line, $replacementMap);
    if (str_contains($normalizedLine, 'iban') || str_contains($normalizedLine, 'swift')) {
        $result['confidence'] = clamp_confidence(((float) ($result['confidence'] ?? 0.0)) - 0.35);
    }

    return $result;
}

function extract_bankgiro_field_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['bankgiro', 'bg'], $replacementMap, 'bankgiro_candidates_from_text', 2);
    return ($result['value'] ?? null) !== null ? $result : empty_extraction_field_result();
}

function extract_plusgiro_field_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['plusgiro', 'pg'], $replacementMap, 'plusgiro_candidates_from_text', 2);
    return ($result['value'] ?? null) !== null ? $result : empty_extraction_field_result();
}

function extract_due_date_field_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate(
        $lines,
        ['förfallodatum', 'forfallodatum', 'förfaller', 'forfaller', 'att betala senast'],
        $replacementMap,
        'due_date_candidates_from_text',
        2
    );
    return ($result['value'] ?? null) !== null ? $result : empty_extraction_field_result();
}

function extract_amount_field_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate(
        $lines,
        ['fakturabelopp', 'att betala', 'summa att betala', 'belopp att betala', 'total att betala'],
        $replacementMap,
        'amount_candidates_from_text',
        2
    );
    return ($result['value'] ?? null) !== null ? $result : empty_extraction_field_result();
}

function extract_payment_receiver_field_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['betalningsmottagare', 'mottagare'], $replacementMap, 'payment_receiver_candidates_from_text', 1);
    return ($result['value'] ?? null) !== null ? $result : empty_extraction_field_result();
}

function extract_supplier_field_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate(
        $lines,
        ['leverantör', 'leverantor'],
        $replacementMap,
        'payment_receiver_candidates_from_text',
        1
    );
    return ($result['value'] ?? null) !== null ? $result : empty_extraction_field_result();
}

function extract_iban_field_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['iban'], $replacementMap, 'iban_candidates_from_text', 1);
    return ($result['value'] ?? null) !== null ? $result : empty_extraction_field_result();
}

function extract_swift_field_result(array $lines, array $replacementMap): array
{
    $result = select_best_labeled_candidate($lines, ['swift'], $replacementMap, 'swift_candidates_from_text', 1);
    return ($result['value'] ?? null) !== null ? $result : empty_extraction_field_result();
}

function extract_configured_text_field_results(array $lines, array $replacementMap, array $fields): array
{
    $results = [];

    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }

        $key = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        $name = is_string($field['name'] ?? null) ? trim((string) $field['name']) : '';
        $searchString = is_string($field['searchString'] ?? null) ? trim((string) $field['searchString']) : '';
        $extractor = valid_extraction_field_extractor(
            is_string($field['extractor'] ?? null) ? (string) $field['extractor'] : 'generic_label'
        );
        if ($key === '' || $name === '' || ($searchString === '' && $extractor === 'generic_label')) {
            continue;
        }

        if ($extractor === 'amount') {
            $result = extract_amount_field_result($lines, $replacementMap);
        } elseif ($extractor === 'due_date') {
            $result = extract_due_date_field_result($lines, $replacementMap);
        } elseif ($extractor === 'document_date') {
            $result = extract_document_date_field_result($lines);
        } elseif ($extractor === 'bankgiro') {
            $result = extract_bankgiro_field_result($lines, $replacementMap);
        } elseif ($extractor === 'plusgiro') {
            $result = extract_plusgiro_field_result($lines, $replacementMap);
        } elseif ($extractor === 'supplier') {
            $result = extract_supplier_field_result($lines, $replacementMap);
        } elseif ($extractor === 'payment_receiver') {
            $result = extract_payment_receiver_field_result($lines, $replacementMap);
        } elseif ($extractor === 'iban') {
            $result = extract_iban_field_result($lines, $replacementMap);
        } elseif ($extractor === 'swift') {
            $result = extract_swift_field_result($lines, $replacementMap);
        } elseif ($extractor === 'ocr') {
            $result = extract_ocr_number_field_result($lines, $replacementMap);
        } else {
            $result = select_best_labeled_candidate(
                $lines,
                [$searchString],
                $replacementMap,
                'generic_text_segment_candidates_from_text',
                1
            );
        }

        if (
            ($result['value'] ?? null) === null
            && !array_key_exists('selectedValue', $result)
            && !is_array($result['candidates'] ?? null)
        ) {
            $result = empty_extraction_field_result();
        }

        $results[$key] = [
            'key' => $key,
            'name' => $name,
            'searchString' => $searchString,
            'extractor' => $extractor,
            'value' => match (true) {
                is_string($result['value'] ?? null) => trim((string) $result['value']),
                is_int($result['value'] ?? null), is_float($result['value'] ?? null) => $result['value'] + 0,
                is_bool($result['value'] ?? null) => (bool) $result['value'],
                default => null,
            },
            'confidence' => isset($result['confidence']) ? clamp_confidence((float) $result['confidence']) : 0.0,
            'lineIndex' => is_int($result['lineIndex'] ?? null) ? (int) $result['lineIndex'] : null,
            'source' => is_string($result['source'] ?? null) ? (string) $result['source'] : 'none',
            'raw' => is_string($result['raw'] ?? null) ? (string) $result['raw'] : null,
        ];
        if (array_key_exists('selectedValue', $result)) {
            $results[$key]['selectedValue'] = $result['selectedValue'];
        }
        if (is_array($result['selectedCandidate'] ?? null)) {
            $results[$key]['selectedCandidate'] = $result['selectedCandidate'];
        }
        if (is_array($result['candidates'] ?? null)) {
            $results[$key]['candidates'] = $result['candidates'];
        }
    }

    return $results;
}

function simplify_extraction_field_values(array $results): array
{
    $values = [];
    foreach ($results as $key => $result) {
        $resolvedKey = is_string($key) && trim($key) !== ''
            ? trim($key)
            : (is_string($result['key'] ?? null) ? trim((string) $result['key']) : '');
        if ($resolvedKey === '') {
            continue;
        }

        $value = is_array($result) ? ($result['value'] ?? null) : null;
        if ($value === null) {
            continue;
        }
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }
        }

        $values[$resolvedKey] = $value;
    }

    return $values;
}

function simplify_extraction_field_meta(array $results): array
{
    $meta = [];
    foreach ($results as $key => $result) {
        $resolvedKey = is_string($key) && trim($key) !== ''
            ? trim($key)
            : (is_string($result['key'] ?? null) ? trim((string) $result['key']) : '');
        if ($resolvedKey === '' || !is_array($result)) {
            continue;
        }

        $fieldMeta = [];
        if (array_key_exists('selectedValue', $result)) {
            $fieldMeta['selectedValue'] = $result['selectedValue'];
        }
        if (is_array($result['selectedCandidate'] ?? null)) {
            $fieldMeta['selectedCandidate'] = $result['selectedCandidate'];
        }
        if (is_array($result['candidates'] ?? null)) {
            $fieldMeta['candidates'] = $result['candidates'];
        }

        if ($fieldMeta !== []) {
            $meta[$resolvedKey] = $fieldMeta;
        }
    }

    return $meta;
}

function find_sender_by_id(array $senders, int $senderId): ?array
{
    if ($senderId < 1) {
        return null;
    }

    foreach ($senders as $sender) {
        if (!is_array($sender)) {
            continue;
        }
        if ((int) ($sender['id'] ?? 0) === $senderId) {
            return $sender;
        }
    }

    return null;
}

function top_category_match(array $matches): ?array
{
    $best = null;
    foreach ($matches as $match) {
        if (!is_array($match)) {
            continue;
        }
        $categoryId = is_string($match['id'] ?? null) ? trim((string) $match['id']) : '';
        $score = $match['score'] ?? null;
        $numericScore = is_int($score) || is_float($score) || (is_string($score) && is_numeric($score))
            ? (float) $score
            : null;
        if ($categoryId === '' || $numericScore === null || $numericScore <= 0) {
            continue;
        }
        if ($best === null || $numericScore > (float) ($best['score'] ?? 0)) {
            $best = $match;
        }
    }

    return is_array($best) ? $best : null;
}

function partition_archiving_field_values(array $values, array $rules): array
{
    $fieldKeys = [];
    foreach (is_array($rules['fields'] ?? null) ? $rules['fields'] : [] as $field) {
        if (!is_array($field)) {
            continue;
        }
        $key = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        if ($key !== '') {
            $fieldKeys[$key] = true;
        }
    }
    foreach (is_array($rules['predefinedFields'] ?? null) ? $rules['predefinedFields'] : [] as $field) {
        if (!is_array($field)) {
            continue;
        }
        $key = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        if ($key !== '') {
            $fieldKeys[$key] = true;
        }
    }

    $systemFieldKeys = [];
    foreach (is_array($rules['systemFields'] ?? null) ? $rules['systemFields'] : [] as $field) {
        if (!is_array($field)) {
            continue;
        }
        $key = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        if ($key !== '') {
            $systemFieldKeys[$key] = true;
        }
    }

    $fields = [];
    $systemFields = [];
    foreach ($values as $key => $value) {
        $resolvedKey = is_string($key) ? trim($key) : '';
        if ($resolvedKey === '' || $value === null) {
            continue;
        }
        if (isset($systemFieldKeys[$resolvedKey])) {
            $systemFields[$resolvedKey] = $value;
            continue;
        }
        if (isset($fieldKeys[$resolvedKey])) {
            $fields[$resolvedKey] = $value;
        }
    }

    ksort($fields, SORT_NATURAL);
    ksort($systemFields, SORT_NATURAL);

    return [
        'fields' => $fields,
        'systemFields' => $systemFields,
    ];
}

function evaluate_filename_template_parts_backend(array $parts, array $fieldValues): string
{
    if ($parts === []) {
        return '';
    }

    $result = '';
    foreach ($parts as $part) {
        if (!is_array($part)) {
            continue;
        }

        $type = is_string($part['type'] ?? null) ? trim((string) $part['type']) : 'text';
        if ($type === 'dataField' || $type === 'systemField') {
            $key = is_string($part['key'] ?? null) ? trim((string) $part['key']) : '';
            $rawValue = $key !== '' && array_key_exists($key, $fieldValues)
                ? $fieldValues[$key]
                : null;
            $value = is_scalar($rawValue) || $rawValue === null
                ? trim((string) $rawValue)
                : '';
            if ($value === '') {
                continue;
            }
            $result .= evaluate_filename_template_parts_backend(
                is_array($part['prefixParts'] ?? null) ? $part['prefixParts'] : [],
                $fieldValues
            );
            $result .= $value;
            $result .= evaluate_filename_template_parts_backend(
                is_array($part['suffixParts'] ?? null) ? $part['suffixParts'] : [],
                $fieldValues
            );
            continue;
        }

        if ($type === 'category') {
            $value = array_key_exists('category', $fieldValues) ? trim((string) $fieldValues['category']) : '';
            if ($value === '') {
                continue;
            }
            $result .= evaluate_filename_template_parts_backend(
                is_array($part['prefixParts'] ?? null) ? $part['prefixParts'] : [],
                $fieldValues
            );
            $result .= $value;
            $result .= evaluate_filename_template_parts_backend(
                is_array($part['suffixParts'] ?? null) ? $part['suffixParts'] : [],
                $fieldValues
            );
            continue;
        }

        if ($type === 'labels') {
            $rawLabels = $fieldValues['__labels'] ?? null;
            $labelNames = is_array($rawLabels)
                ? array_values(array_filter(array_map(static function ($value): string {
                    return is_scalar($value) ? trim((string) $value) : '';
                }, $rawLabels), static fn (string $value): bool => $value !== ''))
                : [];
            $separator = is_string($part['separator'] ?? null) ? (string) $part['separator'] : ', ';
            $value = $labelNames !== [] ? implode($separator, $labelNames) : '';
            if ($value === '') {
                continue;
            }
            $result .= evaluate_filename_template_parts_backend(
                is_array($part['prefixParts'] ?? null) ? $part['prefixParts'] : [],
                $fieldValues
            );
            $result .= $value;
            $result .= evaluate_filename_template_parts_backend(
                is_array($part['suffixParts'] ?? null) ? $part['suffixParts'] : [],
                $fieldValues
            );
            continue;
        }

        if ($type === 'firstAvailable') {
            $resolved = '';
            foreach (is_array($part['parts'] ?? null) ? $part['parts'] : [] as $candidatePart) {
                $resolved = evaluate_filename_template_parts_backend([$candidatePart], $fieldValues);
                if ($resolved !== '') {
                    break;
                }
            }
            if ($resolved === '') {
                continue;
            }
            $result .= evaluate_filename_template_parts_backend(
                is_array($part['prefixParts'] ?? null) ? $part['prefixParts'] : [],
                $fieldValues
            );
            $result .= $resolved;
            $result .= evaluate_filename_template_parts_backend(
                is_array($part['suffixParts'] ?? null) ? $part['suffixParts'] : [],
                $fieldValues
            );
            continue;
        }

        $result .= is_string($part['value'] ?? null) ? (string) $part['value'] : '';
    }

    return $result;
}

function build_auto_archiving_filename_label_names(array $autoResult, array $rules): array
{
    $labelNameById = [];

    foreach (is_array($rules['systemLabels'] ?? null) ? $rules['systemLabels'] : [] as $label) {
        if (!is_array($label)) {
            continue;
        }
        $id = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
        $name = is_string($label['name'] ?? null) ? trim((string) $label['name']) : '';
        if ($id !== '' && $name !== '') {
            $labelNameById[$id] = $name;
        }
    }

    foreach (is_array($rules['labels'] ?? null) ? $rules['labels'] : [] as $label) {
        if (!is_array($label)) {
            continue;
        }
        $id = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
        $name = is_string($label['name'] ?? null) ? trim((string) $label['name']) : '';
        if ($id !== '' && $name !== '') {
            $labelNameById[$id] = $name;
        }
    }

    $labelNames = [];
    foreach (is_array($autoResult['labels'] ?? null) ? $autoResult['labels'] : [] as $labelId) {
        $resolvedId = is_string($labelId) ? trim((string) $labelId) : '';
        if ($resolvedId === '') {
            continue;
        }
        $labelNames[] = $labelNameById[$resolvedId] ?? $resolvedId;
    }

    return array_values(array_filter($labelNames, static fn (string $value): bool => $value !== ''));
}

function build_auto_archiving_filename_field_values(array $autoResult, array $senders, array $categoriesById, array $rules): array
{
    $values = [];
    $setValue = static function (array &$target, string $key, mixed $value): void {
        if ($value === null || $value === '') {
            return;
        }
        $text = trim((string) $value);
        if ($text !== '') {
            $target[$key] = $text;
        }
    };

    $categoryId = is_string($autoResult['categoryId'] ?? null) ? trim((string) $autoResult['categoryId']) : '';
    $senderId = (int) ($autoResult['senderId'] ?? 0);
    $principalId = is_string($autoResult['principalId'] ?? null) ? trim((string) $autoResult['principalId']) : '';
    $fields = is_array($autoResult['fields'] ?? null) ? $autoResult['fields'] : [];
    $systemFields = is_array($autoResult['systemFields'] ?? null) ? $autoResult['systemFields'] : [];
    $allFields = array_merge($fields, $systemFields);

    $category = $categoryId !== '' ? ($categoriesById[$categoryId] ?? null) : null;
    $sender = $senderId > 0 ? find_sender_by_id($senders, $senderId) : null;

    $setValue($values, 'category', is_array($category) ? ($category['name'] ?? null) : null);
    $setValue($values, 'client', $principalId);
    $setValue($values, 'main_client', $principalId);
    $setValue($values, 'sender', is_array($sender) ? ($sender['name'] ?? null) : null);

    if (array_key_exists('amount', $allFields)) {
        $amount = $allFields['amount'];
        if (is_numeric($amount)) {
            $setValue($values, 'amount', number_format((float) $amount, 2, ',', ' '));
        } else {
            $setValue($values, 'amount', $amount);
        }
    }

    foreach ([
        'supplier',
        'payment_receiver',
        'ocr',
        'due_date',
        'date',
        'swift',
        'iban',
        'document_date',
    ] as $fieldKey) {
        $lookupKey = $fieldKey === 'date' ? 'due_date' : $fieldKey;
        if (array_key_exists($lookupKey, $allFields)) {
            $setValue($values, $fieldKey, $allFields[$lookupKey]);
        }
    }
    if (array_key_exists('payment_receiver', $allFields)) {
        $setValue($values, 'payee', $allFields['payment_receiver']);
    }

    foreach ($allFields as $fieldKey => $fieldValue) {
        $resolvedKey = is_string($fieldKey) ? trim($fieldKey) : '';
        if ($resolvedKey === '') {
            continue;
        }
        $setValue($values, $resolvedKey, $fieldValue);
    }

    $labelNames = build_auto_archiving_filename_label_names($autoResult, $rules);
    if ($labelNames !== []) {
        $values['__labels'] = $labelNames;
    }

    return $values;
}

function generate_auto_archiving_filename(array $job, array $autoResult, array $rules, array $senders): string
{
    $categoriesById = [];
    foreach (build_categories_from_archive_folders(is_array($rules['archiveFolders'] ?? null) ? $rules['archiveFolders'] : []) as $category) {
        if (!is_array($category)) {
            continue;
        }
        $categoryId = is_string($category['id'] ?? null) ? trim((string) $category['id']) : '';
        if ($categoryId !== '') {
            $categoriesById[$categoryId] = $category;
        }
    }

    $categoryId = is_string($autoResult['categoryId'] ?? null) ? trim((string) $autoResult['categoryId']) : '';
    $category = $categoryId !== '' ? ($categoriesById[$categoryId] ?? null) : null;
    $template = is_array($category) ? normalize_filename_template($category['filenameTemplate'] ?? null) : normalize_filename_template(null);
    $rendered = trim(preg_replace(
        '/\s+/',
        ' ',
        evaluate_filename_template_parts_backend(
            is_array($template['parts'] ?? null) ? $template['parts'] : [],
            build_auto_archiving_filename_field_values($autoResult, $senders, $categoriesById, $rules)
        )
    ) ?? '');

    if ($rendered !== '') {
        return str_ends_with(lowercase_text($rendered), '.pdf') ? $rendered : ($rendered . '.pdf');
    }

    $fallback = is_string($job['originalFilename'] ?? null) ? trim((string) $job['originalFilename']) : '';
    return sanitize_pdf_filename($fallback !== '' ? $fallback : 'dokument.pdf');
}

function normalize_auto_archiving_result(array $result): array
{
    $labels = array_values(array_filter(array_map(static function ($value): string {
        return is_string($value) ? trim($value) : '';
    }, is_array($result['labels'] ?? null) ? $result['labels'] : []), static fn (string $value): bool => $value !== ''));

    $fields = [];
    foreach (is_array($result['fields'] ?? null) ? $result['fields'] : [] as $key => $value) {
        $resolvedKey = is_string($key) ? trim($key) : '';
        $normalizedValue = normalize_auto_archiving_field_value($value);
        if ($resolvedKey === '' || $normalizedValue === null) {
            continue;
        }
        $fields[$resolvedKey] = $normalizedValue;
    }

    $systemFields = [];
    foreach (is_array($result['systemFields'] ?? null) ? $result['systemFields'] : [] as $key => $value) {
        $resolvedKey = is_string($key) ? trim($key) : '';
        $normalizedValue = normalize_auto_archiving_field_value($value);
        if ($resolvedKey === '' || $normalizedValue === null) {
            continue;
        }
        $systemFields[$resolvedKey] = $normalizedValue;
    }

    ksort($fields, SORT_NATURAL);
    ksort($systemFields, SORT_NATURAL);

    return [
        'principalId' => is_string($result['principalId'] ?? null) ? trim((string) $result['principalId']) : null,
        'senderId' => isset($result['senderId']) && (int) $result['senderId'] > 0 ? (int) $result['senderId'] : null,
        'categoryId' => is_string($result['categoryId'] ?? null) ? trim((string) $result['categoryId']) : null,
        'labels' => array_values(array_unique($labels)),
        'fields' => $fields,
        'systemFields' => $systemFields,
        'filename' => is_string($result['filename'] ?? null) ? trim((string) $result['filename']) : null,
        'archiveFolderId' => is_string($result['archiveFolderId'] ?? null) ? trim((string) $result['archiveFolderId']) : null,
        'archiveFolderPath' => is_string($result['archiveFolderPath'] ?? null) ? trim((string) $result['archiveFolderPath']) : null,
    ];
}

function normalize_auto_archiving_field_value(mixed $value): mixed
{
    if ($value === null) {
        return null;
    }

    if (is_string($value)) {
        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }

    if (is_int($value) || is_float($value)) {
        return trim((string) $value);
    }

    if (is_bool($value)) {
        return $value;
    }

    if (is_scalar($value)) {
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    return $value;
}

function calculate_auto_archiving_result_from_text(
    string $ocrText,
    array $job,
    array $rules,
    array $clients,
    array $senders,
    array $replacementMap
): array {
    $systemLabels = is_array($rules['systemLabels'] ?? null) ? $rules['systemLabels'] : [];
    $labels = is_array($rules['labels'] ?? null) ? $rules['labels'] : [];
    $categories = build_categories_from_archive_folders(is_array($rules['archiveFolders'] ?? null) ? $rules['archiveFolders'] : []);
    $configuredFields = array_values(array_merge(
        is_array($rules['predefinedFields'] ?? null) ? $rules['predefinedFields'] : [],
        is_array($rules['systemFields'] ?? null) ? $rules['systemFields'] : [],
        is_array($rules['fields'] ?? null) ? $rules['fields'] : []
    ));

    $systemLabelMatches = find_incremental_label_matches($ocrText, $systemLabels, $replacementMap);
    $configuredFieldResults = extract_configured_text_field_results(
        split_lines_for_matching($ocrText),
        $replacementMap,
        $configuredFields
    );
    $configuredFieldValues = simplify_extraction_field_values($configuredFieldResults);
    $configuredFieldMeta = simplify_extraction_field_meta($configuredFieldResults);
    $fieldPartitions = partition_archiving_field_values($configuredFieldValues, $rules);

    $orgNumber = detect_org_number_from_ocr_text($ocrText);
    $bankgiroValue = is_string($configuredFieldValues['bankgiro'] ?? null)
        ? trim((string) $configuredFieldValues['bankgiro'])
        : null;
    $plusgiroValue = is_string($configuredFieldValues['plusgiro'] ?? null)
        ? trim((string) $configuredFieldValues['plusgiro'])
        : null;
    $senderLookup = sender_lookup_result(
        $orgNumber,
        $bankgiroValue !== '' ? $bankgiroValue : null,
        $plusgiroValue !== '' ? $plusgiroValue : null
    );

    $matchedClientDirName = match_client_dir_name($ocrText, $clients);
    $preselectedClient = null;
    if (is_string($matchedClientDirName) && trim($matchedClientDirName) !== '') {
        $preselectedClient = [
            'dirName' => trim($matchedClientDirName),
        ];
    }

    $preselectedSender = null;
    $matchedSenderId = null;
    if (($senderLookup['matched'] ?? false) === true && is_array($senderLookup['sender'] ?? null)) {
        $sender = $senderLookup['sender'];
        $senderId = isset($sender['id']) ? (int) $sender['id'] : 0;
        if ($senderId > 0) {
            $matchedSenderId = $senderId;
            $preselectedSender = [
                'id' => $senderId,
                'name' => is_string($sender['name'] ?? null) ? trim((string) $sender['name']) : '',
                'matchedBy' => is_string($senderLookup['matchedBy'] ?? null) ? $senderLookup['matchedBy'] : null,
                'matchedValue' => is_string($senderLookup['matchedValue'] ?? null) ? $senderLookup['matchedValue'] : null,
            ];
        }
    }

    $labelMatches = find_incremental_label_matches($ocrText, $labels, $replacementMap, [
        'matchedLabelsById' => matched_labels_by_id($systemLabelMatches),
    ]);
    $categoryMatches = find_category_matches($ocrText, $categories, $replacementMap, [
        'matchedLabelsById' => matched_labels_by_id(array_merge($systemLabelMatches, $labelMatches)),
    ]);
    $resolvedLabels = resolved_label_ids_from_matches($systemLabelMatches, $labelMatches);
    $bestCategory = top_category_match($categoryMatches);

    $autoResult = normalize_auto_archiving_result([
        'principalId' => is_string($matchedClientDirName) && trim($matchedClientDirName) !== ''
            ? trim($matchedClientDirName)
            : null,
        'senderId' => $matchedSenderId,
        'categoryId' => is_array($bestCategory) ? ($bestCategory['id'] ?? null) : null,
        'labels' => $resolvedLabels,
        'fields' => $fieldPartitions['fields'],
        'systemFields' => $fieldPartitions['systemFields'],
        'archiveFolderId' => is_array($bestCategory) ? ($bestCategory['archiveFolderId'] ?? null) : null,
        'archiveFolderPath' => is_array($bestCategory) ? ($bestCategory['path'] ?? null) : null,
    ]);
    $autoResult['filename'] = generate_auto_archiving_filename($job, $autoResult, $rules, $senders);

    return [
        'matchedClientDirName' => $matchedClientDirName,
        'matchedSenderId' => $matchedSenderId,
        'preselectedClient' => $preselectedClient,
        'preselectedSender' => $preselectedSender,
        'senderLookup' => $senderLookup,
        'systemLabelMatches' => $systemLabelMatches,
        'labelMatches' => $labelMatches,
        'categoryMatches' => $categoryMatches,
        'labels' => $resolvedLabels,
        'extractionFieldResults' => $configuredFieldResults,
        'extractionFieldValues' => $configuredFieldValues,
        'extractionFieldMeta' => $configuredFieldMeta,
        'autoArchivingResult' => $autoResult,
    ];
}

function load_job_ocr_text(string $jobDir): string
{
    $ocrPath = $jobDir . '/ocr.txt';
    $raw = is_file($ocrPath) ? file_get_contents($ocrPath) : false;
    return is_string($raw) ? $raw : '';
}

function calculate_auto_archiving_result_for_job(array $config, string $jobId, ?array $rules = null, ?array $job = null): array
{
    if (!is_valid_job_id($jobId)) {
        throw new RuntimeException('Ogiltigt jobb-id');
    }

    $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
    $jobPath = $jobDir . DIRECTORY_SEPARATOR . 'job.json';
    $loadedJob = is_array($job) ? $job : load_json_file($jobPath);
    if (!is_array($loadedJob)) {
        throw new RuntimeException('Jobbet kunde inte läsas');
    }

    $ocrText = load_job_ocr_text($jobDir);
    $matchingPayload = load_matching_settings_payload();
    $replacementMap = replacement_map(is_array($matchingPayload['replacements'] ?? null) ? $matchingPayload['replacements'] : []);

    return calculate_auto_archiving_result_from_text(
        $ocrText,
        $loadedJob,
        is_array($rules) ? $rules : load_active_archiving_rules(),
        load_clients(),
        load_senders(),
        $replacementMap
    );
}

function current_approved_archiving_for_job(array $job): array
{
    $snapshot = job_archiving_snapshot($job);
    if (is_array($snapshot) && is_array($snapshot['userApproved'] ?? null)) {
        return normalize_auto_archiving_result($snapshot['userApproved']);
    }

    if (is_array($job['approvedArchiving'] ?? null)) {
        return normalize_auto_archiving_result($job['approvedArchiving']);
    }

    $analysis = is_array($job['analysis'] ?? null) ? $job['analysis'] : [];
    $autoResult = is_array($analysis['autoArchivingResult'] ?? null) ? $analysis['autoArchivingResult'] : [];
    $normalized = normalize_auto_archiving_result($autoResult);

    $selectedClientDirName = is_string($job['selectedClientDirName'] ?? null) ? trim((string) $job['selectedClientDirName']) : '';
    $selectedSenderId = resolve_active_sender_id(isset($job['selectedSenderId']) ? (int) $job['selectedSenderId'] : 0);
    $selectedCategoryId = is_string($job['selectedCategoryId'] ?? null) ? trim((string) $job['selectedCategoryId']) : '';
    $filename = is_string($job['filename'] ?? null) ? trim((string) $job['filename']) : '';

    if ($selectedClientDirName !== '') {
        $normalized['principalId'] = $selectedClientDirName;
    }
    if ($selectedSenderId > 0) {
        $normalized['senderId'] = $selectedSenderId;
    }
    if ($selectedCategoryId !== '') {
        $normalized['categoryId'] = $selectedCategoryId;
    }
    if ($filename !== '') {
        $normalized['filename'] = $filename;
    }

    return normalize_auto_archiving_result($normalized);
}

function approved_archiving_from_archive_request(array $job, array $autoResult, array $payload, array $categories): array
{
    $approved = normalize_auto_archiving_result($autoResult);
    $selectedClientDirName = array_key_exists('selectedClientDirName', $payload)
        ? (is_string($payload['selectedClientDirName'] ?? null) ? trim((string) $payload['selectedClientDirName']) : '')
        : (is_string($job['selectedClientDirName'] ?? null) ? trim((string) $job['selectedClientDirName']) : '');
    $selectedSenderId = array_key_exists('selectedSenderId', $payload)
        ? (int) ($payload['selectedSenderId'] ?? 0)
        : (isset($job['selectedSenderId']) ? (int) $job['selectedSenderId'] : 0);
    $selectedSenderId = resolve_active_sender_id($selectedSenderId) ?? 0;
    $selectedCategoryId = array_key_exists('selectedCategoryId', $payload)
        ? (is_string($payload['selectedCategoryId'] ?? null) ? trim((string) $payload['selectedCategoryId']) : '')
        : (is_string($job['selectedCategoryId'] ?? null) ? trim((string) $job['selectedCategoryId']) : '');
    $filenameInput = array_key_exists('filename', $payload)
        ? (is_string($payload['filename'] ?? null) ? trim((string) $payload['filename']) : '')
        : (is_string($job['filename'] ?? null) ? trim((string) $job['filename']) : '');

    if ($selectedClientDirName !== '') {
        $approved['principalId'] = $selectedClientDirName;
    }
    if ($selectedSenderId > 0) {
        $approved['senderId'] = $selectedSenderId;
    }
    if ($selectedCategoryId !== '') {
        $approved['categoryId'] = $selectedCategoryId;
        $category = find_loaded_category_by_id($categories, $selectedCategoryId);
        if (is_array($category)) {
            $approved['archiveFolderId'] = is_string($category['archiveFolderId'] ?? null) ? trim((string) $category['archiveFolderId']) : null;
            $approved['archiveFolderPath'] = is_string($category['path'] ?? null) ? trim((string) $category['path']) : null;
        }
    }
    if ($filenameInput !== '') {
        $approved['filename'] = sanitize_pdf_filename($filenameInput);
    }

    return normalize_auto_archiving_result($approved);
}

function archiving_result_diff(array $baseline, array $candidate): array
{
    return archiving_result_diff_with_options($baseline, $candidate);
}

function archiving_review_result_diff(array $baseline, array $candidate): array
{
    return archiving_result_diff_with_options($baseline, $candidate, ['includeFilename' => false]);
}

function archiving_review_value_is_empty(mixed $value): bool
{
    if ($value === null) {
        return true;
    }
    if (is_string($value)) {
        return trim($value) === '';
    }
    if (is_array($value)) {
        return count($value) === 0;
    }
    return false;
}

function archiving_review_change_type(mixed $approved, mixed $active, mixed $draft): string
{
    if ($draft === $active) {
        return 'unchanged';
    }
    if ($draft === $approved && $active !== $approved) {
        return 'improvement';
    }
    if ($active === $approved && $draft !== $approved) {
        return archiving_review_value_is_empty($approved) ? 'info' : 'risk';
    }
    return 'info';
}

function archiving_review_display_maps(array $activeRules, array $draftRules): array
{
    $categoryNames = [];
    $archiveFolderNames = [];
    foreach (array_merge(
        build_categories_from_archive_folders(is_array($activeRules['archiveFolders'] ?? null) ? $activeRules['archiveFolders'] : []),
        build_categories_from_archive_folders(is_array($draftRules['archiveFolders'] ?? null) ? $draftRules['archiveFolders'] : [])
    ) as $category) {
        if (!is_array($category)) {
            continue;
        }
        $categoryId = is_string($category['id'] ?? null) ? trim((string) $category['id']) : '';
        $categoryName = is_string($category['name'] ?? null) ? trim((string) $category['name']) : '';
        if ($categoryId !== '' && $categoryName !== '' && !isset($categoryNames[$categoryId])) {
            $categoryNames[$categoryId] = $categoryName;
        }
        $archiveFolderId = is_string($category['archiveFolderId'] ?? null) ? trim((string) $category['archiveFolderId']) : '';
        $archiveFolderName = is_string($category['archiveFolderName'] ?? null) ? trim((string) $category['archiveFolderName']) : '';
        if ($archiveFolderId !== '' && $archiveFolderName !== '' && !isset($archiveFolderNames[$archiveFolderId])) {
            $archiveFolderNames[$archiveFolderId] = $archiveFolderName;
        }
    }

    $labelNames = [];
    foreach (array_merge(
        array_values(is_array($activeRules['systemLabels'] ?? null) ? $activeRules['systemLabels'] : []),
        is_array($activeRules['labels'] ?? null) ? $activeRules['labels'] : [],
        array_values(is_array($draftRules['systemLabels'] ?? null) ? $draftRules['systemLabels'] : []),
        is_array($draftRules['labels'] ?? null) ? $draftRules['labels'] : []
    ) as $label) {
        if (!is_array($label)) {
            continue;
        }
        $id = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
        $name = is_string($label['name'] ?? null) ? trim((string) $label['name']) : '';
        if ($id !== '' && $name !== '' && !isset($labelNames[$id])) {
            $labelNames[$id] = $name;
        }
    }

    $fieldNames = [];
    foreach (array_merge(
        is_array($activeRules['fields'] ?? null) ? $activeRules['fields'] : [],
        is_array($activeRules['predefinedFields'] ?? null) ? $activeRules['predefinedFields'] : [],
        is_array($activeRules['systemFields'] ?? null) ? $activeRules['systemFields'] : [],
        is_array($draftRules['fields'] ?? null) ? $draftRules['fields'] : [],
        is_array($draftRules['predefinedFields'] ?? null) ? $draftRules['predefinedFields'] : [],
        is_array($draftRules['systemFields'] ?? null) ? $draftRules['systemFields'] : []
    ) as $field) {
        if (!is_array($field)) {
            continue;
        }
        $key = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        $name = is_string($field['name'] ?? null) ? trim((string) $field['name']) : '';
        if ($key !== '' && $name !== '' && !isset($fieldNames[$key])) {
            $fieldNames[$key] = $name;
        }
    }

    $clientNames = [];
    foreach (load_clients() as $client) {
        if (!is_array($client)) {
            continue;
        }
        $dirName = is_string($client['dirName'] ?? null) ? trim((string) $client['dirName']) : '';
        if ($dirName !== '') {
            $clientNames[$dirName] = $dirName;
        }
    }

    $senderNames = [];
    foreach (load_senders() as $sender) {
        if (!is_array($sender)) {
            continue;
        }
        $id = isset($sender['id']) ? (int) $sender['id'] : 0;
        $name = is_string($sender['name'] ?? null) ? trim((string) $sender['name']) : '';
        if ($id > 0) {
            $senderNames[(string) $id] = $name !== '' ? $name : (string) $id;
        }
    }

    return [
        'categories' => $categoryNames,
        'archiveFolders' => $archiveFolderNames,
        'labels' => $labelNames,
        'fields' => $fieldNames,
        'clients' => $clientNames,
        'senders' => $senderNames,
    ];
}

function archiving_review_display_value(string $key, mixed $value, array $displayMaps): string
{
    if (archiving_review_value_is_empty($value)) {
        return '—';
    }

    if ($key === 'principalId') {
        $resolved = (string) $value;
        return $displayMaps['clients'][$resolved] ?? $resolved;
    }
    if ($key === 'senderId') {
        $resolved = (string) ((int) $value);
        return $displayMaps['senders'][$resolved] ?? $resolved;
    }
    if ($key === 'categoryId') {
        $resolved = (string) $value;
        return $displayMaps['categories'][$resolved] ?? $resolved;
    }
    if ($key === 'archiveFolderId') {
        $resolved = (string) $value;
        return $displayMaps['archiveFolders'][$resolved] ?? $resolved;
    }

    return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function archiving_review_change_detail(string $type, mixed $approved, mixed $active, mixed $draft): string
{
    if ($type === 'improvement') {
        return 'Matchar nu tidigare godkänt värde.';
    }
    if ($type === 'risk') {
        return 'Det tidigare automatiska värdet matchade tidigare godkänt värde bättre.';
    }
    if (archiving_review_value_is_empty($approved) && !archiving_review_value_is_empty($draft)) {
        return 'Ny information som inte fanns i tidigare godkänt värde.';
    }
    if (!archiving_review_value_is_empty($approved) && archiving_review_value_is_empty($draft)) {
        return 'Tidigare godkänd information skulle försvinna.';
    }
    return 'Det automatiska resultatet ändras.';
}

function archiving_review_scalar_change_item(
    string $key,
    string $label,
    mixed $approved,
    mixed $active,
    mixed $draft,
    array $displayMaps
): array {
    $type = archiving_review_change_type($approved, $active, $draft);
    return [
        'type' => $type,
        'field' => $key,
        'message' => $label . ' ändrad: '
            . archiving_review_display_value($key, $active, $displayMaps)
            . ' -> '
            . archiving_review_display_value($key, $draft, $displayMaps),
        'detail' => archiving_review_change_detail($type, $approved, $active, $draft),
    ];
}

function archiving_review_label_change_item(string $labelName, bool $approvedHasLabel, bool $activeHasLabel, bool $draftHasLabel): ?array
{
    if ($draftHasLabel === $activeHasLabel) {
        return null;
    }

    if ($draftHasLabel && !$activeHasLabel) {
        return [
            'type' => $approvedHasLabel ? 'improvement' : 'info',
            'field' => 'labels',
            'message' => 'Etikett tillagd: ' . $labelName,
            'detail' => $approvedHasLabel
                ? 'Matchar nu användarens tidigare manuella val.'
                : 'Etiketten fanns inte tidigare.',
        ];
    }

    if (!$draftHasLabel && $activeHasLabel) {
        return [
            'type' => $approvedHasLabel ? 'risk' : 'improvement',
            'field' => 'labels',
            'message' => 'Etikett borttagen: ' . $labelName,
            'detail' => $approvedHasLabel
                ? 'Etiketten fanns tidigare och var godkänd.'
                : 'Etiketten fanns inte i tidigare godkänt värde.',
        ];
    }

    return null;
}

function archiving_review_change_items(array $approved, array $activeResult, array $draftResult, array $displayMaps): array
{
    $draftVsActive = archiving_review_result_diff($activeResult, $draftResult);
    if (($draftVsActive['changed'] ?? false) !== true) {
        return [];
    }

    $changes = is_array($draftVsActive['changes'] ?? null) ? $draftVsActive['changes'] : [];
    $items = [];

    foreach ([
        'principalId' => 'Huvudman',
        'senderId' => 'Avsändare',
        'categoryId' => 'Kategori',
        'archiveFolderId' => 'Arkivmapp',
    ] as $key => $label) {
        if (!isset($changes[$key]) || !is_array($changes[$key])) {
            continue;
        }
        $items[] = archiving_review_scalar_change_item(
            $key,
            $label,
            $approved[$key] ?? null,
            $changes[$key]['before'] ?? null,
            $changes[$key]['after'] ?? null,
            $displayMaps
        );
    }

    if (isset($changes['labels']) && is_array($changes['labels'])) {
        $approvedLabels = array_values(array_unique(is_array($approved['labels'] ?? null) ? $approved['labels'] : []));
        $activeLabels = array_values(array_unique(is_array($changes['labels']['before'] ?? null) ? $changes['labels']['before'] : []));
        foreach (is_array($changes['labels']['added'] ?? null) ? $changes['labels']['added'] : [] as $labelId) {
            $item = archiving_review_label_change_item(
                $displayMaps['labels'][$labelId] ?? $labelId,
                in_array($labelId, $approvedLabels, true),
                in_array($labelId, $activeLabels, true),
                true
            );
            if (is_array($item)) {
                $items[] = $item;
            }
        }
        foreach (is_array($changes['labels']['removed'] ?? null) ? $changes['labels']['removed'] : [] as $labelId) {
            $item = archiving_review_label_change_item(
                $displayMaps['labels'][$labelId] ?? $labelId,
                in_array($labelId, $approvedLabels, true),
                true,
                false
            );
            if (is_array($item)) {
                $items[] = $item;
            }
        }
    }

    foreach ([
        'fields' => 'Datafält',
        'systemFields' => 'Systemdatafält',
    ] as $groupKey => $groupLabel) {
        if (!is_array($changes[$groupKey] ?? null)) {
            continue;
        }
        foreach ($changes[$groupKey] as $fieldKey => $fieldChange) {
            if (!is_array($fieldChange)) {
                continue;
            }
            $approvedValue = is_array($approved[$groupKey] ?? null) && array_key_exists($fieldKey, $approved[$groupKey])
                ? $approved[$groupKey][$fieldKey]
                : null;
            $before = $fieldChange['before'] ?? null;
            $after = $fieldChange['after'] ?? null;
            $type = archiving_review_change_type($approvedValue, $before, $after);
            $fieldName = $displayMaps['fields'][$fieldKey] ?? $fieldKey;
            if (archiving_review_value_is_empty($before) && !archiving_review_value_is_empty($after)) {
                $message = $groupLabel . ' tillagt: ' . $fieldName . ' = ' . archiving_review_display_value($fieldKey, $after, $displayMaps);
            } elseif (!archiving_review_value_is_empty($before) && archiving_review_value_is_empty($after)) {
                $message = $groupLabel . ' borttaget: ' . $fieldName . ' (var ' . archiving_review_display_value($fieldKey, $before, $displayMaps) . ')';
            } else {
                $message = $groupLabel . ' ändrat: ' . $fieldName . ' '
                    . archiving_review_display_value($fieldKey, $before, $displayMaps)
                    . ' -> '
                    . archiving_review_display_value($fieldKey, $after, $displayMaps);
            }
            $items[] = [
                'type' => $type,
                'field' => $groupKey . '.' . $fieldKey,
                'message' => $message,
                'detail' => archiving_review_change_detail($type, $approvedValue, $before, $after),
            ];
        }
    }

    return $items;
}

function classify_archiving_rule_change(array $approved, array $activeResult, array $draftResult, array $displayMaps = []): array
{
    $activeDiff = archiving_review_result_diff($approved, $activeResult);
    $draftDiff = archiving_review_result_diff($approved, $draftResult);
    $draftVsActive = archiving_review_result_diff($activeResult, $draftResult);
    $changeItems = archiving_review_change_items($approved, $activeResult, $draftResult, $displayMaps);

    $type = 'unchanged';
    foreach ($changeItems as $item) {
        if (!is_array($item)) {
            continue;
        }
        if (($item['type'] ?? null) === 'risk') {
            $type = 'risk';
            break;
        }
        if (($item['type'] ?? null) === 'improvement') {
            $type = 'improvement';
            continue;
        }
        if ($type === 'unchanged' && ($item['type'] ?? null) === 'info') {
            $type = 'info';
        }
    }

    return [
        'type' => (($draftVsActive['changed'] ?? false) === true) ? $type : 'unchanged',
        'activeDiff' => $activeDiff,
        'draftDiff' => $draftDiff,
        'draftVsActiveDiff' => $draftVsActive,
        'changeItems' => $changeItems,
    ];
}

function archived_job_review_payload(array $config, string $jobId, ?array $job = null, ?array $activeRules = null, ?array $draftRules = null): array
{
    $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
    $loadedJob = is_array($job) ? $job : load_json_file($jobDir . '/job.json');
    if (!is_array($loadedJob)) {
        throw new RuntimeException('Jobbet kunde inte läsas');
    }

    $resolvedActiveRules = $activeRules ?? load_active_archiving_rules();
    $resolvedDraftRules = $draftRules ?? load_draft_archiving_rules();
    $activeVersion = active_archiving_rules_version();
    $snapshot = job_archiving_snapshot($loadedJob);
    $activeResult = archived_job_active_result($config, $jobId, $loadedJob, $resolvedActiveRules, $activeVersion);
    $draft = calculate_auto_archiving_result_for_job($config, $jobId, $resolvedDraftRules, $loadedJob);
    $displayMaps = archiving_review_display_maps($resolvedActiveRules, $resolvedDraftRules);
    $archivedApproved = is_array($snapshot['userApproved'] ?? null)
        ? normalize_auto_archiving_result($snapshot['userApproved'])
        : current_approved_archiving_for_job($loadedJob);
    $currentApproved = current_approved_archiving_for_job($loadedJob);
    $classification = classify_archiving_rule_change(
        $archivedApproved,
        $activeResult,
        is_array($draft['autoArchivingResult'] ?? null) ? $draft['autoArchivingResult'] : [],
        $displayMaps
    );

    return [
        'jobId' => $jobId,
        'originalFilename' => is_string($loadedJob['originalFilename'] ?? null) ? (string) $loadedJob['originalFilename'] : $jobId,
        'archivedValue' => $archivedApproved,
        'currentApprovedValue' => $currentApproved,
        'activeAutoResult' => $activeResult,
        'draftAutoResult' => normalize_auto_archiving_result($draft['autoArchivingResult'] ?? []),
        'availableLabels' => array_values(array_merge(
            array_values(is_array($resolvedActiveRules['systemLabels'] ?? null) ? $resolvedActiveRules['systemLabels'] : []),
            is_array($resolvedActiveRules['labels'] ?? null) ? $resolvedActiveRules['labels'] : []
        )),
        'availableFields' => array_values(array_merge(
            is_array($resolvedActiveRules['predefinedFields'] ?? null) ? $resolvedActiveRules['predefinedFields'] : [],
            is_array($resolvedActiveRules['fields'] ?? null) ? $resolvedActiveRules['fields'] : []
        )),
        'availableSystemFields' => is_array($resolvedActiveRules['systemFields'] ?? null) ? $resolvedActiveRules['systemFields'] : [],
        'classification' => $classification,
        'archiveSnapshot' => $snapshot,
    ];
}

function archiving_rules_review_state_path(): string
{
    return DATA_DIR . '/archiving-rules-review.json';
}

function archiving_rules_review_lock_path(): string
{
    return DATA_DIR . '/archiving-rules-review.lock';
}

function with_archiving_rules_review_lock(callable $callback)
{
    ensure_directory(DATA_DIR);
    $handle = fopen(archiving_rules_review_lock_path(), 'c+');
    if ($handle === false) {
        throw new RuntimeException('Could not open archiving review lock');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Could not lock archiving review state');
        }
        $result = $callback();
        flock($handle, LOCK_UN);
        return $result;
    } finally {
        fclose($handle);
    }
}

function archiving_result_diff_with_options(array $baseline, array $candidate, array $options = []): array
{
    $includeFilename = ($options['includeFilename'] ?? true) !== false;
    $scalarKeys = ['principalId', 'senderId', 'categoryId', 'archiveFolderId', 'archiveFolderPath'];
    if ($includeFilename) {
        array_splice($scalarKeys, 3, 0, ['filename']);
    }

    $left = normalize_auto_archiving_result($baseline);
    $right = normalize_auto_archiving_result($candidate);

    $changes = [];
    foreach ($scalarKeys as $key) {
        if (($left[$key] ?? null) !== ($right[$key] ?? null)) {
            $changes[$key] = [
                'before' => $left[$key] ?? null,
                'after' => $right[$key] ?? null,
            ];
        }
    }

    $leftLabels = array_values(array_unique(is_array($left['labels'] ?? null) ? $left['labels'] : []));
    $rightLabels = array_values(array_unique(is_array($right['labels'] ?? null) ? $right['labels'] : []));
    sort($leftLabels, SORT_NATURAL);
    sort($rightLabels, SORT_NATURAL);
    $addedLabels = array_values(array_diff($rightLabels, $leftLabels));
    $removedLabels = array_values(array_diff($leftLabels, $rightLabels));
    if ($addedLabels !== [] || $removedLabels !== []) {
        $changes['labels'] = [
            'added' => $addedLabels,
            'removed' => $removedLabels,
            'before' => $leftLabels,
            'after' => $rightLabels,
        ];
    }

    foreach (['fields', 'systemFields'] as $groupKey) {
        $leftFields = is_array($left[$groupKey] ?? null) ? $left[$groupKey] : [];
        $rightFields = is_array($right[$groupKey] ?? null) ? $right[$groupKey] : [];
        $fieldChanges = [];
        $allKeys = array_values(array_unique(array_merge(array_keys($leftFields), array_keys($rightFields))));
        sort($allKeys, SORT_NATURAL);
        foreach ($allKeys as $fieldKey) {
            $before = array_key_exists($fieldKey, $leftFields) ? $leftFields[$fieldKey] : null;
            $after = array_key_exists($fieldKey, $rightFields) ? $rightFields[$fieldKey] : null;
            if ($before === $after) {
                continue;
            }
            $fieldChanges[$fieldKey] = [
                'before' => $before,
                'after' => $after,
            ];
        }
        if ($fieldChanges !== []) {
            $changes[$groupKey] = $fieldChanges;
        }
    }

    return [
        'changed' => $changes !== [],
        'changes' => $changes,
    ];
}

function archiving_rules_review_job_ids_hash(array $jobIds): string
{
    $encoded = json_encode(array_values($jobIds), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return sha1(is_string($encoded) ? $encoded : '');
}

function empty_archiving_review_summary(): array
{
    return [
        'testedJobs' => 0,
        'unchanged' => 0,
        'improvements' => 0,
        'risks' => 0,
        'info' => 0,
    ];
}

function empty_draft_archiving_review_session(): array
{
    return [
        'status' => 'idle',
        'activeVersion' => 0,
        'activeRulesHash' => '',
        'draftRulesHash' => '',
        'jobIdsHash' => '',
        'jobIds' => [],
        'pendingJobIds' => [],
        'nextIndex' => 0,
        'analyzedCount' => 0,
        'totalCount' => 0,
        'summary' => empty_archiving_review_summary(),
        'processedJobs' => [],
        'startedAt' => null,
        'updatedAt' => null,
    ];
}

function empty_published_archiving_review_session(): array
{
    return [
        'status' => 'idle',
        'activeVersion' => 0,
        'activeRulesHash' => '',
        'jobIdsHash' => '',
        'jobIds' => [],
        'pendingJobIds' => [],
        'nextIndex' => 0,
        'analyzedCount' => 0,
        'totalCount' => 0,
        'affectedJobIds' => [],
        'startedAt' => null,
        'updatedAt' => null,
    ];
}

function normalize_archiving_review_session_status(mixed $value): string
{
    $status = is_string($value) ? trim(strtolower($value)) : '';
    if ($status !== 'running' && $status !== 'complete') {
        return 'idle';
    }
    return $status;
}

function normalize_archiving_rules_review_state(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];
    $draftIn = is_array($decoded['draftSession'] ?? null) ? $decoded['draftSession'] : [];
    $publishedIn = is_array($decoded['publishedSession'] ?? null) ? $decoded['publishedSession'] : [];

    $draft = array_merge(empty_draft_archiving_review_session(), $draftIn);
    $draft['status'] = normalize_archiving_review_session_status($draft['status'] ?? null);
    $draft['activeVersion'] = max(0, (int) ($draft['activeVersion'] ?? 0));
    $draft['activeRulesHash'] = is_string($draft['activeRulesHash'] ?? null) ? trim((string) $draft['activeRulesHash']) : '';
    $draft['draftRulesHash'] = is_string($draft['draftRulesHash'] ?? null) ? trim((string) $draft['draftRulesHash']) : '';
    $draft['jobIdsHash'] = is_string($draft['jobIdsHash'] ?? null) ? trim((string) $draft['jobIdsHash']) : '';
    $draft['jobIds'] = array_values(array_filter(
        array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($draft['jobIds'] ?? null) ? $draft['jobIds'] : []),
        static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
    ));
    $draft['pendingJobIds'] = array_values(array_unique(array_filter(
        array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($draft['pendingJobIds'] ?? null) ? $draft['pendingJobIds'] : []),
        static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
    )));
    $draft['nextIndex'] = max(0, (int) ($draft['nextIndex'] ?? 0));
    $draft['analyzedCount'] = max(0, (int) ($draft['analyzedCount'] ?? 0));
    $draft['totalCount'] = max(0, (int) ($draft['totalCount'] ?? 0));
    $draft['summary'] = array_merge(empty_archiving_review_summary(), is_array($draft['summary'] ?? null) ? $draft['summary'] : []);
    $draft['processedJobs'] = is_array($draft['processedJobs'] ?? null) ? $draft['processedJobs'] : [];
    $draft['startedAt'] = is_string($draft['startedAt'] ?? null) ? $draft['startedAt'] : null;
    $draft['updatedAt'] = is_string($draft['updatedAt'] ?? null) ? $draft['updatedAt'] : null;

    $published = array_merge(empty_published_archiving_review_session(), $publishedIn);
    $published['status'] = normalize_archiving_review_session_status($published['status'] ?? null);
    $published['activeVersion'] = max(0, (int) ($published['activeVersion'] ?? 0));
    $published['activeRulesHash'] = is_string($published['activeRulesHash'] ?? null) ? trim((string) $published['activeRulesHash']) : '';
    $published['jobIdsHash'] = is_string($published['jobIdsHash'] ?? null) ? trim((string) $published['jobIdsHash']) : '';
    $published['jobIds'] = array_values(array_filter(
        array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($published['jobIds'] ?? null) ? $published['jobIds'] : []),
        static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
    ));
    $published['pendingJobIds'] = array_values(array_unique(array_filter(
        array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($published['pendingJobIds'] ?? null) ? $published['pendingJobIds'] : []),
        static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
    )));
    $published['nextIndex'] = max(0, (int) ($published['nextIndex'] ?? 0));
    $published['analyzedCount'] = max(0, (int) ($published['analyzedCount'] ?? 0));
    $published['totalCount'] = max(0, (int) ($published['totalCount'] ?? 0));
    $published['affectedJobIds'] = array_values(array_unique(array_filter(
        array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($published['affectedJobIds'] ?? null) ? $published['affectedJobIds'] : []),
        static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
    )));
    $published['startedAt'] = is_string($published['startedAt'] ?? null) ? $published['startedAt'] : null;
    $published['updatedAt'] = is_string($published['updatedAt'] ?? null) ? $published['updatedAt'] : null;
    $lastStateEventHash = is_string($decoded['lastStateEventHash'] ?? null) ? trim((string) $decoded['lastStateEventHash']) : '';

    return [
        'draftSession' => $draft,
        'publishedSession' => $published,
        'lastStateEventHash' => $lastStateEventHash,
    ];
}

function load_archiving_rules_review_state(): array
{
    $path = archiving_rules_review_state_path();
    if (!is_file($path)) {
        return normalize_archiving_rules_review_state([]);
    }

    return normalize_archiving_rules_review_state(load_json_file($path));
}

function save_archiving_rules_review_state(array $state): array
{
    $normalized = normalize_archiving_rules_review_state($state);
    write_json_file(archiving_rules_review_state_path(), $normalized);
    return $normalized;
}

function archived_job_ids(array $config): array
{
    $jobsDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR);
    $entries = scandir($jobsDir);
    if ($entries === false) {
        return [];
    }

    $jobIds = [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($jobDir)) {
            continue;
        }
        $job = load_json_file($jobDir . '/job.json');
        if (!is_array($job) || ($job['archived'] ?? false) !== true) {
            continue;
        }
        $jobIds[] = $entry;
    }

    sort($jobIds, SORT_NATURAL);
    return $jobIds;
}

function archiving_review_summary_from_processed_jobs(array $processedJobs): array
{
    $summary = empty_archiving_review_summary();
    foreach ($processedJobs as $item) {
        if (!is_array($item)) {
            continue;
        }
        $type = is_string($item['classification']['type'] ?? null) ? (string) $item['classification']['type'] : 'unchanged';
        update_draft_archiving_review_summary($summary, $type);
    }
    return $summary;
}

function archiving_review_session_sync_job_ids(array &$session, array $jobIds): void
{
    $jobIds = array_values(array_filter($jobIds, static fn ($jobId): bool => is_string($jobId) && $jobId !== ''));
    sort($jobIds, SORT_NATURAL);
    $knownIds = array_values(array_filter(
        array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($session['jobIds'] ?? null) ? $session['jobIds'] : []),
        static fn (string $jobId): bool => $jobId !== ''
    ));
    $pending = array_values(array_filter(
        array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($session['pendingJobIds'] ?? null) ? $session['pendingJobIds'] : []),
        static fn (string $jobId): bool => $jobId !== ''
    ));
    $processedJobs = is_array($session['processedJobs'] ?? null) ? $session['processedJobs'] : [];

    $knownLookup = array_fill_keys($knownIds, true);
    foreach ($jobIds as $jobId) {
        if (!isset($knownLookup[$jobId])) {
            $pending[] = $jobId;
        }
    }

    $jobLookup = array_fill_keys($jobIds, true);
    foreach (array_keys($processedJobs) as $processedJobId) {
        if (!isset($jobLookup[$processedJobId])) {
            unset($processedJobs[$processedJobId]);
        }
    }

    $pending = array_values(array_unique(array_filter($pending, static fn (string $jobId): bool => isset($jobLookup[$jobId]))));
    $session['jobIds'] = $jobIds;
    $session['jobIdsHash'] = archiving_rules_review_job_ids_hash($jobIds);
    $session['pendingJobIds'] = $pending;
    $session['processedJobs'] = $processedJobs;
    $session['totalCount'] = count($jobIds);
    $session['analyzedCount'] = count($processedJobs);
    if (count($jobIds) === 0) {
        $session['nextIndex'] = 0;
    } else {
        $session['nextIndex'] = min(max(0, (int) ($session['nextIndex'] ?? 0)), count($jobIds));
    }
}

function archiving_review_session_enqueue_job(array &$session, string $jobId): void
{
    if ($jobId === '') {
        return;
    }
    $jobIds = is_array($session['jobIds'] ?? null) ? $session['jobIds'] : [];
    if (!in_array($jobId, $jobIds, true)) {
        $jobIds[] = $jobId;
        sort($jobIds, SORT_NATURAL);
        $session['jobIds'] = array_values($jobIds);
        $session['jobIdsHash'] = archiving_rules_review_job_ids_hash($session['jobIds']);
        $session['totalCount'] = count($session['jobIds']);
    }
    $pending = is_array($session['pendingJobIds'] ?? null) ? $session['pendingJobIds'] : [];
    if (!in_array($jobId, $pending, true)) {
        array_unshift($pending, $jobId);
        $session['pendingJobIds'] = array_values(array_unique($pending));
    }
    $processedJobs = is_array($session['processedJobs'] ?? null) ? $session['processedJobs'] : [];
    unset($processedJobs[$jobId]);
    $session['processedJobs'] = $processedJobs;
    $session['analyzedCount'] = count($processedJobs);
    $session['summary'] = archiving_review_summary_from_processed_jobs($processedJobs);
    $session['status'] = 'running';
    $session['updatedAt'] = now_iso();
}

function archiving_review_session_remove_job(array &$session, string $jobId): void
{
    $session['jobIds'] = array_values(array_filter(
        is_array($session['jobIds'] ?? null) ? $session['jobIds'] : [],
        static fn ($value): bool => is_string($value) && $value !== $jobId
    ));
    $session['pendingJobIds'] = array_values(array_filter(
        is_array($session['pendingJobIds'] ?? null) ? $session['pendingJobIds'] : [],
        static fn ($value): bool => is_string($value) && $value !== $jobId
    ));
    if (is_array($session['processedJobs'] ?? null)) {
        unset($session['processedJobs'][$jobId]);
    }
    if (is_array($session['affectedJobIds'] ?? null)) {
        $session['affectedJobIds'] = array_values(array_filter(
            $session['affectedJobIds'],
            static fn ($value): bool => is_string($value) && $value !== $jobId
        ));
    }
    $session['jobIdsHash'] = archiving_rules_review_job_ids_hash(is_array($session['jobIds'] ?? null) ? $session['jobIds'] : []);
    $processedJobs = is_array($session['processedJobs'] ?? null) ? $session['processedJobs'] : [];
    $session['summary'] = archiving_review_summary_from_processed_jobs($processedJobs);
    $session['totalCount'] = count(is_array($session['jobIds'] ?? null) ? $session['jobIds'] : []);
    $session['analyzedCount'] = count($processedJobs);
    $session['nextIndex'] = min(max(0, (int) ($session['nextIndex'] ?? 0)), $session['totalCount']);
    $session['updatedAt'] = now_iso();
}

function invalidate_archiving_review_job(array $config, string $jobId, ?bool $isArchived = null): void
{
    if (!is_valid_job_id($jobId)) {
        return;
    }

    $resolvedArchived = $isArchived;
    if ($resolvedArchived === null) {
        $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
        $job = load_json_file($jobDir . '/job.json');
        $resolvedArchived = is_array($job) && (($job['archived'] ?? false) === true);
    }

    with_archiving_rules_review_lock(static function () use ($config, $jobId, $resolvedArchived): void {
        $state = load_archiving_rules_review_state();

        if (archiving_rules_have_unpublished_changes()) {
            $hasReviewRelevantChanges = archiving_rules_have_review_relevant_changes();
            $draftSession = is_array($state['draftSession'] ?? null) ? $state['draftSession'] : empty_draft_archiving_review_session();
            if (!$hasReviewRelevantChanges) {
                $draftSession = empty_draft_archiving_review_session();
            } elseif ($resolvedArchived) {
                archiving_review_session_enqueue_job($draftSession, $jobId);
            } else {
                archiving_review_session_remove_job($draftSession, $jobId);
            }
            if ($hasReviewRelevantChanges && ($draftSession['status'] ?? 'idle') === 'complete' && archiving_rules_have_unpublished_changes()) {
                $draftSession['status'] = !empty($draftSession['pendingJobIds']) ? 'running' : 'complete';
            }
            $state['draftSession'] = $draftSession;
        } else {
            $state['draftSession'] = empty_draft_archiving_review_session();
        }

        $publishedSession = is_array($state['publishedSession'] ?? null) ? $state['publishedSession'] : empty_published_archiving_review_session();
        if ($resolvedArchived) {
            archiving_review_session_enqueue_job($publishedSession, $jobId);
            $publishedSession['affectedJobIds'] = array_values(array_filter(
                is_array($publishedSession['affectedJobIds'] ?? null) ? $publishedSession['affectedJobIds'] : [],
                static fn ($value): bool => is_string($value) && $value !== $jobId
            ));
        } else {
            archiving_review_session_remove_job($publishedSession, $jobId);
        }
        $state['publishedSession'] = $publishedSession;
        save_archiving_rules_review_state($state);
    });
}

function sort_archiving_review_items(array $items): array
{
    $priority = [
        'risk' => 0,
        'improvement' => 1,
        'info' => 2,
        'unchanged' => 3,
    ];

    usort($items, static function (array $left, array $right) use ($priority): int {
        $leftType = is_string($left['classification']['type'] ?? null) ? (string) $left['classification']['type'] : 'info';
        $rightType = is_string($right['classification']['type'] ?? null) ? (string) $right['classification']['type'] : 'info';
        $leftPriority = $priority[$leftType] ?? 9;
        $rightPriority = $priority[$rightType] ?? 9;
        if ($leftPriority !== $rightPriority) {
            return $leftPriority <=> $rightPriority;
        }

        $leftName = is_string($left['originalFilename'] ?? null) ? (string) $left['originalFilename'] : (string) ($left['jobId'] ?? '');
        $rightName = is_string($right['originalFilename'] ?? null) ? (string) $right['originalFilename'] : (string) ($right['jobId'] ?? '');
        return strnatcasecmp($leftName, $rightName);
    });

    return $items;
}

function draft_archiving_review_item(
    string $jobId,
    array $job,
    array $approved,
    array $activeResult,
    array $draftResult,
    array $displayMaps
): array {
    return [
        'jobId' => $jobId,
        'originalFilename' => is_string($job['originalFilename'] ?? null) ? (string) $job['originalFilename'] : $jobId,
        'archivedApproved' => normalize_auto_archiving_result($approved),
        'activeAutoResult' => normalize_auto_archiving_result($activeResult),
        'draftAutoResult' => normalize_auto_archiving_result($draftResult),
        'classification' => classify_archiving_rule_change($approved, $activeResult, $draftResult, $displayMaps),
    ];
}

function update_draft_archiving_review_summary(array &$summary, string $type): void
{
    $summary['testedJobs'] = ((int) ($summary['testedJobs'] ?? 0)) + 1;
    if ($type === 'improvement') {
        $summary['improvements'] = ((int) ($summary['improvements'] ?? 0)) + 1;
    } elseif ($type === 'risk') {
        $summary['risks'] = ((int) ($summary['risks'] ?? 0)) + 1;
    } elseif ($type === 'info') {
        $summary['info'] = ((int) ($summary['info'] ?? 0)) + 1;
    } else {
        $summary['unchanged'] = ((int) ($summary['unchanged'] ?? 0)) + 1;
    }
}

function initialize_draft_archiving_review_session(array $config, array $activeRules, array $draftRules, int $activeVersion): array
{
    $jobIds = archived_job_ids($config);
    $now = now_iso();

    return [
        'status' => count($jobIds) === 0 ? 'complete' : 'running',
        'activeVersion' => $activeVersion,
        'activeRulesHash' => archiving_rules_review_relevant_hash($activeRules),
        'draftRulesHash' => archiving_rules_review_relevant_hash($draftRules),
        'jobIdsHash' => archiving_rules_review_job_ids_hash($jobIds),
        'jobIds' => $jobIds,
        'pendingJobIds' => [],
        'nextIndex' => 0,
        'analyzedCount' => 0,
        'totalCount' => count($jobIds),
        'summary' => empty_archiving_review_summary(),
        'processedJobs' => [],
        'startedAt' => $now,
        'updatedAt' => $now,
    ];
}

function draft_archiving_review_session_is_current(array $session, array $activeRules, array $draftRules, int $activeVersion, array $jobIds): bool
{
    if ((int) ($session['activeVersion'] ?? 0) !== $activeVersion) {
        return false;
    }
    if ((string) ($session['activeRulesHash'] ?? '') !== archiving_rules_review_relevant_hash($activeRules)) {
        return false;
    }
    if ((string) ($session['draftRulesHash'] ?? '') !== archiving_rules_review_relevant_hash($draftRules)) {
        return false;
    }
    return (string) ($session['jobIdsHash'] ?? '') === archiving_rules_review_job_ids_hash($jobIds);
}

function collect_archiving_rules_review(array $config, int $chunkSize = 20): array
{
    return with_archiving_rules_review_lock(static function () use ($config, $chunkSize): array {
        $state = load_archiving_rules_review_state();
        $activeRules = load_active_archiving_rules();
        $draftRules = load_draft_archiving_rules();
        $activeVersion = active_archiving_rules_version();
        $jobIds = archived_job_ids($config);
        $hasUnpublishedChanges = archiving_rules_have_unpublished_changes();
        $hasReviewRelevantChanges = archiving_rules_have_review_relevant_changes($activeRules, $draftRules);
        $displayMaps = archiving_review_display_maps($activeRules, $draftRules);

        if (!$hasUnpublishedChanges || !$hasReviewRelevantChanges) {
            $state['draftSession'] = empty_draft_archiving_review_session();
            save_archiving_rules_review_state($state);
            return [
                'summary' => empty_archiving_review_summary(),
                'jobs' => [],
                'session' => [
                    'status' => 'idle',
                    'analyzedCount' => 0,
                    'totalCount' => 0,
                    'foundCount' => 0,
                    'remainingCount' => 0,
                ],
            ];
        }

        $session = is_array($state['draftSession'] ?? null) ? $state['draftSession'] : empty_draft_archiving_review_session();
        if (!draft_archiving_review_session_is_current($session, $activeRules, $draftRules, $activeVersion, $jobIds)) {
            $session = initialize_draft_archiving_review_session($config, $activeRules, $draftRules, $activeVersion);
        }
        archiving_review_session_sync_job_ids($session, $jobIds);

        $processedJobs = is_array($session['processedJobs'] ?? null) ? $session['processedJobs'] : [];
        $pendingJobIds = array_values(array_filter(
            is_array($session['pendingJobIds'] ?? null) ? $session['pendingJobIds'] : [],
            static fn ($value): bool => is_string($value) && $value !== ''
        ));
        $nextIndex = max(0, (int) ($session['nextIndex'] ?? 0));
        $totalCount = count($jobIds);
        $processed = 0;
        while ($processed < $chunkSize && ($pendingJobIds !== [] || $nextIndex < $totalCount)) {
            if ($pendingJobIds !== []) {
                $jobId = array_shift($pendingJobIds);
            } else {
                $jobId = $jobIds[$nextIndex];
                $nextIndex++;
            }
            $processed++;

            $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
            $job = load_json_file($jobDir . '/job.json');
            if (!is_array($job) || ($job['archived'] ?? false) !== true) {
                unset($processedJobs[$jobId]);
                continue;
            }

            $approved = current_approved_archiving_for_job($job);
            $activeResult = archived_job_active_result($config, $jobId, $job, $activeRules, $activeVersion);
            $draftPayload = calculate_auto_archiving_result_for_job($config, $jobId, $draftRules, $job);
            $draftResult = normalize_auto_archiving_result($draftPayload['autoArchivingResult'] ?? []);
            $item = draft_archiving_review_item($jobId, $job, $approved, $activeResult, $draftResult, $displayMaps);
            $processedJobs[$jobId] = $item;
        }

        $session['processedJobs'] = $processedJobs;
        $session['pendingJobIds'] = array_values(array_unique($pendingJobIds));
        $session['nextIndex'] = $nextIndex;
        $session['analyzedCount'] = count($processedJobs);
        $session['totalCount'] = $totalCount;
        $session['summary'] = archiving_review_summary_from_processed_jobs($processedJobs);
        $session['updatedAt'] = now_iso();
        $session['status'] = ($nextIndex >= $totalCount && $session['pendingJobIds'] === []) ? 'complete' : 'running';
        $state['draftSession'] = $session;
        save_archiving_rules_review_state($state);
        $payload = draft_archiving_review_response_from_session(
            $activeRules,
            $draftRules,
            $activeVersion,
            $session,
            true,
            true
        );

        return [
            'summary' => is_array($payload['summary'] ?? null) ? $payload['summary'] : empty_archiving_review_summary(),
            'jobs' => is_array($payload['jobs'] ?? null) ? $payload['jobs'] : [],
            'session' => is_array($payload['session'] ?? null) ? $payload['session'] : [
                'status' => 'idle',
                'analyzedCount' => 0,
                'totalCount' => 0,
                'foundCount' => 0,
                'remainingCount' => 0,
            ],
        ];
    });
}

function maybe_advance_draft_archiving_review_session(array $config, int $chunkSize = 10): array
{
    if (!archiving_rules_have_unpublished_changes() || !archiving_rules_have_review_relevant_changes()) {
        return [
            'summary' => empty_archiving_review_summary(),
            'jobs' => [],
            'session' => [
                'status' => 'idle',
                'analyzedCount' => 0,
                'totalCount' => 0,
                'foundCount' => 0,
                'remainingCount' => 0,
            ],
        ];
    }
    return collect_archiving_rules_review($config, $chunkSize);
}

function apply_archived_job_rule_review_state(
    array $config,
    string $jobId,
    array $job,
    int $activeVersion,
    bool $needsRuleReview,
    ?array $proposed = null,
    ?array $diff = null
): void {
    $nextJob = $job;
    $nextJob['needsRuleReview'] = $needsRuleReview;
    $nextJob['ruleReviewTargetRulesVersion'] = $activeVersion;
    if ($needsRuleReview) {
        $nextJob['ruleReviewProposedValue'] = normalize_auto_archiving_result(is_array($proposed) ? $proposed : []);
        $nextJob['ruleReviewDiff'] = is_array($diff) ? $diff : archiving_review_result_diff(current_approved_archiving_for_job($job), normalize_auto_archiving_result(is_array($proposed) ? $proposed : []));
    } else {
        $nextJob['lastResolvedArchivingRulesVersion'] = $activeVersion;
        unset($nextJob['ruleReviewProposedValue'], $nextJob['ruleReviewDiff']);
    }

    if (
        ($job['needsRuleReview'] ?? false) === ($nextJob['needsRuleReview'] ?? false)
        && (int) ($job['ruleReviewTargetRulesVersion'] ?? 0) === (int) ($nextJob['ruleReviewTargetRulesVersion'] ?? 0)
        && (int) ($job['lastResolvedArchivingRulesVersion'] ?? 0) === (int) ($nextJob['lastResolvedArchivingRulesVersion'] ?? 0)
        && json_encode($job['ruleReviewProposedValue'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === json_encode($nextJob['ruleReviewProposedValue'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        && json_encode($job['ruleReviewDiff'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === json_encode($nextJob['ruleReviewDiff'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ) {
        return;
    }

    $nextJob['updatedAt'] = now_iso();
    $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
    write_json_file($jobDir . '/job.json', $nextJob);
    queue_job_upsert_event($config, $jobId);
}

function initialize_published_archiving_review_session(array $config, int $activeVersion, array $activeRules): array
{
    $jobIds = archived_job_ids($config);
    $now = now_iso();
    return [
        'status' => count($jobIds) === 0 ? 'complete' : 'running',
        'activeVersion' => $activeVersion,
        'activeRulesHash' => archiving_rules_review_relevant_hash($activeRules),
        'jobIdsHash' => archiving_rules_review_job_ids_hash($jobIds),
        'jobIds' => $jobIds,
        'pendingJobIds' => [],
        'nextIndex' => 0,
        'analyzedCount' => 0,
        'totalCount' => count($jobIds),
        'affectedJobIds' => [],
        'startedAt' => $now,
        'updatedAt' => $now,
    ];
}

function published_archiving_review_session_is_current(array $session, int $activeVersion, array $activeRules, array $jobIds): bool
{
    if ((int) ($session['activeVersion'] ?? 0) !== $activeVersion) {
        return false;
    }
    if ((string) ($session['activeRulesHash'] ?? '') !== archiving_rules_review_relevant_hash($activeRules)) {
        return false;
    }
    return (string) ($session['jobIdsHash'] ?? '') === archiving_rules_review_job_ids_hash($jobIds);
}

function seed_published_archiving_review_session_from_draft(
    array $config,
    array &$publishedSession,
    array $draftSession,
    int $activeVersion
): void {
    $processedJobs = is_array($draftSession['processedJobs'] ?? null) ? $draftSession['processedJobs'] : [];
    $affectedJobIds = [];
    foreach ($processedJobs as $jobId => $item) {
        if (!is_string($jobId) || !is_array($item)) {
            continue;
        }

        $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
        $job = load_json_file($jobDir . '/job.json');
        if (!is_array($job) || ($job['archived'] ?? false) !== true) {
            continue;
        }

        $snapshot = job_archiving_snapshot($job);
        if (is_array($snapshot) && (int) ($snapshot['approvedWithRulesVersion'] ?? 0) === $activeVersion) {
            apply_archived_job_rule_review_state($config, $jobId, $job, $activeVersion, false);
            continue;
        }

        $approved = current_approved_archiving_for_job($job);
        $proposed = normalize_auto_archiving_result($item['draftAutoResult'] ?? []);
        $diff = archiving_review_result_diff($approved, $proposed);
        $needsRuleReview = ($diff['changed'] ?? false) === true;
        apply_archived_job_rule_review_state($config, $jobId, $job, $activeVersion, $needsRuleReview, $proposed, $diff);
        if ($needsRuleReview) {
            $affectedJobIds[] = $jobId;
        }
    }

    $publishedSession['nextIndex'] = min(
        count(is_array($publishedSession['jobIds'] ?? null) ? $publishedSession['jobIds'] : []),
        max(0, (int) ($draftSession['nextIndex'] ?? 0))
    );
    $publishedSession['analyzedCount'] = min(
        count(is_array($publishedSession['jobIds'] ?? null) ? $publishedSession['jobIds'] : []),
        max(0, (int) ($draftSession['analyzedCount'] ?? 0))
    );
    $publishedSession['affectedJobIds'] = array_values(array_unique($affectedJobIds));
    $publishedSession['pendingJobIds'] = array_values(array_unique(array_filter(
        is_array($draftSession['pendingJobIds'] ?? null) ? $draftSession['pendingJobIds'] : [],
        static fn ($value): bool => is_string($value) && $value !== ''
    )));
}

function ensure_published_archiving_review_session(array $config): array
{
    return with_archiving_rules_review_lock(static function () use ($config): array {
        $state = load_archiving_rules_review_state();
        $activeRules = load_active_archiving_rules();
        $activeVersion = active_archiving_rules_version();
        $jobIds = archived_job_ids($config);

        $session = is_array($state['publishedSession'] ?? null) ? $state['publishedSession'] : empty_published_archiving_review_session();
        if (published_archiving_review_session_is_current($session, $activeVersion, $activeRules, $jobIds)) {
            archiving_review_session_sync_job_ids($session, $jobIds);
            return $session;
        }

        $session = initialize_published_archiving_review_session($config, $activeVersion, $activeRules);
        $draftSession = is_array($state['draftSession'] ?? null) ? $state['draftSession'] : empty_draft_archiving_review_session();
        if (
            (string) ($draftSession['draftRulesHash'] ?? '') === (string) ($session['activeRulesHash'] ?? '')
            && (string) ($draftSession['jobIdsHash'] ?? '') === (string) ($session['jobIdsHash'] ?? '')
            && is_array($draftSession['processedJobs'] ?? null)
        ) {
            seed_published_archiving_review_session_from_draft($config, $session, $draftSession, $activeVersion);
            if ((int) ($session['nextIndex'] ?? 0) >= (int) ($session['totalCount'] ?? 0) && empty($session['pendingJobIds'])) {
                $session['status'] = 'complete';
            }
        }
        $session['updatedAt'] = now_iso();
        $state['publishedSession'] = $session;
        save_archiving_rules_review_state($state);
        return $session;
    });
}

function advance_published_archiving_review_session(array $config, int $chunkSize = 10): array
{
    return with_archiving_rules_review_lock(static function () use ($config, $chunkSize): array {
        $state = load_archiving_rules_review_state();
        $activeRules = load_active_archiving_rules();
        $activeVersion = active_archiving_rules_version();
        $jobIds = archived_job_ids($config);

        $session = is_array($state['publishedSession'] ?? null) ? $state['publishedSession'] : empty_published_archiving_review_session();
        if (!published_archiving_review_session_is_current($session, $activeVersion, $activeRules, $jobIds)) {
            $session = initialize_published_archiving_review_session($config, $activeVersion, $activeRules);
            $state['publishedSession'] = $session;
            save_archiving_rules_review_state($state);
        }
        archiving_review_session_sync_job_ids($session, $jobIds);

        if (($session['status'] ?? 'idle') === 'complete' && empty($session['pendingJobIds'])) {
            return $session;
        }

        $nextIndex = max(0, (int) ($session['nextIndex'] ?? 0));
        $totalCount = count($jobIds);
        $affectedJobIds = is_array($session['affectedJobIds'] ?? null) ? $session['affectedJobIds'] : [];
        $pendingJobIds = array_values(array_filter(
            is_array($session['pendingJobIds'] ?? null) ? $session['pendingJobIds'] : [],
            static fn ($value): bool => is_string($value) && $value !== ''
        ));
        $processed = 0;
        while ($processed < $chunkSize && ($pendingJobIds !== [] || $nextIndex < $totalCount)) {
            if ($pendingJobIds !== []) {
                $jobId = array_shift($pendingJobIds);
            } else {
                $jobId = $jobIds[$nextIndex];
                $nextIndex++;
            }
            $processed++;

            $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
            $job = load_json_file($jobDir . '/job.json');
            if (!is_array($job) || ($job['archived'] ?? false) !== true) {
                continue;
            }

            $snapshot = job_archiving_snapshot($job);
            if (is_array($snapshot) && (int) ($snapshot['approvedWithRulesVersion'] ?? 0) === $activeVersion) {
                apply_archived_job_rule_review_state($config, $jobId, $job, $activeVersion, false);
                $affectedJobIds = array_values(array_diff($affectedJobIds, [$jobId]));
                continue;
            }

            $approved = current_approved_archiving_for_job($job);
            $proposed = archived_job_active_result($config, $jobId, $job, $activeRules, $activeVersion);
            $diff = archiving_review_result_diff($approved, $proposed);
            $needsRuleReview = ($diff['changed'] ?? false) === true;
            apply_archived_job_rule_review_state($config, $jobId, $job, $activeVersion, $needsRuleReview, $proposed, $diff);
            if ($needsRuleReview) {
                $affectedJobIds[] = $jobId;
            } else {
                $affectedJobIds = array_values(array_diff($affectedJobIds, [$jobId]));
            }
        }

        $session['jobIds'] = $jobIds;
        $session['jobIdsHash'] = archiving_rules_review_job_ids_hash($jobIds);
        $session['pendingJobIds'] = array_values(array_unique($pendingJobIds));
        $session['nextIndex'] = $nextIndex;
        $session['analyzedCount'] = min($nextIndex, $totalCount);
        $session['totalCount'] = $totalCount;
        $session['affectedJobIds'] = array_values(array_unique($affectedJobIds));
        $session['updatedAt'] = now_iso();
        $session['status'] = ($nextIndex >= $totalCount && $session['pendingJobIds'] === []) ? 'complete' : 'running';
        $state['publishedSession'] = $session;
        save_archiving_rules_review_state($state);
        return $session;
    });
}

function current_published_archiving_review_session(array $config): array
{
    $state = load_archiving_rules_review_state();
    $session = is_array($state['publishedSession'] ?? null) ? $state['publishedSession'] : empty_published_archiving_review_session();
    $activeRules = load_active_archiving_rules();
    $activeVersion = active_archiving_rules_version();
    $jobIds = archived_job_ids($config);
    if (!published_archiving_review_session_is_current($session, $activeVersion, $activeRules, $jobIds)) {
        return empty_published_archiving_review_session();
    }
    return $session;
}

function maybe_advance_published_archiving_review_session(array $config, int $chunkSize = 10): array
{
    $session = current_published_archiving_review_session($config);
    if (($session['status'] ?? 'idle') !== 'running') {
        return $session;
    }
    return advance_published_archiving_review_session($config, $chunkSize);
}

function advance_archiving_review_sessions_background(array $config, int $draftChunkSize = 5, int $publishedChunkSize = 5): void
{
    maybe_advance_draft_archiving_review_session($config, $draftChunkSize);
    maybe_advance_published_archiving_review_session($config, $publishedChunkSize);
    maybe_queue_archiving_rules_update_event($config);
}

function reprocess_unarchived_jobs_for_active_archiving_rules(array $config): array
{
    $jobsDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR);
    $entries = scandir($jobsDir);
    if ($entries === false) {
        return [
            'reprocessedJobIds' => [],
            'reprocessedCount' => 0,
        ];
    }

    $jobIds = [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($jobDir)) {
            continue;
        }
        $job = load_json_file($jobDir . '/job.json');
        if (!is_array($job) || ($job['archived'] ?? false) === true || ($job['status'] ?? '') === 'processing') {
            continue;
        }
        reprocess_job_by_id($config, $entry, 'post-ocr');
        $jobIds[] = $entry;
    }

    return [
        'reprocessedJobIds' => $jobIds,
        'reprocessedCount' => count($jobIds),
    ];
}

function publish_draft_archiving_rules(array $config): array
{
    $state = load_archiving_rules_state();
    $nextVersion = ((int) ($state['activeArchivingRulesVersion'] ?? 1)) + 1;
    $draftRules = normalize_archiving_rules_set($state['draftArchivingRules'] ?? []);
    $state['activeArchivingRules'] = $draftRules;
    $state['activeArchivingRulesVersion'] = $nextVersion;
    $stored = save_archiving_rules_state($state);

    $reprocessed = reprocess_unarchived_jobs_for_active_archiving_rules($config);
    ensure_published_archiving_review_session($config);
    $publishedSession = advance_published_archiving_review_session($config, 20);
    $flagged = [
        'flaggedJobIds' => is_array($publishedSession['affectedJobIds'] ?? null) ? array_values($publishedSession['affectedJobIds']) : [],
        'flaggedCount' => count(is_array($publishedSession['affectedJobIds'] ?? null) ? $publishedSession['affectedJobIds'] : []),
        'status' => is_string($publishedSession['status'] ?? null) ? (string) $publishedSession['status'] : 'idle',
        'analyzedCount' => (int) ($publishedSession['analyzedCount'] ?? 0),
        'totalCount' => (int) ($publishedSession['totalCount'] ?? 0),
    ];

    return [
        'activeArchivingRulesVersion' => (int) ($stored['activeArchivingRulesVersion'] ?? $nextVersion),
        'hasUnpublishedChanges' => false,
        'reprocessedJobs' => $reprocessed,
        'flaggedArchivedJobs' => $flagged,
    ];
}

function reset_draft_archiving_rules_to_active(): array
{
    $state = load_archiving_rules_state();
    $state['draftArchivingRules'] = normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);
    $stored = save_archiving_rules_state($state);
    with_archiving_rules_review_lock(static function () use ($stored): void {
        $reviewState = load_archiving_rules_review_state();
        $reviewState['draftSession'] = empty_draft_archiving_review_session();
        save_archiving_rules_review_state($reviewState);
    });
    return $stored;
}

function normalize_review_labels(array $labels, array $allowedIds): array
{
    $normalized = [];
    foreach ($labels as $labelId) {
        $resolved = is_string($labelId) ? trim($labelId) : '';
        if ($resolved === '' || !isset($allowedIds[$resolved])) {
            continue;
        }
        $normalized[$resolved] = true;
    }
    return array_keys($normalized);
}

function normalize_review_field_values(mixed $input, array $allowedKeys): array
{
    $values = [];
    $decoded = is_array($input) ? $input : [];
    foreach ($decoded as $key => $value) {
        $resolvedKey = is_string($key) ? trim($key) : '';
        if ($resolvedKey === '' || !isset($allowedKeys[$resolvedKey])) {
            continue;
        }
        $normalizedValue = normalize_auto_archiving_field_value($value);
        if ($normalizedValue === null) {
            continue;
        }
        $values[$resolvedKey] = $normalizedValue;
    }
    ksort($values, SORT_NATURAL);
    return $values;
}

function resolved_active_review_value(array $payload, array $proposed, array $config): array
{
    $clients = load_clients();
    $senders = load_senders();
    $categories = load_categories();
    $rules = load_active_archiving_rules();

    $clientDirName = is_string($payload['principalId'] ?? null) ? trim((string) $payload['principalId']) : '';
    if ($clientDirName !== '') {
        $clientExists = false;
        foreach ($clients as $client) {
            if (!is_array($client)) {
                continue;
            }
            if ($clientDirName === (string) ($client['dirName'] ?? '')) {
                $clientExists = true;
                break;
            }
        }
        if (!$clientExists) {
            throw new RuntimeException('Ogiltig huvudman');
        }
    }

    $senderId = isset($payload['senderId']) ? (int) $payload['senderId'] : 0;
    if ($senderId > 0 && !sender_exists_by_id($senders, $senderId)) {
        throw new RuntimeException('Ogiltig avsändare');
    }

    $categoryId = is_string($payload['categoryId'] ?? null) ? trim((string) $payload['categoryId']) : '';
    $category = $categoryId !== '' ? find_loaded_category_by_id($categories, $categoryId) : null;
    if ($categoryId !== '' && !is_array($category)) {
        throw new RuntimeException('Ogiltig kategori');
    }

    $allowedLabelIds = [];
    foreach (array_merge(
        is_array($rules['systemLabels'] ?? null) ? array_values($rules['systemLabels']) : [],
        is_array($rules['labels'] ?? null) ? $rules['labels'] : []
    ) as $label) {
        if (!is_array($label)) {
            continue;
        }
        $labelId = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
        if ($labelId !== '') {
            $allowedLabelIds[$labelId] = true;
        }
    }

    $allowedFieldKeys = [];
    foreach (array_merge(
        is_array($rules['fields'] ?? null) ? $rules['fields'] : [],
        is_array($rules['predefinedFields'] ?? null) ? $rules['predefinedFields'] : []
    ) as $field) {
        if (!is_array($field)) {
            continue;
        }
        $fieldKey = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        if ($fieldKey !== '') {
            $allowedFieldKeys[$fieldKey] = true;
        }
    }
    $allowedSystemFieldKeys = [];
    foreach (is_array($rules['systemFields'] ?? null) ? $rules['systemFields'] : [] as $field) {
        if (!is_array($field)) {
            continue;
        }
        $fieldKey = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        if ($fieldKey !== '') {
            $allowedSystemFieldKeys[$fieldKey] = true;
        }
    }

    $next = normalize_auto_archiving_result($proposed);
    if ($clientDirName !== '') {
        $next['principalId'] = $clientDirName;
    }
    if ($senderId > 0) {
        $next['senderId'] = $senderId;
    }
    if ($categoryId !== '') {
        $next['categoryId'] = $categoryId;
        $next['archiveFolderId'] = is_array($category) ? ($category['archiveFolderId'] ?? null) : null;
        $next['archiveFolderPath'] = is_array($category) ? ($category['path'] ?? null) : null;
    }

    if (array_key_exists('labels', $payload) && is_array($payload['labels'])) {
        $next['labels'] = normalize_review_labels($payload['labels'], $allowedLabelIds);
    }
    if (array_key_exists('fields', $payload)) {
        $next['fields'] = normalize_review_field_values($payload['fields'], $allowedFieldKeys);
    }
    if (array_key_exists('systemFields', $payload)) {
        $next['systemFields'] = normalize_review_field_values($payload['systemFields'], $allowedSystemFieldKeys);
    }

    $filename = is_string($payload['filename'] ?? null) ? trim((string) $payload['filename']) : '';
    if ($filename !== '') {
        $next['filename'] = sanitize_pdf_filename($filename);
    }

    return normalize_auto_archiving_result($next);
}

function save_archived_job_review(array $config, string $jobId, string $action, array $payload = []): array
{
    if (!is_valid_job_id($jobId)) {
        throw new RuntimeException('Ogiltigt jobb-id');
    }

    $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
    $jobPath = $jobDir . '/job.json';
    $job = load_json_file($jobPath);
    if (!is_array($job) || ($job['archived'] ?? false) !== true) {
        throw new RuntimeException('Jobbet är inte arkiverat');
    }

    $activeVersion = active_archiving_rules_version();
    $currentApproved = current_approved_archiving_for_job($job);
    $proposed = archived_job_active_result($config, $jobId, $job, load_active_archiving_rules(), $activeVersion);
    $nextApproved = $currentApproved;

    if ($action === 'use-new') {
        $nextApproved = $proposed;
    } elseif ($action === 'manual') {
        $nextApproved = resolved_active_review_value($payload, $proposed, $config);
    } elseif ($action !== 'keep') {
        throw new RuntimeException('Ogiltig granskningsåtgärd');
    }

    $archivedPdfPath = is_string($job['archivedPdfPath'] ?? null) ? trim((string) $job['archivedPdfPath']) : '';
    if ($archivedPdfPath === '' || !is_file($archivedPdfPath)) {
        throw new RuntimeException('Arkiverad PDF saknas');
    }

    $nextPath = $archivedPdfPath;
    $outputBaseDirectory = trim((string) ($config['outputBaseDirectory'] ?? ''));
    if ($outputBaseDirectory === '' || !is_dir($outputBaseDirectory)) {
        throw new RuntimeException('Bas-sökväg för utdata är inte konfigurerad');
    }
    if ($action !== 'keep') {
        $principalId = is_string($nextApproved['principalId'] ?? null) ? trim((string) $nextApproved['principalId']) : '';
        $archiveFolderPath = is_string($nextApproved['archiveFolderPath'] ?? null) ? trim((string) $nextApproved['archiveFolderPath']) : '';
        $filename = is_string($nextApproved['filename'] ?? null) ? trim((string) $nextApproved['filename']) : '';
        if ($principalId === '' || $filename === '') {
            throw new RuntimeException('Det nya arkiveringsvärdet är ofullständigt');
        }
        $targetDirectory = rtrim($outputBaseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $principalId;
        if ($archiveFolderPath !== '') {
            $targetDirectory .= DIRECTORY_SEPARATOR . $archiveFolderPath;
        }
        ensure_directory($targetDirectory);
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . sanitize_pdf_filename($filename);
        if ($targetPath !== $archivedPdfPath) {
            if (is_file($targetPath)) {
                throw new RuntimeException('Det finns redan en fil med det filnamnet i mål-mappen');
            }
            if (!rename($archivedPdfPath, $targetPath)) {
                throw new RuntimeException('Kunde inte uppdatera arkiverad fil');
            }
            $nextPath = $targetPath;
        }
    }

    $normalizedApproved = normalize_auto_archiving_result($nextApproved);
    $job['approvedArchiving'] = $normalizedApproved;
    $job['selectedClientDirName'] = is_string($nextApproved['principalId'] ?? null) ? trim((string) $nextApproved['principalId']) : null;
    $job['selectedSenderId'] = isset($nextApproved['senderId']) ? (int) $nextApproved['senderId'] : null;
    $job['selectedCategoryId'] = is_string($nextApproved['categoryId'] ?? null) ? trim((string) $nextApproved['categoryId']) : null;
    $job['filename'] = is_string($nextApproved['filename'] ?? null) ? trim((string) $nextApproved['filename']) : null;
    $job['archivedPdfPath'] = $nextPath;
    set_job_archiving_snapshot($job, $activeVersion, $proposed, $normalizedApproved);
    $job['needsRuleReview'] = false;
    $job['lastResolvedArchivingRulesVersion'] = $activeVersion;
    $job['updatedAt'] = now_iso();
    unset($job['ruleReviewProposedValue'], $job['ruleReviewDiff']);
    write_json_file($jobPath, $job);
    invalidate_archiving_review_job($config, $jobId, true);
    queue_job_upsert_event($config, $jobId);
    maybe_queue_archiving_rules_update_event($config);

    return $job;
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
            'extractionFields' => new stdClass(),
            'extractionFieldMeta' => new stdClass(),
            'labels' => [],
        ],
        'files' => [
            'sourcePdf' => 'source.pdf',
            'reviewPdf' => 'review.pdf',
            'ocrText' => 'ocr.txt',
            'ocrObjects' => 'ocr-objects.json',
            'extracted' => 'extracted.json',
        ],
        'selectedClientDirName' => null,
        'selectedSenderId' => null,
        'selectedCategoryId' => null,
        'filename' => null,
        'archived' => false,
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
    array $jobContext,
    array $clients,
    array $categories,
    array $labels,
    array $systemLabels,
    array $replacementMap,
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
    $sourceHadExtractableText = false;
    $ocrUsedExistingText = false;
    $textSourcePdfPath = $sourcePdfPath;
    $sourceDocflowOcrVersion = pdf_docflow_ocr_version($sourcePdfPath);
    $resolvedDocflowOcrVersion = $sourceDocflowOcrVersion;
    if ($runOcr) {
        foreach (glob($jobDir . '/tesseract_page_*.json') ?: [] as $path) {
            @unlink($path);
        }
        foreach (glob($jobDir . '/rapidocr_page_*.json') ?: [] as $path) {
            @unlink($path);
        }
        foreach (glob($jobDir . '/merged_objects_page_*.json') ?: [] as $path) {
            @unlink($path);
        }
        foreach (glob($jobDir . '/tesseract_page_*.txt') ?: [] as $path) {
            @unlink($path);
        }
        foreach (glob($jobDir . '/rapidocr_page_*.txt') ?: [] as $path) {
            @unlink($path);
        }
        foreach (glob($jobDir . '/merged_objects_page_*.txt') ?: [] as $path) {
            @unlink($path);
        }
        if (is_file($jobDir . '/tesseract.txt')) {
            @unlink($jobDir . '/tesseract.txt');
        }
        if (is_file($jobDir . '/rapidocr.txt')) {
            @unlink($jobDir . '/rapidocr.txt');
        }
        if (is_file($jobDir . '/merged_objects.txt')) {
            @unlink($jobDir . '/merged_objects.txt');
        }
        $sourceHadExtractableText = pdf_has_extractable_text($sourcePdfPath);
        $ocrUsedExistingText = $ocrSkipExistingText && $sourceHadExtractableText;
        $ocrProcessedPdf = run_ocrmypdf(
            $sourcePdfPath,
            $reviewPdfPath,
            $ocrPath,
            $jobDir,
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
        if (is_file($reviewPdfPath)) {
            if ($ocrProcessedPdf && !$ocrUsedExistingText) {
                $candidateVersion = docflow_ocr_version();
                if (write_pdf_docflow_ocr_version($reviewPdfPath, $candidateVersion)) {
                    $resolvedDocflowOcrVersion = $candidateVersion;
                } else {
                    $resolvedDocflowOcrVersion = pdf_docflow_ocr_version($reviewPdfPath);
                }
            } else {
                $resolvedDocflowOcrVersion = pdf_docflow_ocr_version($reviewPdfPath);
                if ($resolvedDocflowOcrVersion === null) {
                    if (write_pdf_docflow_ocr_version($reviewPdfPath, $sourceDocflowOcrVersion)) {
                        $resolvedDocflowOcrVersion = $sourceDocflowOcrVersion;
                    }
                }
            }
            if (
                $ocrProcessedPdf
                && !$ocrUsedExistingText
                && $resolvedDocflowOcrVersion !== null
            ) {
                write_pdf_docflow_ocr_version($sourcePdfPath, (int) $resolvedDocflowOcrVersion);
            }
        }
        regenerate_debug_text_files_from_json($jobDir, 'tesseract');
        regenerate_debug_text_files_from_json($jobDir, 'rapidocr');
        write_merged_object_debug_files_from_rapidocr($jobDir);
    } else {
        if (!is_file($reviewPdfPath)) {
            throw new RuntimeException('Missing review.pdf');
        }
        $textSourcePdfPath = $reviewPdfPath;
        $resolvedDocflowOcrVersion = pdf_docflow_ocr_version($reviewPdfPath);
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

    $activeRulesSource = load_active_archiving_rules();
    $allActiveFields = array_values(array_merge(
        is_array($activeRulesSource['predefinedFields'] ?? null) ? $activeRulesSource['predefinedFields'] : [],
        is_array($activeRulesSource['systemFields'] ?? null) ? $activeRulesSource['systemFields'] : [],
        is_array($activeRulesSource['fields'] ?? null) ? $activeRulesSource['fields'] : []
    ));
    $senders = load_senders();
    $activeRules = [
        'archiveFolders' => is_array($activeRulesSource['archiveFolders'] ?? null) ? $activeRulesSource['archiveFolders'] : [],
        'labels' => is_array($activeRulesSource['labels'] ?? null) ? $activeRulesSource['labels'] : [],
        'systemLabels' => is_array($activeRulesSource['systemLabels'] ?? null) ? $activeRulesSource['systemLabels'] : [],
        'fields' => array_values(array_filter($allActiveFields, static function (array $field): bool {
            return ($field['isPredefinedField'] ?? false) !== true && ($field['isSystemField'] ?? false) !== true;
        })),
        'predefinedFields' => array_values(array_filter($allActiveFields, static function (array $field): bool {
            return ($field['isPredefinedField'] ?? false) === true;
        })),
        'systemFields' => array_values(array_filter($allActiveFields, static function (array $field): bool {
            return ($field['isSystemField'] ?? false) === true;
        })),
    ];
    $analysisPayload = calculate_auto_archiving_result_from_text(
        $ocrText,
        $jobContext,
        $activeRules,
        $clients,
        $senders,
        $replacementMap
    );

    $extractedData = [
        'matchedClientDirName' => $analysisPayload['matchedClientDirName'],
        'categoryMatches' => $analysisPayload['categoryMatches'],
        'systemLabelMatches' => $analysisPayload['systemLabelMatches'],
        'labelMatches' => $analysisPayload['labelMatches'],
        'labels' => $analysisPayload['labels'],
        'extractionFields' => $analysisPayload['extractionFieldResults'],
        'preselectedClient' => $analysisPayload['preselectedClient'],
        'preselectedSender' => $analysisPayload['preselectedSender'],
        'senderLookup' => $analysisPayload['senderLookup'],
        'autoArchivingResult' => $analysisPayload['autoArchivingResult'],
    ];

    write_json_file($jobDir . '/extracted.json', $extractedData);

    return [
        'extractedData' => $extractedData,
        'analysis' => [
            'preselectedClient' => $analysisPayload['preselectedClient'],
            'preselectedSender' => $analysisPayload['preselectedSender'],
            'senderLookup' => $analysisPayload['senderLookup'],
            'extractionFields' => $analysisPayload['extractionFieldValues'],
            'extractionFieldMeta' => $analysisPayload['extractionFieldMeta'] !== [] ? $analysisPayload['extractionFieldMeta'] : new stdClass(),
            'labels' => $analysisPayload['labels'],
            'autoArchivingResult' => $analysisPayload['autoArchivingResult'],
        ],
        'ocr' => [
            'runOcr' => $runOcr,
            'skipExistingTextConfigured' => $ocrSkipExistingText,
            'sourceHadExtractableText' => $sourceHadExtractableText,
            'usedExistingText' => $ocrUsedExistingText,
            'textSourcePdf' => basename($textSourcePdfPath),
        ],
        'docflowOcrVersion' => $resolvedDocflowOcrVersion,
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

            $sourceDocflowOcrVersion = pdf_docflow_ocr_version($sourcePdfPath);
            if (is_int($sourceDocflowOcrVersion) && $sourceDocflowOcrVersion > 0) {
                $jobData[DOCFLOW_OCR_METADATA_KEY] = $sourceDocflowOcrVersion;
            }

            write_json_file($jobDir . '/job.json', $jobData);
            queue_job_upsert_event($config, $jobId);
        } catch (Throwable $e) {
            $jobData['status'] = 'failed';
            $jobData['updatedAt'] = now_iso();
            $jobData['error'] = $e->getMessage();
            try {
                write_json_file($jobDir . '/job.json', $jobData);
                queue_job_upsert_event($config, $jobId);
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
    array $labels,
    array $systemLabels,
    array $matchingSettings,
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
        $forceOcr = ($jobData['forceOcr'] ?? false) === true;
        $result = process_claimed_job(
            $jobDir,
            $sourcePdfPath,
            $fallbackTxtPath,
            $jobData,
            $clients,
            $categories,
            $labels,
            $systemLabels,
            $replacementMap,
            $forceOcr ? false : (bool) ($config['ocrSkipExistingText'] ?? true),
            (int) ($config['ocrOptimizeLevel'] ?? 1),
            (string) ($config['ocrTextExtractionMethod'] ?? 'layout'),
            is_array($config['ocrPdfTextSubstitutions'] ?? null) ? $config['ocrPdfTextSubstitutions'] : [],
            $reprocessMode !== 'post-ocr'
        );

        $analysis = $result['analysis'] ?? null;
        if (is_array($analysis)) {
            $jobData['analysis'] = $analysis;
        }
        $ocr = $result['ocr'] ?? null;
        if (is_array($ocr) && ($reprocessMode !== 'post-ocr' || !is_array($jobData['ocr'] ?? null))) {
            $jobData['ocr'] = $ocr;
        }
        $resolvedDocflowOcrVersion = filter_var($result['docflowOcrVersion'] ?? null, FILTER_VALIDATE_INT);
        if ($resolvedDocflowOcrVersion !== false && (int) $resolvedDocflowOcrVersion > 0) {
            $jobData[DOCFLOW_OCR_METADATA_KEY] = (int) $resolvedDocflowOcrVersion;
        } else {
            unset($jobData[DOCFLOW_OCR_METADATA_KEY]);
        }

        $jobData['status'] = 'ready';
        $jobData['updatedAt'] = now_iso();
        unset($jobData['error'], $jobData['reprocessMode'], $jobData['forceOcr']);
        write_json_file($jobJsonPath, $jobData);
        queue_job_upsert_event($config, $jobId);
    } catch (Throwable $e) {
        $jobData['status'] = 'failed';
        $jobData['updatedAt'] = now_iso();
        $jobData['error'] = $e->getMessage();
        unset($jobData['reprocessMode'], $jobData['forceOcr']);
        write_json_file($jobJsonPath, $jobData);
        queue_job_upsert_event($config, $jobId);
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
        $labels = load_labels();
        $systemLabels = load_system_labels();
        $matchingPayload = load_matching_settings_payload();
        $matchingSettings = is_array($matchingPayload['replacements'] ?? null) ? $matchingPayload['replacements'] : [];

        while (true) {
            $jobId = next_processing_job_id($config);
            if ($jobId === null) {
                break;
            }

            process_job_by_id(
                $config,
                $clients,
                $categories,
                $labels,
                $systemLabels,
                $matchingSettings,
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

function write_rapidocr_install_status(array $status): void
{
    $status['updatedAt'] = now_iso();
    write_json_file(rapidocr_install_status_path(), $status);
}

function start_local_rapidocr_install(): void
{
    $python = python_command_path();
    if ($python === null) {
        throw new RuntimeException('Python 3 måste vara installerat först');
    }
    if (!python_can_create_venv($python)) {
        throw new RuntimeException('Python 3 saknar venv-stöd. Installera python3-venv först.');
    }
    if (rapidocr_install_runtime_status()['isInstalling']) {
        return;
    }

    ensure_directory(dirname(rapidocr_local_venv_dir()));
    ensure_directory(DATA_DIR);

    write_rapidocr_install_status([
        'state' => 'installing',
        'message' => 'Startar lokal installation...',
        'startedAt' => now_iso(),
        'finishedAt' => '',
    ]);

    $scriptPath = PROJECT_ROOT . 'scripts/install-rapidocr.php';
    if (!is_file($scriptPath)) {
        throw new RuntimeException('RapidOCR-installationsscript saknas');
    }

    $command = escapeshellarg(PHP_BINARY)
        . ' '
        . escapeshellarg($scriptPath)
        . ' > /dev/null 2>&1 &';
    exec($command);
}

function ensure_job_dispatcher_running(array $config): void
{
    $jobsDir = $config['jobsDirectory'] ?? '';
    if (!is_string($jobsDir) || trim($jobsDir) === '') {
        return;
    }
    $jobsDir = trim($jobsDir);

    ensure_directory($jobsDir);

    // Use the dispatcher's lock file as the source of truth. If we can acquire the lock,
    // there is currently no dispatcher holding it (or it crashed), so we should (re)start it.
    $lockPath = $jobsDir . DIRECTORY_SEPARATOR . '.dispatcher.lock';
    $lockHandle = @fopen($lockPath, 'c+');
    if ($lockHandle === false) {
        return;
    }

    $hasLock = @flock($lockHandle, LOCK_EX | LOCK_NB);
    if (!$hasLock) {
        @fclose($lockHandle);
        return;
    }

    // Dispatcher not running. Release the lock before spawning the dispatcher, otherwise it will exit immediately.
    @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle);

    // Throttle restarts to avoid spawn storms during frequent polling.
    $throttlePath = $jobsDir . DIRECTORY_SEPARATOR . '.dispatcher.ensure';
    $lastAttempt = @filemtime($throttlePath);
    $now = time();
    if (is_int($lastAttempt) && $lastAttempt > 0 && ($now - $lastAttempt) < 2) {
        return;
    }
    @touch($throttlePath);

    start_job_dispatcher();
}

function build_job_state_category_indexes(): array
{
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

    return [$categoryOrderById, $categoryNameById];
}

function build_job_state_entry(
    array $config,
    string $jobDir,
    array $job,
    array $categoryOrderById,
    array $categoryNameById
): ?array {
    $status = $job['status'] ?? '';
    if ($status !== 'ready' && $status !== 'processing' && $status !== 'failed') {
        return null;
    }

    $entry = basename($jobDir);
    $id = is_string($job['id'] ?? null) ? trim((string) $job['id']) : '';
    if ($id === '') {
        $id = $entry;
    }
    $originalFilename = is_string($job['originalFilename'] ?? null) ? $job['originalFilename'] : 'unknown.pdf';
    $createdAt = is_string($job['createdAt'] ?? null) ? $job['createdAt'] : '';
    $updatedAt = is_string($job['updatedAt'] ?? null) ? $job['updatedAt'] : $createdAt;
    $hasReviewPdf = job_review_pdf_path($config, $id, $job) !== null;
    $hasSourcePdf = is_file($jobDir . '/source.pdf');
    $analysis = is_array($job['analysis'] ?? null) ? $job['analysis'] : [];
    if (is_array($analysis) && array_key_exists('extractionFieldMeta', $analysis)) {
        unset($analysis['extractionFieldMeta']);
    }
    $selectedClientDirName = is_string($job['selectedClientDirName'] ?? null)
        ? trim((string) $job['selectedClientDirName'])
        : null;
    $selectedSenderId = resolve_active_sender_id(isset($job['selectedSenderId']) ? (int) $job['selectedSenderId'] : 0);
    if ($selectedSenderId !== null && $selectedSenderId < 1) {
        $selectedSenderId = null;
    }
    $selectedCategoryId = is_string($job['selectedCategoryId'] ?? null)
        ? trim((string) $job['selectedCategoryId'])
        : null;
    $filename = is_string($job['filename'] ?? null)
        ? trim((string) $job['filename'])
        : null;
    $isArchived = ($job['archived'] ?? false) === true;
    $needsRuleReview = ($job['needsRuleReview'] ?? false) === true;
    $archivedAt = is_string($job['archivedAt'] ?? null) ? trim((string) $job['archivedAt']) : null;
    $archivedPdfPath = is_string($job['archivedPdfPath'] ?? null) ? trim((string) $job['archivedPdfPath']) : null;

    if ($status === 'processing') {
        return [
            'list' => 'processingJobs',
            'job' => [
                'id' => $id,
                'originalFilename' => $originalFilename,
                'status' => 'processing',
                'createdAt' => $createdAt,
                'updatedAt' => $updatedAt,
                'hasReviewPdf' => $hasReviewPdf,
                'hasSourcePdf' => $hasSourcePdf,
                'ocr' => is_array($job['ocr'] ?? null) ? $job['ocr'] : null,
                'analysis' => $analysis,
                'selectedClientDirName' => $selectedClientDirName,
                'selectedSenderId' => $selectedSenderId,
                'selectedCategoryId' => $selectedCategoryId,
                'filename' => $filename,
                'needsRuleReview' => $needsRuleReview,
                'archived' => $isArchived,
                'archivedAt' => $archivedAt,
                'archivedPdfPath' => $archivedPdfPath,
            ],
        ];
    }

    if ($status === 'failed') {
        return [
            'list' => 'failedJobs',
            'job' => [
                'id' => $id,
                'originalFilename' => $originalFilename,
                'status' => 'failed',
                'createdAt' => $createdAt,
                'updatedAt' => $updatedAt,
                'hasReviewPdf' => $hasReviewPdf,
                'hasSourcePdf' => $hasSourcePdf,
                'ocr' => is_array($job['ocr'] ?? null) ? $job['ocr'] : null,
                'analysis' => $analysis,
                'selectedClientDirName' => $selectedClientDirName,
                'selectedSenderId' => $selectedSenderId,
                'selectedCategoryId' => $selectedCategoryId,
                'filename' => $filename,
                'needsRuleReview' => $needsRuleReview,
                'archived' => $isArchived,
                'archivedAt' => $archivedAt,
                'archivedPdfPath' => $archivedPdfPath,
                'error' => is_string($job['error'] ?? null) ? $job['error'] : null,
            ],
        ];
    }

    $matchedClientDirName = null;
    $matchedSenderId = null;
    $topMatchedCategoryId = null;
    $topMatchedCategoryName = null;
    $topMatchedCategoryScore = null;
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
        $senderId = resolve_active_sender_id((int) $preselectedSender['id']) ?? 0;
        if ($senderId > 0) {
            $matchedSenderId = $senderId;
        }
    } elseif (is_array($extracted) && is_array($extracted['senderLookup'] ?? null)) {
        $senderLookup = $extracted['senderLookup'];
        $sender = is_array($senderLookup['sender'] ?? null) ? $senderLookup['sender'] : null;
        $senderId = is_array($sender) && isset($sender['id'])
            ? (resolve_active_sender_id((int) $sender['id']) ?? 0)
            : 0;
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

    $readyPayload = [
        'id' => $id,
        'originalFilename' => $originalFilename,
        'status' => 'ready',
        'createdAt' => $createdAt,
        'updatedAt' => $updatedAt,
        'hasReviewPdf' => $hasReviewPdf,
        'hasSourcePdf' => $hasSourcePdf,
        'ocr' => is_array($job['ocr'] ?? null) ? $job['ocr'] : null,
        'analysis' => $analysis,
        'matchedClientDirName' => $matchedClientDirName,
        'matchedSenderId' => $matchedSenderId,
        'topMatchedCategoryId' => $topMatchedCategoryId,
        'topMatchedCategoryName' => $topMatchedCategoryName,
        'topMatchedCategoryScore' => $topMatchedCategoryScore,
        'selectedClientDirName' => $selectedClientDirName,
        'selectedSenderId' => $selectedSenderId,
        'selectedCategoryId' => $selectedCategoryId,
        'filename' => $filename,
        'needsRuleReview' => $needsRuleReview,
        'archived' => $isArchived,
        'archivedAt' => $archivedAt,
        'archivedPdfPath' => $archivedPdfPath,
    ];

    return [
        'list' => $isArchived ? 'archivedJobs' : 'readyJobs',
        'job' => $readyPayload,
    ];
}

function load_job_state_entry_by_id(array $config, string $jobId): ?array
{
    if (!is_valid_job_id($jobId)) {
        return null;
    }
    $jobDir = $config['jobsDirectory'] . DIRECTORY_SEPARATOR . $jobId;
    if (!is_dir($jobDir)) {
        return null;
    }
    $job = load_json_file($jobDir . '/job.json');
    if (!is_array($job)) {
        return null;
    }
    [$categoryOrderById, $categoryNameById] = build_job_state_category_indexes();
    return build_job_state_entry($config, $jobDir, $job, $categoryOrderById, $categoryNameById);
}

function job_events_log_path(): string
{
    return DATA_DIR . '/job-events.jsonl';
}

function job_events_seq_path(): string
{
    return DATA_DIR . '/job-events.seq';
}

function next_job_event_id(): int
{
    $path = job_events_seq_path();
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        throw new RuntimeException('Could not open job event sequence');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Could not lock job event sequence');
        }

        rewind($handle);
        $raw = stream_get_contents($handle);
        $current = is_string($raw) ? (int) trim($raw) : 0;
        $next = $current + 1;
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) $next);
        fflush($handle);
        flock($handle, LOCK_UN);
        return $next;
    } finally {
        fclose($handle);
    }
}

function append_job_event(array $event): array
{
    ensure_directory(DATA_DIR);
    $record = $event;
    $record['id'] = next_job_event_id();
    $record['at'] = now_iso();

    $encoded = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        throw new RuntimeException('Could not encode job event');
    }

    $path = job_events_log_path();
    $handle = fopen($path, 'ab');
    if ($handle === false) {
        throw new RuntimeException('Could not open job event log');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Could not lock job event log');
        }
        fwrite($handle, $encoded . "\n");
        fflush($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }

    return $record;
}

function read_job_events_since(int $afterEventId, int $limit = 500): array
{
    $path = job_events_log_path();
    if (!is_file($path)) {
        return [];
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return [];
    }

    $events = [];
    try {
        while (($line = fgets($handle)) !== false) {
            $decoded = json_decode(trim($line), true);
            if (!is_array($decoded)) {
                continue;
            }
            $eventId = isset($decoded['id']) ? (int) $decoded['id'] : 0;
            if ($eventId <= $afterEventId) {
                continue;
            }
            $events[] = $decoded;
            if (count($events) >= $limit) {
                break;
            }
        }
    } finally {
        fclose($handle);
    }

    return $events;
}

function latest_job_event_id(): int
{
    $path = job_events_seq_path();
    if (!is_file($path)) {
        return 0;
    }
    $raw = file_get_contents($path);
    return is_string($raw) ? max(0, (int) trim($raw)) : 0;
}

function queue_job_upsert_event(array $config, string $jobId, bool $preserveListPosition = false): ?array
{
    $entry = load_job_state_entry_by_id($config, $jobId);
    if (!is_array($entry) || !is_array($entry['job'] ?? null)) {
        return null;
    }

    return append_job_event([
        'type' => 'job.upsert',
        'job' => $entry['job'],
        'list' => is_string($entry['list'] ?? null) ? $entry['list'] : null,
        'preserveListPosition' => $preserveListPosition,
    ]);
}

function queue_job_remove_event(string $jobId): array
{
    return append_job_event([
        'type' => 'job.remove',
        'jobId' => $jobId,
    ]);
}

function read_jobs_state(array $config): array
{
    $jobsDir = $config['jobsDirectory'];
    ensure_directory($jobsDir);
    [$categoryOrderById, $categoryNameById] = build_job_state_category_indexes();

    $entries = scandir($jobsDir);
    if ($entries === false) {
        return [
            'processingJobs' => [],
            'readyJobs' => [],
            'archivedJobs' => [],
            'failedJobs' => [],
        ];
    }

    $processing = [];
    $ready = [];
    $archived = [];
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
        $stateEntry = build_job_state_entry($config, $jobDir, $job, $categoryOrderById, $categoryNameById);
        if (!is_array($stateEntry) || !is_array($stateEntry['job'] ?? null)) {
            continue;
        }
        $listKey = is_string($stateEntry['list'] ?? null) ? $stateEntry['list'] : '';
        if ($listKey === 'processingJobs') {
            $processing[] = $stateEntry['job'];
        } elseif ($listKey === 'readyJobs') {
            $ready[] = $stateEntry['job'];
        } elseif ($listKey === 'archivedJobs') {
            $archived[] = $stateEntry['job'];
        } elseif ($listKey === 'failedJobs') {
            $failed[] = $stateEntry['job'];
        }
    }

    $sortByCreatedDesc = static function (array $a, array $b): int {
        return strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? ''));
    };
    $sortByArchivedDesc = static function (array $a, array $b): int {
        $aValue = (string) ($a['archivedAt'] ?? $a['createdAt'] ?? '');
        $bValue = (string) ($b['archivedAt'] ?? $b['createdAt'] ?? '');
        return strcmp($bValue, $aValue);
    };

    usort($ready, $sortByCreatedDesc);
    usort($archived, $sortByArchivedDesc);
    usort($processing, $sortByCreatedDesc);
    usort($failed, $sortByCreatedDesc);

    return [
        'processingJobs' => $processing,
        'readyJobs' => $ready,
        'archivedJobs' => $archived,
        'failedJobs' => $failed,
    ];
}

function build_jobs_state_payload(array $config): array
{
    $jobsState = read_jobs_state($config);
    return [
        'processingJobs' => $jobsState['processingJobs'],
        'readyJobs' => $jobsState['readyJobs'],
        'archivedJobs' => $jobsState['archivedJobs'],
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
        queue_job_remove_event($entry);
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
        queue_job_remove_event($jobId);
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

    $forceOcr = false;
    $storedDocflowOcrVersion = job_docflow_ocr_version($job);
    $requiresFreshOcr = $storedDocflowOcrVersion === null || $storedDocflowOcrVersion < docflow_ocr_version();
    if ($normalizedMode === 'post-ocr' && $requiresFreshOcr) {
        $normalizedMode = 'full';
        $forceOcr = true;
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
        $artifactPaths[] = $jobDir . '/tesseract.txt';
        $artifactPaths[] = $jobDir . '/rapidocr.txt';
        $artifactPaths[] = $jobDir . '/merged_objects.txt';
    }

    foreach ($artifactPaths as $artifactPath) {
        if (is_file($artifactPath)) {
            @unlink($artifactPath);
        }
    }
    if ($normalizedMode === 'full') {
        foreach (glob($jobDir . '/tesseract_page_*.json') ?: [] as $path) {
            @unlink($path);
        }
        foreach (glob($jobDir . '/rapidocr_page_*.json') ?: [] as $path) {
            @unlink($path);
        }
        foreach (glob($jobDir . '/merged_objects_page_*.json') ?: [] as $path) {
            @unlink($path);
        }
        foreach (glob($jobDir . '/tesseract_page_*.txt') ?: [] as $path) {
            @unlink($path);
        }
        foreach (glob($jobDir . '/rapidocr_page_*.txt') ?: [] as $path) {
            @unlink($path);
        }
        foreach (glob($jobDir . '/merged_objects_page_*.txt') ?: [] as $path) {
            @unlink($path);
        }
    }

    unset($job['error'], $job['analysis']);
    $job['status'] = 'processing';
    $job['updatedAt'] = now_iso();
    $job['reprocessMode'] = $normalizedMode;
    if ($normalizedMode === 'full' && $forceOcr) {
        $job['forceOcr'] = true;
    } else {
        unset($job['forceOcr']);
    }
    write_json_file($jobJsonPath, $job);
    queue_job_upsert_event($config, $jobId, true);

    trigger_processing_worker();

    return [
        'jobId' => $jobId,
        'mode' => $normalizedMode,
        'forceOcr' => $forceOcr,
    ];
}
