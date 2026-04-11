<?php
declare(strict_types=1);

const DOCFLOW_OCR_VERSION = 1;
const DOCFLOW_OCR_METADATA_KEY = 'docflow-ocr-version';
const DOCFLOW_CHROME_EXTENSION_VERSION = '1.0.0';
const DOCFLOW_CHROME_EXTENSION_ID = 'bgpmmblhdghhdcoeoepbelbonhdhcdkg';
const DOCFLOW_CHROME_EXTENSION_MANIFEST_KEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqU9P0SMG3OD3yGoztzCpvGVjdN/kKLgsy8fpCKxlYkIUXzd8eRbFI4kOIMY8PefngWIrVG2dnioOi6naXKtxDFaLvyUkHbxqrqxVK882I4dnKNrygS1HUnWOFLZExwr8+3cJGEvv+gue3Fq6LvTKsNlJQktdmrTqGbD0SLrNopOUPmlRL9qnfHzA4MyqciFiLGZVte7327HRzzQM7LJQ2pG8N8qzt75vift/XEPh4Rvre7nwHmmfQE1UulNaeazvNbtEsmhJwG3wQcsHDKlhUijMiRdrucKLpfnzI/4+ngADIjjibKrBt5bFqJIrBM3LRkuuCAeAWrDWNVUK95WzEQIDAQAB';

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

function docflow_chrome_extension_version(): string
{
    return DOCFLOW_CHROME_EXTENSION_VERSION;
}

function docflow_chrome_extension_id(): string
{
    return DOCFLOW_CHROME_EXTENSION_ID;
}

function docflow_chrome_extension_manifest_key(): string
{
    return DOCFLOW_CHROME_EXTENSION_MANIFEST_KEY;
}

function docflow_chrome_extension_directory(): string
{
    $path = dirname(__DIR__, 2) . '/chrome-extension/docflow-extension';
    $resolved = realpath($path);
    return is_string($resolved) && $resolved !== '' ? $resolved : $path;
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
    $chromeExtensionSuppressMissingNotice = $config['chromeExtensionSuppressMissingNotice'] ?? false;
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
    if (!is_bool($chromeExtensionSuppressMissingNotice)) {
        $chromeExtensionSuppressMissingNotice = filter_var($chromeExtensionSuppressMissingNotice, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (!is_bool($chromeExtensionSuppressMissingNotice)) {
            $chromeExtensionSuppressMissingNotice = false;
        }
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
        'chromeExtensionSuppressMissingNotice' => $chromeExtensionSuppressMissingNotice,
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

function extraction_field_repository_instance(): ?\Docflow\Archiving\ExtractionFieldRepository
{
    static $initialized = false;
    static $repository = null;

    if ($initialized) {
        return $repository;
    }

    $initialized = true;
    try {
        $pdo = \Docflow\Database\Connection::make();
        $repository = new \Docflow\Archiving\ExtractionFieldRepository($pdo);
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
        'senderId' => $senderId,
        'autoSenderId' => $autoSenderId,
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

function job_analysis_snapshot(string $jobId): ?array
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
        $row = $repository->findAnalysisSnapshot($normalizedId);
    } catch (Throwable $e) {
        return null;
    }

    return is_array($row) ? $row : null;
}

function sync_job_analysis_snapshot(string $jobId, array $autoResult, ?string $analyzedAt = null): void
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
        $repository->upsertAnalysisSnapshot(
            $normalizedId,
            normalize_auto_archiving_result($autoResult),
            $analyzedAt
        );
    } catch (Throwable $e) {
        // Best effort for now. job.json remains the fallback while this migration lands.
    }
}

function normalize_stored_merged_objects_page(array $payload, int $fallbackPageNumber): array
{
    $pageNumber = is_numeric($payload['pageNumber'] ?? null) ? (int) $payload['pageNumber'] : $fallbackPageNumber;
    if ($pageNumber <= 0) {
        $pageNumber = $fallbackPageNumber > 0 ? $fallbackPageNumber : 1;
    }

    $pageIndex = is_numeric($payload['pageIndex'] ?? null) ? (int) $payload['pageIndex'] : max(0, $pageNumber - 1);
    $sourceImage = is_string($payload['sourceImage'] ?? null) ? trim((string) $payload['sourceImage']) : null;
    $pageWidth = is_numeric($payload['pageWidth'] ?? null) ? (float) $payload['pageWidth'] : null;
    $pageHeight = is_numeric($payload['pageHeight'] ?? null) ? (float) $payload['pageHeight'] : null;
    $words = array_values(array_filter(
        is_array($payload['words'] ?? null) ? $payload['words'] : [],
        static fn($word): bool => is_array($word)
    ));

    return [
        'pageNumber' => $pageNumber,
        'pageIndex' => max(0, $pageIndex),
        'sourceImage' => $sourceImage,
        'pageWidth' => $pageWidth,
        'pageHeight' => $pageHeight,
        'words' => $words,
        'text' => is_string($payload['text'] ?? null) ? (string) $payload['text'] : '',
    ];
}

function normalize_stored_merged_objects_document(array $document): ?array
{
    $pages = [];
    $sourcePages = is_array($document['pages'] ?? null) ? $document['pages'] : [];
    foreach ($sourcePages as $pageIndex => $page) {
        if (!is_array($page)) {
            continue;
        }
        $pages[] = normalize_stored_merged_objects_page($page, $pageIndex + 1);
    }

    if ($pages === []) {
        return null;
    }

    return [
        'engine' => 'merged_objects',
        'pages' => $pages,
    ];
}

function stored_merged_objects_pages(string $jobId): array
{
    $normalizedId = trim($jobId);
    if ($normalizedId === '') {
        return [];
    }

    $repository = job_repository_instance();
    if ($repository === null) {
        return [];
    }

    try {
        $payload = $repository->findMergedObjectsPayload($normalizedId);
    } catch (Throwable $e) {
        return [];
    }

    $document = is_array($payload) ? normalize_stored_merged_objects_document($payload) : null;
    return is_array($document['pages'] ?? null) ? $document['pages'] : [];
}

function sync_job_merged_objects_document(string $jobId, array $document): void
{
    $normalizedId = trim($jobId);
    if ($normalizedId === '') {
        return;
    }

    $normalizedDocument = normalize_stored_merged_objects_document($document);
    if ($normalizedDocument === null) {
        clear_job_merged_objects_document($normalizedId);
        return;
    }

    $repository = job_repository_instance();
    if ($repository === null) {
        return;
    }

    try {
        $repository->upsertMergedObjectsPayload($normalizedId, $normalizedDocument);
    } catch (Throwable $e) {
        // Best effort only. The caller still writes merged_objects.txt on disk.
    }
}

function clear_job_merged_objects_document(string $jobId): void
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
        $repository->clearMergedObjectsPayload($normalizedId);
    } catch (Throwable $e) {
        // Best effort only.
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
        'orgNumber' => is_string($match['organization_match_number'] ?? null) ? $match['organization_match_number'] : null,
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
        $rows = $repository->listEditorRows();
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
            'organizationNumbers' => array_values(array_filter(
                is_array($row['organizationNumbers'] ?? null) ? $row['organizationNumbers'] : [],
                static fn (mixed $organization): bool => is_array($organization)
            )),
            'paymentNumbers' => array_values(array_filter(
                is_array($row['paymentNumbers'] ?? null) ? $row['paymentNumbers'] : [],
                static fn (mixed $payment): bool => is_array($payment)
            )),
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
            'description' => 'Dokument som är en faktura eller betalningsavi.',
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
            'description' => 'Dokument som rör autogiro eller automatisk betalning.',
            'minScore' => 3,
            'rules' => [
                ['type' => 'text', 'text' => 'autogiro', 'score' => 3],
            ],
        ],
    ];
}

function normalize_archive_rule(mixed $input): array
{
    $rule = is_array($input) ? $input : [];
    $type = is_string($rule['type'] ?? null) ? trim(strtolower((string) $rule['type'])) : 'text';
    if (!in_array($type, ['text', 'sender_is', 'sender_name_contains', 'field_exists'], true)) {
        $type = 'text';
    }

    $text = is_string($rule['text'] ?? null) ? trim((string) $rule['text']) : '';
    $field = is_string($rule['field'] ?? null) ? trim((string) $rule['field']) : '';
    $senderId = null;
    $senderIdRaw = $rule['senderId'] ?? null;
    if (is_int($senderIdRaw)) {
        $senderId = $senderIdRaw > 0 ? $senderIdRaw : null;
    } elseif (is_float($senderIdRaw)) {
        $senderId = (int) floor($senderIdRaw);
        $senderId = $senderId > 0 ? $senderId : null;
    } elseif (is_string($senderIdRaw) && trim($senderIdRaw) !== '') {
        $parsedSenderId = filter_var(trim($senderIdRaw), FILTER_VALIDATE_INT);
        if ($parsedSenderId !== false && (int) $parsedSenderId > 0) {
            $senderId = (int) $parsedSenderId;
        }
    }

    return [
        'type' => $type,
        'text' => in_array($type, ['text', 'sender_name_contains'], true) ? $text : '',
        'isRegex' => $type === 'text'
            && (array_key_exists('isRegex', $rule) && ($rule['isRegex'] === true || $rule['isRegex'] === 1 || $rule['isRegex'] === '1')),
        'senderId' => $type === 'sender_is' ? $senderId : null,
        'field' => $type === 'field_exists' ? $field : '',
        'score' => positive_int($rule['score'] ?? 1, 1),
    ];
}

function normalize_system_label_rule(mixed $input): array
{
    return normalize_archive_rule($input);
}

function normalize_editable_label_rule(mixed $input): array
{
    return normalize_archive_rule($input);
}

function normalize_label_id_list(mixed $input): array
{
    $labelIds = [];
    $seen = [];

    $appendLabelId = static function (mixed $value) use (&$labelIds, &$seen): void {
        $labelId = is_string($value) ? trim((string) $value) : '';
        if ($labelId === '' || isset($seen[$labelId])) {
            return;
        }
        $seen[$labelId] = true;
        $labelIds[] = $labelId;
    };

    if (is_array($input)) {
        foreach ($input as $value) {
            $appendLabelId($value);
        }
    }

    return $labelIds;
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

    if ($type === 'folder') {
        return [
            'type' => 'folder',
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
            'ruleSets' => [[
                'requiresSearchTerms' => true,
                'searchTerms' => ['att betala', 'fakturabelopp', 'summa att betala', 'belopp att betala', 'total att betala'],
                'valuePattern' => '(?:SEK\s*)?(-?\d[\d\s.,]*\d(?:[.,:]\d{2})?)(?:\s*kr)?',
                'normalizationType' => 'none',
                'normalizationChars' => '',
            ]],
        ],
        'due_date' => [
            'name' => 'Förfallodatum',
            'ruleSets' => [[
                'requiresSearchTerms' => true,
                'searchTerms' => ['förfallodatum', 'förfallodag', 'att betala senast'],
                'valuePattern' => '\d{4}-\d{2}-\d{2}|\d{2}[./-]\d{2}[./-]\d{2,4}',
                'normalizationType' => 'none',
                'normalizationChars' => '',
            ]],
        ],
        'bankgiro' => [
            'name' => 'Bankgiro',
            'ruleSets' => [[
                'requiresSearchTerms' => true,
                'searchTerms' => ['bankgiro', 'bg'],
                'valuePattern' => '\d{3,4}[ -]?\d{4}',
                'normalizationType' => 'none',
                'normalizationChars' => '',
            ]],
        ],
        'plusgiro' => [
            'name' => 'Plusgiro',
            'ruleSets' => [[
                'requiresSearchTerms' => true,
                'searchTerms' => ['plusgiro', 'pg'],
                'valuePattern' => '\d{1,8}[ -]?\d',
                'normalizationType' => 'none',
                'normalizationChars' => '',
            ]],
        ],
        'supplier' => [
            'name' => 'Leverantör',
            'ruleSets' => [[
                'requiresSearchTerms' => true,
                'searchTerms' => ['leverantör', 'leverantor'],
                'valuePattern' => '',
                'normalizationType' => 'none',
                'normalizationChars' => '',
            ]],
        ],
        'payment_receiver' => [
            'name' => 'Betalningsmottagare',
            'ruleSets' => [[
                'requiresSearchTerms' => true,
                'searchTerms' => ['betalningsmottagare', 'mottagare'],
                'valuePattern' => '',
                'normalizationType' => 'none',
                'normalizationChars' => '',
            ]],
        ],
        'iban' => [
            'name' => 'IBAN',
            'ruleSets' => [[
                'requiresSearchTerms' => true,
                'searchTerms' => ['iban'],
                'valuePattern' => '[A-Z]{2}\d{2}[A-Z0-9 ]{11,30}',
                'normalizationType' => 'blacklist',
                'normalizationChars' => ' ',
            ]],
        ],
        'swift' => [
            'name' => 'SWIFT',
            'ruleSets' => [[
                'requiresSearchTerms' => true,
                'searchTerms' => ['swift', 'bic'],
                'valuePattern' => '[A-Z]{6}[A-Z0-9]{2}(?:[A-Z0-9]{3})?',
                'normalizationType' => 'blacklist',
                'normalizationChars' => ' ',
            ]],
        ],
        'ocr' => [
            'name' => 'OCR',
            'ruleSets' => [[
                'requiresSearchTerms' => true,
                'searchTerms' => ['ocr-nummer', 'ocr nummer', 'ocr'],
                'valuePattern' => '\d[\d ]{6,40}\d',
                'normalizationType' => 'blacklist',
                'normalizationChars' => ' ',
            ]],
        ],
        'organisationsnummer' => [
            'name' => 'Organisationsnummer',
            'ruleSets' => [
                [
                    'requiresSearchTerms' => true,
                    'searchTerms' => ['organisationsnr', 'org.nr', 'org nr'],
                    'valuePattern' => '\d{2}[2-9]\d{3}[- ]?\d{4}',
                    'normalizationType' => 'whitelist',
                    'normalizationChars' => '0123456789',
                ],
                [
                    'requiresSearchTerms' => false,
                    'searchTerms' => [],
                    'valuePattern' => '\bSE(\d{6}[- ]?\d{4})01\b',
                    'normalizationType' => 'none',
                    'normalizationChars' => '',
                ],
            ],
        ],
    ];
}

function system_extraction_field_definitions(): array
{
    return [
        'document_date' => [
            'name' => 'Dokumentdatum',
            'aliases' => [],
            'searchString' => '',
            'isRegex' => false,
            'normalizationType' => 'none',
            'normalizationChars' => '',
            'extractor' => 'document_date',
        ],
    ];
}

function extraction_field_alias_key(string $value): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }
    return strtolower($value);
}

function normalize_extraction_field_aliases(mixed $input, string $legacyFallback = ''): array
{
    $values = [];
    if (is_array($input)) {
        $values = $input;
    } elseif (is_string($input) || is_numeric($input)) {
        $values = [(string) $input];
    }

    $aliases = [];
    $seen = [];
    foreach ($values as $value) {
        if (!is_string($value) && !is_numeric($value)) {
            continue;
        }
        $alias = normalize_inline_whitespace((string) $value);
        if ($alias === '') {
            continue;
        }
        $key = extraction_field_alias_key($alias);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $aliases[] = $alias;
    }

    $fallback = normalize_inline_whitespace($legacyFallback);
    if ($aliases === [] && $fallback !== '') {
        $aliases[] = $fallback;
    }

    return $aliases;
}

function normalize_extraction_field_search_term_item(mixed $input, bool $legacyIsRegex = false): ?array
{
    $text = '';
    $isRegex = $legacyIsRegex;

    if (is_array($input)) {
        $text = is_string($input['text'] ?? null)
            ? (string) $input['text']
            : (is_string($input['value'] ?? null) ? (string) $input['value'] : '');
        $isRegex = normalize_extraction_field_is_regex($input['isRegex'] ?? $legacyIsRegex);
    } elseif (is_string($input) || is_numeric($input)) {
        $text = (string) $input;
    }

    $text = normalize_inline_whitespace($text);
    if ($text === '') {
        return null;
    }

    return [
        'text' => $text,
        'isRegex' => $isRegex,
    ];
}

function normalize_extraction_field_search_terms(mixed $input, bool $legacyIsRegex = false, string $legacyFallback = ''): array
{
    $values = [];
    if (is_array($input)) {
        $values = $input;
    } elseif (is_string($input) || is_numeric($input)) {
        $values = [(string) $input];
    }

    $searchTerms = [];
    $seen = [];
    foreach ($values as $value) {
        $term = normalize_extraction_field_search_term_item($value, $legacyIsRegex);
        if (!is_array($term)) {
            continue;
        }
        $key = extraction_field_alias_key((string) $term['text']) . '|' . (($term['isRegex'] ?? false) === true ? '1' : '0');
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $searchTerms[] = $term;
    }

    $fallback = normalize_extraction_field_search_term_item($legacyFallback, $legacyIsRegex);
    if ($searchTerms === [] && is_array($fallback)) {
        $searchTerms[] = $fallback;
    }

    return $searchTerms;
}

function normalize_extraction_field_is_regex(mixed $value): bool
{
    return $value === true || $value === 1 || $value === '1';
}

function normalize_extraction_field_normalization_type(mixed $value): string
{
    $normalized = is_string($value) ? trim(strtolower($value)) : '';
    return in_array($normalized, ['whitelist', 'blacklist'], true) ? $normalized : 'none';
}

function normalize_extraction_field_normalization_chars(mixed $value): string
{
    return is_string($value) ? (string) $value : '';
}

function default_extraction_field_rule_set(array $overrides = []): array
{
    $defaults = [
        'requiresSearchTerms' => true,
        'searchTerms' => [],
        'isRegex' => false,
        'valuePattern' => '',
        'normalizationType' => 'none',
        'normalizationChars' => '',
    ];

    return array_merge($defaults, $overrides);
}

function normalize_extraction_field_rule_sets(mixed $input, ?array $legacyField = null): array
{
    $rows = is_array($input) ? $input : [];
    $normalized = [];

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $requiresSearchTerms = !array_key_exists('requiresSearchTerms', $row)
            || $row['requiresSearchTerms'] === true
            || $row['requiresSearchTerms'] === 1
            || $row['requiresSearchTerms'] === '1';
        $legacyIsRegex = normalize_extraction_field_is_regex($row['isRegex'] ?? false);
        $searchTerms = normalize_extraction_field_search_terms($row['searchTerms'] ?? null, $legacyIsRegex);
        $normalized[] = default_extraction_field_rule_set([
            'requiresSearchTerms' => $requiresSearchTerms,
            'searchTerms' => $searchTerms,
            'isRegex' => false,
            'valuePattern' => is_string($row['valuePattern'] ?? null)
                ? trim((string) $row['valuePattern'])
                : (
                    is_string($row['searchString'] ?? null)
                        ? trim((string) $row['searchString'])
                        : (is_string($row['query'] ?? null) ? trim((string) $row['query']) : '')
                ),
            'normalizationType' => normalize_extraction_field_normalization_type($row['normalizationType'] ?? null),
            'normalizationChars' => normalize_extraction_field_normalization_chars($row['normalizationChars'] ?? null),
        ]);
    }

    if ($normalized !== []) {
        return $normalized;
    }

    $legacy = is_array($legacyField) ? $legacyField : [];
    $legacyPattern = is_string($legacy['searchString'] ?? null)
        ? trim((string) $legacy['searchString'])
        : (is_string($legacy['query'] ?? null) ? trim((string) $legacy['query']) : '');
    $legacyIsRegex = normalize_extraction_field_is_regex($legacy['isRegex'] ?? false);
    $legacyAliases = normalize_extraction_field_search_terms($legacy['aliases'] ?? null, $legacyIsRegex, $legacyPattern);
    $requiresSearchTerms = $legacyAliases !== [];
    if ($legacyPattern !== '' || $legacyAliases !== [] || $legacy !== []) {
        return [default_extraction_field_rule_set([
            'requiresSearchTerms' => $requiresSearchTerms,
            'searchTerms' => $requiresSearchTerms ? $legacyAliases : [],
            'isRegex' => false,
            'valuePattern' => $legacyPattern,
            'normalizationType' => normalize_extraction_field_normalization_type($legacy['normalizationType'] ?? null),
            'normalizationChars' => normalize_extraction_field_normalization_chars($legacy['normalizationChars'] ?? null),
        ])];
    }

    return [default_extraction_field_rule_set()];
}

function predefined_extraction_field_default_rule_sets(array $defaults): array
{
    $ruleSets = normalize_extraction_field_rule_sets($defaults['ruleSets'] ?? null, $defaults);
    return $ruleSets !== [] ? $ruleSets : [default_extraction_field_rule_set()];
}

function normalize_predefined_amount_rule_set(array $ruleSet): array
{
    $pattern = is_string($ruleSet['valuePattern'] ?? null) ? trim((string) $ruleSet['valuePattern']) : '';
    if ($pattern === '') {
        return $ruleSet;
    }

    $legacyPatterns = [
        '\d[\d\s.,]*\d(?:[.,:]\d{2})?(?:\s*kr)?' => '(?:SEK\s*)?(-?\d[\d\s.,]*\d(?:[.,:]\d{2})?)(?:\s*kr)?',
        '(?:SEK\\s*)  ?-?\\d{1,3}(?:[ .]\\d{3})*(?:[.,]\\d{2})?(?:\\s*kr)?' => '(?:SEK\\s*)?(-?\\d{1,3}(?:[ .]\\d{3})*(?:[.,]\\d{2})?)(?:\\s*kr)?',
    ];

    if (isset($legacyPatterns[$pattern])) {
        $ruleSet['valuePattern'] = $legacyPatterns[$pattern];
    }

    return $ruleSet;
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
        $ruleSets = normalize_extraction_field_rule_sets($row['ruleSets'] ?? null, $row);

        if ($name === '') {
            continue;
        }

        $storedKey = is_string($row['key'] ?? null) ? trim((string) $row['key']) : '';
        $derivedKey = normalize_config_key($name, 'field');
        $keySource = $storedKey;
        if ($keySource === '') {
            $keySource = $name;
        } elseif (strlen($keySource) <= 1 && $derivedKey !== '' && strlen($derivedKey) > 1 && $derivedKey !== $keySource) {
            $keySource = $derivedKey;
        }
        $baseKey = normalize_config_key($keySource, 'field');
        $key = ensure_unique_config_key($baseKey, $usedKeys);

        $fields[] = [
            'key' => $key,
            'name' => $name,
            'ruleSets' => $ruleSets,
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

    $ruleSets = array_key_exists('ruleSets', $field)
        ? normalize_extraction_field_rule_sets($field['ruleSets'] ?? null, $field)
        : normalize_extraction_field_rule_sets(null, $field);
    $defaultRuleSets = predefined_extraction_field_default_rule_sets($defaults);
    $ruleSets = $ruleSets !== [] ? $ruleSets : $defaultRuleSets;
        if (!array_key_exists('ruleSets', $field)) {
            $hasLegacyOverrides = array_key_exists('aliases', $field)
                || array_key_exists('searchString', $field)
                || array_key_exists('query', $field)
                || array_key_exists('normalizationType', $field)
                || array_key_exists('normalizationChars', $field);
            if (!$hasLegacyOverrides) {
                $ruleSets = $defaultRuleSets;
            }
    }
    if ($key === 'amount') {
        $ruleSets = array_map('normalize_predefined_amount_rule_set', $ruleSets);
    }

    return [
        'key' => $key,
        'name' => $name,
        'ruleSets' => $ruleSets,
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
    $defaultAliases = normalize_extraction_field_aliases($defaults['aliases'] ?? null);
    $aliases = array_key_exists('aliases', $field)
        ? normalize_extraction_field_aliases($field['aliases'] ?? null)
        : $defaultAliases;
    if ($aliases === []) {
        $aliases = $defaultAliases;
    }

    return [
        'key' => $key,
        'name' => $name,
        'aliases' => $aliases,
        'searchString' => $searchString,
        'isRegex' => normalize_extraction_field_is_regex(
            array_key_exists('isRegex', $field)
                ? $field['isRegex']
                : ($defaults['isRegex'] ?? false)
        ),
        'normalizationType' => normalize_extraction_field_normalization_type(
            array_key_exists('normalizationType', $field)
                ? $field['normalizationType']
                : ($defaults['normalizationType'] ?? 'none')
        ),
        'normalizationChars' => normalize_extraction_field_normalization_chars(
            array_key_exists('normalizationChars', $field)
                ? $field['normalizationChars']
                : ($defaults['normalizationChars'] ?? '')
        ),
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
    $description = is_string($label['description'] ?? null) ? trim((string) $label['description']) : '';
    if ($description === '') {
        $description = is_string($defaults['description'] ?? null) ? trim((string) $defaults['description']) : '';
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
        'description' => $description,
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
    $description = is_string($input['description'] ?? null) ? trim((string) $input['description']) : '';

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
        'description' => $description,
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

function normalize_archive_structure_unique_id(string $base, string $fallback, array &$usedIds): string
{
    $candidate = slugify_text($base, '-', $fallback);
    if ($candidate === '') {
        $candidate = $fallback;
    }
    $resolved = $candidate;
    $suffix = 2;
    while (isset($usedIds[$resolved])) {
        $resolved = $candidate . '-' . $suffix;
        $suffix += 1;
    }
    $usedIds[$resolved] = true;
    return $resolved;
}

function archive_folder_display_text(array $folder): string
{
    $name = is_string($folder['name'] ?? null) ? trim((string) $folder['name']) : '';
    if ($name !== '') {
        return $name;
    }

    $template = normalize_filename_template($folder['pathTemplate'] ?? ($folder['path'] ?? null));
    foreach (is_array($template['parts'] ?? null) ? $template['parts'] : [] as $part) {
        if (($part['type'] ?? 'text') !== 'text') {
            continue;
        }
        $value = is_string($part['value'] ?? null) ? trim((string) $part['value']) : '';
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function normalize_archive_folder_definition(array $input, int $index, array &$usedIds): array
{
    $name = is_string($input['name'] ?? null) ? trim((string) $input['name']) : '';
    $pathTemplate = normalize_filename_template($input['pathTemplate'] ?? ($input['path'] ?? null));
    $priority = positive_int($input['priority'] ?? 1, 1);
    $idSource = is_string($input['id'] ?? null) && trim((string) $input['id']) !== ''
        ? trim((string) $input['id'])
        : ($name !== '' ? $name : archive_folder_display_text([
            'name' => $name,
            'pathTemplate' => $pathTemplate,
        ]));
    $folderId = normalize_archive_structure_unique_id($idSource, 'folder', $usedIds);
    $filenameTemplates = normalize_archive_folder_filename_templates($input['filenameTemplates'] ?? null, $folderId);

    return [
        'id' => $folderId,
        'name' => $name,
        'pathTemplate' => $pathTemplate,
        'priority' => $priority,
        'filenameTemplates' => $filenameTemplates,
    ];
}

function normalize_archive_folders(mixed $input): array
{
    if (!is_array($input)) {
        return [];
    }

    $folders = [];
    $usedIds = [];
    $usedTemplateIds = [];
    foreach (array_values($input) as $index => $row) {
        if (!is_array($row)) {
            continue;
        }
        $folder = normalize_archive_folder_definition($row, (int) $index, $usedIds);
        $folderId = is_string($folder['id'] ?? null) ? trim((string) $folder['id']) : '';
        $filenameTemplates = [];
        foreach (is_array($folder['filenameTemplates'] ?? null) ? $folder['filenameTemplates'] : [] as $templateIndex => $template) {
            if (!is_array($template)) {
                continue;
            }
            $templateIdSource = is_string($template['id'] ?? null) && trim((string) $template['id']) !== ''
                ? trim((string) $template['id'])
                : ($folderId !== '' ? $folderId . '-filename-template-' . ((int) $templateIndex + 1) : 'filename-template');
            $template['id'] = normalize_archive_structure_unique_id($templateIdSource, 'filename-template', $usedTemplateIds);
            $filenameTemplates[] = $template;
        }
        $folder['filenameTemplates'] = $filenameTemplates;
        $folders[] = $folder;
    }

    return $folders;
}

function normalize_archive_folder_filename_template_definition(array $input, int $index, string $folderId, array &$usedIds): array
{
    $template = normalize_filename_template($input['template'] ?? ($input['filenameTemplate'] ?? null));
    $idSource = is_string($input['id'] ?? null) && trim((string) $input['id']) !== ''
        ? trim((string) $input['id'])
        : ($folderId . '-filename-template-' . ($index + 1));

    return [
        'id' => normalize_archive_structure_unique_id($idSource, 'filename-template', $usedIds),
        'template' => $template,
        'labelIds' => normalize_archive_rule_label_ids($input['labelIds'] ?? ($input['conditions']['labelIds'] ?? null)),
    ];
}

function normalize_archive_folder_filename_templates(mixed $input, string $folderId): array
{
    if (!is_array($input)) {
        return [];
    }

    $templates = [];
    $usedIds = [];
    foreach (array_values($input) as $index => $row) {
        if (!is_array($row)) {
            continue;
        }
        $templates[] = normalize_archive_folder_filename_template_definition($row, (int) $index, $folderId, $usedIds);
    }

    return $templates;
}

function normalize_archive_rule_label_ids(mixed $input): array
{
    return normalize_label_id_list($input);
}

function normalize_archive_rule_conditions(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];
    return [
        'labelIds' => normalize_archive_rule_label_ids($decoded['labelIds'] ?? null),
    ];
}

function normalize_archive_rule_definition(
    array $input,
    int $index,
    array &$usedIds,
    array $knownFolderIds
): array {
    $name = is_string($input['name'] ?? null) ? trim((string) $input['name']) : '';
    $conditions = normalize_archive_rule_conditions($input['conditions'] ?? $input);
    $folderId = is_string($input['folderId'] ?? null) ? trim((string) $input['folderId']) : '';
    if ($folderId !== '' && !isset($knownFolderIds[$folderId])) {
        $folderId = '';
    }
    $priority = positive_int($input['priority'] ?? ($index + 1), $index + 1);
    $sortOrder = positive_int($input['sortOrder'] ?? ($index + 1), $index + 1);
    $isActive = array_key_exists('isActive', $input) ? ($input['isActive'] !== false) : true;
    $idSource = is_string($input['id'] ?? null) && trim((string) $input['id']) !== ''
        ? trim((string) $input['id'])
        : ($name !== '' ? $name : ('rule-' . ($index + 1)));

    return [
        'id' => normalize_archive_structure_unique_id($idSource, 'rule', $usedIds),
        'name' => $name,
        'priority' => $priority,
        'sortOrder' => $sortOrder,
        'conditions' => $conditions,
        'folderId' => $folderId,
        'isActive' => $isActive,
    ];
}

function normalize_archive_rules(mixed $input, array $folders = []): array
{
    if (!is_array($input)) {
        return [];
    }

    $knownFolderIds = [];
    foreach ($folders as $folder) {
        $folderId = is_string($folder['id'] ?? null) ? trim((string) $folder['id']) : '';
        if ($folderId !== '') {
            $knownFolderIds[$folderId] = true;
        }
    }

    $rules = [];
    $usedIds = [];
    foreach (array_values($input) as $index => $row) {
        if (!is_array($row)) {
            continue;
        }
        $rules[] = normalize_archive_rule_definition($row, (int) $index, $usedIds, $knownFolderIds);
    }

    return $rules;
}

function build_archive_structure_indexes(array $rules): array
{
    $foldersById = [];
    $filenameTemplatesById = [];
    foreach (is_array($rules['archiveFolders'] ?? null) ? $rules['archiveFolders'] : [] as $folder) {
        if (!is_array($folder)) {
            continue;
        }
        $folderId = is_string($folder['id'] ?? null) ? trim((string) $folder['id']) : '';
        if ($folderId !== '') {
            $foldersById[$folderId] = $folder;
            foreach (is_array($folder['filenameTemplates'] ?? null) ? $folder['filenameTemplates'] : [] as $templateIndex => $template) {
                if (!is_array($template)) {
                    continue;
                }
                $templateId = is_string($template['id'] ?? null) ? trim((string) $template['id']) : '';
                if ($templateId === '') {
                    continue;
                }
                $filenameTemplatesById[$templateId] = $template + [
                    'folderId' => $folderId,
                    'folderName' => archive_folder_display_text($folder),
                    'templateIndex' => is_int($templateIndex) ? $templateIndex : 0,
                ];
            }
        }
    }

    return [$foldersById, $filenameTemplatesById];
}

function load_archive_folders(): array
{
    $rules = load_active_archiving_rules();
    return is_array($rules['archiveFolders'] ?? null) ? $rules['archiveFolders'] : [];
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

function known_job_label_ids(): array
{
    $known = [];
    foreach (array_merge(load_system_labels(), load_labels()) as $label) {
        if (!is_array($label)) {
            continue;
        }
        $labelId = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
        if ($labelId !== '') {
            $known[$labelId] = true;
        }
    }

    return $known;
}

function normalize_stored_job_label_ids(mixed $value): array
{
    $resolved = [];
    $seen = [];
    foreach (is_array($value) ? $value : [] as $item) {
        $labelId = is_string($item) ? trim((string) $item) : '';
        if ($labelId === '' || isset($seen[$labelId])) {
            continue;
        }
        $seen[$labelId] = true;
        $resolved[] = $labelId;
    }

    return $resolved;
}

function normalize_selected_job_label_ids_payload(mixed $value, array $knownLabelIds): array
{
    if (!is_array($value)) {
        throw new RuntimeException('Ogiltiga etiketter');
    }

    $resolved = [];
    $seen = [];
    foreach ($value as $item) {
        $labelId = is_string($item) ? trim((string) $item) : '';
        if ($labelId === '' || isset($seen[$labelId])) {
            continue;
        }
        if (!isset($knownLabelIds[$labelId])) {
            throw new RuntimeException('Ogiltiga etiketter');
        }
        $seen[$labelId] = true;
        $resolved[] = $labelId;
    }

    return $resolved;
}

function normalize_archive_structure_data(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];
    $archiveFolders = normalize_archive_folders($decoded['archiveFolders'] ?? null);

    return [
        'archiveFolders' => $archiveFolders,
    ];
}

function normalize_archiving_rules_set(mixed $input): array
{
    $decoded = is_array($input) ? $input : [];
    $archiveStructure = normalize_archive_structure_data($decoded);
    $fields = normalize_extraction_fields(
        is_array($decoded['fields'] ?? null) ? $decoded['fields'] : []
    );
    $predefinedFields = normalize_predefined_extraction_fields(
        is_array($decoded['predefinedFields'] ?? null) ? $decoded['predefinedFields'] : []
    );
    $predefinedDefinitions = predefined_extraction_field_definitions();
    $predefinedByKey = [];
    foreach ($predefinedFields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $fieldKey = is_string($field['predefinedFieldKey'] ?? null)
            ? trim((string) $field['predefinedFieldKey'])
            : (is_string($field['key'] ?? null) ? trim((string) $field['key']) : '');
        if ($fieldKey !== '') {
            $predefinedByKey[$fieldKey] = $field;
        }
    }

    $remainingFields = [];
    foreach ($fields as $field) {
        $fieldKey = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        if ($fieldKey === '' || !isset($predefinedDefinitions[$fieldKey])) {
            $remainingFields[] = $field;
            continue;
        }
        $predefinedByKey[$fieldKey] = normalize_predefined_extraction_field_with_defaults(
            $fieldKey,
            array_merge(is_array($predefinedByKey[$fieldKey] ?? null) ? $predefinedByKey[$fieldKey] : [], $field),
            $predefinedDefinitions[$fieldKey]
        );
    }

    $resolvedPredefinedFields = [];
    foreach ($predefinedDefinitions as $fieldKey => $defaults) {
        $resolvedPredefinedFields[] = normalize_predefined_extraction_field_with_defaults(
            $fieldKey,
            $predefinedByKey[$fieldKey] ?? [],
            $defaults
        );
    }

    return [
        'archiveFolders' => $archiveStructure['archiveFolders'],
        'labels' => normalize_labels(
            is_array($decoded['labels'] ?? null) ? $decoded['labels'] : []
        ),
        'systemLabels' => normalize_system_labels($decoded['systemLabels'] ?? []),
        'fields' => $remainingFields,
        'predefinedFields' => $resolvedPredefinedFields,
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

function hydrate_archiving_rules_state_from_field_repository(
    array $state,
    \Docflow\Archiving\ExtractionFieldRepository $repository
): array {
    $activeFields = $repository->loadScope('active');
    $draftFields = $repository->loadScope('draft');

    $state['activeArchivingRules']['fields'] = is_array($activeFields['fields'] ?? null) ? $activeFields['fields'] : [];
    $state['activeArchivingRules']['predefinedFields'] = is_array($activeFields['predefinedFields'] ?? null) ? $activeFields['predefinedFields'] : [];
    $state['draftArchivingRules']['fields'] = is_array($draftFields['fields'] ?? null) ? $draftFields['fields'] : [];
    $state['draftArchivingRules']['predefinedFields'] = is_array($draftFields['predefinedFields'] ?? null) ? $draftFields['predefinedFields'] : [];

    $state['activeArchivingRules'] = normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);
    $state['draftArchivingRules'] = normalize_archiving_rules_set($state['draftArchivingRules'] ?? []);

    return $state;
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
    $fieldRepository = extraction_field_repository_instance();
    if ($fieldRepository !== null) {
        try {
            if (!$fieldRepository->hasAnyRows()) {
                $fieldRepository->replaceScopes(
                    is_array($normalized['activeArchivingRules'] ?? null) ? $normalized['activeArchivingRules'] : [],
                    is_array($normalized['draftArchivingRules'] ?? null) ? $normalized['draftArchivingRules'] : []
                );
            }
            $normalized = hydrate_archiving_rules_state_from_field_repository($normalized, $fieldRepository);
        } catch (Throwable $e) {
            // Fall back to the inline JSON state if the dedicated rule-set tables are unavailable.
        }
    }
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
    $fieldRepository = extraction_field_repository_instance();
    if ($fieldRepository !== null) {
        try {
            $fieldRepository->replaceScopes(
                is_array($normalized['activeArchivingRules'] ?? null) ? $normalized['activeArchivingRules'] : [],
                is_array($normalized['draftArchivingRules'] ?? null) ? $normalized['draftArchivingRules'] : []
            );
            $normalized = hydrate_archiving_rules_state_from_field_repository($normalized, $fieldRepository);
        } catch (Throwable $e) {
            // Keep inline JSON persistence working even if the dedicated field tables are temporarily unavailable.
        }
    }
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
    return normalize_archiving_rules_set($rules);
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
        'systemLabels' => 'Fördefinierade etiketter',
        'fields' => 'Egna datafält',
        'predefinedFields' => 'Fördefinierade datafält',
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

function flatten_archive_folder_filename_templates(array $rules): array
{
    $flattened = [];
    $usedFolderIds = [];
    foreach (is_array($rules['archiveFolders'] ?? null) ? $rules['archiveFolders'] : [] as $folderIndex => $folder) {
        if (!is_array($folder)) {
            continue;
        }
        $normalizedFolder = normalize_archive_folder_definition($folder, is_int($folderIndex) ? $folderIndex : 0, $usedFolderIds);
        $folderId = is_string($normalizedFolder['id'] ?? null) ? trim((string) $normalizedFolder['id']) : '';
        $folderName = archive_folder_display_text($normalizedFolder);
        foreach (is_array($normalizedFolder['filenameTemplates'] ?? null) ? $normalizedFolder['filenameTemplates'] : [] as $templateIndex => $template) {
            if (!is_array($template)) {
                continue;
            }
            $templateId = is_string($template['id'] ?? null) ? trim((string) $template['id']) : '';
            $flattened[] = [
                'folderId' => $folderId,
                'folderName' => $folderName,
                'templateIndex' => is_int($templateIndex) ? $templateIndex : 0,
                'templateId' => $templateId,
                'template' => normalize_filename_template($template['template'] ?? null),
                'labelIds' => normalize_archive_rule_label_ids($template['labelIds'] ?? null),
            ];
        }
    }

    return $flattened;
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
    } elseif ($type === 'folder') {
        $label = 'Mapp';
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
    $activeTemplates = flatten_archive_folder_filename_templates($activeRules);
    $draftTemplates = flatten_archive_folder_filename_templates($draftRules);
    $nameMaps = filename_template_review_name_maps($activeRules, $draftRules);
    $activeByKey = [];
    $draftByKey = [];
    $orderedKeys = [];

    foreach ($activeTemplates as $index => $template) {
        if (!is_array($template)) {
            continue;
        }
        $templateId = is_string($template['templateId'] ?? null) ? trim((string) $template['templateId']) : '';
        $folderId = is_string($template['folderId'] ?? null) ? trim((string) $template['folderId']) : '';
        $key = ($folderId !== '' ? $folderId : '#folder') . ':' . ($templateId !== '' ? $templateId : ('#' . $index));
        $activeByKey[$key] = $template;
        $orderedKeys[] = $key;
    }

    foreach ($draftTemplates as $index => $template) {
        if (!is_array($template)) {
            continue;
        }
        $templateId = is_string($template['templateId'] ?? null) ? trim((string) $template['templateId']) : '';
        $folderId = is_string($template['folderId'] ?? null) ? trim((string) $template['folderId']) : '';
        $key = ($folderId !== '' ? $folderId : '#folder') . ':' . ($templateId !== '' ? $templateId : ('#' . $index));
        $draftByKey[$key] = $template;
        if (!in_array($key, $orderedKeys, true)) {
            $orderedKeys[] = $key;
        }
    }

    $changes = [];
    foreach ($orderedKeys as $key) {
        $activeTemplate = is_array($activeByKey[$key] ?? null) ? $activeByKey[$key] : [];
        $draftTemplate = is_array($draftByKey[$key] ?? null) ? $draftByKey[$key] : [];
        $before = normalize_filename_template($activeTemplate['template'] ?? null);
        $after = normalize_filename_template($draftTemplate['template'] ?? null);
        if (json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
            continue;
        }

        $folderName = '';
        $templateIndex = 0;
        $templateId = '';
        foreach ([$draftTemplate, $activeTemplate] as $template) {
            if (!is_array($template)) {
                continue;
            }
            $folderName = is_string($template['folderName'] ?? null) ? trim((string) $template['folderName']) : $folderName;
            $templateIndex = is_int($template['templateIndex'] ?? null) ? (int) $template['templateIndex'] : $templateIndex;
            $templateId = is_string($template['templateId'] ?? null) ? trim((string) $template['templateId']) : $templateId;
            if ($folderName !== '' || $templateId !== '') {
                break;
            }
        }
        $templateName = trim(($folderName !== '' ? $folderName . ' / ' : '') . 'Filnamnsmall ' . ($templateIndex + 1));

        $changes[] = [
            'filenameTemplateId' => $templateId,
            'filenameTemplateName' => $templateName,
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

function archived_job_historical_auto_result(string $jobId, array $job, int $activeVersion): array
{
    $snapshot = job_archiving_snapshot($job);
    if (is_array($snapshot) && (int) ($snapshot['approvedWithRulesVersion'] ?? 0) === $activeVersion) {
        return normalize_auto_archiving_result(
            is_array($snapshot['autoDetectedAtApproval'] ?? null) ? $snapshot['autoDetectedAtApproval'] : []
        );
    }

    $stored = job_analysis_snapshot($jobId);
    if (is_array($stored)) {
        return normalize_auto_archiving_result($stored);
    }

    return normalize_auto_archiving_result(
        is_array(($job['analysis'] ?? [])['autoArchivingResult'] ?? null)
            ? $job['analysis']['autoArchivingResult']
            : []
    );
}

function load_matching_settings_payload(): array
{
    $defaultPositionAdjustment = default_matching_position_adjustment_settings();
    $defaultPayload = [
        'replacements' => [],
        'positionAdjustment' => $defaultPositionAdjustment,
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

    $positionAdjustment = normalize_matching_position_adjustment_settings(
        is_array($decoded['positionAdjustment'] ?? null) ? $decoded['positionAdjustment'] : $decoded
    );

    return [
        'replacements' => $replacements,
        'positionAdjustment' => $positionAdjustment,
    ];
}

function load_matching_settings(): array
{
    $payload = load_matching_settings_payload();
    $rows = $payload['replacements'] ?? [];
    return is_array($rows) ? $rows : [];
}

function default_matching_position_adjustment_settings(): array
{
    return [
        'noisePenaltyPerCharacter' => 0.01,
        'rightYOffsetPenalty' => 0.25,
        'downXOffsetPenalty' => 0.25,
    ];
}

function normalize_matching_decimal_setting(mixed $value, float $fallback, ?float $max = 1.0): float
{
    if (is_string($value)) {
        $value = str_replace(',', '.', trim($value));
    }

    if (!is_numeric($value)) {
        $resolvedFallback = (float) $fallback;
        if ($resolvedFallback < 0.0) {
            $resolvedFallback = 0.0;
        }
        if ($max !== null && $resolvedFallback > $max) {
            $resolvedFallback = $max;
        }
        return $resolvedFallback;
    }

    $resolved = (float) $value;
    if ($resolved < 0.0) {
        $resolved = 0.0;
    }
    if ($max !== null && $resolved > $max) {
        $resolved = $max;
    }

    return $resolved;
}

function normalize_matching_position_adjustment_settings(?array $input): array
{
    $defaults = default_matching_position_adjustment_settings();
    $source = is_array($input) ? $input : [];

    return [
        'noisePenaltyPerCharacter' => normalize_matching_decimal_setting(
            $source['noisePenaltyPerCharacter'] ?? $source['noise_penalty_per_character'] ?? null,
            $defaults['noisePenaltyPerCharacter']
        ),
        'rightYOffsetPenalty' => normalize_matching_decimal_setting(
            $source['rightYOffsetPenalty'] ?? $source['right_y_offset_penalty'] ?? $source['downRightPenalty'] ?? $source['down_right_penalty'] ?? null,
            $defaults['rightYOffsetPenalty'],
            null
        ),
        'downXOffsetPenalty' => normalize_matching_decimal_setting(
            $source['downXOffsetPenalty'] ?? $source['down_x_offset_penalty'] ?? $source['downRightPenalty'] ?? $source['down_right_penalty'] ?? null,
            $defaults['downXOffsetPenalty'],
            null
        ),
    ];
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

function find_loaded_archive_folder_by_id(array $folders, string $folderId): ?array
{
    foreach ($folders as $folder) {
        if (!is_array($folder)) {
            continue;
        }
        $id = is_string($folder['id'] ?? null) ? trim((string) $folder['id']) : '';
        if ($id === $folderId) {
            return $folder;
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
    $archiveFolders = load_archive_folders();
    $knownLabelIds = known_job_label_ids();

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

    if (array_key_exists('selectedFolderId', $payload)) {
        $value = $payload['selectedFolderId'];
        if ($value === null || $value === '') {
            unset($job['selectedFolderId']);
        } else {
            $folderId = is_string($value) ? trim($value) : '';
            $folder = find_loaded_archive_folder_by_id($archiveFolders, $folderId);
            if ($folderId === '' || !is_array($folder)) {
                throw new RuntimeException('Ogiltig mapp');
            }
            $job['selectedFolderId'] = $folderId;
        }
    }

    if (array_key_exists('selectedLabelIds', $payload)) {
        $value = $payload['selectedLabelIds'];
        if ($value === null) {
            unset($job['selectedLabelIds']);
        } else {
            $job['selectedLabelIds'] = normalize_selected_job_label_ids_payload($value, $knownLabelIds);
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
        || array_key_exists('selectedFolderId', $payload)
        || array_key_exists('selectedLabelIds', $payload)
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
    $archiveFolders = load_archive_folders();
    $knownLabelIds = known_job_label_ids();

    $selectedClientDirName = array_key_exists('selectedClientDirName', $payload)
        ? (is_string($payload['selectedClientDirName'] ?? null) ? trim((string) $payload['selectedClientDirName']) : '')
        : (is_string($job['selectedClientDirName'] ?? null) ? trim((string) $job['selectedClientDirName']) : '');
    $selectedSenderId = array_key_exists('selectedSenderId', $payload)
        ? (int) ($payload['selectedSenderId'] ?? 0)
        : (int) ($job['selectedSenderId'] ?? 0);
    $selectedFolderId = array_key_exists('selectedFolderId', $payload)
        ? (is_string($payload['selectedFolderId'] ?? null) ? trim((string) $payload['selectedFolderId']) : '')
        : (is_string($job['selectedFolderId'] ?? null) ? trim((string) $job['selectedFolderId']) : '');
    $selectedLabelIds = array_key_exists('selectedLabelIds', $payload)
        ? (($payload['selectedLabelIds'] ?? null) === null ? null : normalize_selected_job_label_ids_payload($payload['selectedLabelIds'], $knownLabelIds))
        : (array_key_exists('selectedLabelIds', $job) ? normalize_stored_job_label_ids($job['selectedLabelIds']) : null);
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

    $archiveFolder = find_loaded_archive_folder_by_id($archiveFolders, $selectedFolderId);
    if (!is_array($archiveFolder)) {
        throw new RuntimeException('Ogiltig mapp');
    }

    $activeRules = load_active_archiving_rules();
    $activeVersion = active_archiving_rules_version();
    $autoAnalysis = calculate_auto_archiving_result_for_job($config, $jobId, $activeRules, $job);
    $autoDetectedAtApproval = normalize_auto_archiving_result(
        is_array($autoAnalysis['autoArchivingResult'] ?? null) ? $autoAnalysis['autoArchivingResult'] : []
    );
    $userApprovedAtApproval = approved_archiving_from_archive_request($job, $autoDetectedAtApproval, $payload, $archiveFolders);

    $filename = sanitize_pdf_filename(
        $filenameInput !== ''
            ? $filenameInput
            : (is_string($userApprovedAtApproval['filename'] ?? null) ? (string) $userApprovedAtApproval['filename'] : (string) ($job['originalFilename'] ?? 'dokument.pdf'))
    );
    $folderPath = render_archive_folder_path($userApprovedAtApproval, $archiveFolder, $activeRules, $senders);
    $targetDirectory = rtrim($outputBaseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $selectedClientDirName;
    if ($folderPath !== '') {
        $targetDirectory .= DIRECTORY_SEPARATOR . $folderPath;
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
    $job['selectedFolderId'] = $selectedFolderId;
    if ($selectedLabelIds === null) {
        unset($job['selectedLabelIds']);
    } else {
        $job['selectedLabelIds'] = $selectedLabelIds;
    }
    $job['filename'] = $filename;
    $approvedSnapshotValue = normalize_auto_archiving_result(array_merge($userApprovedAtApproval, [
        'filename' => $filename,
        'clientId' => $selectedClientDirName,
        'senderId' => $selectedSenderId,
        'folderId' => $selectedFolderId,
        'archiveFolderPath' => $folderPath,
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

function build_merged_objects_txt_from_pages(array $pages): string
{
    $chunks = [];
    foreach ($pages as $index => $page) {
        if (!is_array($page)) {
            continue;
        }
        $pageNumber = is_numeric($page['pageNumber'] ?? null)
            ? (int) $page['pageNumber']
            : ($index + 1);
        if ($pageNumber <= 0) {
            $pageNumber = $index + 1;
        }
        $pageText = rtrim(render_grid_text_from_debug_payload($page), "\r\n");
        $chunks[] = '=== PAGE ' . $pageNumber . " ===\n" . $pageText;
    }

    return implode("\n\n", $chunks);
}

function write_merged_objects_text_from_pages(string $jobDir, array $pages): void
{
    $targetPath = $jobDir . '/merged_objects.txt';
    $combined = build_merged_objects_txt_from_pages($pages);
    if ($combined === '') {
        if (is_file($targetPath)) {
            @unlink($targetPath);
        }
        return;
    }

    file_put_contents($targetPath, $combined . "\n");
}

function job_id_from_directory(string $jobDir): ?string
{
    $jobId = trim((string) basename(rtrim($jobDir, DIRECTORY_SEPARATOR)));
    return is_valid_job_id($jobId) ? $jobId : null;
}

function ensure_merged_objects_text_from_storage(string $jobDir, ?string $jobId = null): array
{
    $resolvedJobId = is_string($jobId) && is_valid_job_id($jobId) ? $jobId : job_id_from_directory($jobDir);
    if ($resolvedJobId === null) {
        return [];
    }

    $pages = stored_merged_objects_pages($resolvedJobId);
    if ($pages === []) {
        return [];
    }

    $targetPath = $jobDir . '/merged_objects.txt';
    if (!is_file($targetPath)) {
        write_merged_objects_text_from_pages($jobDir, $pages);
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

function build_grid_text_lines_from_debug_words(array $words): array
{
    if ($words === []) {
        return [];
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
        return [];
    }

    $charWidth = median_float($wordWidths, 18.0);
    $lineHeight = median_float($lineHeights, 40.0);
    $rows = ocr_layout_group_words_into_rows($normalized);
    $rowTops = [];

    if ($rows === []) {
        return [];
    }

    $grid = [];
    $gridSegments = [];
    foreach ($rows as $rowWords) {
        $wordTop = min(array_map(static fn(array $word): float => (float) ($word['y0'] ?? 0.0), $rowWords));
        $candidateRow = (int) round($wordTop / max($lineHeight, 1.0));
        while (isset($rowTops[$candidateRow]) && abs($rowTops[$candidateRow] - $wordTop) > ($lineHeight * 0.35)) {
            $candidateRow++;
        }
        if (!isset($grid[$candidateRow])) {
            $grid[$candidateRow] = '';
            $gridSegments[$candidateRow] = [];
            $rowTops[$candidateRow] = $wordTop;
        }
        $buffer = '';
        $cursor = 0;
        $segments = [];
        foreach ($rowWords as $word) {
            $targetCol = (int) round(((float) ($word['x0'] ?? 0.0)) / max($charWidth, 1.0));
            if ($targetCol <= $cursor) {
                $targetCol = $cursor > 0 ? $cursor + 1 : 0;
            }
            while ($cursor < $targetCol) {
                $buffer .= ' ';
                $cursor++;
            }
            $start = strlen($buffer);
            $text = (string) ($word['text'] ?? '');
            $buffer .= $text;
            $cursor += utf8_strlen_safe($text);
            $segments[] = [
                'start' => $start,
                'end' => strlen($buffer),
                'text' => $text,
                'bbox' => [
                    'x0' => (float) ($word['x0'] ?? 0.0),
                    'y0' => (float) ($word['y0'] ?? 0.0),
                    'x1' => (float) ($word['x1'] ?? 0.0),
                    'y1' => (float) ($word['y1'] ?? 0.0),
                ],
            ];
        }
        $grid[$candidateRow] = $buffer;
        $gridSegments[$candidateRow] = $segments;
    }

    $pageLines = [];
    $previousRow = null;
    ksort($grid, SORT_NUMERIC);
    foreach ($grid as $rowIndex => $buffer) {
        if ($previousRow !== null) {
            $gap = max(0, $rowIndex - $previousRow - 1);
            for ($i = 0; $i < $gap; $i++) {
                $pageLines[] = [
                    'text' => '',
                    'segments' => [],
                ];
            }
        }
        $pageLines[] = [
            'text' => rtrim($buffer),
            'segments' => array_values(array_filter(
                is_array($gridSegments[$rowIndex] ?? null) ? $gridSegments[$rowIndex] : [],
                static fn($segment): bool => is_array($segment)
            )),
        ];
        $previousRow = $rowIndex;
    }

    return $pageLines;
}

function render_grid_text_from_debug_words(array $words): string
{
    $pageLines = build_grid_text_lines_from_debug_words($words);
    if ($pageLines === []) {
        return '';
    }

    $lines = array_map(static function (array $line): string {
        return is_string($line['text'] ?? null) ? (string) $line['text'] : '';
    }, $pageLines);

    return rtrim(implode("\n", $lines));
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

    $mergedWords = [];
    foreach (is_array($rapidocrPayload['lines'] ?? null) ? $rapidocrPayload['lines'] : [] as $line) {
        if (!is_array($line)) {
            continue;
        }
        $segments = merge_rapidocr_line_into_segments($line);
        $segments = apply_tesseract_swedish_truth_to_segments($segments, $tesseractWords);
        foreach ($segments as $segment) {
            $mergedWords[] = $segment;
        }
    }

    $pageText = render_grid_text_from_debug_payload([
        'pageWidth' => $pageWidth,
        'pageHeight' => $pageHeight,
        'words' => $mergedWords,
        'text' => '',
    ]);

    return [
        'engine' => 'merged_objects',
        'pageNumber' => $pageNumber,
        'pageIndex' => max(0, $pageNumber - 1),
        'sourceImage' => $sourceImage,
        'pageWidth' => $pageWidth,
        'pageHeight' => $pageHeight,
        'words' => $mergedWords,
        'text' => rtrim($pageText, "\r\n"),
    ];
}

function build_merged_objects_document_from_rapidocr_pages(array $rapidocrPages, array $tesseractPages = []): ?array
{
    if ($rapidocrPages === []) {
        return null;
    }

    $pages = [];
    foreach ($rapidocrPages as $pageIndex => $rapidocrPage) {
        if (!is_array($rapidocrPage)) {
            continue;
        }
        $pageNumber = is_numeric($rapidocrPage['pageNumber'] ?? null) ? (int) $rapidocrPage['pageNumber'] : ($pageIndex + 1);
        if ($pageNumber <= 0) {
            $pageNumber = $pageIndex + 1;
        }
        $tesseractPage = is_array($tesseractPages[$pageIndex] ?? null) ? $tesseractPages[$pageIndex] : [];
        $pages[] = build_merged_objects_payload_from_rapidocr_page($rapidocrPage, $pageNumber, $tesseractPage);
    }

    if ($pages === []) {
        return null;
    }

    return [
        'engine' => 'merged_objects',
        'pages' => $pages,
    ];
}

function write_merged_object_debug_files_from_rapidocr(string $jobDir): void
{
    $rapidocrPages = load_job_engine_debug_pages($jobDir, 'rapidocr');
    $tesseractPages = load_job_engine_debug_pages($jobDir, 'tesseract');
    $jobId = job_id_from_directory($jobDir);
    if ($rapidocrPages === []) {
        if ($jobId !== null) {
            clear_job_merged_objects_document($jobId);
        }
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

    $document = build_merged_objects_document_from_rapidocr_pages($rapidocrPages, $tesseractPages);
    if ($document === null) {
        if ($jobId !== null) {
            clear_job_merged_objects_document($jobId);
        }
        if (is_file($jobDir . '/merged_objects.txt')) {
            @unlink($jobDir . '/merged_objects.txt');
        }
        return;
    }

    if ($jobId !== null) {
        sync_job_merged_objects_document($jobId, $document);
    }

    foreach (glob($jobDir . '/merged_objects_page_*.json') ?: [] as $path) {
        @unlink($path);
    }
    foreach (glob($jobDir . '/merged_objects_page_*.txt') ?: [] as $path) {
        @unlink($path);
    }

    write_merged_objects_text_from_pages($jobDir, is_array($document['pages'] ?? null) ? $document['pages'] : []);
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

    static $unicodeLowerMap = [
        'À' => 'à', 'Á' => 'á', 'Â' => 'â', 'Ã' => 'ã', 'Ä' => 'ä', 'Å' => 'å',
        'Æ' => 'æ', 'Ç' => 'ç', 'È' => 'è', 'É' => 'é', 'Ê' => 'ê', 'Ë' => 'ë',
        'Ì' => 'ì', 'Í' => 'í', 'Î' => 'î', 'Ï' => 'ï', 'Ð' => 'ð', 'Ñ' => 'ñ',
        'Ò' => 'ò', 'Ó' => 'ó', 'Ô' => 'ô', 'Õ' => 'õ', 'Ö' => 'ö', 'Ø' => 'ø',
        'Ù' => 'ù', 'Ú' => 'ú', 'Û' => 'û', 'Ü' => 'ü', 'Ý' => 'ý', 'Þ' => 'þ',
        'Ā' => 'ā', 'Ă' => 'ă', 'Ą' => 'ą', 'Ć' => 'ć', 'Ĉ' => 'ĉ', 'Ċ' => 'ċ',
        'Č' => 'č', 'Ď' => 'ď', 'Đ' => 'đ', 'Ē' => 'ē', 'Ĕ' => 'ĕ', 'Ė' => 'ė',
        'Ę' => 'ę', 'Ě' => 'ě', 'Ĝ' => 'ĝ', 'Ğ' => 'ğ', 'Ġ' => 'ġ', 'Ģ' => 'ģ',
        'Ĥ' => 'ĥ', 'Ħ' => 'ħ', 'Ĩ' => 'ĩ', 'Ī' => 'ī', 'Ĭ' => 'ĭ', 'Į' => 'į',
        'İ' => 'i', 'Ĳ' => 'ĳ', 'Ĵ' => 'ĵ', 'Ķ' => 'ķ', 'Ĺ' => 'ĺ', 'Ļ' => 'ļ',
        'Ľ' => 'ľ', 'Ŀ' => 'ŀ', 'Ł' => 'ł', 'Ń' => 'ń', 'Ņ' => 'ņ', 'Ň' => 'ň',
        'Ŋ' => 'ŋ', 'Ō' => 'ō', 'Ŏ' => 'ŏ', 'Ő' => 'ő', 'Œ' => 'œ', 'Ŕ' => 'ŕ',
        'Ŗ' => 'ŗ', 'Ř' => 'ř', 'Ś' => 'ś', 'Ŝ' => 'ŝ', 'Ş' => 'ş', 'Š' => 'š',
        'Ţ' => 'ţ', 'Ť' => 'ť', 'Ŧ' => 'ŧ', 'Ũ' => 'ũ', 'Ū' => 'ū', 'Ŭ' => 'ŭ',
        'Ů' => 'ů', 'Ű' => 'ű', 'Ų' => 'ų', 'Ŵ' => 'ŵ', 'Ŷ' => 'ŷ', 'Ÿ' => 'ÿ',
        'Ź' => 'ź', 'Ż' => 'ż', 'Ž' => 'ž', 'ẞ' => 'ß',
    ];

    return strtolower(strtr($text, $unicodeLowerMap));
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

function build_label_matching_field_name_map(array $fields): array
{
    $names = [];
    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $key = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        $name = is_string($field['name'] ?? null) ? trim((string) $field['name']) : '';
        if ($key === '' || $name === '' || isset($names[$key])) {
            continue;
        }
        $names[$key] = $name;
    }
    return $names;
}

function resolve_label_matching_sender_context(array $fieldValues): array
{
    sync_named_sender_identifier_links();

    $organizationNumbers = normalize_auto_archiving_field_value_list($fieldValues['organisationsnummer'] ?? null);
    $bankgiroValues = normalize_auto_archiving_field_value_list($fieldValues['bankgiro'] ?? null);
    $plusgiroValues = normalize_auto_archiving_field_value_list($fieldValues['plusgiro'] ?? null);
    $senderRowsById = cached_sender_editor_rows_by_id();

    $normalizedOrganizationNumbers = [];
    foreach ($organizationNumbers as $organizationNumber) {
        $normalized = \Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber((string) $organizationNumber);
        if ($normalized !== null) {
            $normalizedOrganizationNumbers[$normalized] = true;
        }
    }

    $normalizedBankgiroValues = [];
    foreach ($bankgiroValues as $bankgiro) {
        $normalized = \Docflow\Senders\IdentifierNormalizer::normalizeBankgiro((string) $bankgiro);
        if ($normalized !== null) {
            $normalizedBankgiroValues[$normalized] = true;
        }
    }

    $normalizedPlusgiroValues = [];
    foreach ($plusgiroValues as $plusgiro) {
        $normalized = \Docflow\Senders\IdentifierNormalizer::normalizePlusgiro((string) $plusgiro);
        if ($normalized !== null) {
            $normalizedPlusgiroValues[$normalized] = true;
        }
    }

    $senderIds = [];
    foreach ($organizationNumbers as $organizationNumber) {
        $observedRow = observed_sender_organization_summary_row((string) $organizationNumber);
        $senderId = isset($observedRow['senderId']) ? (int) $observedRow['senderId'] : 0;
        if ($senderId > 0) {
            $senderIds[$senderId] = true;
        }
    }
    foreach ($bankgiroValues as $bankgiro) {
        $observedRow = observed_sender_payment_summary_row('bankgiro', (string) $bankgiro);
        $senderId = isset($observedRow['senderId']) ? (int) $observedRow['senderId'] : 0;
        if ($senderId > 0) {
            $senderIds[$senderId] = true;
        }
    }
    foreach ($plusgiroValues as $plusgiro) {
        $observedRow = observed_sender_payment_summary_row('plusgiro', (string) $plusgiro);
        $senderId = isset($observedRow['senderId']) ? (int) $observedRow['senderId'] : 0;
        if ($senderId > 0) {
            $senderIds[$senderId] = true;
        }
    }

    $candidates = [];
    foreach (array_keys($senderIds) as $senderId) {
        $senderRow = $senderRowsById[$senderId] ?? null;
        if (!is_array($senderRow)) {
            continue;
        }
        $name = is_string($senderRow['name'] ?? null) ? trim((string) $senderRow['name']) : '';
        if ($name === '') {
            continue;
        }

        $strongMatchCount = 0;
        $organizationRows = is_array($senderRow['organizationNumbers'] ?? null) ? $senderRow['organizationNumbers'] : [];
        foreach ($organizationRows as $organizationRow) {
            if (!is_array($organizationRow)) {
                continue;
            }
            $normalizedNumber = \Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber((string) ($organizationRow['organizationNumber'] ?? ''));
            if ($normalizedNumber !== null && isset($normalizedOrganizationNumbers[$normalizedNumber])) {
                $strongMatchCount++;
                break;
            }
        }

        $paymentRows = is_array($senderRow['paymentNumbers'] ?? null) ? $senderRow['paymentNumbers'] : [];
        foreach ($paymentRows as $paymentRow) {
            if (!is_array($paymentRow)) {
                continue;
            }
            $paymentType = is_string($paymentRow['type'] ?? null) ? trim(strtolower((string) $paymentRow['type'])) : 'bankgiro';
            $normalizedNumber = $paymentType === 'plusgiro'
                ? \Docflow\Senders\IdentifierNormalizer::normalizePlusgiro((string) ($paymentRow['number'] ?? ''))
                : \Docflow\Senders\IdentifierNormalizer::normalizeBankgiro((string) ($paymentRow['number'] ?? ''));
            if ($normalizedNumber === null) {
                continue;
            }
            if (
                ($paymentType === 'plusgiro' && isset($normalizedPlusgiroValues[$normalizedNumber]))
                || ($paymentType !== 'plusgiro' && isset($normalizedBankgiroValues[$normalizedNumber]))
            ) {
                $strongMatchCount++;
            }
        }

        $candidates[] = [
            'senderId' => $senderId,
            'name' => $name,
            'strongMatchCount' => $strongMatchCount,
        ];
    }

    usort($candidates, static function (array $left, array $right): int {
        $strongCompare = (int) ($right['strongMatchCount'] ?? 0) <=> (int) ($left['strongMatchCount'] ?? 0);
        if ($strongCompare !== 0) {
            return $strongCompare;
        }
        return strcmp(
            strtolower((string) ($left['name'] ?? '')),
            strtolower((string) ($right['name'] ?? ''))
        );
    });

    $senderNamesById = [];
    foreach ($senderRowsById as $senderId => $senderRow) {
        if (!is_array($senderRow)) {
            continue;
        }
        $name = is_string($senderRow['name'] ?? null) ? trim((string) $senderRow['name']) : '';
        if ($name !== '') {
            $senderNamesById[(int) $senderId] = $name;
        }
    }

    $first = $candidates[0] ?? null;
    return [
        'senderId' => is_array($first) ? (int) ($first['senderId'] ?? 0) : 0,
        'senderName' => is_array($first) && is_string($first['name'] ?? null) ? trim((string) $first['name']) : '',
        'senderNamesById' => $senderNamesById,
    ];
}

function find_scored_rule_signal_matches(string $ocrText, array $entities, array $replacementMap, array $context = []): array
{
    $inverseMap = build_inverse_single_char_map($replacementMap);
    $matches = [];
    $matchedSenderId = isset($context['senderId']) ? (int) $context['senderId'] : 0;
    $matchedSenderName = is_string($context['senderName'] ?? null) ? trim((string) $context['senderName']) : '';
    $senderNamesById = is_array($context['senderNamesById'] ?? null) ? $context['senderNamesById'] : [];
    $fieldValues = is_array($context['fieldValues'] ?? null) ? $context['fieldValues'] : [];
    $fieldNamesByKey = is_array($context['fieldNamesByKey'] ?? null) ? $context['fieldNamesByKey'] : [];

    foreach ($entities as $entityIndex => $entity) {
        if (!is_array($entity)) {
            continue;
        }

        $rules = $entity['rules'] ?? [];
        if (!is_array($rules) || count($rules) === 0) {
            continue;
        }

        $minScore = positive_int($entity['minScore'] ?? 1, 1);
        $entityId = is_string($entity['id'] ?? null) ? trim((string) $entity['id']) : '';
        $entityName = is_string($entity['name'] ?? null) ? trim((string) $entity['name']) : '';
        $entityPath = is_string($entity['path'] ?? null) ? trim((string) $entity['path']) : '';
        $archiveFolderName = is_string($entity['archiveFolderName'] ?? null) ? trim((string) $entity['archiveFolderName']) : '';
        $systemLabelKey = is_string($entity['systemLabelKey'] ?? null) ? trim((string) $entity['systemLabelKey']) : '';
        $isSystemLabel = ($entity['isSystemLabel'] ?? false) === true;
        if ($entityId === '') {
            continue;
        }
        if ($entityName === '') {
            $entityName = 'Namnlös post';
        }

        $score = 0;
        $matchedRules = [];

        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $ruleType = is_string($rule['type'] ?? null) ? trim(strtolower((string) $rule['type'])) : 'text';
            $ruleText = is_string($rule['text'] ?? null) ? trim((string) $rule['text']) : '';
            $ruleSenderId = isset($rule['senderId']) ? (int) $rule['senderId'] : 0;
            $ruleField = is_string($rule['field'] ?? null) ? trim((string) $rule['field']) : '';
            $ruleScore = positive_int($rule['score'] ?? 1, 1);
            $sourceText = '';
            $displayText = $ruleText;

            if ($ruleType === 'sender_is') {
                if ($ruleSenderId < 1 || $matchedSenderId < 1 || $ruleSenderId !== $matchedSenderId) {
                    continue;
                }
                $senderName = is_string($senderNamesById[$ruleSenderId] ?? null)
                    ? trim((string) $senderNamesById[$ruleSenderId])
                    : '';
                $sourceText = $matchedSenderName !== '' ? $matchedSenderName : ($senderName !== '' ? $senderName : (string) $ruleSenderId);
                $displayText = 'Avsändare är: ' . ($senderName !== '' ? $senderName : (string) $ruleSenderId);
            } elseif ($ruleType === 'sender_name_contains') {
                if ($ruleText === '' || $matchedSenderName === '') {
                    continue;
                }
                $ruleTextLower = normalize_for_matching($ruleText, $replacementMap);
                $senderNameLower = normalize_for_matching($matchedSenderName, $replacementMap);
                if (!str_contains($senderNameLower, $ruleTextLower)) {
                    continue;
                }
                $sourceText = $matchedSenderName;
                $displayText = 'Avsändarnamn innehåller: ' . $ruleText;
            } elseif ($ruleType === 'field_exists') {
                if ($ruleField === '') {
                    continue;
                }
                $fieldValueList = normalize_auto_archiving_field_value_list($fieldValues[$ruleField] ?? null);
                if ($fieldValueList === []) {
                    continue;
                }
                $fieldName = is_string($fieldNamesByKey[$ruleField] ?? null) ? trim((string) $fieldNamesByKey[$ruleField]) : '';
                $firstValue = $fieldValueList[0] ?? null;
                $sourceText = is_scalar($firstValue) ? trim((string) $firstValue) : ($fieldName !== '' ? $fieldName : $ruleField);
                $displayText = 'Fält finns: ' . ($fieldName !== '' ? $fieldName : $ruleField);
            } else {
                if ($ruleText === '') {
                    continue;
                }

                $ruleIsRegex = $rule['isRegex'] ?? false;
                $textPattern = build_label_rule_text_regex(
                    $ruleText,
                    $ruleIsRegex === true || $ruleIsRegex === 1 || $ruleIsRegex === '1',
                    $replacementMap
                );
                if (!is_string($textPattern)) {
                    continue;
                }

                $textMatches = [];
                if (@preg_match($textPattern, $ocrText, $textMatches) !== 1) {
                    continue;
                }

                $sourceText = is_string($textMatches[0] ?? null) && trim((string) $textMatches[0]) !== ''
                    ? (string) $textMatches[0]
                    : find_source_text_for_rule($ocrText, $ruleText, $inverseMap);
            }
            $score += $ruleScore;
            $matchedRules[] = [
                'type' => $ruleType,
                'text' => $displayText,
                'isRegex' => $ruleType === 'text' && ($rule['isRegex'] ?? false) === true,
                'senderId' => $ruleType === 'sender_is' ? $ruleSenderId : null,
                'field' => $ruleType === 'field_exists' ? $ruleField : '',
                'sourceText' => $sourceText,
                'score' => $ruleScore,
            ];
        }

        if (count($matchedRules) === 0) {
            continue;
        }

        $matches[] = [
            'id' => $entityId,
            'name' => $entityName,
            'path' => $entityPath,
            'archiveFolderName' => $archiveFolderName,
            'systemLabelKey' => $systemLabelKey,
            'isSystemLabel' => $isSystemLabel,
            'minScore' => $minScore,
            'score' => $score,
            'matchedRules' => $matchedRules,
            '_categoryOrder' => is_int($entityIndex) ? $entityIndex : 0,
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

function filter_scored_rule_matches_by_threshold(array $matches): array
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

function find_scored_rule_matches(string $ocrText, array $entities, array $replacementMap, array $context = []): array
{
    $signalMatches = find_scored_rule_signal_matches($ocrText, $entities, $replacementMap, $context);
    return filter_scored_rule_matches_by_threshold($signalMatches);
}

function select_archive_folder_filename_template(array $folder, array $matchedLabelsById): ?array
{
    $templates = is_array($folder['filenameTemplates'] ?? null) ? array_values($folder['filenameTemplates']) : [];
    if ($templates === []) {
        return null;
    }

    $candidates = [];
    foreach ($templates as $templateIndex => $template) {
        if (!is_array($template)) {
            continue;
        }
        $labelIds = normalize_archive_rule_label_ids($template['labelIds'] ?? null);
        $matchedCount = 0;
        foreach ($labelIds as $labelId) {
            if (is_array($matchedLabelsById[$labelId] ?? null)) {
                $matchedCount += 1;
            }
        }
        if ($labelIds !== [] && $matchedCount < count($labelIds)) {
            continue;
        }
        $candidates[] = [
            'template' => $template,
            'templateIndex' => is_int($templateIndex) ? $templateIndex : 0,
            'matchedCount' => $matchedCount,
            'conditionCount' => count($labelIds),
        ];
    }

    if ($candidates === []) {
        $firstTemplate = $templates[0] ?? null;
        return is_array($firstTemplate)
            ? [
                'template' => $firstTemplate,
                'templateIndex' => 0,
                'matchedCount' => 0,
                'conditionCount' => count(normalize_archive_rule_label_ids($firstTemplate['labelIds'] ?? null)),
            ]
            : null;
    }

    usort($candidates, static function (array $left, array $right): int {
        $matchedCompare = (int) ($right['matchedCount'] ?? 0) <=> (int) ($left['matchedCount'] ?? 0);
        if ($matchedCompare !== 0) {
            return $matchedCompare;
        }
        $conditionCompare = (int) ($right['conditionCount'] ?? 0) <=> (int) ($left['conditionCount'] ?? 0);
        if ($conditionCompare !== 0) {
            return $conditionCompare;
        }
        return (int) ($left['templateIndex'] ?? 0) <=> (int) ($right['templateIndex'] ?? 0);
    });

    return $candidates[0] ?? null;
}

function select_archive_folder_filename_template_by_label_ids(array $folder, array $labelIds): ?array
{
    $matchedLabelsById = [];
    foreach ($labelIds as $labelId) {
        $resolvedId = is_string($labelId) ? trim((string) $labelId) : '';
        if ($resolvedId === '') {
            continue;
        }
        $matchedLabelsById[$resolvedId] = ['id' => $resolvedId];
    }

    return select_archive_folder_filename_template($folder, $matchedLabelsById);
}

function select_archive_folder_by_labels(array $folders, array $matchedLabelsById): ?array
{
    $candidates = [];

    foreach (array_values($folders) as $folderIndex => $folder) {
        if (!is_array($folder)) {
            continue;
        }

        $folderId = is_string($folder['id'] ?? null) ? trim((string) $folder['id']) : '';
        if ($folderId === '') {
            continue;
        }

        $bestMatchedCount = 0;
        $bestConditionCount = 0;
        foreach (is_array($folder['filenameTemplates'] ?? null) ? $folder['filenameTemplates'] : [] as $template) {
            if (!is_array($template)) {
                continue;
            }

            $labelIds = normalize_archive_rule_label_ids($template['labelIds'] ?? null);
            if ($labelIds === []) {
                continue;
            }

            $matchedCount = 0;
            foreach ($labelIds as $labelId) {
                if (is_array($matchedLabelsById[$labelId] ?? null)) {
                    $matchedCount += 1;
                }
            }

            $conditionCount = count($labelIds);
            if ($matchedCount < $conditionCount) {
                continue;
            }
            if ($matchedCount > $bestMatchedCount || ($matchedCount === $bestMatchedCount && $conditionCount > $bestConditionCount)) {
                $bestMatchedCount = $matchedCount;
                $bestConditionCount = $conditionCount;
            }
        }

        if ($bestMatchedCount < 1) {
            continue;
        }

        $candidates[] = [
            'folder' => $folder,
            'folderIndex' => is_int($folderIndex) ? $folderIndex : 0,
            'priority' => positive_int($folder['priority'] ?? 1, 1),
            'matchedCount' => $bestMatchedCount,
            'conditionCount' => $bestConditionCount,
        ];
    }

    if ($candidates === []) {
        return null;
    }

    usort($candidates, static function (array $left, array $right): int {
        $priorityCompare = (int) ($right['priority'] ?? 1) <=> (int) ($left['priority'] ?? 1);
        if ($priorityCompare !== 0) {
            return $priorityCompare;
        }

        return (int) ($left['folderIndex'] ?? 0) <=> (int) ($right['folderIndex'] ?? 0);
    });

    return $candidates[0] ?? null;
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
        $labelMatches = find_scored_rule_matches($ocrText, [$label], $replacementMap, $currentContext);
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

function regex_pattern_with_whitespace_wildcards(string $pattern): string
{
    $rewritten = preg_replace('/\s+/u', '\\s+', $pattern);
    return is_string($rewritten) ? $rewritten : $pattern;
}

function build_literal_space_flexible_regex(string $text, array $replacementMap, bool $useWordBoundaries): ?string
{
    $trimmed = trim($text);
    if ($trimmed === '') {
        return null;
    }

    $inverseMap = build_inverse_single_char_map($replacementMap);
    $segments = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($segments) || $segments === []) {
        return null;
    }

    $patternParts = [];
    foreach ($segments as $segment) {
        if (!is_string($segment) || $segment === '') {
            continue;
        }

        $segmentPattern = build_rule_match_pattern($segment, $inverseMap);
        if (!is_string($segmentPattern) || strlen($segmentPattern) < 4) {
            return null;
        }

        $body = substr($segmentPattern, 1, -3);
        if (!is_string($body) || $body === '') {
            return null;
        }

        $patternParts[] = $body;
    }

    if ($patternParts === []) {
        return null;
    }

    $joined = implode('\s+', $patternParts);
    if ($useWordBoundaries) {
        return '/\b' . $joined . '\b/iu';
    }

    return '/' . $joined . '/iu';
}

function build_label_rule_text_regex(string $ruleText, bool $isRegex, array $replacementMap): ?string
{
    $trimmed = trim($ruleText);
    if ($trimmed === '') {
        return null;
    }

    if ($isRegex) {
        return '/' . str_replace('/', '\/', regex_pattern_with_whitespace_wildcards($trimmed)) . '/iu';
    }

    return build_literal_space_flexible_regex($trimmed, $replacementMap, false);
}

function build_data_field_search_term_regex(string $searchTerm, array $replacementMap, bool $isRegex = false): ?string
{
    $trimmed = trim($searchTerm);
    if ($trimmed === '') {
        return null;
    }

    if ($isRegex) {
        return '/' . str_replace('/', '\/', regex_pattern_with_whitespace_wildcards($trimmed)) . '/iu';
    }

    return build_literal_space_flexible_regex($trimmed, $replacementMap, true);
}

function find_label_hits(array $lines, array $labels, array $replacementMap, bool $isRegex = false): array
{
    $compiled = [];
    foreach ($labels as $label) {
        $labelText = '';
        $labelIsRegex = $isRegex;
        if (is_array($label)) {
            $labelText = is_string($label['text'] ?? null)
                ? (string) $label['text']
                : (is_string($label['label'] ?? null) ? (string) $label['label'] : '');
            $labelIsRegex = normalize_extraction_field_is_regex($label['isRegex'] ?? $isRegex);
        } elseif (is_string($label) || is_numeric($label)) {
            $labelText = (string) $label;
        }

        if ($labelText === '') {
            continue;
        }

        $pattern = build_data_field_search_term_regex($labelText, $replacementMap, $labelIsRegex);
        if (!is_string($pattern)) {
            continue;
        }

        $compiled[] = [
            'label' => $labelText,
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

        $lineHits = [];
        foreach ($compiled as $item) {
            $pattern = $item['pattern'];
            $labelMatches = [];
            if (@preg_match_all($pattern, $line, $labelMatches, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }

            $fullMatches = $labelMatches[0] ?? null;
            if (!is_array($fullMatches)) {
                continue;
            }

            foreach ($fullMatches as $matched) {
                $matchedText = is_array($matched) && is_string($matched[0] ?? null) ? (string) $matched[0] : '';
                $labelStart = is_array($matched) && is_int($matched[1] ?? null) ? (int) $matched[1] : 0;
                if ($matchedText === '') {
                    continue;
                }

                $labelEnd = $labelStart + strlen($matchedText);
                $lineHits[] = [
                    'index' => is_int($index) ? (int) $index : 0,
                    'line' => $line,
                    'pattern' => $pattern,
                    'label' => $item['label'],
                    'labelStart' => $labelStart,
                    'labelEnd' => $labelEnd,
                    '_matchedLength' => strlen($matchedText),
                ];
            }
        }

        if ($lineHits === []) {
            continue;
        }

        usort($lineHits, static function (array $left, array $right): int {
            $startCompare = ((int) ($left['labelStart'] ?? 0)) <=> ((int) ($right['labelStart'] ?? 0));
            if ($startCompare !== 0) {
                return $startCompare;
            }

            $lengthCompare = ((int) ($right['_matchedLength'] ?? 0)) <=> ((int) ($left['_matchedLength'] ?? 0));
            if ($lengthCompare !== 0) {
                return $lengthCompare;
            }

            return ((int) ($right['labelEnd'] ?? 0)) <=> ((int) ($left['labelEnd'] ?? 0));
        });

        $selectedLineHits = [];
        foreach ($lineHits as $candidateHit) {
            $candidateStart = (int) ($candidateHit['labelStart'] ?? 0);
            $candidateEnd = (int) ($candidateHit['labelEnd'] ?? 0);
            $candidateLength = (int) ($candidateHit['_matchedLength'] ?? 0);
            $replaced = false;

            foreach ($selectedLineHits as $selectedIndex => $selectedHit) {
                $selectedStart = (int) ($selectedHit['labelStart'] ?? 0);
                $selectedEnd = (int) ($selectedHit['labelEnd'] ?? 0);
                $overlaps = $candidateStart < $selectedEnd && $candidateEnd > $selectedStart;
                if (!$overlaps) {
                    continue;
                }

                $selectedLength = (int) ($selectedHit['_matchedLength'] ?? 0);
                if ($candidateLength > $selectedLength) {
                    $selectedLineHits[$selectedIndex] = $candidateHit;
                }
                $replaced = true;
                break;
            }

            if (!$replaced) {
                $selectedLineHits[] = $candidateHit;
            }
        }

        usort($selectedLineHits, static function (array $left, array $right): int {
            $startCompare = ((int) ($left['labelStart'] ?? 0)) <=> ((int) ($right['labelStart'] ?? 0));
            if ($startCompare !== 0) {
                return $startCompare;
            }

            return ((int) ($left['labelEnd'] ?? 0)) <=> ((int) ($right['labelEnd'] ?? 0));
        });

        foreach ($selectedLineHits as $selectedHit) {
            unset($selectedHit['_matchedLength']);
            $hits[] = $selectedHit;
        }
    }

    return $hits;
}

function matched_label_text_from_hit(array $hit): ?string
{
    $line = is_string($hit['line'] ?? null) ? (string) $hit['line'] : '';
    $labelStart = is_int($hit['labelStart'] ?? null) ? (int) $hit['labelStart'] : -1;
    $labelEnd = is_int($hit['labelEnd'] ?? null) ? (int) $hit['labelEnd'] : -1;
    if ($line === '' || $labelStart < 0 || $labelEnd <= $labelStart) {
        return null;
    }

    $matchedText = substr($line, $labelStart, $labelEnd - $labelStart);
    if (!is_string($matchedText)) {
        return null;
    }

    $matchedText = trim($matchedText);
    return $matchedText !== '' ? $matchedText : null;
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

function candidate_between_text(
    array $hit,
    string $candidateLine,
    int $candidateStart,
    int $candidateLineIndex
): string {
    $hitIndex = is_int($hit['index'] ?? null) ? (int) $hit['index'] : -1;
    $labelEnd = is_int($hit['labelEnd'] ?? null) ? (int) $hit['labelEnd'] : -1;
    if ($hitIndex < 0 || $labelEnd < 0 || $candidateStart < $labelEnd || $candidateLineIndex !== $hitIndex) {
        return '';
    }

    $line = $candidateLine !== ''
        ? $candidateLine
        : (is_string($hit['line'] ?? null) ? (string) $hit['line'] : '');
    if ($line === '') {
        return '';
    }

    $between = substr($line, $labelEnd, $candidateStart - $labelEnd);
    return is_string($between) ? $between : '';
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

function build_matching_line_geometries_for_job(array $job, string $ocrText = ''): array
{
    $jobId = is_string($job['id'] ?? null) ? trim((string) $job['id']) : '';
    if ($jobId === '') {
        return [];
    }

    $pages = stored_merged_objects_pages($jobId);
    if ($pages === []) {
        return [];
    }

    $lineEntries = [];
    foreach ($pages as $pageIndex => $page) {
        if (!is_array($page)) {
            continue;
        }

        $pageNumber = is_numeric($page['pageNumber'] ?? null) ? (int) $page['pageNumber'] : ($pageIndex + 1);
        if ($pageNumber <= 0) {
            $pageNumber = $pageIndex + 1;
        }

        $lineEntries[] = [
            'text' => '=== PAGE ' . $pageNumber . ' ===',
            'segments' => [],
            'pageNumber' => $pageNumber,
        ];

        $wordLines = build_grid_text_lines_from_debug_words(
            is_array($page['words'] ?? null) ? $page['words'] : []
        );
        $pageText = is_string($page['text'] ?? null) ? rtrim((string) $page['text'], "\r\n") : '';
        $renderedWordText = $wordLines !== []
            ? implode("\n", array_map(
                static fn(array $line): string => is_string($line['text'] ?? null) ? (string) $line['text'] : '',
                $wordLines
            ))
            : '';

        if ($pageText !== '' && $renderedWordText !== '' && $pageText !== $renderedWordText) {
            foreach (ocr_text_lines($pageText) as $textLine) {
                $lineEntries[] = [
                    'text' => is_string($textLine) ? $textLine : '',
                    'segments' => [],
                    'pageNumber' => $pageNumber,
                ];
            }
            continue;
        }

        if ($wordLines !== []) {
            foreach ($wordLines as $line) {
                $lineEntries[] = [
                    'text' => is_string($line['text'] ?? null) ? (string) $line['text'] : '',
                    'segments' => is_array($line['segments'] ?? null) ? $line['segments'] : [],
                    'pageNumber' => $pageNumber,
                ];
            }
            continue;
        }

        foreach (ocr_text_lines($pageText) as $textLine) {
            $lineEntries[] = [
                'text' => is_string($textLine) ? $textLine : '',
                'segments' => [],
                'pageNumber' => $pageNumber,
            ];
        }

        if ($pageIndex < (count($pages) - 1)) {
            $lineEntries[] = [
                'text' => '',
                'segments' => [],
                'pageNumber' => $pageNumber,
            ];
        }
    }

    if ($ocrText !== '') {
        if (preg_match('/\R\z/u', $ocrText) === 1) {
            $lineEntries[] = [
                'text' => '',
                'segments' => [],
                'pageNumber' => is_numeric($pages[count($pages) - 1]['pageNumber'] ?? null)
                    ? (int) $pages[count($pages) - 1]['pageNumber']
                    : count($pages),
            ];
        }
        $ocrLines = split_lines_for_matching($ocrText);
        $geometryTexts = array_map(
            static fn(array $entry): string => is_string($entry['text'] ?? null) ? (string) $entry['text'] : '',
            $lineEntries
        );
        if ($ocrLines !== $geometryTexts) {
            return [];
        }
    }

    return $lineEntries;
}

function line_geometry_span_bbox(?array $lineGeometry, int $start, int $end): ?array
{
    if (!is_array($lineGeometry) || $start < 0 || $end <= $start) {
        return null;
    }

    $segments = is_array($lineGeometry['segments'] ?? null) ? $lineGeometry['segments'] : [];
    $bbox = null;
    foreach ($segments as $segment) {
        if (!is_array($segment)) {
            continue;
        }

        $segmentStart = is_int($segment['start'] ?? null) ? (int) $segment['start'] : -1;
        $segmentEnd = is_int($segment['end'] ?? null) ? (int) $segment['end'] : -1;
        $segmentBbox = normalize_debug_word_bbox($segment['bbox'] ?? null);
        if ($segmentStart < 0 || $segmentEnd <= $segmentStart || $segmentBbox === null) {
            continue;
        }

        if ($segmentEnd <= $start || $segmentStart >= $end) {
            continue;
        }

        if ($bbox === null) {
            $bbox = $segmentBbox;
            continue;
        }

        $bbox = [
            'x0' => min($bbox['x0'], $segmentBbox['x0']),
            'y0' => min($bbox['y0'], $segmentBbox['y0']),
            'x1' => max($bbox['x1'], $segmentBbox['x1']),
            'y1' => max($bbox['y1'], $segmentBbox['y1']),
        ];
    }

    return $bbox;
}

function bbox_center_point(array $bbox): array
{
    return [
        'x' => ((float) ($bbox['x0'] ?? 0.0) + (float) ($bbox['x1'] ?? 0.0)) / 2.0,
        'y' => ((float) ($bbox['y0'] ?? 0.0) + (float) ($bbox['y1'] ?? 0.0)) / 2.0,
    ];
}

function clamp_float_range(float $value, float $min, float $max): float
{
    if ($value < $min) {
        return $min;
    }
    if ($value > $max) {
        return $max;
    }
    return $value;
}

function closest_point_on_bbox_to_point(array $bbox, float $x, float $y): array
{
    return [
        'x' => clamp_float_range($x, (float) ($bbox['x0'] ?? 0.0), (float) ($bbox['x1'] ?? 0.0)),
        'y' => clamp_float_range($y, (float) ($bbox['y0'] ?? 0.0), (float) ($bbox['y1'] ?? 0.0)),
    ];
}

function connector_points_between_bboxes(array $labelBbox, array $candidateBbox): array
{
    $labelCenter = bbox_center_point($labelBbox);
    $candidateCenter = bbox_center_point($candidateBbox);

    return [
        'start' => closest_point_on_bbox_to_point($labelBbox, (float) $candidateCenter['x'], (float) $candidateCenter['y']),
        'end' => closest_point_on_bbox_to_point($candidateBbox, (float) $labelCenter['x'], (float) $labelCenter['y']),
    ];
}

function connector_vector(array $connector): array
{
    $start = is_array($connector['start'] ?? null) ? $connector['start'] : [];
    $end = is_array($connector['end'] ?? null) ? $connector['end'] : [];

    return [
        'dx' => ((float) ($end['x'] ?? 0.0)) - ((float) ($start['x'] ?? 0.0)),
        'dy' => ((float) ($end['y'] ?? 0.0)) - ((float) ($start['y'] ?? 0.0)),
    ];
}

function normalize_angle_360(float $angle): float
{
    $normalized = fmod($angle, 360.0);
    if ($normalized < 0.0) {
        $normalized += 360.0;
    }

    return $normalized;
}

function angular_distance_degrees(float $left, float $right): float
{
    $diff = abs(normalize_angle_360($left) - normalize_angle_360($right));
    return min($diff, 360.0 - $diff);
}

function connector_main_direction(array $connector): string
{
    $vector = connector_vector($connector);
    $dx = (float) ($vector['dx'] ?? 0.0);
    $dy = (float) ($vector['dy'] ?? 0.0);
    if (abs($dx) < 0.001 && abs($dy) < 0.001) {
        return 'right';
    }

    $angle = normalize_angle_360(rad2deg(atan2($dy, $dx)));
    $references = [
        'right' => 0.0,
        'down' => 90.0,
        'left' => 180.0,
        'up' => 270.0,
    ];

    $bestDirection = 'right';
    $bestDistance = PHP_FLOAT_MAX;
    foreach ($references as $direction => $referenceAngle) {
        $distance = angular_distance_degrees($angle, $referenceAngle);
        if ($distance < $bestDistance) {
            $bestDistance = $distance;
            $bestDirection = $direction;
        }
    }

    return $bestDirection;
}

function bbox_gap_vector(array $labelBbox, array $candidateBbox): array
{
    $labelX0 = (float) ($labelBbox['x0'] ?? 0.0);
    $labelY0 = (float) ($labelBbox['y0'] ?? 0.0);
    $labelX1 = (float) ($labelBbox['x1'] ?? 0.0);
    $labelY1 = (float) ($labelBbox['y1'] ?? 0.0);
    $candidateX0 = (float) ($candidateBbox['x0'] ?? 0.0);
    $candidateY0 = (float) ($candidateBbox['y0'] ?? 0.0);
    $candidateX1 = (float) ($candidateBbox['x1'] ?? 0.0);
    $candidateY1 = (float) ($candidateBbox['y1'] ?? 0.0);

    $dx = 0.0;
    if ($candidateX0 >= $labelX1) {
        $dx = $candidateX0 - $labelX1;
    } elseif ($labelX0 >= $candidateX1) {
        $dx = -($labelX0 - $candidateX1);
    }

    $dy = 0.0;
    if ($candidateY0 >= $labelY1) {
        $dy = $candidateY0 - $labelY1;
    } elseif ($labelY0 >= $candidateY1) {
        $dy = -($labelY0 - $candidateY1);
    }

    if (abs($dx) < 0.001 && abs($dy) < 0.001) {
        $labelCenter = bbox_center_point($labelBbox);
        $candidateCenter = bbox_center_point($candidateBbox);
        $dx = (float) ($candidateCenter['x'] ?? 0.0) - (float) ($labelCenter['x'] ?? 0.0);
        $dy = (float) ($candidateCenter['y'] ?? 0.0) - (float) ($labelCenter['y'] ?? 0.0);
    }

    return [
        'dx' => $dx,
        'dy' => $dy,
    ];
}

function bbox_overlap_length(float $leftStart, float $leftEnd, float $rightStart, float $rightEnd): float
{
    return max(0.0, min($leftEnd, $rightEnd) - max($leftStart, $rightStart));
}

function bbox_main_direction(array $labelBbox, array $candidateBbox, ?int $labelLineIndex = null, ?int $candidateLineIndex = null): string
{
    $labelX0 = (float) ($labelBbox['x0'] ?? 0.0);
    $labelX1 = (float) ($labelBbox['x1'] ?? 0.0);
    $labelY0 = (float) ($labelBbox['y0'] ?? 0.0);
    $labelY1 = (float) ($labelBbox['y1'] ?? 0.0);
    $candidateX0 = (float) ($candidateBbox['x0'] ?? 0.0);
    $candidateX1 = (float) ($candidateBbox['x1'] ?? 0.0);
    $candidateY0 = (float) ($candidateBbox['y0'] ?? 0.0);
    $candidateY1 = (float) ($candidateBbox['y1'] ?? 0.0);
    $horizontalOverlap = bbox_overlap_length($labelX0, $labelX1, $candidateX0, $candidateX1);
    $verticalOverlap = bbox_overlap_length($labelY0, $labelY1, $candidateY0, $candidateY1);
    if ($labelLineIndex !== null && $candidateLineIndex !== null && $candidateLineIndex !== $labelLineIndex) {
        if ($horizontalOverlap > 0.0) {
            return $candidateLineIndex > $labelLineIndex ? 'down' : 'up';
        }
    }
    if ($verticalOverlap > 0.0) {
        $labelCenter = bbox_center_point($labelBbox);
        $candidateCenter = bbox_center_point($candidateBbox);
        return ((float) ($candidateCenter['x'] ?? 0.0)) >= ((float) ($labelCenter['x'] ?? 0.0)) ? 'right' : 'left';
    }
    if ($horizontalOverlap > 0.0) {
        $labelCenter = bbox_center_point($labelBbox);
        $candidateCenter = bbox_center_point($candidateBbox);
        return ((float) ($candidateCenter['y'] ?? 0.0)) >= ((float) ($labelCenter['y'] ?? 0.0)) ? 'down' : 'up';
    }

    $vector = bbox_gap_vector($labelBbox, $candidateBbox);
    $dx = (float) ($vector['dx'] ?? 0.0);
    $dy = (float) ($vector['dy'] ?? 0.0);
    if (abs($dx) < 0.001 && abs($dy) < 0.001) {
        return 'right';
    }

    $angle = normalize_angle_360(rad2deg(atan2($dy, $dx)));
    $references = [
        'right' => 0.0,
        'down' => 90.0,
        'left' => 180.0,
        'up' => 270.0,
    ];

    $bestDirection = 'right';
    $bestDistance = PHP_FLOAT_MAX;
    foreach ($references as $direction => $referenceAngle) {
        $distance = angular_distance_degrees($angle, $referenceAngle);
        if ($distance < $bestDistance) {
            $bestDistance = $distance;
            $bestDirection = $direction;
        }
    }

    return $bestDirection;
}

function bbox_height(array $bbox): float
{
    return max(0.0, (float) ($bbox['y1'] ?? 0.0) - (float) ($bbox['y0'] ?? 0.0));
}

function position_penalty_line_height(array $labelBbox, array $candidateBbox): float
{
    $labelHeight = bbox_height($labelBbox);
    $candidateHeight = bbox_height($candidateBbox);
    $lineHeight = max($labelHeight, $candidateHeight);

    return $lineHeight > 0.0 ? $lineHeight : 1.0;
}

function bbox_horizontal_overlap(array $labelBbox, array $candidateBbox): float
{
    return bbox_overlap_length(
        (float) ($labelBbox['x0'] ?? 0.0),
        (float) ($labelBbox['x1'] ?? 0.0),
        (float) ($candidateBbox['x0'] ?? 0.0),
        (float) ($candidateBbox['x1'] ?? 0.0)
    );
}

function bbox_best_horizontal_alignment_diff(array $labelBbox, array $candidateBbox): float
{
    $labelCenter = bbox_center_point($labelBbox);
    $candidateCenter = bbox_center_point($candidateBbox);

    return min(
        abs((float) ($candidateBbox['x0'] ?? 0.0) - (float) ($labelBbox['x0'] ?? 0.0)),
        abs((float) ($candidateCenter['x'] ?? 0.0) - (float) ($labelCenter['x'] ?? 0.0)),
        abs((float) ($candidateBbox['x1'] ?? 0.0) - (float) ($labelBbox['x1'] ?? 0.0))
    );
}

function bboxes_overlap(array $left, array $right): bool
{
    $overlapX = min((float) ($left['x1'] ?? 0.0), (float) ($right['x1'] ?? 0.0))
        - max((float) ($left['x0'] ?? 0.0), (float) ($right['x0'] ?? 0.0));
    $overlapY = min((float) ($left['y1'] ?? 0.0), (float) ($right['y1'] ?? 0.0))
        - max((float) ($left['y0'] ?? 0.0), (float) ($right['y0'] ?? 0.0));

    return $overlapX > 0.0 && $overlapY > 0.0;
}

function line_segment_intersects_bbox(array $start, array $end, array $bbox): bool
{
    $x0 = (float) ($start['x'] ?? 0.0);
    $y0 = (float) ($start['y'] ?? 0.0);
    $x1 = (float) ($end['x'] ?? 0.0);
    $y1 = (float) ($end['y'] ?? 0.0);

    $minX = (float) ($bbox['x0'] ?? 0.0);
    $minY = (float) ($bbox['y0'] ?? 0.0);
    $maxX = (float) ($bbox['x1'] ?? 0.0);
    $maxY = (float) ($bbox['y1'] ?? 0.0);

    $dx = $x1 - $x0;
    $dy = $y1 - $y0;
    $t0 = 0.0;
    $t1 = 1.0;

    $clip = static function (float $p, float $q, float &$t0, float &$t1): bool {
        if (abs($p) < 0.0000001) {
            return $q >= 0.0;
        }

        $r = $q / $p;
        if ($p < 0.0) {
            if ($r > $t1) {
                return false;
            }
            if ($r > $t0) {
                $t0 = $r;
            }
            return true;
        }

        if ($r < $t0) {
            return false;
        }
        if ($r < $t1) {
            $t1 = $r;
        }
        return true;
    };

    return $clip(-$dx, $x0 - $minX, $t0, $t1)
        && $clip($dx, $maxX - $x0, $t0, $t1)
        && $clip(-$dy, $y0 - $minY, $t0, $t1)
        && $clip($dy, $maxY - $y0, $t0, $t1);
}

function resolve_candidate_geometry_relation(
    array $hit,
    int $candidateStart,
    int $candidateLineIndex,
    ?string $candidateSpanText,
    array $lineGeometries
): ?array {
    $hitIndex = is_int($hit['index'] ?? null) ? (int) $hit['index'] : -1;
    $labelStart = is_int($hit['labelStart'] ?? null) ? (int) $hit['labelStart'] : -1;
    $labelEnd = is_int($hit['labelEnd'] ?? null) ? (int) $hit['labelEnd'] : -1;
    if ($hitIndex < 0 || $candidateLineIndex < 0 || $labelStart < 0 || $labelEnd <= $labelStart) {
        return null;
    }

    $hitGeometry = is_array($lineGeometries[$hitIndex] ?? null) ? $lineGeometries[$hitIndex] : null;
    $candidateGeometry = is_array($lineGeometries[$candidateLineIndex] ?? null) ? $lineGeometries[$candidateLineIndex] : null;
    if ($hitGeometry === null || $candidateGeometry === null) {
        return null;
    }

    $labelBbox = line_geometry_span_bbox($hitGeometry, $labelStart, $labelEnd);
    $candidateLength = is_string($candidateSpanText) && $candidateSpanText !== ''
        ? strlen($candidateSpanText)
        : 1;
    $candidateBbox = line_geometry_span_bbox($candidateGeometry, $candidateStart, $candidateStart + max(1, $candidateLength));
    if ($labelBbox === null || $candidateBbox === null) {
        return null;
    }

    $labelPageNumber = is_numeric($hitGeometry['pageNumber'] ?? null) ? (int) $hitGeometry['pageNumber'] : null;
    $candidatePageNumber = is_numeric($candidateGeometry['pageNumber'] ?? null) ? (int) $candidateGeometry['pageNumber'] : null;
    if ($labelPageNumber !== null && $candidatePageNumber !== null && $labelPageNumber !== $candidatePageNumber) {
        return null;
    }

    return [
        'labelBbox' => $labelBbox,
        'candidateBbox' => $candidateBbox,
        'pageNumber' => $labelPageNumber ?? $candidatePageNumber,
        'connector' => connector_points_between_bboxes($labelBbox, $candidateBbox),
    ];
}

function candidate_noise_details(
    array $hit,
    int $candidateStart,
    int $candidateLineIndex,
    ?string $candidateSpanText,
    array $lineGeometries
): array {
    $relation = resolve_candidate_geometry_relation($hit, $candidateStart, $candidateLineIndex, $candidateSpanText, $lineGeometries);
    if (!is_array($relation)) {
        return [
            'characterCount' => 0,
            'text' => '',
            'connector' => null,
            'noiseSegments' => [],
        ];
    }

    $labelBbox = is_array($relation['labelBbox'] ?? null) ? $relation['labelBbox'] : null;
    $candidateBbox = is_array($relation['candidateBbox'] ?? null) ? $relation['candidateBbox'] : null;
    $connector = is_array($relation['connector'] ?? null) ? $relation['connector'] : null;
    $pageNumber = is_numeric($relation['pageNumber'] ?? null) ? (int) $relation['pageNumber'] : null;
    if ($labelBbox === null || $candidateBbox === null || $connector === null) {
        return [
            'characterCount' => 0,
            'text' => '',
            'connector' => null,
            'noiseSegments' => [],
        ];
    }

    $noiseTexts = [];
    $noiseCharacters = 0;
    $noiseSegments = [];
    foreach ($lineGeometries as $lineIndex => $lineGeometry) {
        if (!is_array($lineGeometry)) {
            continue;
        }
        if ($pageNumber !== null) {
            $linePageNumber = is_numeric($lineGeometry['pageNumber'] ?? null) ? (int) $lineGeometry['pageNumber'] : null;
            if ($linePageNumber !== $pageNumber) {
                continue;
            }
        }

        $segments = is_array($lineGeometry['segments'] ?? null) ? $lineGeometry['segments'] : [];
        foreach ($segments as $segment) {
            if (!is_array($segment)) {
                continue;
            }

            $segmentText = is_string($segment['text'] ?? null) ? (string) $segment['text'] : '';
            if (trim($segmentText) === '') {
                continue;
            }

            $segmentBbox = normalize_debug_word_bbox($segment['bbox'] ?? null);
            if ($segmentBbox === null) {
                continue;
            }
            if (bboxes_overlap($segmentBbox, $labelBbox) || bboxes_overlap($segmentBbox, $candidateBbox)) {
                continue;
            }
            if (!line_segment_intersects_bbox(
                is_array($connector['start'] ?? null) ? $connector['start'] : [],
                is_array($connector['end'] ?? null) ? $connector['end'] : [],
                $segmentBbox
            )) {
                continue;
            }

            $noiseTexts[] = $segmentText;
            $noiseCharacters += count_pattern_matches('/\S/u', $segmentText);
            $segmentStart = is_numeric($segment['start'] ?? null) ? (int) $segment['start'] : null;
            $segmentEnd = is_numeric($segment['end'] ?? null) ? (int) $segment['end'] : null;
            if (is_int($lineIndex) && is_int($segmentStart) && is_int($segmentEnd) && $segmentEnd > $segmentStart) {
                $noiseSegments[] = [
                    'text' => $segmentText,
                    'lineIndex' => $lineIndex,
                    'start' => $segmentStart,
                    'end' => $segmentEnd,
                ];
            }
        }
    }

    return [
        'characterCount' => $noiseCharacters,
        'text' => implode(' ', $noiseTexts),
        'connector' => $connector,
        'noiseSegments' => $noiseSegments,
    ];
}

function candidate_position_penalty_details(
    array $hit,
    int $candidateStart,
    int $candidateLineIndex,
    ?string $candidateSpanText,
    array $lineGeometries,
    array $positionSettings
): array {
    $empty = [
        'penalty' => 0.0,
        'mainDirection' => null,
        'axis' => null,
        'diff' => 0.0,
        'normalizedDiff' => 0.0,
    ];

    $relation = resolve_candidate_geometry_relation($hit, $candidateStart, $candidateLineIndex, $candidateSpanText, $lineGeometries);
    if (!is_array($relation)) {
        return $empty;
    }

    $labelBbox = is_array($relation['labelBbox'] ?? null) ? $relation['labelBbox'] : null;
    $candidateBbox = is_array($relation['candidateBbox'] ?? null) ? $relation['candidateBbox'] : null;
    $connector = is_array($relation['connector'] ?? null) ? $relation['connector'] : null;
    if ($labelBbox === null || $candidateBbox === null) {
        return $empty;
    }

    $settings = normalize_matching_position_adjustment_settings($positionSettings);
    $hitIndex = is_int($hit['index'] ?? null) ? (int) $hit['index'] : null;
    $mainDirection = bbox_main_direction($labelBbox, $candidateBbox, $hitIndex, $candidateLineIndex);
    $labelCenter = bbox_center_point($labelBbox);
    $candidateCenter = bbox_center_point($candidateBbox);
    $lineHeight = position_penalty_line_height($labelBbox, $candidateBbox);

    if ($mainDirection === 'left' || $mainDirection === 'up') {
        return [
            'penalty' => 1.0,
            'mainDirection' => $mainDirection,
            'axis' => 'invalid',
            'diff' => 0.0,
            'normalizedDiff' => 0.0,
        ];
    }

    if ($mainDirection === 'right') {
        $diff = abs((float) ($candidateBbox['y1'] ?? 0.0) - (float) ($labelBbox['y1'] ?? 0.0));
        $normalizedDiff = $lineHeight > 0.0 ? ($diff / $lineHeight) : 0.0;
        return [
            'penalty' => max(0.0, $normalizedDiff * (float) ($settings['rightYOffsetPenalty'] ?? 0.0)),
            'mainDirection' => $mainDirection,
            'axis' => 'y',
            'diff' => $diff,
            'normalizedDiff' => $normalizedDiff,
        ];
    }

    $diff = bbox_best_horizontal_alignment_diff($labelBbox, $candidateBbox);
    $normalizedDiff = $lineHeight > 0.0 ? ($diff / $lineHeight) : 0.0;
    return [
        'penalty' => max(0.0, $normalizedDiff * (float) ($settings['downXOffsetPenalty'] ?? 0.0)),
        'mainDirection' => $mainDirection,
        'axis' => 'x',
        'diff' => $diff,
        'normalizedDiff' => $normalizedDiff,
    ];
}

function candidate_confidence_score(
    array $components
): float
{
    $base = isset($components['base']) && is_numeric($components['base']) ? (float) $components['base'] : 1.0;
    $noisePenalty = isset($components['noisePenalty']) && is_numeric($components['noisePenalty']) ? (float) $components['noisePenalty'] : 0.0;
    $positionPenalty = isset($components['positionPenalty']) && is_numeric($components['positionPenalty']) ? (float) $components['positionPenalty'] : 0.0;
    $contentPenalty = isset($components['contentPenalty']) && is_numeric($components['contentPenalty']) ? (float) $components['contentPenalty'] : 0.0;

    $confidence = $base;
    $confidence *= max(0.0, 1.0 - clamp_confidence($noisePenalty));
    $confidence *= max(0.0, 1.0 - clamp_confidence($positionPenalty));
    $confidence -= max(0.0, $contentPenalty);

    return clamp_confidence($confidence);
}

function candidate_confidence_components(
    array $hit,
    string $candidateLine,
    int $candidateStart,
    int $candidateLineIndex,
    string $scope,
    array $positionSettings = [],
    array $lineGeometries = [],
    ?string $candidateSpanText = null
): array
{
    $settings = normalize_matching_position_adjustment_settings($positionSettings);

    $base = 1.0;
    $betweenText = candidate_between_text($hit, $candidateLine, $candidateStart, $candidateLineIndex);

    $noiseDetails = candidate_noise_details(
        $hit,
        $candidateStart,
        $candidateLineIndex,
        $candidateSpanText,
        $lineGeometries
    );
    $noiseText = is_string($noiseDetails['text'] ?? null) ? (string) $noiseDetails['text'] : '';
    $noiseSegments = array_values(array_filter(
        is_array($noiseDetails['noiseSegments'] ?? null) ? $noiseDetails['noiseSegments'] : [],
        static function ($segment): bool {
            return is_array($segment)
                && is_string($segment['text'] ?? null)
                && is_int($segment['lineIndex'] ?? null)
                && is_int($segment['start'] ?? null)
                && is_int($segment['end'] ?? null)
                && (int) $segment['end'] > (int) $segment['start'];
        }
    ));
    $noiseCharacters = is_int($noiseDetails['characterCount'] ?? null) ? (int) $noiseDetails['characterCount'] : 0;
    $noisePenalty = min(1.0, $noiseCharacters * (float) $settings['noisePenaltyPerCharacter']);

    $positionPenaltyDetails = candidate_position_penalty_details(
        $hit,
        $candidateStart,
        $candidateLineIndex,
        $candidateSpanText,
        $lineGeometries,
        $settings
    );

    $contentPenalty = 0.0;
    if (@preg_match('/(https?:\/\/|www\.|@[A-Za-z0-9._-]+)/iu', $noiseText) === 1) {
        $contentPenalty = 0.15;
    }

    return [
        'base' => $base,
        'noisePenalty' => clamp_confidence($noisePenalty),
        'positionPenalty' => max(0.0, (float) ($positionPenaltyDetails['penalty'] ?? 0.0)),
        'positionPenaltyAxis' => is_string($positionPenaltyDetails['axis'] ?? null) ? (string) $positionPenaltyDetails['axis'] : null,
        'mainDirection' => is_string($positionPenaltyDetails['mainDirection'] ?? null) ? (string) $positionPenaltyDetails['mainDirection'] : null,
        'positionDiff' => is_numeric($positionPenaltyDetails['diff'] ?? null) ? (float) $positionPenaltyDetails['diff'] : null,
        'positionNormalizedDiff' => is_numeric($positionPenaltyDetails['normalizedDiff'] ?? null) ? (float) ($positionPenaltyDetails['normalizedDiff']) : null,
        'contentPenalty' => max(0.0, $contentPenalty),
        'betweenText' => $betweenText,
        'noiseText' => $noiseText,
        'noiseSegments' => $noiseSegments,
    ];
}

function select_best_labeled_candidate(
    array $lines,
    array $labels,
    array $replacementMap,
    callable $candidateExtractor,
    int $nearbyDistance = 1,
    array $positionSettings = [],
    array $lineGeometries = [],
    bool $labelsAreRegex = false
): array
{
    $best = [
        'value' => null,
        'confidence' => 0.0,
        'lineIndex' => null,
        'source' => 'none',
        'raw' => null,
        'matchText' => null,
    ];

    $hits = find_label_hits($lines, $labels, $replacementMap, $labelsAreRegex);
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

                    $spanText = is_string($candidate['spanText'] ?? null)
                        ? (string) $candidate['spanText']
                        : (is_string($raw) && $raw !== '' ? $raw : (is_scalar($value) ? (string) $value : null));
                    $components = candidate_confidence_components(
                        $hit,
                        $line,
                        $start,
                        $hitIndex,
                        'tail',
                        $positionSettings,
                        $lineGeometries,
                        $spanText
                    );
                    $confidence = candidate_confidence_score($components);
                    if ($confidence > (float) $best['confidence']) {
                        $best = [
                            'value' => $value,
                            'confidence' => $confidence,
                            'lineIndex' => $hitIndex,
                            'source' => 'tail',
                            'raw' => $raw,
                            'matchText' => is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw,
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

                $spanText = is_string($candidate['spanText'] ?? null)
                    ? (string) $candidate['spanText']
                    : (is_string($raw) && $raw !== '' ? $raw : (is_scalar($value) ? (string) $value : null));
                $components = candidate_confidence_components(
                    $hit,
                    $line,
                    $start,
                    $hitIndex,
                    'line',
                    $positionSettings,
                    $lineGeometries,
                    $spanText
                );
                $confidence = candidate_confidence_score($components);
                if ($confidence > (float) $best['confidence']) {
                    $best = [
                        'value' => $value,
                        'confidence' => $confidence,
                        'lineIndex' => $hitIndex,
                        'source' => 'line',
                        'raw' => $raw,
                        'matchText' => is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw,
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

                $spanText = is_string($candidate['spanText'] ?? null)
                    ? (string) $candidate['spanText']
                    : (is_string($raw) && $raw !== '' ? $raw : (is_scalar($value) ? (string) $value : null));
                $components = candidate_confidence_components(
                    $hit,
                    $nearLine,
                    $start,
                    (int) $nearIndex,
                    'nearby',
                    $positionSettings,
                    $lineGeometries,
                    $spanText
                );
                $confidence = candidate_confidence_score($components);
                if ($confidence > (float) $best['confidence']) {
                    $best = [
                        'value' => $value,
                        'confidence' => $confidence,
                        'lineIndex' => $nearIndex,
                        'source' => 'nearby',
                        'raw' => $raw,
                        'matchText' => is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw,
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
            if (@preg_match('/^[^\pL\d]+$/u', $value) === 1) {
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

    if (@preg_match('/^[^\pL\d]+$/u', $trimmed) === 1) {
        return [];
    }

    return [[
        'value' => $trimmed,
        'raw' => $trimmed,
        'start' => $offsetBase,
    ]];
}

function extraction_field_pattern_candidates_from_text(string $text, string $searchString, bool $isRegex, int $offsetBase = 0): array
{
    $pattern = trim($searchString);
    if ($pattern === '') {
        return generic_text_segment_candidates_from_text($text, $offsetBase);
    }

    $delimitedPattern = $isRegex
        ? '/' . str_replace('/', '\/', $pattern) . '/iu'
        : '/' . preg_quote($pattern, '/') . '/iu';

    $matches = [];
    if (@preg_match_all($delimitedPattern, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) < 1) {
        return [];
    }

    $candidates = [];
    foreach ($matches as $match) {
        if (!is_array($match)) {
            continue;
        }

        $fullMatch = $match[0] ?? null;
        if (!is_array($fullMatch) || count($fullMatch) < 2) {
            continue;
        }

        $raw = is_string($fullMatch[0] ?? null) ? (string) $fullMatch[0] : '';
        $start = is_int($fullMatch[1] ?? null) ? (int) $fullMatch[1] : -1;
        if ($raw === '' || $start < 0) {
            continue;
        }

        $rawLength = strlen($raw);
        $beforeChar = $start > 0 ? substr($text, $start - 1, 1) : '';
        $afterChar = substr($text, $start + $rawLength, 1);
        $touchesLeftToken = is_string($beforeChar) && $beforeChar !== '' && @preg_match('/[\pL\d]/u', $beforeChar) === 1;
        $touchesRightToken = is_string($afterChar) && $afterChar !== '' && @preg_match('/[\pL\d]/u', $afterChar) === 1;
        if ($touchesLeftToken || $touchesRightToken) {
            continue;
        }

        $value = $raw;
        $extractedRaw = $raw;
        if ($isRegex && array_key_exists(1, $match)) {
            $captureGroup = $match[1];
            $captureValue = is_array($captureGroup) && is_string($captureGroup[0] ?? null)
                ? (string) $captureGroup[0]
                : '';
            $captureStart = is_array($captureGroup) && is_int($captureGroup[1] ?? null)
                ? (int) $captureGroup[1]
                : -1;
            if ($captureValue === '' || $captureStart < 0) {
                continue;
            }
            $value = $captureValue;
            $extractedRaw = $captureValue;
        }

        $candidates[] = [
            'value' => $value,
            'raw' => $extractedRaw,
            'matchText' => $raw,
            'start' => $offsetBase + $start,
            'spanText' => $raw,
            'matchType' => 'pattern',
        ];
    }

    return $candidates;
}

function apply_extraction_field_normalization(mixed $value, string $type, string $chars): mixed
{
    $normalizedType = normalize_extraction_field_normalization_type($type);
    if ($normalizedType === 'none' || !is_string($value)) {
        return $value;
    }

    $characters = utf8_chars($chars);
    $characterMap = [];
    foreach ($characters as $character) {
        $characterMap[$character] = true;
    }

    $result = '';
    foreach (utf8_chars($value) as $character) {
        $isListed = isset($characterMap[$character]);
        if (($normalizedType === 'whitelist' && $isListed) || ($normalizedType === 'blacklist' && !$isListed)) {
            $result .= $character;
        }
    }

    return $result;
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
        'matchText' => null,
    ];
}

function extraction_field_match_storage_key(int $lineIndex, int $start, mixed $value, ?string $raw): string
{
    $encodedValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encodedValue)) {
        $encodedValue = is_scalar($value) ? (string) $value : '';
    }

    return $lineIndex . '|' . $start . '|' . $encodedValue . '|' . ($raw ?? '');
}

function add_extraction_field_match(
    array &$matchesByKey,
    int $lineIndex,
    int $start,
    mixed $value,
    ?string $raw,
    ?string $matchText,
    string $source,
    float $confidence,
    ?string $matchType = null,
    ?string $searchTerm = null,
    ?float $score = null,
    ?float $noisePenalty = null,
    ?float $positionPenalty = null,
    ?string $positionPenaltyAxis = null,
    ?string $mainDirection = null,
    ?float $positionDiff = null,
    ?float $positionNormalizedDiff = null,
    ?string $betweenText = null,
    ?string $noiseText = null,
    ?string $labelText = null,
    ?int $labelLineIndex = null,
    ?array $noiseSegments = null
): void {
    if ($lineIndex < 0 || $start < 0 || $value === null) {
        return;
    }

    $key = extraction_field_match_storage_key($lineIndex, $start, $value, $raw);
    $candidate = [
        'value' => $value,
        'confidence' => clamp_confidence($confidence),
        'lineIndex' => $lineIndex,
        'source' => $source,
        'raw' => $raw,
        'start' => $start,
    ];
    if (is_string($matchText)) {
        $candidate['matchText'] = $matchText;
    }
    if (is_string($matchType) && trim($matchType) !== '') {
        $candidate['matchType'] = trim($matchType);
    }
    if (is_string($searchTerm) && trim($searchTerm) !== '') {
        $candidate['searchTerm'] = trim($searchTerm);
    }
    if (is_numeric($score)) {
        $candidate['score'] = (float) $score;
    }
    if (is_numeric($noisePenalty)) {
        $candidate['noisePenalty'] = clamp_confidence((float) $noisePenalty);
    }
    if (is_numeric($positionPenalty)) {
        $candidate['positionPenalty'] = max(0.0, (float) $positionPenalty);
    }
    if (is_string($positionPenaltyAxis) && trim($positionPenaltyAxis) !== '') {
        $candidate['positionPenaltyAxis'] = trim($positionPenaltyAxis);
    }
    if (is_string($mainDirection) && trim($mainDirection) !== '') {
        $candidate['mainDirection'] = trim($mainDirection);
    }
    if (is_numeric($positionDiff)) {
        $candidate['positionDiff'] = max(0.0, (float) $positionDiff);
    }
    if (is_numeric($positionNormalizedDiff)) {
        $candidate['positionNormalizedDiff'] = max(0.0, (float) $positionNormalizedDiff);
    }
    if (is_string($betweenText)) {
        $candidate['between'] = $betweenText;
    }
    if (is_string($noiseText)) {
        $candidate['noiseText'] = $noiseText;
    }
    if (is_string($labelText) && trim($labelText) !== '') {
        $candidate['labelText'] = trim($labelText);
    }
    if (is_int($labelLineIndex) && $labelLineIndex >= 0) {
        $candidate['labelLineIndex'] = $labelLineIndex;
    }
    if (is_array($noiseSegments)) {
        $normalizedNoiseSegments = array_values(array_filter(array_map(
            static function ($segment): ?array {
                if (!is_array($segment)) {
                    return null;
                }
                $text = is_string($segment['text'] ?? null) ? (string) $segment['text'] : '';
                $segmentLineIndex = is_int($segment['lineIndex'] ?? null) ? (int) $segment['lineIndex'] : null;
                $segmentStart = is_int($segment['start'] ?? null) ? (int) $segment['start'] : null;
                $segmentEnd = is_int($segment['end'] ?? null) ? (int) $segment['end'] : null;
                if ($text === '' || $segmentLineIndex === null || $segmentStart === null || $segmentEnd === null || $segmentEnd <= $segmentStart) {
                    return null;
                }
                return [
                    'text' => $text,
                    'lineIndex' => $segmentLineIndex,
                    'start' => $segmentStart,
                    'end' => $segmentEnd,
                ];
            },
            $noiseSegments
        ), static fn ($segment): bool => is_array($segment)));
        if ($normalizedNoiseSegments !== []) {
            $candidate['noiseSegments'] = $normalizedNoiseSegments;
        }
    }

    if (!isset($matchesByKey[$key]) || (float) ($matchesByKey[$key]['confidence'] ?? 0.0) < $candidate['confidence']) {
        $matchesByKey[$key] = $candidate;
    }
}

function sort_extraction_field_matches(array $matches): array
{
    usort($matches, static function (array $left, array $right): int {
        $lineCompare = ((int) ($left['lineIndex'] ?? 0)) <=> ((int) ($right['lineIndex'] ?? 0));
        if ($lineCompare !== 0) {
            return $lineCompare;
        }

        $startCompare = ((int) ($left['start'] ?? 0)) <=> ((int) ($right['start'] ?? 0));
        if ($startCompare !== 0) {
            return $startCompare;
        }

        $confidenceCompare = ((float) ($right['confidence'] ?? 0.0)) <=> ((float) ($left['confidence'] ?? 0.0));
        if ($confidenceCompare !== 0) {
            return $confidenceCompare;
        }

        return strcmp((string) ($left['source'] ?? ''), (string) ($right['source'] ?? ''));
    });

    return array_values($matches);
}

function collect_labeled_candidate_matches(
    array $lines,
    array $labels,
    array $replacementMap,
    callable $candidateExtractor,
    int $nearbyDistance = 1,
    array $positionSettings = [],
    array $lineGeometries = [],
    bool $labelsAreRegex = false
): array
{
    $matchesByKey = [];

    $hits = find_label_hits($lines, $labels, $replacementMap, $labelsAreRegex);
    foreach ($hits as $hit) {
        $line = is_string($hit['line'] ?? null) ? (string) $hit['line'] : '';
        $pattern = is_string($hit['pattern'] ?? null) ? (string) $hit['pattern'] : '';
        $label = is_string($hit['label'] ?? null) ? trim((string) $hit['label']) : '';
        $matchedLabelText = matched_label_text_from_hit($hit);
        $hitIndex = is_int($hit['index'] ?? null) ? (int) $hit['index'] : -1;
        $labelEnd = is_int($hit['labelEnd'] ?? null) ? (int) $hit['labelEnd'] : 0;
        if ($line === '' || $hitIndex < 0) {
            continue;
        }

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

                    $confidenceComponents = candidate_confidence_components(
                        $hit,
                        $line,
                        $start,
                        $hitIndex,
                        'tail',
                        $positionSettings,
                        $lineGeometries,
                        is_string($candidate['spanText'] ?? null)
                            ? (string) $candidate['spanText']
                            : (is_string($raw) && $raw !== '' ? $raw : (is_scalar($value) ? (string) $value : null))
                    );

                    add_extraction_field_match(
                        $matchesByKey,
                        $hitIndex,
                        $start,
                        $value,
                        $raw,
                        is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw,
                        'tail',
                        candidate_confidence_score($confidenceComponents),
                        is_string($candidate['matchType'] ?? null) ? (string) $candidate['matchType'] : null,
                        $label !== '' ? $label : null,
                        null,
                        is_numeric($confidenceComponents['noisePenalty'] ?? null) ? (float) $confidenceComponents['noisePenalty'] : null,
                        is_numeric($confidenceComponents['positionPenalty'] ?? null) ? (float) $confidenceComponents['positionPenalty'] : null,
                        is_string($confidenceComponents['positionPenaltyAxis'] ?? null) ? (string) $confidenceComponents['positionPenaltyAxis'] : null,
                        is_string($confidenceComponents['mainDirection'] ?? null) ? (string) $confidenceComponents['mainDirection'] : null,
                        is_numeric($confidenceComponents['positionDiff'] ?? null) ? (float) $confidenceComponents['positionDiff'] : null,
                        is_numeric($confidenceComponents['positionNormalizedDiff'] ?? null) ? (float) $confidenceComponents['positionNormalizedDiff'] : null,
                        is_string($confidenceComponents['betweenText'] ?? null) ? (string) $confidenceComponents['betweenText'] : null,
                        is_string($confidenceComponents['noiseText'] ?? null) ? (string) $confidenceComponents['noiseText'] : null,
                        $matchedLabelText,
                        $hitIndex,
                        is_array($confidenceComponents['noiseSegments'] ?? null) ? $confidenceComponents['noiseSegments'] : null
                    );
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

                $confidenceComponents = candidate_confidence_components(
                    $hit,
                    $line,
                    $start,
                    $hitIndex,
                    'line',
                    $positionSettings,
                    $lineGeometries,
                    is_string($candidate['spanText'] ?? null)
                        ? (string) $candidate['spanText']
                        : (is_string($raw) && $raw !== '' ? $raw : (is_scalar($value) ? (string) $value : null))
                );

                add_extraction_field_match(
                    $matchesByKey,
                    $hitIndex,
                    $start,
                    $value,
                    $raw,
                    is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw,
                    'line',
                    candidate_confidence_score($confidenceComponents),
                    is_string($candidate['matchType'] ?? null) ? (string) $candidate['matchType'] : null,
                    $label !== '' ? $label : null,
                    null,
                    is_numeric($confidenceComponents['noisePenalty'] ?? null) ? (float) $confidenceComponents['noisePenalty'] : null,
                    is_numeric($confidenceComponents['positionPenalty'] ?? null) ? (float) $confidenceComponents['positionPenalty'] : null,
                    is_string($confidenceComponents['positionPenaltyAxis'] ?? null) ? (string) $confidenceComponents['positionPenaltyAxis'] : null,
                    is_string($confidenceComponents['mainDirection'] ?? null) ? (string) $confidenceComponents['mainDirection'] : null,
                    is_numeric($confidenceComponents['positionDiff'] ?? null) ? (float) $confidenceComponents['positionDiff'] : null,
                    is_numeric($confidenceComponents['positionNormalizedDiff'] ?? null) ? (float) $confidenceComponents['positionNormalizedDiff'] : null,
                    is_string($confidenceComponents['betweenText'] ?? null) ? (string) $confidenceComponents['betweenText'] : null,
                    is_string($confidenceComponents['noiseText'] ?? null) ? (string) $confidenceComponents['noiseText'] : null,
                    $matchedLabelText,
                    $hitIndex,
                    is_array($confidenceComponents['noiseSegments'] ?? null) ? $confidenceComponents['noiseSegments'] : null
                );
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

                $confidenceComponents = candidate_confidence_components(
                    $hit,
                    $nearLine,
                    $start,
                    (int) $nearIndex,
                    'nearby',
                    $positionSettings,
                    $lineGeometries,
                    is_string($candidate['spanText'] ?? null)
                        ? (string) $candidate['spanText']
                        : (is_string($raw) && $raw !== '' ? $raw : (is_scalar($value) ? (string) $value : null))
                );

                add_extraction_field_match(
                    $matchesByKey,
                    (int) $nearIndex,
                    $start,
                    $value,
                    $raw,
                    is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw,
                    'nearby',
                    candidate_confidence_score($confidenceComponents),
                    is_string($candidate['matchType'] ?? null) ? (string) $candidate['matchType'] : null,
                    $label !== '' ? $label : null,
                    null,
                    is_numeric($confidenceComponents['noisePenalty'] ?? null) ? (float) $confidenceComponents['noisePenalty'] : null,
                    is_numeric($confidenceComponents['positionPenalty'] ?? null) ? (float) $confidenceComponents['positionPenalty'] : null,
                    is_string($confidenceComponents['positionPenaltyAxis'] ?? null) ? (string) $confidenceComponents['positionPenaltyAxis'] : null,
                    is_string($confidenceComponents['mainDirection'] ?? null) ? (string) $confidenceComponents['mainDirection'] : null,
                    is_numeric($confidenceComponents['positionDiff'] ?? null) ? (float) $confidenceComponents['positionDiff'] : null,
                    is_numeric($confidenceComponents['positionNormalizedDiff'] ?? null) ? (float) $confidenceComponents['positionNormalizedDiff'] : null,
                    is_string($confidenceComponents['betweenText'] ?? null) ? (string) $confidenceComponents['betweenText'] : null,
                    is_string($confidenceComponents['noiseText'] ?? null) ? (string) $confidenceComponents['noiseText'] : null,
                    $matchedLabelText,
                    $hitIndex,
                    is_array($confidenceComponents['noiseSegments'] ?? null) ? $confidenceComponents['noiseSegments'] : null
                );
            }
        }
    }

    return sort_extraction_field_matches(array_values($matchesByKey));
}

function document_date_result_matches(array $result): array
{
    $matchesByKey = [];
    $candidates = is_array($result['candidates'] ?? null) ? $result['candidates'] : [];
    foreach ($candidates as $candidate) {
        if (!is_array($candidate) || ($candidate['excluded'] ?? false) === true) {
            continue;
        }

        $value = $candidate['value'] ?? null;
        $lineIndex = is_int($candidate['lineIndex'] ?? null) ? (int) $candidate['lineIndex'] : -1;
        $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
        $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null;
        if ($lineIndex < 0 || $start < 0 || $value === null) {
            continue;
        }

        add_extraction_field_match(
            $matchesByKey,
            $lineIndex,
            $start,
            $value,
            $raw,
            $raw,
            'document_date_heuristic',
            clamp_confidence(((int) ($candidate['score'] ?? 0)) / 180),
            'document_date_heuristic',
            null,
            (float) ((int) ($candidate['score'] ?? 0))
        );
    }

    if ($matchesByKey === [] && ($result['value'] ?? null) !== null) {
        $lineIndex = is_int($result['lineIndex'] ?? null) ? (int) $result['lineIndex'] : -1;
        if ($lineIndex >= 0) {
            add_extraction_field_match(
                $matchesByKey,
                $lineIndex,
                0,
                $result['value'],
                is_string($result['raw'] ?? null) ? (string) $result['raw'] : null,
                is_string($result['raw'] ?? null) ? (string) $result['raw'] : null,
                is_string($result['source'] ?? null) ? (string) $result['source'] : 'document_date_heuristic',
                isset($result['confidence']) ? (float) $result['confidence'] : 0.0,
                'document_date_heuristic',
                null,
                is_numeric($result['selectedCandidate']['score'] ?? null)
                    ? (float) $result['selectedCandidate']['score']
                    : null
            );
        }
    }

    return sort_extraction_field_matches(array_values($matchesByKey));
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

function extract_generic_text_field_result(
    array $lines,
    array $searchTerms,
    string $searchString,
    array $replacementMap,
    array $positionSettings = [],
    array $lineGeometries = []
): array {
    $resolvedAliases = normalize_extraction_field_search_terms($searchTerms);
    if ($resolvedAliases === []) {
        return empty_extraction_field_result();
    }

    $pattern = trim($searchString);
    if ($pattern === '') {
        $result = select_best_labeled_candidate(
            $lines,
            $resolvedAliases,
            $replacementMap,
            'generic_text_segment_candidates_from_text',
            1,
            $positionSettings,
            $lineGeometries
        );
        return ($result['value'] ?? null) !== null ? $result : empty_extraction_field_result();
    }

    $result = select_best_labeled_candidate(
        $lines,
        $resolvedAliases,
        $replacementMap,
        static function (string $text, int $offsetBase) use ($pattern): array {
            return extraction_field_pattern_candidates_from_text($text, $pattern, true, $offsetBase);
        },
        1,
        $positionSettings,
        $lineGeometries
    );
    return ($result['value'] ?? null) !== null ? $result : empty_extraction_field_result();
}

function extract_generic_text_field_matches(
    array $lines,
    array $searchTerms,
    string $searchString,
    array $replacementMap,
    array $positionSettings = [],
    array $lineGeometries = []
): array {
    $resolvedAliases = normalize_extraction_field_search_terms($searchTerms);
    if ($resolvedAliases === []) {
        return [];
    }

    $pattern = trim($searchString);
    if ($pattern === '') {
        return collect_labeled_candidate_matches(
            $lines,
            $resolvedAliases,
            $replacementMap,
            'generic_text_segment_candidates_from_text',
            1,
            $positionSettings,
            $lineGeometries
        );
    }

    return collect_labeled_candidate_matches(
        $lines,
        $resolvedAliases,
        $replacementMap,
        static function (string $text, int $offsetBase) use ($pattern): array {
            return extraction_field_pattern_candidates_from_text($text, $pattern, true, $offsetBase);
        },
        1,
        $positionSettings,
        $lineGeometries
    );
}

function extract_unlabeled_pattern_field_result(array $lines, string $pattern): array
{
    $resolvedPattern = trim($pattern);
    if ($resolvedPattern === '') {
        return empty_extraction_field_result();
    }

    foreach ($lines as $lineIndex => $line) {
        $resolvedLine = is_string($line) ? $line : '';
        if ($resolvedLine === '') {
            continue;
        }

        $candidates = extraction_field_pattern_candidates_from_text($resolvedLine, $resolvedPattern, true, 0);
        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $value = $candidate['value'] ?? null;
            if ($value === null) {
                continue;
            }

            return [
                'value' => $value,
                'confidence' => 0.55,
                'lineIndex' => is_int($lineIndex) ? $lineIndex : null,
                'source' => 'pattern',
                'raw' => is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null,
                'matchText' => is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : (is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null),
            ];
        }
    }

    return empty_extraction_field_result();
}

function extract_unlabeled_pattern_field_matches(array $lines, string $pattern): array
{
    $resolvedPattern = trim($pattern);
    if ($resolvedPattern === '') {
        return [];
    }

    $matchesByKey = [];
    foreach ($lines as $lineIndex => $line) {
        $resolvedLine = is_string($line) ? $line : '';
        if ($resolvedLine === '' || !is_int($lineIndex)) {
            continue;
        }

        $candidates = extraction_field_pattern_candidates_from_text($resolvedLine, $resolvedPattern, true, 0);
        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $value = $candidate['value'] ?? null;
            $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
            $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null;
            if ($value === null || $start < 0) {
                continue;
            }

            add_extraction_field_match(
                $matchesByKey,
                $lineIndex,
                $start,
                $value,
                $raw,
                is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw,
                'pattern',
                0.55,
                is_string($candidate['matchType'] ?? null) ? (string) $candidate['matchType'] : 'pattern'
            );
        }
    }

    return sort_extraction_field_matches(array_values($matchesByKey));
}

function extract_configured_rule_set_field_matches(
    array $lines,
    array $replacementMap,
    array $ruleSet,
    array $positionSettings = [],
    array $lineGeometries = []
): array
{
    $requiresSearchTerms = !array_key_exists('requiresSearchTerms', $ruleSet)
        || $ruleSet['requiresSearchTerms'] === true
        || $ruleSet['requiresSearchTerms'] === 1
        || $ruleSet['requiresSearchTerms'] === '1';
    $searchTerms = normalize_extraction_field_search_terms(
        $ruleSet['searchTerms'] ?? null,
        normalize_extraction_field_is_regex($ruleSet['isRegex'] ?? false)
    );
    $valuePattern = is_string($ruleSet['valuePattern'] ?? null) ? trim((string) $ruleSet['valuePattern']) : '';

    if (!$requiresSearchTerms) {
        return extract_unlabeled_pattern_field_matches($lines, $valuePattern);
    }

    if ($searchTerms === []) {
        return [];
    }

    return extract_generic_text_field_matches(
        $lines,
        $searchTerms,
        $valuePattern,
        $replacementMap,
        $positionSettings,
        $lineGeometries
    );
}

function extract_configured_rule_set_field_result(
    array $lines,
    array $replacementMap,
    array $ruleSet,
    array $positionSettings = [],
    array $lineGeometries = []
): array
{
    $requiresSearchTerms = !array_key_exists('requiresSearchTerms', $ruleSet)
        || $ruleSet['requiresSearchTerms'] === true
        || $ruleSet['requiresSearchTerms'] === 1
        || $ruleSet['requiresSearchTerms'] === '1';
    $searchTerms = normalize_extraction_field_search_terms(
        $ruleSet['searchTerms'] ?? null,
        normalize_extraction_field_is_regex($ruleSet['isRegex'] ?? false)
    );
    $valuePattern = is_string($ruleSet['valuePattern'] ?? null) ? trim((string) $ruleSet['valuePattern']) : '';

    if (!$requiresSearchTerms) {
        return extract_unlabeled_pattern_field_result($lines, $valuePattern);
    }

    if ($searchTerms === []) {
        return empty_extraction_field_result();
    }

    return extract_generic_text_field_result(
        $lines,
        $searchTerms,
        $valuePattern,
        $replacementMap,
        $positionSettings,
        $lineGeometries
    );
}

function extract_configured_text_field_results(
    array $lines,
    array $replacementMap,
    array $fields,
    array $positionSettings = [],
    array $lineGeometries = []
): array
{
    $results = [];

    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }

        $key = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        $name = is_string($field['name'] ?? null) ? trim((string) $field['name']) : '';
        $ruleSets = normalize_extraction_field_rule_sets($field['ruleSets'] ?? null, $field);
        $extractor = valid_extraction_field_extractor(
            is_string($field['extractor'] ?? null) ? (string) $field['extractor'] : 'generic_label'
        );
        if ($key === '' || $name === '') {
            continue;
        }

        $matchedRuleSetIndex = null;
        $matchedRuleSet = default_extraction_field_rule_set();
        $matches = [];
        if ($extractor === 'document_date') {
            $result = extract_document_date_field_result($lines);
            $ruleSets = [];
            $matches = document_date_result_matches($result);
        } else {
            $result = empty_extraction_field_result();
            foreach ($ruleSets as $ruleSetIndex => $ruleSet) {
                if (!is_array($ruleSet)) {
                    continue;
                }

                $candidateMatches = extract_configured_rule_set_field_matches(
                    $lines,
                    $replacementMap,
                    $ruleSet,
                    $positionSettings,
                    $lineGeometries
                );
                if ($candidateMatches === []) {
                    continue;
                }

                if ($matchedRuleSetIndex === null) {
                    $result = is_array($candidateMatches[0] ?? null)
                        ? $candidateMatches[0]
                        : empty_extraction_field_result();
                    $matchedRuleSetIndex = $ruleSetIndex;
                    $matchedRuleSet = $ruleSet;
                }

                foreach ($candidateMatches as $candidateMatch) {
                    if (!is_array($candidateMatch)) {
                        continue;
                    }
                    $candidateMatch['ruleSetIndex'] = $ruleSetIndex;
                    $matches[] = $candidateMatch;
                }
            }
        }

        if (
            ($result['value'] ?? null) === null
            && !array_key_exists('selectedValue', $result)
            && !is_array($result['candidates'] ?? null)
            && $matches === []
        ) {
            $result = empty_extraction_field_result();
        }

        $resolvedMatches = [];
        foreach ($matches as $match) {
            if (!is_array($match)) {
                continue;
            }

            $matchRuleSetIndex = is_int($match['ruleSetIndex'] ?? null) ? (int) $match['ruleSetIndex'] : null;
            $normalizationRuleSet = $matchedRuleSet;
            if ($matchRuleSetIndex !== null && is_array($ruleSets[$matchRuleSetIndex] ?? null)) {
                $normalizationRuleSet = $ruleSets[$matchRuleSetIndex];
            }

            $resolvedValue = $match['value'] ?? null;
            if (is_string($resolvedValue)) {
                $resolvedValue = apply_extraction_field_normalization(
                    $resolvedValue,
                    is_string($normalizationRuleSet['normalizationType'] ?? null) ? (string) $normalizationRuleSet['normalizationType'] : 'none',
                    is_string($normalizationRuleSet['normalizationChars'] ?? null) ? (string) $normalizationRuleSet['normalizationChars'] : ''
                );
            }

            $resolvedValue = normalize_auto_archiving_field_value_item($resolvedValue);
            if ($resolvedValue === null) {
                continue;
            }

            $resolvedMatch = $match;
            if ($matchRuleSetIndex !== null) {
                $resolvedMatch['ruleSetIndex'] = $matchRuleSetIndex;
            }
            $resolvedMatch['value'] = $resolvedValue;
            $resolvedMatches[] = $resolvedMatch;
        }

        $resolvedValues = array_values(array_map(
            static fn (array $match): mixed => $match['value'] ?? null,
            $resolvedMatches
        ));
        $primaryMatch = is_array($resolvedMatches[0] ?? null) ? $resolvedMatches[0] : null;

        $results[$key] = [
            'key' => $key,
            'name' => $name,
            'ruleSets' => $ruleSets,
            'extractor' => $extractor,
            'value' => $primaryMatch['value'] ?? null,
            'values' => $resolvedValues,
            'confidence' => is_array($primaryMatch) && isset($primaryMatch['confidence'])
                ? clamp_confidence((float) $primaryMatch['confidence'])
                : 0.0,
            'lineIndex' => is_array($primaryMatch) && is_int($primaryMatch['lineIndex'] ?? null) ? (int) $primaryMatch['lineIndex'] : null,
            'source' => is_array($primaryMatch) && is_string($primaryMatch['source'] ?? null) ? (string) $primaryMatch['source'] : 'none',
            'raw' => is_array($primaryMatch) && is_string($primaryMatch['raw'] ?? null) ? (string) $primaryMatch['raw'] : null,
            'matchText' => is_array($primaryMatch) && is_string($primaryMatch['matchText'] ?? null) ? (string) $primaryMatch['matchText'] : (is_array($primaryMatch) && is_string($primaryMatch['raw'] ?? null) ? (string) $primaryMatch['raw'] : null),
            'matchedRuleSetIndex' => $matchedRuleSetIndex,
            'matches' => $resolvedMatches,
        ];
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

        $valueList = normalize_auto_archiving_field_value_list(
            is_array($result) && is_array($result['values'] ?? null)
                ? $result['values']
                : (is_array($result) && array_key_exists('value', $result) ? [$result['value']] : [])
        );
        if ($valueList === []) {
            continue;
        }

        $values[$resolvedKey] = $valueList;
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

        $fieldMeta = [
            'key' => is_string($result['key'] ?? null) ? trim((string) $result['key']) : $resolvedKey,
            'name' => is_string($result['name'] ?? null) ? trim((string) $result['name']) : $resolvedKey,
        ];
        if (is_string($result['extractor'] ?? null) && trim((string) $result['extractor']) !== '') {
            $fieldMeta['extractor'] = trim((string) $result['extractor']);
        }
        if (array_key_exists('matchedRuleSetIndex', $result) && is_int($result['matchedRuleSetIndex'])) {
            $fieldMeta['matchedRuleSetIndex'] = (int) $result['matchedRuleSetIndex'];
        }
        if (is_array($result['matches'] ?? null)) {
            $fieldMeta['matches'] = array_values(array_map(
                static function (array $match): array {
                    return [
                        'value' => $match['value'] ?? null,
                        'raw' => is_string($match['raw'] ?? null) ? (string) $match['raw'] : null,
                        'matchText' => is_string($match['matchText'] ?? null) ? (string) $match['matchText'] : (is_string($match['raw'] ?? null) ? (string) $match['raw'] : null),
                        'source' => is_string($match['source'] ?? null) ? (string) $match['source'] : null,
                        'labelText' => is_string($match['labelText'] ?? null) ? trim((string) $match['labelText']) : null,
                        'between' => is_string($match['between'] ?? null) ? (string) $match['between'] : null,
                        'confidence' => isset($match['confidence']) ? clamp_confidence((float) $match['confidence']) : 0.0,
                        'lineIndex' => is_int($match['lineIndex'] ?? null) ? (int) $match['lineIndex'] : null,
                        'labelLineIndex' => is_int($match['labelLineIndex'] ?? null) ? (int) $match['labelLineIndex'] : null,
                        'start' => is_int($match['start'] ?? null) ? (int) $match['start'] : null,
                        'ruleSetIndex' => is_int($match['ruleSetIndex'] ?? null) ? (int) $match['ruleSetIndex'] : null,
                        'matchType' => is_string($match['matchType'] ?? null) ? trim((string) $match['matchType']) : null,
                        'searchTerm' => is_string($match['searchTerm'] ?? null) ? trim((string) $match['searchTerm']) : null,
                        'score' => is_numeric($match['score'] ?? null) ? (float) $match['score'] : null,
                        'noisePenalty' => is_numeric($match['noisePenalty'] ?? null) ? clamp_confidence((float) $match['noisePenalty']) : null,
                        'positionPenalty' => is_numeric($match['positionPenalty'] ?? null) ? max(0.0, (float) $match['positionPenalty']) : (is_numeric($match['directionPenalty'] ?? null) ? max(0.0, (float) $match['directionPenalty']) : null),
                        'positionPenaltyAxis' => is_string($match['positionPenaltyAxis'] ?? null) ? trim((string) $match['positionPenaltyAxis']) : null,
                        'mainDirection' => is_string($match['mainDirection'] ?? null) ? trim((string) $match['mainDirection']) : null,
                        'positionDiff' => is_numeric($match['positionDiff'] ?? null) ? (float) $match['positionDiff'] : null,
                        'positionNormalizedDiff' => is_numeric($match['positionNormalizedDiff'] ?? null) ? (float) $match['positionNormalizedDiff'] : null,
                        'noiseText' => is_string($match['noiseText'] ?? null) ? (string) $match['noiseText'] : null,
                        'noiseSegments' => array_values(array_filter(array_map(
                            static function ($segment): ?array {
                                if (!is_array($segment)) {
                                    return null;
                                }
                                $text = is_string($segment['text'] ?? null) ? (string) $segment['text'] : '';
                                $lineIndex = is_int($segment['lineIndex'] ?? null) ? (int) $segment['lineIndex'] : null;
                                $start = is_int($segment['start'] ?? null) ? (int) $segment['start'] : null;
                                $end = is_int($segment['end'] ?? null) ? (int) $segment['end'] : null;
                                if ($text === '' || $lineIndex === null || $start === null || $end === null || $end <= $start) {
                                    return null;
                                }
                                return [
                                    'text' => $text,
                                    'lineIndex' => $lineIndex,
                                    'start' => $start,
                                    'end' => $end,
                                ];
                            },
                            is_array($match['noiseSegments'] ?? null) ? $match['noiseSegments'] : []
                        ), static fn ($segment): bool => is_array($segment))),
                    ];
                },
                array_values(array_filter($result['matches'], static fn ($match): bool => is_array($match)))
            ));
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
        if ($resolvedKey === '' || $value === null || (is_array($value) && $value === [])) {
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
            $firstValue = first_auto_archiving_field_value($rawValue);
            $value = is_scalar($firstValue) || $firstValue === null
                ? trim((string) $firstValue)
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

        if ($type === 'folder') {
            $value = array_key_exists('folder', $fieldValues) ? trim((string) $fieldValues['folder']) : '';
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

function build_auto_archiving_filename_field_values(array $autoResult, array $senders, array $foldersById, array $rules): array
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

    $folderId = is_string($autoResult['folderId'] ?? null) ? trim((string) $autoResult['folderId']) : '';
    $senderId = (int) ($autoResult['senderId'] ?? 0);
    $clientId = is_string($autoResult['clientId'] ?? null) ? trim((string) $autoResult['clientId']) : '';
    $fields = is_array($autoResult['fields'] ?? null) ? $autoResult['fields'] : [];
    $systemFields = is_array($autoResult['systemFields'] ?? null) ? $autoResult['systemFields'] : [];
    $allFields = array_merge($fields, $systemFields);

    $folder = $folderId !== '' ? ($foldersById[$folderId] ?? null) : null;
    $sender = $senderId > 0 ? find_sender_by_id($senders, $senderId) : null;

    $setValue($values, 'folder', is_array($folder) ? archive_folder_display_text($folder) : null);
    $setValue($values, 'client', $clientId);
    $setValue($values, 'main_client', $clientId);
    $setValue($values, 'sender', is_array($sender) ? ($sender['name'] ?? null) : null);

    if (array_key_exists('amount', $allFields)) {
        $amount = first_auto_archiving_field_value($allFields['amount']);
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
            $setValue($values, $fieldKey, first_auto_archiving_field_value($allFields[$lookupKey]));
        }
    }
    if (array_key_exists('payment_receiver', $allFields)) {
        $setValue($values, 'payee', first_auto_archiving_field_value($allFields['payment_receiver']));
    }

    foreach ($allFields as $fieldKey => $fieldValue) {
        $resolvedKey = is_string($fieldKey) ? trim($fieldKey) : '';
        if ($resolvedKey === '') {
            continue;
        }
        $setValue($values, $resolvedKey, first_auto_archiving_field_value($fieldValue));
    }

    $labelNames = build_auto_archiving_filename_label_names($autoResult, $rules);
    if ($labelNames !== []) {
        $values['__labels'] = $labelNames;
    }

    return $values;
}

function render_archive_folder_path(array $autoResult, array $folder, array $rules, array $senders): string
{
    $folderId = is_string($folder['id'] ?? null) ? trim((string) $folder['id']) : '';
    $template = normalize_filename_template($folder['pathTemplate'] ?? null);
    $rendered = trim(preg_replace(
        '/\s+/',
        ' ',
        evaluate_filename_template_parts_backend(
            is_array($template['parts'] ?? null) ? $template['parts'] : [],
            build_auto_archiving_filename_field_values($autoResult, $senders, $folderId !== '' ? [$folderId => $folder] : [], $rules)
        )
    ) ?? '');

    return trim($rendered, " \t\n\r\0\x0B" . DIRECTORY_SEPARATOR);
}

function generate_auto_archiving_filename(array $job, array $autoResult, array $rules, array $senders): string
{
    [$foldersById, $filenameTemplatesById] = build_archive_structure_indexes($rules);
    $folderId = is_string($autoResult['folderId'] ?? null) ? trim((string) $autoResult['folderId']) : '';
    $folder = $folderId !== '' ? ($foldersById[$folderId] ?? null) : null;
    $labelIds = array_values(array_filter(array_map(static function ($value): string {
        return is_string($value) ? trim((string) $value) : '';
    }, is_array($autoResult['labels'] ?? null) ? $autoResult['labels'] : []), static fn (string $value): bool => $value !== ''));
    $selectedTemplate = null;
    $filenameTemplateId = is_string($autoResult['filenameTemplateId'] ?? null) ? trim((string) $autoResult['filenameTemplateId']) : '';
    if ($filenameTemplateId !== '') {
        $selectedTemplate = $filenameTemplatesById[$filenameTemplateId] ?? null;
    }
    if ($selectedTemplate === null && is_array($folder)) {
        $selectedTemplateMatch = select_archive_folder_filename_template_by_label_ids($folder, $labelIds);
        $selectedTemplate = is_array($selectedTemplateMatch) ? ($selectedTemplateMatch['template'] ?? null) : null;
    }
    $template = normalize_filename_template(is_array($selectedTemplate) ? ($selectedTemplate['template'] ?? null) : null);
    $rendered = trim(preg_replace(
        '/\s+/',
        ' ',
        evaluate_filename_template_parts_backend(
            is_array($template['parts'] ?? null) ? $template['parts'] : [],
            build_auto_archiving_filename_field_values($autoResult, $senders, is_array($folder) && $folderId !== '' ? [$folderId => $folder] : [], $rules)
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
        $normalizedValue = normalize_auto_archiving_field_value_list($value);
        if ($resolvedKey === '' || $normalizedValue === []) {
            continue;
        }
        $fields[$resolvedKey] = $normalizedValue;
    }

    $systemFields = [];
    foreach (is_array($result['systemFields'] ?? null) ? $result['systemFields'] : [] as $key => $value) {
        $resolvedKey = is_string($key) ? trim($key) : '';
        $normalizedValue = normalize_auto_archiving_field_value_list($value);
        if ($resolvedKey === '' || $normalizedValue === []) {
            continue;
        }
        $systemFields[$resolvedKey] = $normalizedValue;
    }

    ksort($fields, SORT_NATURAL);
    ksort($systemFields, SORT_NATURAL);

    $clientId = is_string($result['clientId'] ?? null) ? trim((string) $result['clientId']) : '';
    if ($clientId === '' && is_string($result['principalId'] ?? null)) {
        $clientId = trim((string) $result['principalId']);
    }

    return [
        'clientId' => $clientId !== '' ? $clientId : null,
        'senderId' => isset($result['senderId']) && (int) $result['senderId'] > 0 ? (int) $result['senderId'] : null,
        'folderId' => is_string($result['folderId'] ?? null) ? trim((string) $result['folderId']) : null,
        'filenameTemplateId' => is_string($result['filenameTemplateId'] ?? null) ? trim((string) $result['filenameTemplateId']) : null,
        'labels' => array_values(array_unique($labels)),
        'fields' => $fields,
        'systemFields' => $systemFields,
        'filename' => is_string($result['filename'] ?? null) ? trim((string) $result['filename']) : null,
        'archiveFolderPath' => is_string($result['archiveFolderPath'] ?? null) ? trim((string) $result['archiveFolderPath']) : null,
    ];
}

function job_auto_archiving_result(array $job): array
{
    $jobId = is_string($job['id'] ?? null) ? trim((string) $job['id']) : '';
    $stored = $jobId !== '' ? job_analysis_snapshot($jobId) : null;
    if (is_array($stored)) {
        return normalize_auto_archiving_result($stored);
    }

    $analysis = is_array($job['analysis'] ?? null) ? $job['analysis'] : [];
    $normalized = normalize_auto_archiving_result(
        is_array($analysis['autoArchivingResult'] ?? null) ? $analysis['autoArchivingResult'] : []
    );
    if (
        $normalized['clientId'] !== null
        || $normalized['senderId'] !== null
        || $normalized['folderId'] !== null
        || $normalized['labels'] !== []
        || $normalized['fields'] !== []
        || $normalized['systemFields'] !== []
    ) {
        return $normalized;
    }

    return $normalized;
}

function job_analysis_payload(array $job): array
{
    $analysis = is_array($job['analysis'] ?? null) ? $job['analysis'] : [];
    $jobId = is_string($job['id'] ?? null) ? trim((string) $job['id']) : '';
    $stored = $jobId !== '' ? job_analysis_snapshot($jobId) : null;
    $autoResult = job_auto_archiving_result($job);

    if (
        $autoResult['clientId'] !== null
        || $autoResult['senderId'] !== null
        || $autoResult['folderId'] !== null
        || $autoResult['labels'] !== []
        || $autoResult['fields'] !== []
        || $autoResult['systemFields'] !== []
    ) {
        $analysis['autoArchivingResult'] = $autoResult;
    }

    $analyzedAt = is_string($analysis['analyzedAt'] ?? null) ? trim((string) $analysis['analyzedAt']) : '';
    if ($analyzedAt === '' && is_array($stored) && is_string($stored['analyzedAt'] ?? null)) {
        $analyzedAt = trim((string) $stored['analyzedAt']);
    }
    if ($analyzedAt !== '') {
        $analysis['analyzedAt'] = $analyzedAt;
    }

    return $analysis;
}

function normalize_auto_archiving_field_value_item(mixed $value): mixed
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

function normalize_auto_archiving_field_value_list(mixed $value): array
{
    if (is_array($value)) {
        if (array_key_exists('values', $value) && is_array($value['values'])) {
            $items = array_values($value['values']);
        } elseif (array_key_exists('value', $value)) {
            $items = [$value['value']];
        } else {
            $items = array_values($value);
        }
    } else {
        $items = [$value];
    }
    $normalized = [];

    foreach ($items as $item) {
        $normalizedItem = normalize_auto_archiving_field_value_item($item);
        if ($normalizedItem === null) {
            continue;
        }
        $normalized[] = $normalizedItem;
    }

    return $normalized;
}

function first_auto_archiving_field_value(mixed $value): mixed
{
    $values = normalize_auto_archiving_field_value_list($value);
    return $values[0] ?? null;
}

function calculate_auto_archiving_result_from_text(
    string $ocrText,
    array $job,
    array $rules,
    array $clients,
    array $senders,
    array $replacementMap,
    ?array $matchingPayload = null,
    string $jobDir = ''
): array {
    $resolvedMatchingPayload = is_array($matchingPayload) ? $matchingPayload : load_matching_settings_payload();
    $positionSettings = normalize_matching_position_adjustment_settings(
        is_array($resolvedMatchingPayload['positionAdjustment'] ?? null) ? $resolvedMatchingPayload['positionAdjustment'] : []
    );
    $lineGeometries = build_matching_line_geometries_for_job($job, $ocrText);
    $systemLabels = is_array($rules['systemLabels'] ?? null) ? $rules['systemLabels'] : [];
    $labels = is_array($rules['labels'] ?? null) ? $rules['labels'] : [];
    $archiveFolders = is_array($rules['archiveFolders'] ?? null) ? $rules['archiveFolders'] : [];
    $configuredFields = array_values(array_merge(
        is_array($rules['predefinedFields'] ?? null) ? $rules['predefinedFields'] : [],
        is_array($rules['systemFields'] ?? null) ? $rules['systemFields'] : [],
        is_array($rules['fields'] ?? null) ? $rules['fields'] : []
    ));

    $configuredFieldResults = extract_configured_text_field_results(
        split_lines_for_matching($ocrText),
        $replacementMap,
        $configuredFields,
        $positionSettings,
        $lineGeometries
    );
    $configuredFieldValues = simplify_extraction_field_values($configuredFieldResults);
    $configuredFieldMeta = simplify_extraction_field_meta($configuredFieldResults);
    $fieldPartitions = partition_archiving_field_values($configuredFieldValues, $rules);
    $fieldNamesByKey = build_label_matching_field_name_map($configuredFields);

    $matchedClientDirName = match_client_dir_name($ocrText, $clients);
    $preselectedClient = null;
    if (is_string($matchedClientDirName) && trim($matchedClientDirName) !== '') {
        $preselectedClient = [
            'dirName' => trim($matchedClientDirName),
        ];
    }

    $senderContext = resolve_label_matching_sender_context($configuredFieldValues);
    $matchedSenderId = ($senderContext['senderId'] ?? 0) > 0 ? (int) $senderContext['senderId'] : null;
    $preselectedSender = $matchedSenderId !== null
        ? [
            'id' => $matchedSenderId,
            'name' => is_string($senderContext['senderName'] ?? null) ? trim((string) $senderContext['senderName']) : '',
        ]
        : null;
    if ($jobDir !== '') {
        $senderSummary = build_job_sender_summary([
            'extractionFields' => $configuredFieldValues,
        ], $jobDir, null, null);
        $senderSelection = single_preselected_sender_from_summary($senderSummary);
        $matchedSenderId = isset($senderSelection['matchedSenderId']) && (int) ($senderSelection['matchedSenderId'] ?? 0) > 0
            ? (int) $senderSelection['matchedSenderId']
            : null;
        $preselectedSender = is_array($senderSelection['preselectedSender'] ?? null)
            ? $senderSelection['preselectedSender']
            : null;
    }

    $labelMatchContext = [
        'senderId' => $matchedSenderId,
        'senderName' => is_array($preselectedSender) ? (string) ($preselectedSender['name'] ?? '') : '',
        'senderNamesById' => is_array($senderContext['senderNamesById'] ?? null) ? $senderContext['senderNamesById'] : [],
        'fieldValues' => $configuredFieldValues,
        'fieldNamesByKey' => $fieldNamesByKey,
    ];

    $systemLabelMatches = find_incremental_label_matches($ocrText, $systemLabels, $replacementMap, $labelMatchContext);
    $labelMatches = find_incremental_label_matches($ocrText, $labels, $replacementMap, [
        ...$labelMatchContext,
        'matchedLabelsById' => matched_labels_by_id($systemLabelMatches),
    ]);
    $matchedLabelsById = matched_labels_by_id(array_merge($systemLabelMatches, $labelMatches));
    $resolvedLabels = resolved_label_ids_from_matches($systemLabelMatches, $labelMatches);
    $selectedFolderMatch = select_archive_folder_by_labels($archiveFolders, $matchedLabelsById);
    $selectedFolder = is_array($selectedFolderMatch) ? ($selectedFolderMatch['folder'] ?? null) : null;
    $selectedFolderId = is_string($selectedFolder['id'] ?? null) ? trim((string) $selectedFolder['id']) : null;
    $selectedFilenameTemplate = is_array($selectedFolder)
        ? select_archive_folder_filename_template($selectedFolder, $matchedLabelsById)
        : null;
    $selectedFilenameTemplateId = is_array($selectedFilenameTemplate)
        ? (is_string($selectedFilenameTemplate['template']['id'] ?? null) ? trim((string) $selectedFilenameTemplate['template']['id']) : null)
        : null;

    $autoResult = normalize_auto_archiving_result([
        'clientId' => is_string($matchedClientDirName) && trim($matchedClientDirName) !== ''
            ? trim($matchedClientDirName)
            : null,
        'senderId' => $matchedSenderId,
        'folderId' => $selectedFolderId,
        'filenameTemplateId' => $selectedFilenameTemplateId,
        'labels' => $resolvedLabels,
        'fields' => $fieldPartitions['fields'],
        'systemFields' => $fieldPartitions['systemFields'],
    ]);
    if (is_array($selectedFolder)) {
        $autoResult['archiveFolderPath'] = render_archive_folder_path($autoResult, $selectedFolder, $rules, $senders);
    }
    $autoResult['filename'] = generate_auto_archiving_filename($job, $autoResult, $rules, $senders);

    return [
        'matchedClientDirName' => $matchedClientDirName,
        'matchedSenderId' => $matchedSenderId,
        'preselectedClient' => $preselectedClient,
        'preselectedSender' => $preselectedSender,
        'systemLabelMatches' => $systemLabelMatches,
        'labelMatches' => $labelMatches,
        'labels' => $resolvedLabels,
        'extractionFieldResults' => $configuredFieldResults,
        'extractionFieldValues' => $configuredFieldValues,
        'extractionFieldMeta' => $configuredFieldMeta,
        'autoArchivingResult' => $autoResult,
    ];
}

function load_job_ocr_text(string $jobDir): string
{
    ensure_merged_objects_text_from_storage($jobDir);
    $ocrPath = $jobDir . '/merged_objects.txt';
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
        $replacementMap,
        $matchingPayload,
        $jobDir
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

    $normalized = job_auto_archiving_result($job);

    $selectedClientDirName = is_string($job['selectedClientDirName'] ?? null) ? trim((string) $job['selectedClientDirName']) : '';
    $selectedSenderId = resolve_active_sender_id(isset($job['selectedSenderId']) ? (int) $job['selectedSenderId'] : 0);
    $selectedFolderId = is_string($job['selectedFolderId'] ?? null) ? trim((string) $job['selectedFolderId']) : '';
    $selectedLabelIds = array_key_exists('selectedLabelIds', $job)
        ? normalize_stored_job_label_ids($job['selectedLabelIds'])
        : null;
    $filename = is_string($job['filename'] ?? null) ? trim((string) $job['filename']) : '';

    if ($selectedClientDirName !== '') {
        $normalized['clientId'] = $selectedClientDirName;
    }
    if ($selectedSenderId > 0) {
        $normalized['senderId'] = $selectedSenderId;
    }
    if ($selectedFolderId !== '') {
        $normalized['folderId'] = $selectedFolderId;
        $archiveFolder = find_loaded_archive_folder_by_id(load_archive_folders(), $selectedFolderId);
        if (is_array($archiveFolder)) {
            $selectedFilenameTemplate = select_archive_folder_filename_template_by_label_ids(
                $archiveFolder,
                is_array($selectedLabelIds) ? $selectedLabelIds : (is_array($normalized['labels'] ?? null) ? $normalized['labels'] : [])
            );
            $normalized['filenameTemplateId'] = is_array($selectedFilenameTemplate)
                ? (is_string($selectedFilenameTemplate['template']['id'] ?? null) ? trim((string) $selectedFilenameTemplate['template']['id']) : null)
                : null;
        }
    }
    if (is_array($selectedLabelIds)) {
        $normalized['labels'] = $selectedLabelIds;
    }
    if ($filename !== '') {
        $normalized['filename'] = $filename;
    }

    return normalize_auto_archiving_result($normalized);
}

function approved_archiving_from_archive_request(array $job, array $autoResult, array $payload, array $archiveFolders): array
{
    $approved = normalize_auto_archiving_result($autoResult);
    $selectedClientDirName = array_key_exists('selectedClientDirName', $payload)
        ? (is_string($payload['selectedClientDirName'] ?? null) ? trim((string) $payload['selectedClientDirName']) : '')
        : (is_string($job['selectedClientDirName'] ?? null) ? trim((string) $job['selectedClientDirName']) : '');
    $selectedSenderId = array_key_exists('selectedSenderId', $payload)
        ? (int) ($payload['selectedSenderId'] ?? 0)
        : (isset($job['selectedSenderId']) ? (int) $job['selectedSenderId'] : 0);
    $selectedSenderId = resolve_active_sender_id($selectedSenderId) ?? 0;
    $selectedFolderId = array_key_exists('selectedFolderId', $payload)
        ? (is_string($payload['selectedFolderId'] ?? null) ? trim((string) $payload['selectedFolderId']) : '')
        : (is_string($job['selectedFolderId'] ?? null) ? trim((string) $job['selectedFolderId']) : '');
    $selectedLabelIds = array_key_exists('selectedLabelIds', $payload)
        ? (($payload['selectedLabelIds'] ?? null) === null ? null : normalize_selected_job_label_ids_payload($payload['selectedLabelIds'], known_job_label_ids()))
        : (array_key_exists('selectedLabelIds', $job) ? normalize_stored_job_label_ids($job['selectedLabelIds']) : null);
    $filenameInput = array_key_exists('filename', $payload)
        ? (is_string($payload['filename'] ?? null) ? trim((string) $payload['filename']) : '')
        : (is_string($job['filename'] ?? null) ? trim((string) $job['filename']) : '');

    if ($selectedClientDirName !== '') {
        $approved['clientId'] = $selectedClientDirName;
    }
    if ($selectedSenderId > 0) {
        $approved['senderId'] = $selectedSenderId;
    }
    if ($selectedFolderId !== '') {
        $approved['folderId'] = $selectedFolderId;
        $archiveFolder = find_loaded_archive_folder_by_id($archiveFolders, $selectedFolderId);
        if (is_array($archiveFolder)) {
            $selectedFilenameTemplate = select_archive_folder_filename_template_by_label_ids(
                $archiveFolder,
                is_array($selectedLabelIds) ? $selectedLabelIds : (is_array($approved['labels'] ?? null) ? $approved['labels'] : [])
            );
            $approved['filenameTemplateId'] = is_array($selectedFilenameTemplate)
                ? (is_string($selectedFilenameTemplate['template']['id'] ?? null) ? trim((string) $selectedFilenameTemplate['template']['id']) : null)
                : null;
            $approved['archiveFolderPath'] = render_archive_folder_path($approved, $archiveFolder, load_active_archiving_rules(), load_senders());
        }
    }
    if (is_array($selectedLabelIds)) {
        $approved['labels'] = $selectedLabelIds;
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
    $folderNames = [];
    foreach (array_merge(
        is_array($activeRules['archiveFolders'] ?? null) ? $activeRules['archiveFolders'] : [],
        is_array($draftRules['archiveFolders'] ?? null) ? $draftRules['archiveFolders'] : []
    ) as $folder) {
        if (!is_array($folder)) {
            continue;
        }
        $folderId = is_string($folder['id'] ?? null) ? trim((string) $folder['id']) : '';
        $folderName = archive_folder_display_text($folder);
        if ($folderId !== '' && $folderName !== '' && !isset($folderNames[$folderId])) {
            $folderNames[$folderId] = $folderName;
        }
    }

    $filenameTemplateNames = [];
    foreach (array_merge(
        flatten_archive_folder_filename_templates($activeRules),
        flatten_archive_folder_filename_templates($draftRules)
    ) as $template) {
        if (!is_array($template)) {
            continue;
        }
        $templateId = is_string($template['templateId'] ?? null) ? trim((string) $template['templateId']) : '';
        $folderName = is_string($template['folderName'] ?? null) ? trim((string) $template['folderName']) : '';
        $templateIndex = is_int($template['templateIndex'] ?? null) ? (int) $template['templateIndex'] : 0;
        $templateName = trim(($folderName !== '' ? $folderName . ' / ' : '') . 'Filnamnsmall ' . ($templateIndex + 1));
        if ($templateId !== '' && $templateName !== '' && !isset($filenameTemplateNames[$templateId])) {
            $filenameTemplateNames[$templateId] = $templateName;
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
        'archiveFolders' => $folderNames,
        'filenameTemplates' => $filenameTemplateNames,
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

    if (is_array($value)) {
        $value = first_auto_archiving_field_value($value);
    }

    if ($key === 'clientId') {
        $resolved = (string) $value;
        return $displayMaps['clients'][$resolved] ?? $resolved;
    }
    if ($key === 'senderId') {
        $resolved = (string) ((int) $value);
        return $displayMaps['senders'][$resolved] ?? $resolved;
    }
    if ($key === 'folderId') {
        $resolved = (string) $value;
        return $displayMaps['archiveFolders'][$resolved] ?? $resolved;
    }
    if ($key === 'filenameTemplateId') {
        $resolved = (string) $value;
        return $displayMaps['filenameTemplates'][$resolved] ?? $resolved;
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
        'clientId' => 'Huvudman',
        'senderId' => 'Avsändare',
        'folderId' => 'Mapp',
        'filenameTemplateId' => 'Filnamnsregel',
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
    $scalarKeys = ['clientId', 'senderId', 'folderId', 'filenameTemplateId', 'archiveFolderPath'];
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
            $activeResult = archived_job_historical_auto_result($jobId, $job, $activeVersion);
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
        $normalizedValue = normalize_auto_archiving_field_value_list($value);
        if ($normalizedValue === []) {
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
    $archiveFolders = load_archive_folders();
    $rules = load_active_archiving_rules();

    $clientDirName = is_string($payload['clientId'] ?? null) ? trim((string) $payload['clientId']) : '';
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

    $folderId = is_string($payload['folderId'] ?? null) ? trim((string) $payload['folderId']) : '';
    $archiveFolder = $folderId !== '' ? find_loaded_archive_folder_by_id($archiveFolders, $folderId) : null;
    if ($folderId !== '' && !is_array($archiveFolder)) {
        throw new RuntimeException('Ogiltig mapp');
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
        $next['clientId'] = $clientDirName;
    }
    if ($senderId > 0) {
        $next['senderId'] = $senderId;
    }
    if ($folderId !== '') {
        $next['folderId'] = $folderId;
        $next['archiveFolderPath'] = is_array($archiveFolder)
            ? render_archive_folder_path($next, $archiveFolder, $rules, $senders)
            : null;
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
        $clientId = is_string($nextApproved['clientId'] ?? null) ? trim((string) $nextApproved['clientId']) : '';
        $archiveFolderPath = is_string($nextApproved['archiveFolderPath'] ?? null) ? trim((string) $nextApproved['archiveFolderPath']) : '';
        $filename = is_string($nextApproved['filename'] ?? null) ? trim((string) $nextApproved['filename']) : '';
        if ($clientId === '' || $filename === '') {
            throw new RuntimeException('Det nya arkiveringsvärdet är ofullständigt');
        }
        $targetDirectory = rtrim($outputBaseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $clientId;
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
    $job['selectedClientDirName'] = is_string($nextApproved['clientId'] ?? null) ? trim((string) $nextApproved['clientId']) : null;
    $job['selectedSenderId'] = isset($nextApproved['senderId']) ? (int) $nextApproved['senderId'] : null;
    $job['selectedFolderId'] = is_string($nextApproved['folderId'] ?? null) ? trim((string) $nextApproved['folderId']) : null;
    $job['selectedLabelIds'] = normalize_stored_job_label_ids($nextApproved['labels'] ?? null);
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
            'senderMatches' => [],
            'extractionFields' => new stdClass(),
            'extractionFieldMeta' => new stdClass(),
            'labels' => [],
        ],
        'files' => [
            'sourcePdf' => 'source.pdf',
            'reviewPdf' => 'review.pdf',
            'ocrText' => 'merged_objects.txt',
            'ocrObjects' => 'ocr-objects.json',
            'extracted' => 'extracted.json',
        ],
        'selectedClientDirName' => null,
        'selectedSenderId' => null,
        'selectedFolderId' => null,
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
    array $archiveFolders,
    array $labels,
    array $systemLabels,
    array $matchingPayload,
    array $replacementMap,
    bool $ocrSkipExistingText,
    int $ocrOptimizeLevel,
    string $ocrTextExtractionMethod,
    array $ocrPdfTextSubstitutions,
    bool $runOcr = true
): array
{
    $reviewPdfPath = $jobDir . '/review.pdf';
    $legacyOcrPath = $jobDir . '/ocr.txt';
    $ocrmypdfSidecarPath = $jobDir . '/ocrmypdf_sidecar.txt';
    $ocrObjectsPath = $jobDir . '/ocr-objects.json';
    if (is_file($legacyOcrPath)) {
        @unlink($legacyOcrPath);
    }
    if (is_file($ocrmypdfSidecarPath)) {
        @unlink($ocrmypdfSidecarPath);
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
    $jobId = job_id_from_directory($jobDir);
    if ($runOcr) {
        if ($jobId !== null) {
            clear_job_merged_objects_document($jobId);
        }
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
            $ocrmypdfSidecarPath,
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
        if (!is_file($jobDir . '/merged_objects.txt')) {
            ensure_merged_objects_text_from_storage($jobDir, $jobId);
        }
    }

    if (is_file($ocrObjectsPath)) {
        @unlink($ocrObjectsPath);
    }
    unset($ocrTextExtractionMethod, $fallbackTxtPath);

    $ocrText = load_job_ocr_text($jobDir);

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
        $replacementMap,
        $matchingPayload,
        $jobDir
    );

    observe_extracted_sender_identifiers($analysisPayload['extractionFieldResults']);

    $senderLinkSync = [
        'merged' => false,
        'merges' => [],
        'components' => [],
    ];
    $config = null;
    if ($jobId !== null) {
        $config = load_config();
        $senderLinkSync = sync_job_sender_document_links($config, $jobId);
        foreach (is_array($senderLinkSync['merges'] ?? null) ? $senderLinkSync['merges'] : [] as $merge) {
            if (!is_array($merge)) {
                continue;
            }
            foreach (is_array($merge['movedOrganizationNumbers'] ?? null) ? $merge['movedOrganizationNumbers'] : [] as $organizationNumber) {
                if (!is_string($organizationNumber) || trim($organizationNumber) === '') {
                    continue;
                }
                handle_resolved_sender_identifier_followups($config, 'organization_number', $organizationNumber, $jobId);
            }
            foreach (is_array($merge['movedPaymentNumbers'] ?? null) ? $merge['movedPaymentNumbers'] : [] as $payment) {
                if (!is_array($payment)) {
                    continue;
                }
                $paymentType = is_string($payment['type'] ?? null) ? trim((string) $payment['type']) : '';
                $paymentNumber = is_string($payment['number'] ?? null) ? trim((string) $payment['number']) : '';
                if ($paymentType === '' || $paymentNumber === '') {
                    continue;
                }
                handle_resolved_sender_identifier_followups($config, $paymentType, $paymentNumber, $jobId);
            }
        }
    }

    $extractedData = [
        'matchedClientDirName' => $analysisPayload['matchedClientDirName'],
        'systemLabelMatches' => $analysisPayload['systemLabelMatches'],
        'labelMatches' => $analysisPayload['labelMatches'],
        'labels' => $analysisPayload['labels'],
        'extractionFields' => $analysisPayload['extractionFieldValues'],
        'extractionFieldMeta' => $analysisPayload['extractionFieldMeta'] !== [] ? $analysisPayload['extractionFieldMeta'] : new stdClass(),
        'preselectedClient' => $analysisPayload['preselectedClient'],
        'preselectedSender' => null,
        'autoArchivingResult' => $analysisPayload['autoArchivingResult'],
    ];

    $senderSummary = build_job_sender_summary($extractedData, $jobDir, null, null);
    $senderSelection = single_preselected_sender_from_summary($senderSummary);
    $analysisPayload['matchedSenderId'] = isset($senderSelection['matchedSenderId']) ? (int) ($senderSelection['matchedSenderId'] ?? 0) : null;
    if ($analysisPayload['matchedSenderId'] !== null && $analysisPayload['matchedSenderId'] < 1) {
        $analysisPayload['matchedSenderId'] = null;
    }
    $analysisPayload['preselectedSender'] = is_array($senderSelection['preselectedSender'] ?? null)
        ? $senderSelection['preselectedSender']
        : null;
    $analysisPayload['senderMatches'] = is_array($senderSelection['senderMatches'] ?? null)
        ? $senderSelection['senderMatches']
        : [];
    $analysisPayload['autoArchivingResult']['senderId'] = $analysisPayload['matchedSenderId'];
    $analysisPayload['autoArchivingResult'] = normalize_auto_archiving_result($analysisPayload['autoArchivingResult']);
    $analysisPayload['autoArchivingResult']['filename'] = generate_auto_archiving_filename(
        $jobContext,
        $analysisPayload['autoArchivingResult'],
        $activeRules,
        load_senders()
    );

    $extractedData = [
        'matchedClientDirName' => $analysisPayload['matchedClientDirName'],
        'matchedSenderId' => $analysisPayload['matchedSenderId'],
        'systemLabelMatches' => $analysisPayload['systemLabelMatches'],
        'labelMatches' => $analysisPayload['labelMatches'],
        'labels' => $analysisPayload['labels'],
        'extractionFields' => $analysisPayload['extractionFieldValues'],
        'extractionFieldMeta' => $analysisPayload['extractionFieldMeta'] !== [] ? $analysisPayload['extractionFieldMeta'] : new stdClass(),
        'preselectedClient' => $analysisPayload['preselectedClient'],
        'preselectedSender' => $analysisPayload['preselectedSender'],
        'senderMatches' => $analysisPayload['senderMatches'],
        'autoArchivingResult' => $analysisPayload['autoArchivingResult'],
    ];

    write_json_file($jobDir . '/extracted.json', $extractedData);

    $analyzedAt = now_iso();
    $jobId = basename($jobDir);
    if (is_string($jobId) && $jobId !== '') {
        sync_job_analysis_snapshot($jobId, $analysisPayload['autoArchivingResult'], $analyzedAt);
    }

    return [
        'extractedData' => $extractedData,
        'analysis' => [
            'preselectedClient' => $analysisPayload['preselectedClient'],
            'preselectedSender' => $analysisPayload['preselectedSender'],
            'senderMatches' => $analysisPayload['senderMatches'],
            'extractionFields' => $analysisPayload['extractionFieldValues'],
            'extractionFieldMeta' => $analysisPayload['extractionFieldMeta'] !== [] ? $analysisPayload['extractionFieldMeta'] : new stdClass(),
            'labels' => $analysisPayload['labels'],
            'autoArchivingResult' => $analysisPayload['autoArchivingResult'],
            'analyzedAt' => $analyzedAt,
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
    array $archiveFolders,
    array $labels,
    array $systemLabels,
    array $matchingPayload,
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

        $replacementMap = replacement_map(
            is_array($matchingPayload['replacements'] ?? null) ? $matchingPayload['replacements'] : []
        );
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
            $archiveFolders,
            $labels,
            $systemLabels,
            $matchingPayload,
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
        unset($jobData['analysisOutdated'], $jobData['analysisAutoReprocessQueued']);
        unset($jobData['error'], $jobData['reprocessMode'], $jobData['forceOcr']);
        write_json_file($jobJsonPath, $jobData);
        queue_job_upsert_event($config, $jobId);
    } catch (Throwable $e) {
        $jobData['status'] = 'failed';
        $jobData['updatedAt'] = now_iso();
        $jobData['error'] = $e->getMessage();
        unset($jobData['analysisOutdated'], $jobData['analysisAutoReprocessQueued']);
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
        $archiveFolders = load_archive_folders();
        $labels = load_labels();
        $systemLabels = load_system_labels();
        $matchingPayload = load_matching_settings_payload();

        while (true) {
            $jobId = next_processing_job_id($config);
            if ($jobId === null) {
                break;
            }

            process_job_by_id(
                $config,
                $clients,
                $archiveFolders,
                $labels,
                $systemLabels,
                $matchingPayload,
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

function extracted_field_scalar_value(array $extracted, string $fieldKey): ?string
{
    $value = first_auto_archiving_field_value($extracted['extractionFields'][$fieldKey] ?? null);
    if ($value === null) {
        return null;
    }
    if (is_string($value)) {
        $trimmed = trim($value);
        return $trimmed !== '' ? $trimmed : null;
    }
    if (is_numeric($value)) {
        return trim((string) $value);
    }

    return null;
}

function extracted_field_string_values(array $extracted, string $fieldKey): array
{
    $values = normalize_auto_archiving_field_value_list($extracted['extractionFields'][$fieldKey] ?? null);
    $strings = [];
    foreach ($values as $value) {
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '') {
                $strings[] = $trimmed;
            }
            continue;
        }
        if (is_numeric($value)) {
            $strings[] = trim((string) $value);
        }
    }

    return $strings;
}

function sync_named_sender_identifier_links(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $repository = sender_repository_instance();
    if ($repository === null) {
        return;
    }

    try {
        $repository->resolveUnlinkedNamedIdentifiers();
    } catch (Throwable $e) {
        // Best effort only. State rendering should still work if link sync fails.
    }
}

function cached_sender_editor_rows_by_id(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $cache = [];
    $repository = sender_repository_instance();
    if ($repository === null) {
        return $cache;
    }

    try {
        $rows = $repository->listEditorRows();
    } catch (Throwable $e) {
        return $cache;
    }

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $senderId = isset($row['id']) ? (int) $row['id'] : 0;
        if ($senderId < 1) {
            continue;
        }
        $cache[$senderId] = $row;
    }

    return $cache;
}

function normalized_sender_summary_search_text(string $value): string
{
    $lowered = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    $collapsed = preg_replace('/\s+/u', ' ', $lowered);
    return is_string($collapsed) ? trim($collapsed) : trim($lowered);
}

function sender_summary_document_text(string $jobDir): string
{
    static $cache = [];
    if (array_key_exists($jobDir, $cache)) {
        return $cache[$jobDir];
    }

    $path = rtrim($jobDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'merged_objects.txt';
    $text = is_file($path) ? file_get_contents($path) : '';
    if (!is_string($text) || $text === '') {
        $cache[$jobDir] = '';
        return '';
    }

    $cache[$jobDir] = normalized_sender_summary_search_text($text);
    return $cache[$jobDir];
}

function sender_summary_text_contains(string $haystack, string $needle): bool
{
    $normalizedNeedle = normalized_sender_summary_search_text($needle);
    if ($normalizedNeedle === '' || $haystack === '') {
        return false;
    }

    return str_contains($haystack, $normalizedNeedle);
}

function build_job_sender_summary(?array $extracted, string $jobDir, ?int $matchedSenderId, ?int $selectedSenderId): ?array
{
    if (!is_array($extracted)) {
        return null;
    }

    sync_named_sender_identifier_links();

    $organizationNumbers = extracted_field_string_values($extracted, 'organisationsnummer');
    $bankgiroValues = extracted_field_string_values($extracted, 'bankgiro');
    $plusgiroValues = extracted_field_string_values($extracted, 'plusgiro');
    $documentText = sender_summary_document_text($jobDir);
    $senderRowsById = cached_sender_editor_rows_by_id();

    $normalizedOrganizationNumbers = [];
    foreach ($organizationNumbers as $organizationNumber) {
        $normalized = \Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber($organizationNumber);
        if ($normalized !== null) {
            $normalizedOrganizationNumbers[$normalized] = true;
        }
    }

    $normalizedBankgiroValues = [];
    foreach ($bankgiroValues as $bankgiro) {
        $normalized = \Docflow\Senders\IdentifierNormalizer::normalizeBankgiro($bankgiro);
        if ($normalized !== null) {
            $normalizedBankgiroValues[$normalized] = true;
        }
    }

    $normalizedPlusgiroValues = [];
    foreach ($plusgiroValues as $plusgiro) {
        $normalized = \Docflow\Senders\IdentifierNormalizer::normalizePlusgiro($plusgiro);
        if ($normalized !== null) {
            $normalizedPlusgiroValues[$normalized] = true;
        }
    }

    $observations = [];
    $observationKeys = [];
    $senderIds = [];

    foreach ($organizationNumbers as $organizationNumber) {
        $observedRow = observed_sender_organization_summary_row($organizationNumber);
        $observedSenderId = isset($observedRow['senderId']) && (int) $observedRow['senderId'] > 0 ? (int) $observedRow['senderId'] : null;
        if ($observedSenderId !== null) {
            $senderIds[$observedSenderId] = true;
        } else {
            $observationKey = 'organization_number:' . (is_string($observedRow['organizationNumber'] ?? null)
                ? $observedRow['organizationNumber']
                : (\Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber($organizationNumber) ?? $organizationNumber));
            if (isset($observationKeys[$observationKey])) {
                continue;
            }
            $observationKeys[$observationKey] = true;
            $observations[] = [
                'key' => $observationKey,
                'type' => 'organization_number',
                'itemLabel' => 'ORG.NR',
                'itemValue' => \Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber($organizationNumber)
                    ?? preg_replace('/\D+/', '', $organizationNumber)
                    ?? $organizationNumber,
                'status' => 'pending',
            ];
        }
    }

    foreach ($bankgiroValues as $bankgiro) {
        $observedRow = observed_sender_payment_summary_row('bankgiro', $bankgiro);
        $observedSenderId = isset($observedRow['senderId']) && (int) $observedRow['senderId'] > 0 ? (int) $observedRow['senderId'] : null;
        $lookupStatus = is_string($observedRow['payeeLookupStatus'] ?? null) ? trim((string) $observedRow['payeeLookupStatus']) : '';
        if ($observedSenderId !== null) {
            $senderIds[$observedSenderId] = true;
        } else {
            $observationKey = 'bankgiro:' . (is_string($observedRow['number'] ?? null)
                ? $observedRow['number']
                : (\Docflow\Senders\IdentifierNormalizer::normalizeBankgiro($bankgiro) ?? $bankgiro));
            if (isset($observationKeys[$observationKey])) {
                continue;
            }
            $observationKeys[$observationKey] = true;
            $observations[] = [
                'key' => $observationKey,
                'type' => 'bankgiro',
                'itemLabel' => 'BANKGIRO',
                'itemValue' => $bankgiro,
                'status' => $lookupStatus === 'not_found' ? 'not_found' : 'pending',
            ];
        }
    }

    foreach ($plusgiroValues as $plusgiro) {
        $observedRow = observed_sender_payment_summary_row('plusgiro', $plusgiro);
        $observedSenderId = isset($observedRow['senderId']) && (int) $observedRow['senderId'] > 0 ? (int) $observedRow['senderId'] : null;
        $lookupStatus = is_string($observedRow['payeeLookupStatus'] ?? null) ? trim((string) $observedRow['payeeLookupStatus']) : '';
        if ($observedSenderId !== null) {
            $senderIds[$observedSenderId] = true;
        } else {
            $observationKey = 'plusgiro:' . (is_string($observedRow['number'] ?? null)
                ? $observedRow['number']
                : (\Docflow\Senders\IdentifierNormalizer::normalizePlusgiro($plusgiro) ?? $plusgiro));
            if (isset($observationKeys[$observationKey])) {
                continue;
            }
            $observationKeys[$observationKey] = true;
            $observations[] = [
                'key' => $observationKey,
                'type' => 'plusgiro',
                'itemLabel' => 'PLUSGIRO',
                'itemValue' => $plusgiro,
                'status' => $lookupStatus === 'not_found' ? 'not_found' : 'pending',
            ];
        }
    }

    foreach ([$matchedSenderId, $selectedSenderId] as $senderId) {
        if ($senderId !== null && $senderId > 0) {
            $senderIds[$senderId] = true;
        }
    }

    $senders = [];
    foreach (array_keys($senderIds) as $senderId) {
        $senderRow = $senderRowsById[$senderId] ?? null;
        if (!is_array($senderRow)) {
            continue;
        }

        $name = is_string($senderRow['name'] ?? null) ? trim((string) $senderRow['name']) : '';
        if ($name === '') {
            continue;
        }

        $organizationEntry = null;
        $organizationRows = is_array($senderRow['organizationNumbers'] ?? null) ? $senderRow['organizationNumbers'] : [];
        $firstOrganizationRow = $organizationRows[0] ?? null;
        if (is_array($firstOrganizationRow)) {
            $rawNumber = is_string($firstOrganizationRow['organizationNumber'] ?? null) ? trim((string) $firstOrganizationRow['organizationNumber']) : '';
            $normalizedNumber = \Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber($rawNumber);
            if ($normalizedNumber !== null) {
                $organizationEntry = [
                    'label' => 'Org.nr',
                    'value' => $normalizedNumber,
                    'found' => isset($normalizedOrganizationNumbers[$normalizedNumber]),
                ];
            }
        }

        $nameComponents = [];
        $nameComponentMap = [];
        foreach ($organizationRows as $organizationRow) {
            if (!is_array($organizationRow)) {
                continue;
            }
            $organizationName = is_string($organizationRow['organizationName'] ?? null) ? trim((string) $organizationRow['organizationName']) : '';
            if ($organizationName === '') {
                continue;
            }
            $normalizedOrganizationName = normalized_sender_summary_search_text($organizationName);
            if ($normalizedOrganizationName === '' || isset($nameComponentMap[$normalizedOrganizationName])) {
                continue;
            }
            $nameComponentMap[$normalizedOrganizationName] = true;
            $nameComponents[] = [
                'label' => 'Namn',
                'value' => $organizationName,
                'found' => sender_summary_text_contains($documentText, $organizationName),
            ];
        }

        $paymentEntries = [];
        $paymentRows = is_array($senderRow['paymentNumbers'] ?? null) ? $senderRow['paymentNumbers'] : [];
        foreach ($paymentRows as $paymentRow) {
            if (!is_array($paymentRow)) {
                continue;
            }
            $paymentType = is_string($paymentRow['type'] ?? null) ? trim(strtolower((string) $paymentRow['type'])) : 'bankgiro';
            $rawNumber = is_string($paymentRow['number'] ?? null) ? trim((string) $paymentRow['number']) : '';
            $normalizedNumber = $paymentType === 'plusgiro'
                ? \Docflow\Senders\IdentifierNormalizer::normalizePlusgiro($rawNumber)
                : \Docflow\Senders\IdentifierNormalizer::normalizeBankgiro($rawNumber);
            if ($normalizedNumber === null) {
                continue;
            }

            $paymentEntries[] = [
                'label' => $paymentType === 'plusgiro' ? 'PG' : 'BG',
                'value' => $rawNumber,
                'found' => $paymentType === 'plusgiro'
                    ? isset($normalizedPlusgiroValues[$normalizedNumber])
                    : isset($normalizedBankgiroValues[$normalizedNumber]),
            ];

            $payeeName = is_string($paymentRow['payeeName'] ?? null) ? trim((string) $paymentRow['payeeName']) : '';
            if ($payeeName !== '') {
                $normalizedPayeeName = normalized_sender_summary_search_text($payeeName);
                if ($normalizedPayeeName !== '' && !isset($nameComponentMap[$normalizedPayeeName])) {
                    $nameComponentMap[$normalizedPayeeName] = true;
                    $nameComponents[] = [
                        'label' => 'Namn',
                        'value' => $payeeName,
                        'found' => sender_summary_text_contains($documentText, $payeeName),
                    ];
                }
            }
        }

        $matchKinds = [];
        foreach ($nameComponents as $nameComponent) {
            if (($nameComponent['found'] ?? false) === true) {
                $matchKinds[] = 'name';
                break;
            }
        }
        if (is_array($organizationEntry) && ($organizationEntry['found'] ?? false) === true) {
            $matchKinds[] = 'organization_number';
        }
        foreach ($paymentEntries as $paymentEntry) {
            if (($paymentEntry['found'] ?? false) === true) {
                $matchKinds[] = 'payment_number';
                break;
            }
        }
        $matchKinds = array_values(array_unique($matchKinds));
        $strongMatchCount = 0;
        if (is_array($organizationEntry) && ($organizationEntry['found'] ?? false) === true) {
            $strongMatchCount++;
        }
        foreach ($paymentEntries as $paymentEntry) {
            if (($paymentEntry['found'] ?? false) === true) {
                $strongMatchCount++;
            }
        }
        $headerFound = $strongMatchCount > 0;
        $matchedBy = count($matchKinds) > 1
            ? 'kombination'
            : ($matchKinds[0] ?? null);

        $senders[] = [
            'key' => 'sender:' . $senderId,
            'senderId' => $senderId,
            'name' => $name,
            'headerFound' => $headerFound,
            'matchedBy' => $matchedBy,
            'matchedAlias' => null,
            'nameComponents' => $nameComponents,
            'organizationNumber' => $organizationEntry,
            'paymentNumbers' => $paymentEntries,
        ];
    }

    usort(
        $senders,
        static function (array $left, array $right): int {
            $countStrongMatches = static function (array $row): int {
                $count = 0;
                if (is_array($row['organizationNumber'] ?? null) && (($row['organizationNumber']['found'] ?? false) === true)) {
                    $count++;
                }
                foreach (is_array($row['paymentNumbers'] ?? null) ? $row['paymentNumbers'] : [] as $paymentEntry) {
                    if (is_array($paymentEntry) && (($paymentEntry['found'] ?? false) === true)) {
                        $count++;
                    }
                }
                return $count;
            };

            $countAllFoundMarks = static function (array $row) use ($countStrongMatches): int {
                $count = $countStrongMatches($row);
                foreach (is_array($row['nameComponents'] ?? null) ? $row['nameComponents'] : [] as $nameComponent) {
                    if (is_array($nameComponent) && (($nameComponent['found'] ?? false) === true)) {
                        $count++;
                    }
                }
                return $count;
            };

            $leftStrongCount = $countStrongMatches($left);
            $rightStrongCount = $countStrongMatches($right);
            if ($leftStrongCount !== $rightStrongCount) {
                return $rightStrongCount <=> $leftStrongCount;
            }

            $leftFoundCount = $countAllFoundMarks($left);
            $rightFoundCount = $countAllFoundMarks($right);
            if ($leftFoundCount !== $rightFoundCount) {
                return $rightFoundCount <=> $leftFoundCount;
            }

            return strcmp(
                strtolower((string) ($left['name'] ?? '')),
                strtolower((string) ($right['name'] ?? ''))
            );
        }
    );

    return $observations !== [] || $senders !== []
        ? [
            'unknownObservations' => $observations,
            'senders' => $senders,
        ]
        : null;
}

function observed_sender_organization_summary_row(string $organizationNumber): array
{
    static $cache = [];

    $normalized = \Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber($organizationNumber);
    if ($normalized === null) {
        return [];
    }
    if (array_key_exists($normalized, $cache)) {
        return $cache[$normalized];
    }

    $repository = sender_repository_instance();
    if ($repository === null) {
        $cache[$normalized] = [];
        return [];
    }

    try {
        $row = $repository->findObservedOrganizationNumberRow($normalized);
    } catch (Throwable $e) {
        $row = null;
    }

    $cache[$normalized] = is_array($row) ? [
        'organizationNumber' => is_string($row['organization_number'] ?? null) ? trim((string) $row['organization_number']) : $normalized,
        'organizationName' => is_string($row['organization_name'] ?? null) ? trim((string) $row['organization_name']) : '',
        'senderId' => isset($row['sender_id']) ? (int) $row['sender_id'] : null,
        'source' => is_string($row['source'] ?? null) ? trim((string) $row['source']) : '',
    ] : [];

    return $cache[$normalized];
}

function observed_sender_payment_summary_row(string $type, string $number): array
{
    static $cache = [];

    $normalizedType = trim(strtolower($type)) === 'plusgiro' ? 'plusgiro' : 'bankgiro';
    $normalizedNumber = $normalizedType === 'plusgiro'
        ? \Docflow\Senders\IdentifierNormalizer::normalizePlusgiro($number)
        : \Docflow\Senders\IdentifierNormalizer::normalizeBankgiro($number);
    if ($normalizedNumber === null) {
        return [];
    }

    $cacheKey = $normalizedType . ':' . $normalizedNumber;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $repository = sender_repository_instance();
    if ($repository === null) {
        $cache[$cacheKey] = [];
        return [];
    }

    try {
        $row = $repository->findObservedPaymentNumberRow($normalizedType, $normalizedNumber);
    } catch (Throwable $e) {
        $row = null;
    }

    $cache[$cacheKey] = is_array($row) ? [
        'type' => is_string($row['type'] ?? null) ? trim((string) $row['type']) : $normalizedType,
        'number' => is_string($row['number'] ?? null) ? trim((string) $row['number']) : $normalizedNumber,
        'payeeName' => is_string($row['payee_name'] ?? null) ? trim((string) $row['payee_name']) : '',
        'payeeLookupStatus' => is_string($row['payee_lookup_status'] ?? null) ? trim((string) $row['payee_lookup_status']) : '',
        'senderId' => isset($row['sender_id']) ? (int) $row['sender_id'] : null,
    ] : [];

    return $cache[$cacheKey];
}

function extraction_field_result_string(array $results, string $key, string $property = 'value'): ?string
{
    $result = $results[$key] ?? null;
    if (!is_array($result)) {
        return null;
    }

    $value = $result[$property] ?? null;
    if ($value === null) {
        return null;
    }
    if (is_string($value)) {
        $trimmed = trim($value);
        return $trimmed !== '' ? $trimmed : null;
    }
    if (is_numeric($value)) {
        return trim((string) $value);
    }

    return null;
}

function extraction_field_result_matches(array $results, string $key): array
{
    $result = $results[$key] ?? null;
    if (!is_array($result)) {
        return [];
    }

    $matches = is_array($result['matches'] ?? null) ? $result['matches'] : [];
    if ($matches !== []) {
        return array_values(array_filter($matches, static fn ($match): bool => is_array($match)));
    }

    $value = $result['value'] ?? null;
    if ($value === null) {
        return [];
    }

    return [[
        'value' => $value,
        'raw' => is_string($result['raw'] ?? null) ? (string) $result['raw'] : null,
        'matchText' => is_string($result['matchText'] ?? null) ? (string) $result['matchText'] : (is_string($result['raw'] ?? null) ? (string) $result['raw'] : null),
        'source' => is_string($result['source'] ?? null) ? (string) $result['source'] : 'none',
        'confidence' => isset($result['confidence']) ? clamp_confidence((float) $result['confidence']) : 0.0,
        'lineIndex' => is_int($result['lineIndex'] ?? null) ? (int) $result['lineIndex'] : null,
        'start' => null,
        'matchType' => is_string($result['matchType'] ?? null) ? trim((string) $result['matchType']) : null,
        'searchTerm' => is_string($result['searchTerm'] ?? null) ? trim((string) $result['searchTerm']) : null,
    ]];
}

function observe_extracted_sender_identifiers(array $fieldResults): void
{
    $repository = sender_repository_instance();
    if ($repository === null) {
        return;
    }

    $seenOrganizationNumbers = [];
    foreach (extraction_field_result_matches($fieldResults, 'organisationsnummer') as $match) {
        $organizationNumber = is_string($match['value'] ?? null) ? trim((string) $match['value']) : '';
        if ($organizationNumber === '' || isset($seenOrganizationNumbers[$organizationNumber])) {
            continue;
        }
        $seenOrganizationNumbers[$organizationNumber] = true;

        try {
            $repository->observeOrganizationNumber($organizationNumber, null, 'document_auto');
        } catch (Throwable $e) {
            // Best effort only. Identifier observation should not fail the document analysis pipeline.
        }
    }

    $observePaymentMatches = static function (string $type) use ($repository, $fieldResults): void {
        $seen = [];
        foreach (extraction_field_result_matches($fieldResults, $type) as $match) {
            $number = is_string($match['value'] ?? null) ? trim((string) $match['value']) : '';
            if ($number === '' || isset($seen[$number])) {
                continue;
            }
            $seen[$number] = true;

            try {
                $repository->observePaymentNumber(
                    $type,
                    $number,
                    is_string($match['raw'] ?? null) ? (string) $match['raw'] : null,
                    'document_auto',
                    1.0
                );
            } catch (Throwable $e) {
                // Best effort only. Identifier observation should not fail the document analysis pipeline.
            }
        }
    };

    $observePaymentMatches('bankgiro');
    $observePaymentMatches('plusgiro');
}

function sync_job_sender_document_links(array $config, string $jobId): array
{
    if (!is_valid_job_id($jobId)) {
        return [
            'merged' => false,
            'merges' => [],
            'components' => [],
        ];
    }

    $jobDir = rtrim((string) $config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
    $extracted = load_json_file($jobDir . '/extracted.json');
    if (!is_array($extracted)) {
        return [
            'merged' => false,
            'merges' => [],
            'components' => [],
        ];
    }

    $organizationNumbers = extracted_field_string_values($extracted, 'organisationsnummer');
    $bankgiroValues = extracted_field_string_values($extracted, 'bankgiro');
    $plusgiroValues = extracted_field_string_values($extracted, 'plusgiro');

    $repository = sender_repository_instance();
    if ($repository === null) {
        return [
            'merged' => false,
            'merges' => [],
            'components' => [],
        ];
    }

    try {
        return $repository->resolveDocumentSenderLinks($organizationNumbers, $bankgiroValues, $plusgiroValues);
    } catch (Throwable $e) {
        return [
            'merged' => false,
            'merges' => [],
            'components' => [],
            'error' => $e->getMessage(),
        ];
    }
}

function sender_analysis_matches_from_summary(?array $senderSummary): array
{
    if (!is_array($senderSummary) || !is_array($senderSummary['senders'] ?? null)) {
        return [];
    }

    $items = [];
    foreach ($senderSummary['senders'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $senderId = isset($row['senderId']) ? (int) $row['senderId'] : 0;
        if ($senderId < 1) {
            continue;
        }
        $items[] = [
            'senderId' => $senderId,
            'senderName' => is_string($row['name'] ?? null) ? trim((string) $row['name']) : '',
            'matchedBy' => is_string($row['matchedBy'] ?? null) ? trim((string) $row['matchedBy']) : null,
            'matchedAlias' => is_string($row['matchedAlias'] ?? null) ? trim((string) $row['matchedAlias']) : null,
        ];
    }

    return $items;
}

function single_preselected_sender_from_summary(?array $senderSummary): array
{
    $matches = sender_analysis_matches_from_summary($senderSummary);
    if ($matches === []) {
        return [
            'matchedSenderId' => null,
            'preselectedSender' => null,
            'senderMatches' => $matches,
        ];
    }

    $match = $matches[0];
    $senderId = isset($match['senderId']) ? (int) $match['senderId'] : 0;
    if ($senderId < 1) {
        return [
            'matchedSenderId' => null,
            'preselectedSender' => null,
            'senderMatches' => $matches,
        ];
    }

    return [
        'matchedSenderId' => $senderId,
        'preselectedSender' => [
            'id' => $senderId,
            'name' => is_string($match['senderName'] ?? null) ? trim((string) $match['senderName']) : '',
            'matchedBy' => is_string($match['matchedBy'] ?? null) ? trim((string) $match['matchedBy']) : null,
            'matchedAlias' => is_string($match['matchedAlias'] ?? null) ? trim((string) $match['matchedAlias']) : null,
        ],
        'senderMatches' => $matches,
    ];
}

function build_sender_payee_lookup_queue_state_payload(int $limit = 1): array
{
    sync_named_sender_identifier_links();

    $repository = sender_repository_instance();
    if ($repository === null) {
        return [
            'remainingCount' => 0,
            'item' => null,
        ];
    }

    try {
        $items = $repository->listPaymentNumbersMissingPayeeName($limit);
        $remainingCount = $repository->countPaymentNumbersMissingPayeeName();
    } catch (Throwable $e) {
        return [
            'remainingCount' => 0,
            'item' => null,
        ];
    }

    $item = $items[0] ?? null;
    if (!is_array($item)) {
        $item = null;
    }

    return [
        'remainingCount' => max(0, (int) $remainingCount),
        'item' => $item,
    ];
}

function build_sender_organization_lookup_queue_state_payload(int $limit = 1): array
{
    sync_named_sender_identifier_links();

    $repository = sender_repository_instance();
    if ($repository === null) {
        return [
            'remainingCount' => 0,
            'item' => null,
        ];
    }

    try {
        $items = $repository->listOrganizationNumbersMissingName($limit);
        $remainingCount = $repository->countOrganizationNumbersMissingName();
    } catch (Throwable $e) {
        return [
            'remainingCount' => 0,
            'item' => null,
        ];
    }

    $item = $items[0] ?? null;
    if (!is_array($item)) {
        $item = null;
    }

    return [
        'remainingCount' => max(0, (int) $remainingCount),
        'item' => $item,
    ];
}

function job_extracted_contains_sender_identifier(array $extracted, string $identifierType, string $normalizedValue): bool
{
    if ($normalizedValue === '') {
        return false;
    }

    if ($identifierType === 'organization_number') {
        foreach (extracted_field_string_values($extracted, 'organisationsnummer') as $value) {
            if (\Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber($value) === $normalizedValue) {
                return true;
            }
        }
        return false;
    }

    if ($identifierType === 'bankgiro') {
        foreach (extracted_field_string_values($extracted, 'bankgiro') as $value) {
            if (\Docflow\Senders\IdentifierNormalizer::normalizeBankgiro($value) === $normalizedValue) {
                return true;
            }
        }
        return false;
    }

    if ($identifierType === 'plusgiro') {
        foreach (extracted_field_string_values($extracted, 'plusgiro') as $value) {
            if (\Docflow\Senders\IdentifierNormalizer::normalizePlusgiro($value) === $normalizedValue) {
                return true;
            }
        }
        return false;
    }

    return false;
}

function ready_job_ids_affected_by_sender_identifier(array $config, string $identifierType, string $normalizedValue): array
{
    $jobsDir = $config['jobsDirectory'] ?? '';
    if (!is_string($jobsDir) || trim($jobsDir) === '' || !is_dir($jobsDir)) {
        return [];
    }

    $affectedJobIds = [];
    $entries = scandir($jobsDir);
    if (!is_array($entries)) {
        return [];
    }

    foreach ($entries as $entry) {
        if (!is_valid_job_id($entry)) {
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
        if (($job['status'] ?? '') !== 'ready' || ($job['archived'] ?? false) === true) {
            continue;
        }

        $extracted = load_json_file($jobDir . '/extracted.json');
        if (!is_array($extracted)) {
            continue;
        }

        if (job_extracted_contains_sender_identifier($extracted, $identifierType, $normalizedValue)) {
            $affectedJobIds[] = $entry;
        }
    }

    sort($affectedJobIds, SORT_STRING);
    return $affectedJobIds;
}

function set_job_analysis_outdated_flag(array $config, string $jobId, bool $isOutdated): bool
{
    if (!is_valid_job_id($jobId)) {
        return false;
    }

    $jobPath = rtrim((string) $config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId . DIRECTORY_SEPARATOR . 'job.json';
    $job = load_json_file($jobPath);
    if (!is_array($job)) {
        return false;
    }

    $current = ($job['analysisOutdated'] ?? false) === true;
    if ($current === $isOutdated) {
        return false;
    }

    if ($isOutdated) {
        $job['analysisOutdated'] = true;
    } else {
        unset($job['analysisOutdated']);
    }
    $job['updatedAt'] = now_iso();
    write_json_file($jobPath, $job);
    queue_job_upsert_event($config, $jobId);
    return true;
}

function maybe_queue_sender_auto_reprocess(array $config, string $jobId): bool
{
    if (!is_valid_job_id($jobId)) {
        return false;
    }

    $jobPath = rtrim((string) $config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId . DIRECTORY_SEPARATOR . 'job.json';
    $job = load_json_file($jobPath);
    if (!is_array($job)) {
        return false;
    }
    if (($job['status'] ?? '') !== 'ready' || ($job['archived'] ?? false) === true) {
        return false;
    }
    if (($job['analysisAutoReprocessQueued'] ?? false) === true) {
        return false;
    }

    reprocess_job_by_id($config, $jobId, 'post-ocr', false, true);
    return true;
}

function handle_resolved_sender_identifier_followups(
    array $config,
    string $identifierType,
    string $normalizedValue,
    ?string $currentSelectedJobId = null
): array {
    $affectedJobIds = ready_job_ids_affected_by_sender_identifier($config, $identifierType, $normalizedValue);
    $markedOutdatedJobIds = [];
    $autoReprocessedJobIds = [];
    $selectedJobId = is_string($currentSelectedJobId) && is_valid_job_id($currentSelectedJobId)
        ? $currentSelectedJobId
        : null;

    foreach ($affectedJobIds as $affectedJobId) {
        sync_job_sender_document_links($config, $affectedJobId);

        if ($selectedJobId !== null && $affectedJobId === $selectedJobId) {
            if (set_job_analysis_outdated_flag($config, $affectedJobId, true)) {
                $markedOutdatedJobIds[] = $affectedJobId;
            }
            continue;
        }

        if (maybe_queue_sender_auto_reprocess($config, $affectedJobId)) {
            $autoReprocessedJobIds[] = $affectedJobId;
        }
    }

    return [
        'affectedJobIds' => $affectedJobIds,
        'markedOutdatedJobIds' => $markedOutdatedJobIds,
        'autoReprocessedJobIds' => $autoReprocessedJobIds,
    ];
}

function build_job_state_entry(
    array $config,
    string $jobDir,
    array $job
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
    $analysis = job_analysis_payload($job);
    if (is_array($analysis) && array_key_exists('extractionFieldMeta', $analysis)) {
        unset($analysis['extractionFieldMeta']);
    }
    if (is_array($analysis) && array_key_exists('senderLookup', $analysis)) {
        unset($analysis['senderLookup']);
    }
    $selectedClientDirName = is_string($job['selectedClientDirName'] ?? null)
        ? trim((string) $job['selectedClientDirName'])
        : null;
    $selectedSenderId = resolve_active_sender_id(isset($job['selectedSenderId']) ? (int) $job['selectedSenderId'] : 0);
    if ($selectedSenderId !== null && $selectedSenderId < 1) {
        $selectedSenderId = null;
    }
    $selectedFolderId = is_string($job['selectedFolderId'] ?? null)
        ? trim((string) $job['selectedFolderId'])
        : null;
    $selectedLabelIds = array_key_exists('selectedLabelIds', $job)
        ? normalize_stored_job_label_ids($job['selectedLabelIds'])
        : null;
    $filename = is_string($job['filename'] ?? null)
        ? trim((string) $job['filename'])
        : null;
    $isArchived = ($job['archived'] ?? false) === true;
    $needsRuleReview = ($job['needsRuleReview'] ?? false) === true;
    $archivedAt = is_string($job['archivedAt'] ?? null) ? trim((string) $job['archivedAt']) : null;
    $archivedPdfPath = is_string($job['archivedPdfPath'] ?? null) ? trim((string) $job['archivedPdfPath']) : null;
    $extracted = load_json_file($jobDir . '/extracted.json');
    $senderSummary = build_job_sender_summary($extracted, $jobDir, null, $selectedSenderId);
    $analysisOutdated = ($job['analysisOutdated'] ?? false) === true;
    $analysisAutoReprocessQueued = ($job['analysisAutoReprocessQueued'] ?? false) === true;

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
                'reprocessMode' => is_string($job['reprocessMode'] ?? null) ? trim((string) $job['reprocessMode']) : null,
                'forceOcr' => ($job['forceOcr'] ?? false) === true,
                'ocr' => is_array($job['ocr'] ?? null) ? $job['ocr'] : null,
                'analysis' => $analysis,
                'selectedClientDirName' => $selectedClientDirName,
                'selectedSenderId' => $selectedSenderId,
                'selectedFolderId' => $selectedFolderId,
                'selectedLabelIds' => $selectedLabelIds,
                'filename' => $filename,
                'senderSummary' => $senderSummary,
                'analysisOutdated' => $analysisOutdated,
                'analysisAutoReprocessQueued' => $analysisAutoReprocessQueued,
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
                'reprocessMode' => is_string($job['reprocessMode'] ?? null) ? trim((string) $job['reprocessMode']) : null,
                'forceOcr' => ($job['forceOcr'] ?? false) === true,
                'ocr' => is_array($job['ocr'] ?? null) ? $job['ocr'] : null,
                'analysis' => $analysis,
                'selectedClientDirName' => $selectedClientDirName,
                'selectedSenderId' => $selectedSenderId,
                'selectedFolderId' => $selectedFolderId,
                'selectedLabelIds' => $selectedLabelIds,
                'filename' => $filename,
                'senderSummary' => $senderSummary,
                'analysisOutdated' => $analysisOutdated,
                'analysisAutoReprocessQueued' => $analysisAutoReprocessQueued,
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
    }

    $senderSummary = build_job_sender_summary($extracted, $jobDir, $matchedSenderId, $selectedSenderId);

    $readyPayload = [
        'id' => $id,
        'originalFilename' => $originalFilename,
        'status' => 'ready',
        'createdAt' => $createdAt,
        'updatedAt' => $updatedAt,
        'hasReviewPdf' => $hasReviewPdf,
        'hasSourcePdf' => $hasSourcePdf,
        'reprocessMode' => is_string($job['reprocessMode'] ?? null) ? trim((string) $job['reprocessMode']) : null,
        'forceOcr' => ($job['forceOcr'] ?? false) === true,
        'ocr' => is_array($job['ocr'] ?? null) ? $job['ocr'] : null,
        'analysis' => $analysis,
        'matchedClientDirName' => $matchedClientDirName,
        'matchedSenderId' => $matchedSenderId,
        'selectedClientDirName' => $selectedClientDirName,
        'selectedSenderId' => $selectedSenderId,
        'selectedFolderId' => $selectedFolderId,
        'selectedLabelIds' => $selectedLabelIds,
        'filename' => $filename,
        'senderSummary' => $senderSummary,
        'analysisOutdated' => $analysisOutdated,
        'analysisAutoReprocessQueued' => $analysisAutoReprocessQueued,
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
    return build_job_state_entry($config, $jobDir, $job);
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
        $stateEntry = build_job_state_entry($config, $jobDir, $job);
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

function reprocess_job_by_id(array $config, string $jobId, string $mode = 'post-ocr', bool $forceOcr = false, bool $autoQueued = false): array
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
        $jobDir . '/ocrmypdf_sidecar.txt',
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

    sync_job_sender_snapshot_ids($jobId, null, null);

    unset($job['error'], $job['analysis']);
    unset(
        $job['selectedClientDirName'],
        $job['selectedSenderId'],
        $job['selectedFolderId'],
        $job['selectedLabelIds'],
        $job['filename'],
        $job['approvedArchiving'],
        $job['archiveSnapshot'],
        $job['needsRuleReview'],
        $job['ruleReviewTargetRulesVersion'],
        $job['lastResolvedArchivingRulesVersion'],
        $job['ruleReviewProposedValue'],
        $job['ruleReviewDiff']
    );
    unset($job['analysisOutdated']);
    if ($autoQueued) {
        $job['analysisAutoReprocessQueued'] = true;
    } else {
        unset($job['analysisAutoReprocessQueued']);
    }
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
