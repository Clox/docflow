<?php
declare(strict_types=1);

const DOCFLOW_OCR_VERSION = 1;
const DOCFLOW_OCR_METADATA_KEY = 'docflow-ocr-version';
const DOCFLOW_CHROME_EXTENSION_VERSION = '1.0.2';
const DOCFLOW_CHROME_EXTENSION_ID = 'bgpmmblhdghhdcoeoepbelbonhdhcdkg';
const DOCFLOW_CHROME_EXTENSION_MANIFEST_KEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqU9P0SMG3OD3yGoztzCpvGVjdN/kKLgsy8fpCKxlYkIUXzd8eRbFI4kOIMY8PefngWIrVG2dnioOi6naXKtxDFaLvyUkHbxqrqxVK882I4dnKNrygS1HUnWOFLZExwr8+3cJGEvv+gue3Fq6LvTKsNlJQktdmrTqGbD0SLrNopOUPmlRL9qnfHzA4MyqciFiLGZVte7327HRzzQM7LJQ2pG8N8qzt75vift/XEPh4Rvre7nwHmmfQE1UulNaeazvNbtEsmhJwG3wQcsHDKlhUijMiRdrucKLpfnzI/4+ngADIjjibKrBt5bFqJIrBM3LRkuuCAeAWrDWNVUK95WzEQIDAQAB';
const DEFAULT_OCR_DEBUG_EXPORT_DIRECTORY = 'data/debug_exports/';

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
    $ocrDebugExportDirectory = $config['ocrDebugExportDirectory'] ?? DEFAULT_OCR_DEBUG_EXPORT_DIRECTORY;
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
    if (!is_string($ocrDebugExportDirectory)) {
        $ocrDebugExportDirectory = DEFAULT_OCR_DEBUG_EXPORT_DIRECTORY;
    }
    $ocrDebugExportDirectory = trim($ocrDebugExportDirectory);
    if ($ocrDebugExportDirectory === '') {
        $ocrDebugExportDirectory = DEFAULT_OCR_DEBUG_EXPORT_DIRECTORY;
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
        'ocrDebugExportDirectory' => $ocrDebugExportDirectory,
        'ocrSkipExistingText' => $ocrSkipExistingText,
        'ocrOptimizeLevel' => $ocrOptimizeLevel,
        'stateUpdateTransport' => $stateUpdateTransport,
        'ocrTextExtractionMethod' => $ocrTextExtractionMethod,
        'ocrPdfTextSubstitutions' => $ocrPdfTextSubstitutions,
        'chromeExtensionSuppressMissingNotice' => $chromeExtensionSuppressMissingNotice,
    ];
}

function sanitize_state_update_transport_value(mixed $value, string $fallback = 'polling'): string
{
    if (!is_string($value)) {
        return $fallback === 'sse' ? 'sse' : 'polling';
    }

    $normalized = trim(strtolower($value));
    if ($normalized === 'sse') {
        return 'sse';
    }

    return 'polling';
}

function sanitize_ocr_text_extraction_method_value(mixed $value, string $fallback = 'layout'): string
{
    if (!is_string($value)) {
        return $fallback === 'bbox' ? 'bbox' : 'layout';
    }

    $normalized = trim(strtolower($value));
    if ($normalized === 'bbox') {
        return 'bbox';
    }
    if ($normalized === 'layout') {
        return 'layout';
    }

    return $fallback === 'bbox' ? 'bbox' : 'layout';
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
        if ($from === '') {
            continue;
        }

        $normalized[] = [
            'from' => $from,
            'to' => $to,
            'isRegex' => ($row['isRegex'] ?? false) === true,
            'enabled' => ($row['enabled'] ?? true) !== false,
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
        $preferredFirstNameIndex = normalize_client_preferred_first_name_index(
            $row['preferred_first_name_index'] ?? null,
            $firstName
        );
        $preferredFirstName = client_preferred_first_name_from_parts(
            split_client_first_names($firstName),
            $preferredFirstNameIndex
        );

        if ($dirName === '' || $pin === '') {
            continue;
        }

        $clients[] = [
            'name' => $name,
            'dirName' => $dirName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'personalIdentityNumber' => $pin,
            'preferredFirstNameIndex' => $preferredFirstNameIndex,
            'preferredFirstName' => $preferredFirstName,
        ];
    }

    return $clients;
}

function load_client_export_rows(): array
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

        $clients[] = [
            'firstName' => is_string($row['first_name'] ?? null) ? (string) $row['first_name'] : '',
            'lastName' => is_string($row['last_name'] ?? null) ? (string) $row['last_name'] : '',
            'folderName' => is_string($row['folder_name'] ?? null) ? (string) $row['folder_name'] : '',
            'personalIdentityNumber' => is_string($row['personal_identity_number'] ?? null)
                ? (string) $row['personal_identity_number']
                : '',
            'preferredFirstNameIndex' => isset($row['preferred_first_name_index']) && is_numeric($row['preferred_first_name_index'])
                ? (int) $row['preferred_first_name_index']
                : null,
        ];
    }

    return $clients;
}

function load_sender_export_rows(): array
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

        $senderId = isset($row['id']) ? (int) $row['id'] : 0;
        $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
        $displayName = is_string($row['displayName'] ?? null) ? trim((string) $row['displayName']) : '';
        if ($senderId < 1) {
            continue;
        }

        $organizationNumbers = [];
        foreach (is_array($row['organizationNumbers'] ?? null) ? $row['organizationNumbers'] : [] as $organization) {
            if (!is_array($organization)) {
                continue;
            }
            $organizationNumber = is_string($organization['organizationNumber'] ?? null)
                ? trim((string) $organization['organizationNumber'])
                : '';
            if ($organizationNumber === '') {
                continue;
            }
            $organizationNumbers[] = [
                'id' => isset($organization['id']) && is_numeric($organization['id']) ? (int) $organization['id'] : null,
                'organizationNumber' => $organizationNumber,
                'organizationName' => is_string($organization['organizationName'] ?? null)
                    ? trim((string) $organization['organizationName'])
                    : '',
            ];
        }

        $paymentNumbers = [];
        foreach (is_array($row['paymentNumbers'] ?? null) ? $row['paymentNumbers'] : [] as $payment) {
            if (!is_array($payment)) {
                continue;
            }
            $type = is_string($payment['type'] ?? null) ? trim(strtolower((string) $payment['type'])) : '';
            $number = is_string($payment['number'] ?? null) ? trim((string) $payment['number']) : '';
            if ($number === '' || ($type !== 'bankgiro' && $type !== 'plusgiro')) {
                continue;
            }
            $paymentNumbers[] = [
                'id' => isset($payment['id']) && is_numeric($payment['id']) ? (int) $payment['id'] : null,
                'type' => $type,
                'number' => $number,
            ];
        }

        $matchNames = [];
        foreach (is_array($row['matchNames'] ?? null) ? $row['matchNames'] : [] as $matchName) {
            if (!is_array($matchName)) {
                continue;
            }
            $matchNameValue = is_string($matchName['name'] ?? null)
                ? trim((string) $matchName['name'])
                : '';
            if ($matchNameValue === '') {
                continue;
            }
            $matchNames[] = [
                'id' => isset($matchName['id']) && is_numeric($matchName['id']) ? (int) $matchName['id'] : null,
                'name' => $matchNameValue,
            ];
        }

        $senders[] = [
            'id' => $senderId,
            'name' => $name,
            'displayName' => $displayName,
            'domain' => is_string($row['domain'] ?? null) ? trim((string) $row['domain']) : '',
            'kind' => is_string($row['kind'] ?? null) ? trim((string) $row['kind']) : '',
            'notes' => is_string($row['notes'] ?? null) ? (string) $row['notes'] : '',
            'matchNames' => $matchNames,
            'organizationNumbers' => $organizationNumbers,
            'paymentNumbers' => $paymentNumbers,
        ];
    }

    return $senders;
}

function configuration_export_filename(?string $exportedAt = null): string
{
    return configuration_backup_filename($exportedAt, 'docflow-config');
}

function configuration_pre_import_filename(?string $backedUpAt = null): string
{
    return configuration_backup_filename($backedUpAt, 'pre-import');
}

function configuration_backup_filename(?string $backedUpAt = null, string $prefix = 'docflow-config'): string
{
    $timestamp = $backedUpAt !== null && trim($backedUpAt) !== ''
        ? strtotime($backedUpAt)
        : false;
    $formatted = $timestamp !== false ? gmdate('Ymd_His', $timestamp) : gmdate('Ymd_His');
    $normalizedPrefix = trim(preg_replace('/[^a-z0-9-]+/i', '-', $prefix) ?? '', '-');
    if ($normalizedPrefix === '') {
        $normalizedPrefix = 'docflow-config';
    }
    return $normalizedPrefix . '-' . $formatted . '.json';
}

function configuration_backup_directory(): string
{
    return PROJECT_ROOT . '/backups/config';
}

function ensure_configuration_backup_directory(): void
{
    ensure_directory(configuration_backup_directory());
}

function is_configuration_backup_filename(string $filename): bool
{
    return preg_match('/^(?:docflow-config|manual|imported|pre-restore|pre-import|snapshot)-\d{8}_\d{6}\.json$/', $filename) === 1;
}

function normalize_configuration_backup_filename(string $filename): ?string
{
    $basename = basename(trim($filename));
    return is_configuration_backup_filename($basename) ? $basename : null;
}

function configuration_backup_path(string $filename): ?string
{
    $normalized = normalize_configuration_backup_filename($filename);
    if ($normalized === null) {
        return null;
    }

    return configuration_backup_directory() . DIRECTORY_SEPARATOR . $normalized;
}

function configuration_backup_summary(array $payload): array
{
    return [
        'clients' => is_array($payload['clients'] ?? null) ? count($payload['clients']) : 0,
        'senders' => is_array($payload['senders'] ?? null) ? count($payload['senders']) : 0,
        'labels' => is_array($payload['labels'] ?? null) ? count($payload['labels']) : 0,
        'dataFields' => is_array($payload['dataFields']['fields'] ?? null) ? count($payload['dataFields']['fields']) : 0,
        'zones' => is_array($payload['dataFields']['zones'] ?? null) ? count($payload['dataFields']['zones']) : 0,
        'valuePatterns' => is_array($payload['valuePatterns'] ?? null) ? count($payload['valuePatterns']) : 0,
        'archiveFolders' => is_array($payload['archiveStructure']['archiveFolders'] ?? null) ? count($payload['archiveStructure']['archiveFolders']) : 0,
    ];
}

function configuration_backup_comment(array $payload): string
{
    return is_string($payload['comment'] ?? null) ? trim((string) $payload['comment']) : '';
}

function normalize_configuration_snapshot_type(mixed $type): string
{
    $normalized = trim(strtolower(is_string($type) ? $type : ''));
    $normalized = str_replace('-', '_', $normalized);
    if (in_array($normalized, ['pre_import', 'pre_restore', 'before_restore'], true)) {
        return 'before_restore';
    }
    return in_array($normalized, ['manual', 'imported', 'snapshot'], true) ? $normalized : '';
}

function configuration_snapshot_type_label(string $snapshotType): string
{
    return match (normalize_configuration_snapshot_type($snapshotType)) {
        'before_restore' => 'Före återställning',
        'imported' => 'Importerad',
        'snapshot' => 'Snapshot',
        default => 'Manuell',
    };
}

function configuration_snapshot_type_prefix(string $snapshotType): string
{
    return match (normalize_configuration_snapshot_type($snapshotType)) {
        'before_restore' => 'pre-restore',
        'imported' => 'imported',
        'snapshot' => 'snapshot',
        default => 'manual',
    };
}

function configuration_snapshot_payload(array $payload, string $snapshotType, ?string $snapshotAt = null): array
{
    $normalizedType = normalize_configuration_snapshot_type($snapshotType);
    if ($normalizedType === '') {
        $normalizedType = 'manual';
    }
    $normalizedPayload = $payload;
    $normalizedPayload['snapshotType'] = $normalizedType;
    $normalizedPayload['snapshotAt'] = is_string($snapshotAt) && trim($snapshotAt) !== '' ? trim($snapshotAt) : now_iso();
    return $normalizedPayload;
}

function configuration_backup_entry_from_path(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }

    $payload = load_json_file($path);
    if (!is_array($payload)) {
        return null;
    }

    if (($payload['format'] ?? null) !== 'docflow_config') {
        return null;
    }

    $filename = basename($path);
    $snapshotType = normalize_configuration_snapshot_type($payload['snapshotType'] ?? null);
    if ($snapshotType === '') {
        if (str_starts_with($filename, 'pre-import-') || str_starts_with($filename, 'pre-restore-')) {
            $snapshotType = 'before_restore';
        } elseif (str_starts_with($filename, 'snapshot-')) {
            $snapshotType = 'snapshot';
        } elseif (str_starts_with($filename, 'imported-')) {
            $snapshotType = 'imported';
        } else {
            $snapshotType = 'manual';
        }
    }
    $snapshotAt = is_string($payload['snapshotAt'] ?? null) ? trim((string) $payload['snapshotAt']) : '';
    $exportedAt = is_string($payload['exportedAt'] ?? null) ? trim((string) $payload['exportedAt']) : '';
    $timestampSource = $snapshotAt !== '' ? $snapshotAt : ($exportedAt !== '' ? $exportedAt : '');
    $exportedAtTimestamp = $timestampSource !== '' ? strtotime($timestampSource) : false;
    $modifiedAtTimestamp = filemtime($path);
    $sortTimestamp = $exportedAtTimestamp !== false
        ? $exportedAtTimestamp
        : ($modifiedAtTimestamp !== false ? $modifiedAtTimestamp : 0);

    return [
        'filename' => $filename,
        'kind' => $snapshotType !== '' ? $snapshotType : 'manual',
        'snapshotType' => $snapshotType !== '' ? $snapshotType : 'manual',
        'snapshotAt' => $snapshotAt !== '' ? $snapshotAt : null,
        'exportedAt' => $exportedAt !== '' ? $exportedAt : null,
        'version' => isset($payload['version']) && is_numeric($payload['version']) ? (int) $payload['version'] : 0,
        'summary' => configuration_backup_summary($payload),
        'comment' => configuration_backup_comment($payload),
        'snapshotId' => isset($payload['snapshotId']) && is_numeric($payload['snapshotId']) ? (int) $payload['snapshotId'] : null,
        'snapshotFolderName' => is_string($payload['snapshotFolderName'] ?? null) ? trim((string) $payload['snapshotFolderName']) : '',
        'snapshotCreatedAt' => is_string($payload['snapshotCreatedAt'] ?? null) ? trim((string) $payload['snapshotCreatedAt']) : null,
        'sizeBytes' => filesize($path) ?: 0,
        'modifiedAt' => $modifiedAtTimestamp !== false ? gmdate(DATE_ATOM, $modifiedAtTimestamp) : null,
        'sortTimestamp' => $sortTimestamp,
    ];
}

function list_configuration_backups(): array
{
    ensure_configuration_backup_directory();
    $paths = glob(configuration_backup_directory() . DIRECTORY_SEPARATOR . '*.json');
    if (!is_array($paths)) {
        return [];
    }

    $entries = [];
    foreach ($paths as $path) {
        if (!is_string($path)) {
            continue;
        }
        $entry = configuration_backup_entry_from_path($path);
        if ($entry !== null) {
            $entries[] = $entry;
        }
    }

    usort(
        $entries,
        static function (array $left, array $right): int {
            $rightSort = (int) ($right['sortTimestamp'] ?? 0);
            $leftSort = (int) ($left['sortTimestamp'] ?? 0);
            if ($rightSort === $leftSort) {
                return strcmp((string) ($right['filename'] ?? ''), (string) ($left['filename'] ?? ''));
            }
            return $rightSort <=> $leftSort;
        }
    );

    return $entries;
}

function prune_configuration_backups(int $maxBackups = 50): void
{
    if ($maxBackups < 1) {
        return;
    }

    $entries = list_configuration_backups();
    if (count($entries) <= $maxBackups) {
        return;
    }

    foreach (array_slice($entries, $maxBackups) as $entry) {
        $path = configuration_backup_path((string) ($entry['filename'] ?? ''));
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }
}

function build_configuration_export_payload(): array
{
    $activeRules = load_active_archiving_rules();
    $config = load_config();
    $matching = load_matching_settings_payload();

    return [
        'format' => 'docflow_config',
        'version' => 1,
        'exportedAt' => now_iso(),
        'clients' => load_client_export_rows(),
        'senders' => load_sender_export_rows(),
        'labels' => array_values(is_array($activeRules['labels'] ?? null) ? $activeRules['labels'] : []),
        'systemLabels' => is_array($activeRules['systemLabels'] ?? null) ? $activeRules['systemLabels'] : system_labels_template(),
        'archiveStructure' => [
            'archiveFolders' => is_array($activeRules['archiveFolders'] ?? null) ? $activeRules['archiveFolders'] : [],
        ],
        'valuePatterns' => is_array($activeRules['valuePatterns'] ?? null) ? $activeRules['valuePatterns'] : [],
        'dataFields' => [
            'fields' => is_array($activeRules['fields'] ?? null) ? $activeRules['fields'] : [],
            'predefinedFields' => is_array($activeRules['predefinedFields'] ?? null) ? $activeRules['predefinedFields'] : [],
            'systemFields' => is_array($activeRules['systemFields'] ?? null) ? $activeRules['systemFields'] : [],
            'zones' => is_array($activeRules['zones'] ?? null) ? $activeRules['zones'] : [],
        ],
        'matching' => [
            'replacements' => is_array($matching['replacements'] ?? null) ? $matching['replacements'] : [],
            'positionAdjustment' => normalize_matching_position_adjustment_settings(
                is_array($matching['positionAdjustment'] ?? null) ? $matching['positionAdjustment'] : []
            ),
            'bboxSpanBuilding' => normalize_matching_bbox_span_building_settings(
                is_array($matching['bboxSpanBuilding'] ?? null) ? $matching['bboxSpanBuilding'] : []
            ),
            'dataFieldAcceptanceThreshold' => isset($matching['dataFieldAcceptanceThreshold']) && is_numeric($matching['dataFieldAcceptanceThreshold'])
                ? clamp_confidence((float) $matching['dataFieldAcceptanceThreshold'])
                : 0.5,
        ],
        'ocr' => [
            'ocrSkipExistingText' => (bool) ($config['ocrSkipExistingText'] ?? true),
            'ocrOptimizeLevel' => (int) ($config['ocrOptimizeLevel'] ?? 1),
            'ocrTextExtractionMethod' => (string) ($config['ocrTextExtractionMethod'] ?? 'layout'),
            'ocrPdfTextSubstitutions' => is_array($config['ocrPdfTextSubstitutions'] ?? null)
                ? sanitize_ocr_pdf_text_substitutions($config['ocrPdfTextSubstitutions'])
                : [],
        ],
        'system' => [
            'stateUpdateTransport' => (string) ($config['stateUpdateTransport'] ?? 'polling'),
            'chromeExtensionSuppressMissingNotice' => (bool) ($config['chromeExtensionSuppressMissingNotice'] ?? false),
        ],
    ];
}

function write_configuration_backup(array $payload): string
{
    return write_configuration_snapshot_to_prefix($payload, 'manual');
}

function write_configuration_backup_to_prefix(array $payload, string $prefix): string
{
    $normalizedPrefix = trim(strtolower($prefix));
    $snapshotType = match ($normalizedPrefix) {
        'pre-import', 'pre-restore', 'before-restore' => 'before_restore',
        'imported' => 'imported',
        'snapshot' => 'snapshot',
        default => 'manual',
    };
    return write_configuration_snapshot_to_prefix($payload, $snapshotType);
}

function write_configuration_snapshot_to_prefix(array $payload, string $snapshotType): string
{
    ensure_configuration_backup_directory();
    $normalizedType = normalize_configuration_snapshot_type($snapshotType);
    if ($normalizedType === '') {
        $normalizedType = 'manual';
    }
    $snapshotPayload = configuration_snapshot_payload($payload, $normalizedType);
    $snapshotAt = is_string($snapshotPayload['snapshotAt'] ?? null) ? (string) $snapshotPayload['snapshotAt'] : null;
    $backupPath = configuration_backup_directory() . DIRECTORY_SEPARATOR . configuration_backup_filename(
        $snapshotAt,
        configuration_snapshot_type_prefix($normalizedType)
    );
    write_json_file($backupPath, $snapshotPayload);
    prune_configuration_backups(50);
    return $backupPath;
}

function create_configuration_pre_import_backup(): string
{
    return create_configuration_pre_restore_backup();
}

function create_configuration_pre_restore_backup(): string
{
    return write_configuration_snapshot_to_prefix(build_configuration_export_payload(), 'before_restore');
}

function configuration_backup_payload_fingerprint(array $payload): string
{
    $normalized = $payload;
    unset(
        $normalized['exportedAt'],
        $normalized['snapshotAt'],
        $normalized['snapshotType'],
        $normalized['comment'],
        $normalized['snapshotId'],
        $normalized['snapshotFolderName'],
        $normalized['snapshotCreatedAt']
    );
    $encoded = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return is_string($encoded) ? sha1($encoded) : sha1('');
}

function update_configuration_backup_comment(string $filename, string $comment): array
{
    $path = configuration_backup_path($filename);
    if ($path === null || !is_file($path)) {
        throw new RuntimeException('Backup file not found');
    }

    $payload = load_json_file($path);
    if (!is_array($payload) || ($payload['format'] ?? null) !== 'docflow_config') {
        throw new RuntimeException('Backup file could not be read');
    }

    $resolvedComment = trim($comment);
    if ($resolvedComment === '') {
        unset($payload['comment']);
    } else {
        $payload['comment'] = $resolvedComment;
    }
    write_json_file($path, $payload);

    $entry = configuration_backup_entry_from_path($path);
    if (!is_array($entry)) {
        throw new RuntimeException('Backup file could not be read after update');
    }

    return $entry;
}

function latest_configuration_backup_entry(): ?array
{
    $entries = list_configuration_backups();
    if (!is_array($entries) || $entries === []) {
        return null;
    }

    $latest = $entries[0];
    return is_array($latest) ? $latest : null;
}

function write_configuration_backup_if_changed(array $payload): array
{
    return write_configuration_snapshot_if_changed($payload, 'manual');
}

function write_configuration_snapshot_if_changed(array $payload, string $snapshotType): array
{
    foreach (list_configuration_backups() as $entry) {
        if (!is_array($entry) || !is_string($entry['filename'] ?? null)) {
            continue;
        }
        $entryPath = configuration_backup_path((string) $entry['filename']);
        if ($entryPath === null || !is_file($entryPath)) {
            continue;
        }
        $entryPayload = load_json_file($entryPath);
        if (
            is_array($entryPayload)
            && configuration_backup_payload_fingerprint($entryPayload) === configuration_backup_payload_fingerprint($payload)
        ) {
            return [
                'created' => false,
                'backupPath' => $entryPath,
                'backupFile' => basename($entryPath),
            ];
        }
    }

    $backupPath = write_configuration_snapshot_to_prefix($payload, $snapshotType);
    return [
        'created' => true,
        'backupPath' => $backupPath,
        'backupFile' => basename($backupPath),
    ];
}

function store_imported_configuration_snapshot(array $payload): array
{
    validate_configuration_import_payload($payload);
    $result = write_configuration_snapshot_if_changed($payload, 'imported');
    $result['snapshotType'] = 'imported';
    return $result;
}

function create_configuration_backup_for_ocr_debug_snapshot(array $snapshotRow): array
{
    $snapshotId = isset($snapshotRow['id']) && is_numeric($snapshotRow['id']) ? (int) $snapshotRow['id'] : 0;
    $folderName = is_string($snapshotRow['folder_name'] ?? null) ? trim((string) $snapshotRow['folder_name']) : '';
    if ($snapshotId < 1 || $folderName === '') {
        throw new RuntimeException('Snapshot saknar identifierare för inställningsbackup.');
    }

    $createdAt = is_string($snapshotRow['created_at'] ?? null) ? trim((string) $snapshotRow['created_at']) : '';
    $payload = build_configuration_export_payload();
    $payload['snapshotId'] = $snapshotId;
    $payload['snapshotFolderName'] = $folderName;
    if ($createdAt !== '') {
        $payload['snapshotCreatedAt'] = $createdAt;
    }
    $payload['comment'] = 'Inställningsbackup för snapshot: ' . $folderName;

    $backupPath = write_configuration_snapshot_to_prefix($payload, 'snapshot');
    $entry = configuration_backup_entry_from_path($backupPath);
    if (!is_array($entry)) {
        throw new RuntimeException('Inställningsbackupen kunde inte läsas efter skapande.');
    }

    return $entry;
}

function normalize_export_client_rows(array $rows): array
{
    $clients = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $clients[] = [
            'firstName' => is_string($row['firstName'] ?? null) ? trim((string) $row['firstName']) : '',
            'lastName' => is_string($row['lastName'] ?? null) ? trim((string) $row['lastName']) : '',
            'folderName' => is_string($row['folderName'] ?? null) ? trim((string) $row['folderName']) : '',
            'personalIdentityNumber' => is_string($row['personalIdentityNumber'] ?? null)
                ? trim((string) $row['personalIdentityNumber'])
                : '',
            'preferredFirstNameIndex' => isset($row['preferredFirstNameIndex']) && is_numeric($row['preferredFirstNameIndex'])
                ? (int) $row['preferredFirstNameIndex']
                : null,
        ];
    }

    return $clients;
}

function normalize_export_sender_rows(array $rows): array
{
    $senders = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $organizationNumbers = [];
        foreach (is_array($row['organizationNumbers'] ?? null) ? $row['organizationNumbers'] : [] as $organization) {
            if (!is_array($organization)) {
                continue;
            }
            $organizationNumbers[] = [
                'id' => isset($organization['id']) && is_numeric($organization['id']) ? (int) $organization['id'] : null,
                'organizationNumber' => is_string($organization['organizationNumber'] ?? null)
                    ? trim((string) $organization['organizationNumber'])
                    : '',
                'organizationName' => is_string($organization['organizationName'] ?? null)
                    ? trim((string) $organization['organizationName'])
                    : '',
            ];
        }

        $paymentNumbers = [];
        foreach (is_array($row['paymentNumbers'] ?? null) ? $row['paymentNumbers'] : [] as $payment) {
            if (!is_array($payment)) {
                continue;
            }
            $type = is_string($payment['type'] ?? null) ? trim(strtolower((string) $payment['type'])) : '';
            $number = is_string($payment['number'] ?? null) ? trim((string) $payment['number']) : '';
            if ($number === '' || ($type !== 'bankgiro' && $type !== 'plusgiro')) {
                continue;
            }
            $paymentNumbers[] = [
                'id' => isset($payment['id']) && is_numeric($payment['id']) ? (int) $payment['id'] : null,
                'type' => $type,
                'number' => $number,
            ];
        }

        $matchNames = [];
        foreach (is_array($row['matchNames'] ?? null) ? $row['matchNames'] : [] as $matchName) {
            $matchNameValue = is_string($matchName)
                ? trim($matchName)
                : (is_array($matchName) && is_string($matchName['name'] ?? null)
                    ? trim((string) $matchName['name'])
                    : '');
            if ($matchNameValue === '') {
                continue;
            }
            $matchNames[] = [
                'id' => is_array($matchName) && isset($matchName['id']) && is_numeric($matchName['id'])
                    ? (int) $matchName['id']
                    : null,
                'name' => $matchNameValue,
            ];
        }

        $senders[] = [
            'id' => isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : null,
            'name' => is_string($row['name'] ?? null) ? trim((string) $row['name']) : '',
            'domain' => is_string($row['domain'] ?? null) ? trim((string) $row['domain']) : '',
            'kind' => is_string($row['kind'] ?? null) ? trim((string) $row['kind']) : '',
            'notes' => is_string($row['notes'] ?? null) ? (string) $row['notes'] : '',
            'matchNames' => $matchNames,
            'organizationNumbers' => $organizationNumbers,
            'paymentNumbers' => $paymentNumbers,
        ];
    }

    return $senders;
}

function validate_configuration_import_payload(array $payload): void
{
    if (($payload['format'] ?? null) !== 'docflow_config' || (int) ($payload['version'] ?? 0) !== 1) {
        throw new RuntimeException('Unsupported configuration format');
    }

    if (
        !is_array($payload['clients'] ?? null)
        || !is_array($payload['senders'] ?? null)
        || !is_array($payload['labels'] ?? null)
        || !is_array($payload['systemLabels'] ?? null)
        || !is_array($payload['archiveStructure'] ?? null)
        || !is_array($payload['dataFields'] ?? null)
        || !is_array($payload['matching'] ?? null)
        || !is_array($payload['ocr'] ?? null)
        || !is_array($payload['system'] ?? null)
    ) {
        throw new RuntimeException('Configuration payload is missing required sections');
    }
}

function apply_configuration_import_payload(array $payload): array
{
    validate_configuration_import_payload($payload);

    $backupPath = create_configuration_pre_restore_backup();

    $currentConfig = load_config();
    $currentMatching = load_matching_settings_payload();
    $currentClients = load_client_export_rows();
    $currentSenders = load_sender_export_rows();
    $currentActiveRules = load_active_archiving_rules();

    $importedClients = normalize_export_client_rows($payload['clients']);
    $importedSenders = normalize_export_sender_rows($payload['senders']);

    $nextActiveRules = normalize_archiving_rules_set([
        'archiveFolders' => is_array($payload['archiveStructure']['archiveFolders'] ?? null)
            ? $payload['archiveStructure']['archiveFolders']
            : [],
        'valuePatterns' => is_array($payload['valuePatterns'] ?? null) ? $payload['valuePatterns'] : [],
        'labels' => is_array($payload['labels'] ?? null) ? $payload['labels'] : [],
        'systemLabels' => is_array($payload['systemLabels'] ?? null) ? $payload['systemLabels'] : [],
        'fields' => is_array($payload['dataFields']['fields'] ?? null) ? $payload['dataFields']['fields'] : [],
        'predefinedFields' => is_array($payload['dataFields']['predefinedFields'] ?? null) ? $payload['dataFields']['predefinedFields'] : [],
        'systemFields' => is_array($payload['dataFields']['systemFields'] ?? null) ? $payload['dataFields']['systemFields'] : [],
        'zones' => is_array($payload['dataFields']['zones'] ?? null) ? $payload['dataFields']['zones'] : [],
    ]);

    $normalizedMatching = [
        'replacements' => array_values(array_filter(
            array_map(
                static function (mixed $row): ?array {
                    if (!is_array($row)) {
                        return null;
                    }
                    $from = is_string($row['from'] ?? null) ? trim((string) $row['from']) : '';
                    $to = is_string($row['to'] ?? null) ? trim((string) $row['to']) : '';
                    if ($from === '' || $to === '') {
                        return null;
                    }
                    return [
                        'from' => $from,
                        'to' => $to,
                    ];
                },
                is_array($payload['matching']['replacements'] ?? null) ? $payload['matching']['replacements'] : []
            ),
            static fn (?array $row): bool => is_array($row)
        )),
        'positionAdjustment' => normalize_matching_position_adjustment_settings(
            is_array($payload['matching']['positionAdjustment'] ?? null)
                ? $payload['matching']['positionAdjustment']
                : default_matching_position_adjustment_settings()
        ),
        'bboxSpanBuilding' => normalize_matching_bbox_span_building_settings(
            is_array($payload['matching']['bboxSpanBuilding'] ?? null) ? $payload['matching']['bboxSpanBuilding'] : []
        ),
        'dataFieldAcceptanceThreshold' => isset($payload['matching']['dataFieldAcceptanceThreshold']) && is_numeric($payload['matching']['dataFieldAcceptanceThreshold'])
            ? clamp_confidence((float) $payload['matching']['dataFieldAcceptanceThreshold'])
            : 0.5,
    ];

    $nextConfig = load_raw_config();
    if (array_key_exists('ocrSkipExistingText', $payload['ocr'])) {
        $nextConfig['ocrSkipExistingText'] = (bool) $payload['ocr']['ocrSkipExistingText'];
    }
    if (array_key_exists('ocrOptimizeLevel', $payload['ocr'])) {
        $nextConfig['ocrOptimizeLevel'] = max(0, min(3, (int) $payload['ocr']['ocrOptimizeLevel']));
    }
    if (array_key_exists('ocrTextExtractionMethod', $payload['ocr'])) {
        $nextConfig['ocrTextExtractionMethod'] = sanitize_ocr_text_extraction_method_value($payload['ocr']['ocrTextExtractionMethod'], 'layout');
    }
    if (array_key_exists('ocrPdfTextSubstitutions', $payload['ocr'])) {
        $nextConfig['ocrPdfTextSubstitutions'] = sanitize_ocr_pdf_text_substitutions($payload['ocr']['ocrPdfTextSubstitutions']);
    }
    if (array_key_exists('stateUpdateTransport', $payload['system'])) {
        $nextConfig['stateUpdateTransport'] = sanitize_state_update_transport_value($payload['system']['stateUpdateTransport'], 'polling');
    }
    if (array_key_exists('chromeExtensionSuppressMissingNotice', $payload['system'])) {
        $nextConfig['chromeExtensionSuppressMissingNotice'] = (bool) $payload['system']['chromeExtensionSuppressMissingNotice'];
    }

    $clientRepository = client_repository_instance();
    if ($clientRepository === null) {
        throw new RuntimeException('Client repository is unavailable.');
    }
    $storedClients = $clientRepository->replaceAll($importedClients);

    $senderRepository = sender_repository_instance();
    if ($senderRepository === null) {
        throw new RuntimeException('Sender repository is unavailable.');
    }
    $storedSenders = $senderRepository->replaceAll($importedSenders);

    $state = load_archiving_rules_state();
    $previousActiveRules = normalize_archiving_rules_set($currentActiveRules);
    $reviewRelevantChanged = archiving_rules_review_relevant_hash($previousActiveRules) !== archiving_rules_review_relevant_hash($nextActiveRules);
    $changedSections = $reviewRelevantChanged
        ? archiving_rules_changed_sections($previousActiveRules, $nextActiveRules)
        : [];
    $templateChanges = $reviewRelevantChanged
        ? archiving_rules_filename_template_changes($previousActiveRules, $nextActiveRules)
        : [];

    $state['activeArchivingRulesVersion'] = max(
        1,
        (int) ($state['activeArchivingRulesVersion'] ?? 1) + ($reviewRelevantChanged ? 1 : 0)
    );
    $state['activeArchivingRules'] = $nextActiveRules;
    $state['draftArchivingRules'] = $nextActiveRules;
    $storedRulesState = save_archiving_rules_state($state);

    if ($reviewRelevantChanged) {
        restart_archiving_update_session(
            $currentConfig,
            $previousActiveRules,
            $nextActiveRules,
            (int) ($storedRulesState['activeArchivingRulesVersion'] ?? $state['activeArchivingRulesVersion']),
            [
                'reason' => 'import',
                'changedSections' => $changedSections,
                'templateChanges' => $templateChanges,
            ]
        );
        maybe_queue_archiving_rules_update_event($currentConfig);
    }

    save_raw_config($nextConfig);
    $savedConfig = load_config();

    $currentMatchingJson = json_encode([
        'replacements' => is_array($currentMatching['replacements'] ?? null) ? $currentMatching['replacements'] : [],
        'positionAdjustment' => normalize_matching_position_adjustment_settings(
            is_array($currentMatching['positionAdjustment'] ?? null) ? $currentMatching['positionAdjustment'] : []
        ),
        'bboxSpanBuilding' => normalize_matching_bbox_span_building_settings(
            is_array($currentMatching['bboxSpanBuilding'] ?? null) ? $currentMatching['bboxSpanBuilding'] : []
        ),
        'dataFieldAcceptanceThreshold' => isset($currentMatching['dataFieldAcceptanceThreshold']) && is_numeric($currentMatching['dataFieldAcceptanceThreshold'])
            ? clamp_confidence((float) $currentMatching['dataFieldAcceptanceThreshold'])
            : 0.5,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $nextMatchingJson = json_encode($normalizedMatching, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($currentMatchingJson) || !is_string($nextMatchingJson)) {
        throw new RuntimeException('Could not encode matching settings.');
    }
    if ($currentMatchingJson !== $nextMatchingJson) {
        write_json_file(DATA_DIR . '/matching.json', $normalizedMatching);
    }

    $analysisRelevantConfigChanged = (
        (bool) ($currentConfig['ocrSkipExistingText'] ?? true) !== (bool) ($savedConfig['ocrSkipExistingText'] ?? true)
        || (int) ($currentConfig['ocrOptimizeLevel'] ?? 1) !== (int) ($savedConfig['ocrOptimizeLevel'] ?? 1)
        || (string) ($currentConfig['ocrTextExtractionMethod'] ?? 'layout') !== (string) ($savedConfig['ocrTextExtractionMethod'] ?? 'layout')
        || json_encode(
            sanitize_ocr_pdf_text_substitutions($currentConfig['ocrPdfTextSubstitutions'] ?? []),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) !== json_encode(
            sanitize_ocr_pdf_text_substitutions($savedConfig['ocrPdfTextSubstitutions'] ?? []),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )
    );
    $clientConfigChanged = json_encode($currentClients, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        !== json_encode($importedClients, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $senderConfigChanged = json_encode($currentSenders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        !== json_encode($importedSenders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $matchingChanged = $currentMatchingJson !== $nextMatchingJson;
    $shouldReprocess = $analysisRelevantConfigChanged
        || $clientConfigChanged
        || $senderConfigChanged
        || $matchingChanged
        || $reviewRelevantChanged;

    $reprocessedJobs = [
        'reprocessedJobIds' => [],
        'reprocessedCount' => 0,
        'mode' => 'full',
    ];
    if ($shouldReprocess) {
        ensure_job_dispatcher_running($savedConfig);
        $reprocessedJobs = reprocess_unarchived_jobs_for_analysis_change($savedConfig, 'full', false);
    }

    return [
        'backupFile' => basename($backupPath),
        'clients' => $importedClients,
        'senders' => $importedSenders,
        'labels' => array_values(is_array($nextActiveRules['labels'] ?? null) ? $nextActiveRules['labels'] : []),
        'systemLabels' => is_array($nextActiveRules['systemLabels'] ?? null) ? $nextActiveRules['systemLabels'] : system_labels_template(),
        'archiveStructure' => [
            'archiveFolders' => is_array($nextActiveRules['archiveFolders'] ?? null) ? $nextActiveRules['archiveFolders'] : [],
        ],
        'dataFields' => [
            'fields' => is_array($nextActiveRules['fields'] ?? null) ? $nextActiveRules['fields'] : [],
            'predefinedFields' => is_array($nextActiveRules['predefinedFields'] ?? null) ? $nextActiveRules['predefinedFields'] : [],
            'systemFields' => is_array($nextActiveRules['systemFields'] ?? null) ? $nextActiveRules['systemFields'] : [],
            'zones' => is_array($nextActiveRules['zones'] ?? null) ? $nextActiveRules['zones'] : [],
        ],
        'matching' => $normalizedMatching,
        'ocr' => [
            'ocrSkipExistingText' => (bool) ($savedConfig['ocrSkipExistingText'] ?? true),
            'ocrOptimizeLevel' => (int) ($savedConfig['ocrOptimizeLevel'] ?? 1),
            'ocrTextExtractionMethod' => (string) ($savedConfig['ocrTextExtractionMethod'] ?? 'layout'),
            'ocrPdfTextSubstitutions' => is_array($savedConfig['ocrPdfTextSubstitutions'] ?? null)
                ? sanitize_ocr_pdf_text_substitutions($savedConfig['ocrPdfTextSubstitutions'])
                : [],
        ],
        'system' => [
            'stateUpdateTransport' => (string) ($savedConfig['stateUpdateTransport'] ?? 'polling'),
            'chromeExtensionSuppressMissingNotice' => (bool) ($savedConfig['chromeExtensionSuppressMissingNotice'] ?? false),
        ],
        'reprocessedJobs' => $reprocessedJobs,
    ];
}

function split_client_first_names(string $firstName): array
{
    $normalized = normalize_inline_whitespace($firstName);
    if ($normalized === '') {
        return [];
    }

    $parts = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($parts) ? array_values($parts) : [];
}

function normalize_client_preferred_first_name_index(mixed $value, string $firstName): ?int
{
    return normalize_client_preferred_first_name_index_from_parts($value, split_client_first_names($firstName));
}

function normalize_client_preferred_first_name_index_from_parts(mixed $value, array $parts): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_string($value)) {
        $value = trim($value);
    }

    if (!is_int($value) && !is_string($value) && !is_float($value)) {
        return null;
    }

    if (!is_numeric($value)) {
        return null;
    }

    $index = (int) $value;
    if ($index < 0 || !array_key_exists($index, $parts)) {
        return null;
    }

    return $index;
}

function client_preferred_first_name_from_parts(array $parts, ?int $preferredFirstNameIndex): ?string
{
    if ($preferredFirstNameIndex === null || !array_key_exists($preferredFirstNameIndex, $parts)) {
        return null;
    }

    $value = trim((string) $parts[$preferredFirstNameIndex]);
    return $value !== '' ? $value : null;
}

function client_preferred_first_name(array $client): ?string
{
    $firstName = is_string($client['firstName'] ?? null) ? (string) $client['firstName'] : '';
    $parts = split_client_first_names($firstName);
    $index = normalize_client_preferred_first_name_index_from_parts($client['preferredFirstNameIndex'] ?? null, $parts);
    return client_preferred_first_name_from_parts($parts, $index);
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

function zone_repository_instance(): ?\Docflow\Archiving\ZoneRepository
{
    static $initialized = false;
    static $repository = null;

    if ($initialized) {
        return $repository;
    }

    $initialized = true;
    try {
        $pdo = \Docflow\Database\Connection::make();
        $repository = new \Docflow\Archiving\ZoneRepository($pdo);
    } catch (Throwable $e) {
        $repository = null;
    }

    return $repository;
}

function label_repository_instance(): ?\Docflow\Archiving\LabelRepository
{
    static $initialized = false;
    static $repository = null;

    if ($initialized) {
        return $repository;
    }

    $initialized = true;
    try {
        $pdo = \Docflow\Database\Connection::make();
        $repository = new \Docflow\Archiving\LabelRepository($pdo);
    } catch (Throwable $e) {
        $repository = null;
    }

    return $repository;
}

function document_metadata_repository_instance(): ?\Docflow\Jobs\DocumentMetadataRepository
{
    static $initialized = false;
    static $repository = null;

    if ($initialized) {
        return $repository;
    }

    $initialized = true;
    try {
        $pdo = \Docflow\Database\Connection::make();
        $repository = new \Docflow\Jobs\DocumentMetadataRepository($pdo);
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

function sync_job_analysis_snapshot(
    string $jobId,
    array $autoResult,
    ?string $analyzedAt = null,
    ?array $previousAutoResultOverride = null,
    ?array $currentLabelIdsOverride = null,
    ?array $currentDataValuesOverride = null
): void
{
    $normalizedId = trim($jobId);
    if ($normalizedId === '') {
        return;
    }

    $repository = job_repository_instance();
    if ($repository === null) {
        return;
    }

    $previousAutoResult = $previousAutoResultOverride !== null
        ? normalize_auto_archiving_result($previousAutoResultOverride)
        : job_analysis_snapshot($normalizedId);
    try {
        $repository->upsertAnalysisSnapshot(
            $normalizedId,
            normalize_auto_archiving_result($autoResult),
            $analyzedAt
        );
    } catch (Throwable $e) {
        // Best effort for now. job.json remains the fallback while this migration lands.
    }

    sync_document_metadata_from_auto_transition(
        $normalizedId,
        is_array($previousAutoResult) ? normalize_auto_archiving_result($previousAutoResult) : null,
        normalize_auto_archiving_result($autoResult),
        $currentLabelIdsOverride,
        $currentDataValuesOverride
    );
}

function document_metadata_label_ids(string $jobId): ?array
{
    $repository = document_metadata_repository_instance();
    if ($repository === null) {
        return null;
    }

    try {
        $labelIds = $repository->findLabelIds($jobId);
    } catch (Throwable $e) {
        return null;
    }

    return is_array($labelIds) ? normalize_stored_job_label_ids($labelIds) : null;
}

function persist_document_metadata_label_ids(string $jobId, array $labelIds): void
{
    $repository = document_metadata_repository_instance();
    if ($repository === null) {
        return;
    }

    try {
        $repository->replaceLabels($jobId, normalize_stored_job_label_ids($labelIds));
    } catch (Throwable $e) {
        // job.json remains the compatibility fallback.
    }
}

function document_metadata_actual_data_values(string $jobId): ?array
{
    $repository = document_metadata_repository_instance();
    if ($repository === null) {
        return null;
    }

    try {
        $selections = $repository->findDataSelections($jobId);
    } catch (Throwable $e) {
        return null;
    }

    return is_array($selections) ? $selections : null;
}

function normalize_document_actual_data_values(mixed $input): array
{
    if (!is_array($input)) {
        return [];
    }

    $resolved = [];
    foreach ($input as $fieldKey => $selection) {
        $normalizedKey = is_string($fieldKey) ? trim($fieldKey) : '';
        if ($normalizedKey === '' || !is_array($selection)) {
            continue;
        }
        $values = normalize_auto_archiving_field_value_list($selection['values'] ?? []);
        $primaryValue = is_scalar($selection['primaryValue'] ?? null) ? trim((string) $selection['primaryValue']) : '';
        if ($primaryValue !== '' && !in_array($primaryValue, $values, true)) {
            $values[] = $primaryValue;
        }
        if ($values === []) {
            continue;
        }
        $resolved[$normalizedKey] = [
            'values' => array_values(array_unique($values)),
            'primaryValue' => $primaryValue !== '' ? $primaryValue : $values[0],
        ];
    }

    ksort($resolved, SORT_NATURAL);
    return $resolved;
}

function selected_extraction_field_values_from_document_actual(array $actualValues, array $autoResult): array
{
    $autoFields = is_array($autoResult['fields'] ?? null) ? $autoResult['fields'] : [];
    $fieldKeys = array_values(array_unique(array_merge(array_keys($actualValues), array_keys($autoFields))));
    sort($fieldKeys, SORT_NATURAL);

    $selected = [];
    foreach ($fieldKeys as $fieldKey) {
        $normalizedKey = is_string($fieldKey) ? trim($fieldKey) : '';
        if ($normalizedKey === '') {
            continue;
        }
        $actual = normalize_auto_archiving_field_value_list($actualValues[$normalizedKey]['values'] ?? []);
        $auto = normalize_auto_archiving_field_value_list($autoFields[$normalizedKey] ?? []);
        $manual = array_values(array_filter($actual, static fn(string $value): bool => !in_array($value, $auto, true)));
        $excluded = array_values(array_filter($auto, static fn(string $value): bool => !in_array($value, $actual, true)));
        $primaryValue = is_scalar($actualValues[$normalizedKey]['primaryValue'] ?? null) ? trim((string) $actualValues[$normalizedKey]['primaryValue']) : '';
        if ($primaryValue === '' && $actual !== []) {
            $primaryValue = $actual[0];
        }
        $autoPrimary = $auto[0] ?? '';
        $primaryIsOverride = $primaryValue !== '' && $primaryValue !== $autoPrimary;
        if ($manual === [] && $excluded === [] && !$primaryIsOverride) {
            continue;
        }
        $selected[$normalizedKey] = [
            'manualValues' => $manual,
            'excludedValues' => $excluded,
            'primaryValue' => $primaryValue !== '' ? $primaryValue : null,
        ];
    }

    return normalize_stored_job_extraction_field_values($selected);
}

function document_actual_data_values_from_selected(array $job, ?array $selectedValues): array
{
    $autoResult = job_auto_archiving_result($job);
    $autoFields = is_array($autoResult['fields'] ?? null) ? $autoResult['fields'] : [];
    if ($selectedValues === null) {
        $selectedValues = [];
    }

    $fieldKeys = array_values(array_unique(array_merge(array_keys($autoFields), array_keys($selectedValues))));
    sort($fieldKeys, SORT_NATURAL);
    $actual = [];
    foreach ($fieldKeys as $fieldKey) {
        $normalizedKey = is_string($fieldKey) ? trim($fieldKey) : '';
        if ($normalizedKey === '') {
            continue;
        }
        $auto = normalize_auto_archiving_field_value_list($autoFields[$normalizedKey] ?? []);
        $selection = is_array($selectedValues[$normalizedKey] ?? null) ? $selectedValues[$normalizedKey] : [];
        $excluded = normalize_auto_archiving_field_value_list($selection['excludedValues'] ?? []);
        $manual = normalize_auto_archiving_field_value_list($selection['manualValues'] ?? []);
        $values = array_values(array_filter($auto, static fn(string $value): bool => !in_array($value, $excluded, true)));
        foreach ($manual as $value) {
            if (!in_array($value, $values, true)) {
                $values[] = $value;
            }
        }
        $primaryValue = is_scalar($selection['primaryValue'] ?? null) ? trim((string) $selection['primaryValue']) : '';
        if ($primaryValue !== '' && !in_array($primaryValue, $values, true)) {
            $values[] = $primaryValue;
        }
        if ($values === []) {
            continue;
        }
        $actual[$normalizedKey] = [
            'values' => $values,
            'primaryValue' => $primaryValue !== '' && in_array($primaryValue, $values, true) ? $primaryValue : $values[0],
        ];
    }

    return $actual;
}

function persist_document_metadata_data_values(string $jobId, array $actualValues): void
{
    $repository = document_metadata_repository_instance();
    if ($repository === null) {
        return;
    }

    try {
        $repository->replaceDataSelections($jobId, normalize_document_actual_data_values($actualValues));
    } catch (Throwable $e) {
        // job.json remains the compatibility fallback.
    }
}

function document_actual_data_values_from_auto_result(array $autoResult): array
{
    $actual = [];
    foreach (is_array($autoResult['fields'] ?? null) ? $autoResult['fields'] : [] as $fieldKey => $values) {
        $normalizedKey = is_string($fieldKey) ? trim($fieldKey) : '';
        $normalizedValues = normalize_auto_archiving_field_value_list($values);
        if ($normalizedKey === '' || $normalizedValues === []) {
            continue;
        }
        $actual[$normalizedKey] = [
            'values' => $normalizedValues,
            'primaryValue' => $normalizedValues[0],
        ];
    }

    return $actual;
}

function auto_archiving_fields_from_document_actual_data_values(array $actualValues): array
{
    $fields = [];
    foreach (normalize_document_actual_data_values($actualValues) as $fieldKey => $selection) {
        $values = normalize_auto_archiving_field_value_list($selection['values'] ?? []);
        if ($values === []) {
            continue;
        }
        $primaryValue = is_scalar($selection['primaryValue'] ?? null) ? trim((string) $selection['primaryValue']) : '';
        if ($primaryValue !== '' && in_array($primaryValue, $values, true)) {
            $values = array_values(array_merge(
                [$primaryValue],
                array_filter($values, static fn(string $value): bool => $value !== $primaryValue)
            ));
        }
        $fields[$fieldKey] = $values;
    }

    return $fields;
}

function auto_archiving_result_with_document_actual_data_values(array $autoResult, array $actualValues): array
{
    $fields = auto_archiving_fields_from_document_actual_data_values($actualValues);
    if ($fields === []) {
        return normalize_auto_archiving_result($autoResult);
    }

    $next = $autoResult;
    $next['fields'] = $fields;
    return normalize_auto_archiving_result($next);
}

function document_actual_data_values_equal(array $left, array $right): bool
{
    return normalize_document_actual_data_values($left) === normalize_document_actual_data_values($right);
}

function job_document_label_ids_for_metadata_sync(string $jobId, array $job): ?array
{
    $stored = document_metadata_label_ids($jobId);
    if (is_array($stored)) {
        return normalize_stored_job_label_ids($stored);
    }
    if (array_key_exists('selectedLabelIds', $job)) {
        return normalize_stored_job_label_ids($job['selectedLabelIds']);
    }
    return null;
}

function job_document_data_values_for_metadata_sync(string $jobId, array $job): ?array
{
    $stored = document_metadata_actual_data_values($jobId);
    if (is_array($stored)) {
        return normalize_document_actual_data_values($stored);
    }
    if (array_key_exists('selectedExtractionFieldValues', $job)) {
        return document_actual_data_values_from_selected(
            $job,
            normalize_stored_job_extraction_field_values($job['selectedExtractionFieldValues'])
        );
    }
    return null;
}

function sync_document_metadata_from_auto_transition(
    string $jobId,
    ?array $previousAutoResult,
    array $nextAutoResult,
    ?array $currentLabelIdsOverride = null,
    ?array $currentDataValuesOverride = null
): void
{
    $currentLabelIds = $currentLabelIdsOverride !== null
        ? normalize_stored_job_label_ids($currentLabelIdsOverride)
        : document_metadata_label_ids($jobId);
    $previousLabelIds = $previousAutoResult !== null ? normalize_stored_job_label_ids($previousAutoResult['labels'] ?? []) : null;
    $nextLabelIds = normalize_stored_job_label_ids($nextAutoResult['labels'] ?? []);
    $normalizedCurrentLabelIds = $currentLabelIds !== null ? normalize_stored_job_label_ids($currentLabelIds) : null;
    if (is_array($normalizedCurrentLabelIds)) {
        sort($normalizedCurrentLabelIds, SORT_NATURAL);
    }
    if (is_array($previousLabelIds)) {
        sort($previousLabelIds, SORT_NATURAL);
    }
    if ($currentLabelIds === null || ($previousLabelIds !== null && $normalizedCurrentLabelIds === $previousLabelIds)) {
        persist_document_metadata_label_ids($jobId, $nextLabelIds);
    } elseif ($currentLabelIdsOverride !== null) {
        persist_document_metadata_label_ids($jobId, normalize_stored_job_label_ids($currentLabelIds));
    }

    $currentDataValues = $currentDataValuesOverride !== null
        ? normalize_document_actual_data_values($currentDataValuesOverride)
        : document_metadata_actual_data_values($jobId);
    $previousDataValues = $previousAutoResult !== null ? document_actual_data_values_from_auto_result($previousAutoResult) : null;
    $nextDataValues = document_actual_data_values_from_auto_result($nextAutoResult);
    if (
        $currentDataValues === null
        || ($previousDataValues !== null && document_actual_data_values_equal($currentDataValues, $previousDataValues))
    ) {
        persist_document_metadata_data_values($jobId, $nextDataValues);
    } elseif ($currentDataValuesOverride !== null) {
        persist_document_metadata_data_values($jobId, $currentDataValues);
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

function fallback_merged_objects_pages_from_job_debug(string $jobId): array
{
    $normalizedId = trim($jobId);
    if ($normalizedId === '') {
        return [];
    }

    try {
        $config = load_config();
    } catch (Throwable $e) {
        return [];
    }

    $jobsDirectory = is_string($config['jobsDirectory'] ?? null) ? trim((string) $config['jobsDirectory']) : '';
    if ($jobsDirectory === '') {
        return [];
    }

    $jobDir = rtrim($jobsDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $normalizedId;
    if (!is_dir($jobDir)) {
        return [];
    }

    $rapidocrPages = load_job_engine_debug_pages($jobDir, 'rapidocr');
    if ($rapidocrPages === []) {
        return [];
    }

    $tesseractPages = load_job_engine_debug_pages($jobDir, 'tesseract');
    $document = build_merged_objects_document_from_rapidocr_pages($rapidocrPages, $tesseractPages);
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

function delete_job_repository_entry(string $jobId): void
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
        $repository->deleteById($normalizedId);
    } catch (Throwable $e) {
        // Best effort only. job.json on disk remains the source of truth.
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

function signed_int(mixed $value, int $fallback = 0): int
{
    if (is_int($value)) {
        return $value;
    }

    if (is_float($value)) {
        return (int) floor($value);
    }

    if (is_string($value) && trim($value) !== '') {
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        if ($parsed !== false) {
            return (int) $parsed;
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
        'score' => signed_int($rule['score'] ?? 1, 1),
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

function normalize_if_labels_mode(mixed $input): string
{
    return is_string($input) && trim($input) === 'all' ? 'all' : 'any';
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

function filename_template_date_format_default(): string
{
    return 'YYYY-MM-DD';
}

function filename_template_date_formats(): array
{
    return [
        'YYYY-MM-DD',
        'YYYY-MM',
        'YYYY',
        'MMM-YYYY',
        'MMMM YYYY',
        'DD MMM YYYY',
    ];
}

function normalize_filename_template_date_format(mixed $value, string $fallback = ''): string
{
    $normalized = is_string($value) ? trim($value) : '';
    if (in_array($normalized, filename_template_date_formats(), true)) {
        return $normalized;
    }
    return $fallback;
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
        $key = is_string($input['key'] ?? null)
            ? trim((string) $input['key'])
            : ($type === 'dataField' && is_string($input['fieldKey'] ?? null)
                ? trim((string) $input['fieldKey'])
                : ($type === 'systemField' && is_string($input['systemFieldKey'] ?? null) ? trim((string) $input['systemFieldKey']) : ''));
        if ($key === '') {
            return null;
        }

        $normalized = [
            'type' => $type,
            'key' => $key,
            'prefixParts' => $prefixParts,
            'suffixParts' => $suffixParts,
        ];
        $dateFormat = normalize_filename_template_date_format($input['dateFormat'] ?? null);
        if ($dateFormat !== '') {
            $normalized['dateFormat'] = $dateFormat;
        }
        return $normalized;
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

    if ($type === 'ifLabels') {
        return [
            'type' => 'ifLabels',
            'mode' => normalize_if_labels_mode($input['mode'] ?? null),
            'labelIds' => normalize_label_id_list($input['labelIds'] ?? null),
            'thenParts' => normalize_filename_template_parts($input['thenParts'] ?? [], $depth + 1),
            'elseParts' => normalize_filename_template_parts($input['elseParts'] ?? [], $depth + 1),
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
        'primary_date',
        'title',
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
            'valueType' => 'amount',
            'ruleSets' => [[
                'useSearchText' => true,
                'requiresSearchTerms' => true,
                'searchTerms' => ['att betala', 'fakturabelopp', 'summa att betala', 'belopp att betala', 'total att betala'],
                'amountPosition' => 'first',
                'valuePattern' => '',
                'normalizationType' => 'none',
                'normalizationChars' => '',
            ]],
        ],
        'due_date' => [
            'name' => 'Förfallodatum',
            'valueType' => 'date',
            'ruleSets' => [[
                'useSearchText' => true,
                'requiresSearchTerms' => true,
                'searchTerms' => ['förfallodatum', 'förfallodag', 'att betala senast'],
                'datePosition' => 'first',
            ]],
        ],
        'bankgiro' => [
            'name' => 'Bankgiro',
            'normalizationType' => 'whitelist',
            'normalizationChars' => '0123456789',
            'normalizationReplacements' => [],
            'ruleSets' => [[
                'useSearchText' => true,
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
                'useSearchText' => true,
                'requiresSearchTerms' => true,
                'searchTerms' => ['plusgiro', 'pg'],
                'valuePattern' => '\d{1,8}(?:[ -]\d{1,4})+',
                'normalizationType' => 'none',
                'normalizationChars' => '',
            ]],
        ],
        'supplier' => [
            'name' => 'Leverantör',
            'ruleSets' => [[
                'useSearchText' => true,
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
                'useSearchText' => true,
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
                'useSearchText' => true,
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
                'useSearchText' => true,
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
                'useSearchText' => true,
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
                    'useSearchText' => true,
                    'requiresSearchTerms' => true,
                    'searchTerms' => ['organisationsnr', 'org.nr', 'org nr'],
                    'valuePattern' => '\d{2}[2-9]\d{3}[- ]?\d{4}',
                    'normalizationType' => 'whitelist',
                    'normalizationChars' => '0123456789',
                ],
                [
                    'useSearchText' => false,
                    'requiresSearchTerms' => false,
                    'searchTerms' => [],
                    'valuePattern' => '\bSE(\d{6}[- ]?\d{4})01\b',
                    'captureGroup' => 1,
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
        'primary_date' => [
            'name' => 'Huvuddatum',
            'aliases' => [],
            'searchString' => '',
            'isRegex' => false,
            'normalizationType' => 'none',
            'normalizationChars' => '',
            'extractor' => 'primary_date',
            'primaryDateHeuristics' => default_primary_date_heuristics(),
        ],
        'title' => [
            'name' => 'Rubrik',
            'aliases' => [],
            'searchString' => '',
            'isRegex' => false,
            'normalizationType' => 'none',
            'normalizationChars' => '',
            'valueType' => 'text',
            'extractor' => 'title',
            'titleHeuristics' => default_title_heuristics(),
        ],
    ];
}

function default_title_heuristics(): array
{
    return [
        'full_confidence_score' => 120.0,
        'signals' => [
            'vertical_position' => [
                'enabled' => true,
                'curve' => [
                    ['x' => 0.0, 'y' => 35.0],
                    ['x' => 0.20, 'y' => 25.0],
                    ['x' => 0.60, 'y' => 0.0],
                    ['x' => 0.95, 'y' => -25.0],
                ],
                'description' => 'Poängkurva baserad på rubrikkandidatens vertikala position på sidan.',
            ],
            'horizontal_position' => [
                'enabled' => true,
                'curve' => [
                    ['x' => 0.0, 'y' => 30.0],
                    ['x' => 0.25, 'y' => 20.0],
                    ['x' => 0.55, 'y' => 0.0],
                    ['x' => 1.0, 'y' => -20.0],
                ],
                'description' => 'Poängkurva baserad på hur nära rubrikkandidaten ligger sidans mitt.',
            ],
            'text_size' => [
                'enabled' => true,
                'curve' => [
                    ['x' => 0.75, 'y' => -20.0],
                    ['x' => 1.0, 'y' => 0.0],
                    ['x' => 1.50, 'y' => 25.0],
                    ['x' => 2.20, 'y' => 45.0],
                ],
                'description' => 'Poängkurva baserad på textstorlek relativt normal radhöjd på sidan.',
            ],
            'uppercase_ratio' => [
                'enabled' => true,
                'curve' => [
                    ['x' => 0.0, 'y' => 0.0],
                    ['x' => 0.45, 'y' => 10.0],
                    ['x' => 0.80, 'y' => 25.0],
                    ['x' => 1.0, 'y' => 30.0],
                ],
                'description' => 'Poängkurva baserad på andelen versaler i rubrikkandidaten.',
            ],
            'brevity' => [
                'enabled' => true,
                'curve' => [
                    ['x' => 1.0, 'y' => 25.0],
                    ['x' => 3.0, 'y' => 20.0],
                    ['x' => 6.0, 'y' => 0.0],
                    ['x' => 10.0, 'y' => -25.0],
                ],
                'description' => 'Poängkurva baserad på hur många ord rubrikkandidaten innehåller.',
            ],
            'text_density' => [
                'enabled' => true,
                'curve' => [
                    ['x' => 0.0, 'y' => 25.0],
                    ['x' => 0.35, 'y' => 15.0],
                    ['x' => 0.70, 'y' => 0.0],
                    ['x' => 1.0, 'y' => -35.0],
                ],
                'description' => 'Poängkurva baserad på hur mycket omgivande text som finns runt rubrikkandidaten.',
            ],
        ],
    ];
}

function default_primary_date_heuristics(): array
{
    return [
        'full_confidence_score' => 130.0,
        'bonuses' => [
            'place_near_date' => [
                'enabled' => true,
                'curve' => [
                    ['x' => 0.0, 'y' => 100.0],
                    ['x' => 1.0, 'y' => 90.0],
                    ['x' => 3.0, 'y' => 45.0],
                    ['x' => 6.0, 'y' => 0.0],
                ],
                'description' => 'Poängkurva när en svensk ort/tätort finns ungefär till vänster om eller ovanför kandidatdatumet.',
            ],
            'document_position' => [
                'enabled' => true,
                'curve' => [
                    ['x' => 0.0, 'y' => 50.0],
                    ['x' => 0.08, 'y' => 50.0],
                    ['x' => 0.35, 'y' => 0.0],
                    ['x' => 0.90, 'y' => -30.0],
                ],
                'description' => 'Poängkurva baserad på datumets vertikala position på sidan.',
            ],
        ],
        'penalties' => [
            'date_word_nearby' => [
                'enabled' => true,
                'curve' => [
                    ['x' => 0.0, 'y' => 0.0],
                    ['x' => 0.5, 'y' => -18.0],
                    ['x' => 1.0, 'y' => -40.0],
                ],
                'description' => 'Poängkurva baserad på hur säkert kandidatdatumet matchar text som innehåller "datum" i närheten.',
            ],
            'page_in_document' => [
                'enabled' => true,
                'curve' => [
                    ['x' => 1.0, 'y' => 0.0],
                    ['x' => 2.0, 'y' => -60.0],
                    ['x' => 3.0, 'y' => -120.0],
                ],
                'description' => 'Poängkurva baserad på sidnumret där datumet hittas.',
            ],
            'text_density' => [
                'enabled' => true,
                'curve' => [
                    ['x' => 0.0, 'y' => 0.0],
                    ['x' => 0.35, 'y' => 0.0],
                    ['x' => 1.0, 'y' => -60.0],
                ],
                'description' => 'Poängkurva baserad på hur mycket omgivande text som finns runt kandidatdatumet.',
            ],
        ],
    ];
}

function normalize_primary_date_bool(mixed $value, bool $fallback): bool
{
    return is_bool($value) ? $value : $fallback;
}

function normalize_primary_date_number(mixed $value, float $fallback): float
{
    return is_numeric($value) ? (float) $value : $fallback;
}

function normalize_primary_date_int(mixed $value, int $fallback): int
{
    return is_numeric($value) ? (int) $value : $fallback;
}

function normalize_primary_date_ratio(mixed $value, float $fallback): float
{
    return max(0.0, min(1.0, normalize_primary_date_number($value, $fallback)));
}

function normalize_primary_date_keywords(mixed $value, array $fallback): array
{
    $values = is_array($value) ? $value : $fallback;
    $normalized = [];
    $seen = [];
    foreach ($values as $keyword) {
        if (!is_string($keyword) && !is_numeric($keyword)) {
            continue;
        }
        $raw = trim((string) $keyword);
        $key = normalize_primary_date_lookup_text($raw);
        if ($raw === '' || $key === '' || isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $normalized[] = $raw;
    }
    return $normalized !== [] ? $normalized : $fallback;
}

function normalize_primary_date_score_curve(mixed $value, array $fallback): array
{
    $source = is_array($value) ? $value : $fallback;
    $points = [];
    foreach ($source as $point) {
        if (!is_array($point) || !is_numeric($point['x'] ?? null) || !is_numeric($point['y'] ?? null)) {
            continue;
        }
        $x = max(0.0, (float) $point['x']);
        $points[sprintf('%.6F', $x)] = [
            'x' => $x,
            'y' => (float) $point['y'],
        ];
    }
    $points = array_values($points);
    usort($points, static fn (array $left, array $right): int => $left['x'] <=> $right['x'] ?: $left['y'] <=> $right['y']);
    if (count($points) >= 2) {
        return $points;
    }
    if ($fallback === []) {
        return [];
    }
    return normalize_primary_date_score_curve($fallback, []);
}

function interpolate_primary_date_score_curve(array $points, float $x): float
{
    $curve = normalize_primary_date_score_curve($points, []);
    if (count($curve) === 0) {
        return 0.0;
    }
    if ($x <= (float) $curve[0]['x']) {
        return (float) $curve[0]['y'];
    }
    $last = $curve[count($curve) - 1];
    if ($x >= (float) $last['x']) {
        return (float) $last['y'];
    }
    for ($index = 1; $index < count($curve); $index++) {
        $left = $curve[$index - 1];
        $right = $curve[$index];
        if ($x > (float) $right['x']) {
            continue;
        }
        $span = max(0.000001, (float) $right['x'] - (float) $left['x']);
        $ratio = ($x - (float) $left['x']) / $span;
        return (float) $left['y'] + (((float) $right['y'] - (float) $left['y']) * $ratio);
    }
    return 0.0;
}

function normalize_primary_date_heuristics(mixed $input): array
{
    $defaults = default_primary_date_heuristics();
    $source = is_array($input) ? $input : [];
    $result = $defaults;
    $result['full_confidence_score'] = max(1.0, normalize_primary_date_number(
        $source['full_confidence_score'] ?? null,
        (float) $defaults['full_confidence_score']
    ));

    foreach ($defaults['bonuses'] as $key => $default) {
        $raw = is_array($source['bonuses'][$key] ?? null) ? $source['bonuses'][$key] : [];
        $result['bonuses'][$key]['enabled'] = true;
        foreach (['points', 'max_points'] as $field) {
            if (array_key_exists($field, $default)) {
                $result['bonuses'][$key][$field] = normalize_primary_date_number($raw[$field] ?? null, (float) $default[$field]);
            }
        }
        foreach (['full_until_y_ratio', 'zero_after_y_ratio', 'max_y_ratio'] as $field) {
            if (array_key_exists($field, $default)) {
                $result['bonuses'][$key][$field] = normalize_primary_date_ratio($raw[$field] ?? null, (float) $default[$field]);
            }
        }
        if (array_key_exists('keywords', $default)) {
            $result['bonuses'][$key]['keywords'] = normalize_primary_date_keywords($raw['keywords'] ?? null, $default['keywords']);
        }
        if (array_key_exists('curve', $default)) {
            $result['bonuses'][$key]['curve'] = normalize_primary_date_score_curve($raw['curve'] ?? null, $default['curve']);
        }
        $result['bonuses'][$key]['description'] = (string) $default['description'];
    }

    foreach ($defaults['penalties'] as $key => $default) {
        $raw = is_array($source['penalties'][$key] ?? null) ? $source['penalties'][$key] : [];
        if ($key === 'text_density' && $raw === [] && is_array($source['penalties']['running_text'] ?? null)) {
            $raw = $source['penalties']['running_text'];
        }
        $result['penalties'][$key]['enabled'] = true;
        foreach (['points', 'max_points', 'direct_before_points', 'same_line_points', 'line_above_points', 'points_per_page'] as $field) {
            if (array_key_exists($field, $default)) {
                $result['penalties'][$key][$field] = normalize_primary_date_number($raw[$field] ?? null, (float) $default[$field]);
            }
        }
        foreach (['starts_at_y_ratio', 'full_after_y_ratio'] as $field) {
            if (array_key_exists($field, $default)) {
                $result['penalties'][$key][$field] = normalize_primary_date_ratio($raw[$field] ?? null, (float) $default[$field]);
            }
        }
        foreach (['no_penalty_until_words', 'full_penalty_from_words'] as $field) {
            if (array_key_exists($field, $default)) {
                $result['penalties'][$key][$field] = max(0, normalize_primary_date_int($raw[$field] ?? null, (int) $default[$field]));
            }
        }
        if (array_key_exists('curve', $default)) {
            $rawCurve = $raw['curve'] ?? null;
            if ($key === 'date_word_nearby' && is_array($rawCurve)) {
                foreach ($rawCurve as $point) {
                    if (is_array($point) && is_numeric($point['x'] ?? null) && (float) $point['x'] > 1.0) {
                        $rawCurve = null;
                        break;
                    }
                }
            }
            $result['penalties'][$key]['curve'] = normalize_primary_date_score_curve($rawCurve, $default['curve']);
        }
        $result['penalties'][$key]['description'] = (string) $default['description'];
    }

    return $result;
}

function title_full_confidence_score(array $heuristics): float
{
    $normalized = normalize_title_heuristics($heuristics);
    return max(1.0, (float) ($normalized['full_confidence_score'] ?? 120.0));
}

function normalize_title_heuristics(mixed $input): array
{
    $defaults = default_title_heuristics();
    $source = is_array($input) ? $input : [];
    $result = $defaults;
    $result['full_confidence_score'] = max(1.0, normalize_primary_date_number(
        $source['full_confidence_score'] ?? null,
        (float) $defaults['full_confidence_score']
    ));

    foreach ($defaults['signals'] as $key => $default) {
        $raw = is_array($source['signals'][$key] ?? null) ? $source['signals'][$key] : [];
        $result['signals'][$key]['enabled'] = true;
        $result['signals'][$key]['curve'] = normalize_primary_date_score_curve(
            $raw['curve'] ?? null,
            $default['curve']
        );
        $result['signals'][$key]['description'] = (string) $default['description'];
    }

    return $result;
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

function normalize_extraction_field_rule_scope(mixed $input): ?array
{
    if (!is_array($input)) {
        return null;
    }

    $type = is_string($input['type'] ?? null) ? trim(strtolower((string) $input['type'])) : '';
    if ($type !== 'after_text') {
        return null;
    }

    $text = is_string($input['text'] ?? null)
        ? normalize_inline_whitespace((string) $input['text'])
        : '';
    if ($text === '') {
        return null;
    }

    return [
        'type' => 'after_text',
        'text' => $text,
        'isRegex' => normalize_extraction_field_is_regex($input['isRegex'] ?? false),
    ];
}

function normalize_extraction_field_normalization_type(mixed $value): string
{
    $normalized = is_string($value) ? trim(strtolower($value)) : '';
    return in_array($normalized, ['whitelist', 'blacklist', 'replacements'], true) ? $normalized : 'none';
}

function normalize_extraction_field_normalization_chars(mixed $value): string
{
    return is_string($value) ? (string) $value : '';
}

function normalize_extraction_field_normalization_replacement_item(mixed $input): ?array
{
    if (!is_array($input)) {
        return null;
    }

    $find = is_string($input['find'] ?? null)
        ? (string) $input['find']
        : (is_string($input['from'] ?? null) ? (string) $input['from'] : '');
    $replace = is_string($input['replace'] ?? null)
        ? (string) $input['replace']
        : (is_string($input['to'] ?? null) ? (string) $input['to'] : '');
    if ($find === '') {
        return null;
    }

    return [
        'find' => $find,
        'replace' => $replace,
        'isRegex' => normalize_extraction_field_is_regex($input['isRegex'] ?? false),
    ];
}

function normalize_extraction_field_normalization_replacements(mixed $input): array
{
    $rows = is_array($input) ? $input : [];
    $normalized = [];
    foreach ($rows as $row) {
        $item = normalize_extraction_field_normalization_replacement_item($row);
        if (is_array($item)) {
            $normalized[] = $item;
        }
    }

    return $normalized;
}

function extraction_field_date_atom_pattern(): string
{
    $monthPattern = 'jan(?:uari|uary)?|feb(?:ruari|ruary)?|mar(?:s|ch)?|apr(?:il)?|maj|may|jun(?:i|e)?|jul(?:i|y)?|aug(?:usti|ust)?|sep(?:t(?:ember)?)?|okt(?:ober)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?';
    $numericSeparator = '(?:\\s*[-.\\/]\\s*|\\s+)';
    return '(?:20\\d{2}' . $numericSeparator . '\\d{1,2}(?:' . $numericSeparator . '\\d{1,2})?|\\d{1,2}\\s+(?:' . $monthPattern . ')\\s+20\\d{2}|(?:' . $monthPattern . ')\\s+20\\d{2})';
}

function extraction_field_amount_atom_pattern(): string
{
    return '-?(?:\\d{1,3}(?:[\\s\\x{00A0}.]+\\d{3})+|\\d+)(?:[.,]\\d{2})?';
}

function extraction_field_split_kronor_atom_pattern(): string
{
    return '(?:\\d{1,3}(?:[\\s\\x{00A0}.]+\\d{3})+|\\d+)';
}

function extraction_field_split_oren_atom_pattern(): string
{
    return '\\d{2}';
}

function extraction_field_date_value_pattern(string $position = 'first'): string
{
    $atomPattern = extraction_field_date_atom_pattern();
    $normalizedPosition = normalize_extraction_field_position($position);
    if ($normalizedPosition === 'second') {
        return '(?:' . $atomPattern . '\\s*[-]\\s*)(' . $atomPattern . ')';
    }
    if ($normalizedPosition === 'last') {
        return '(?:' . $atomPattern . '\\s*[-]\\s*)*(' . $atomPattern . ')';
    }
    return '(' . $atomPattern . ')(?:\\s*[-]\\s*' . $atomPattern . ')*';
}

function extraction_field_date_capture_pattern(): string
{
    return '(\\d{4})\\s*[-.\\/ ]\\s*(\\d{2})\\s*[-.\\/ ]\\s*(\\d{2})';
}

function extraction_field_date_normalization_replacements(): array
{
    return [[
        'find' => '^' . extraction_field_date_capture_pattern() . '$',
        'replace' => '$1-$2-$3',
        'isRegex' => true,
    ]];
}

function normalize_extraction_field_value_type(mixed $value, ?array $legacyField = null, ?array $legacyRuleSet = null): string
{
    $normalized = is_string($value) ? trim(strtolower($value)) : '';
    if (in_array($normalized, ['text', 'date', 'amount'], true)) {
        return $normalized;
    }

    $legacyRuleValueType = is_array($legacyRuleSet) && is_string($legacyRuleSet['valueType'] ?? null)
        ? trim(strtolower((string) $legacyRuleSet['valueType']))
        : '';
    if (in_array($legacyRuleValueType, ['text', 'date', 'amount'], true)) {
        return $legacyRuleValueType;
    }

    $legacyTypeValue = is_array($legacyField) && array_key_exists('type', $legacyField) ? $legacyField['type'] : null;
    $legacyType = normalize_extraction_field_type($legacyTypeValue, $legacyField, $legacyRuleSet);

    return match ($legacyType) {
        'date' => 'date',
        'amount' => 'amount',
        default => 'text',
    };
}

function extraction_field_rule_set_value_type(array $ruleSet, ?array $legacyField = null): string
{
    return normalize_extraction_field_value_type($ruleSet['valueType'] ?? null, $legacyField, $ruleSet);
}

function extraction_field_value_type(array $field): string
{
    $firstRuleSet = is_array($field['ruleSets'][0] ?? null) ? $field['ruleSets'][0] : [];
    return normalize_extraction_field_value_type($field['valueType'] ?? null, $field, $firstRuleSet);
}

function normalize_extraction_field_use_value_pattern(mixed $value, string $pattern): bool
{
    if ($value === null) {
        return trim($pattern) !== '';
    }

    return $value === true || $value === 1 || $value === '1';
}

function expand_extraction_field_value_pattern_macros(string $pattern): string
{
    if ($pattern === '') {
        return '';
    }

    return strtr($pattern, [
        '{DATUM}' => '(?:' . extraction_field_date_atom_pattern() . ')',
        '{BELOPP}' => '(?:' . extraction_field_amount_atom_pattern() . ')',
        '{KRONOR}' => '(?<docflow_kronor>' . extraction_field_split_kronor_atom_pattern() . ')',
        '{ÖREN}' => '(?<docflow_oren>' . extraction_field_split_oren_atom_pattern() . ')',
        '{OREN}' => '(?<docflow_oren>' . extraction_field_split_oren_atom_pattern() . ')',
    ]);
}

function normalize_extraction_field_split_amount_value(string $kronor, string $oren): ?string
{
    $normalizedKronor = preg_replace('/[\\s\\x{00A0}.]+/u', '', trim($kronor));
    if (!is_string($normalizedKronor) || preg_match('/^\\d+$/u', $normalizedKronor) !== 1) {
        return null;
    }

    $normalizedOren = trim($oren);
    if ($normalizedOren === '' || preg_match('/^\\d{2}$/u', $normalizedOren) !== 1) {
        return null;
    }

    $amount = normalize_swedish_amount($normalizedKronor . ',' . $normalizedOren);
    if (!is_float($amount)) {
        return null;
    }

    return number_format($amount, 2, '.', '');
}

function extraction_field_value_pattern_uses_semantic_amount_parts(string $pattern): bool
{
    return (
        str_contains($pattern, '{KRONOR}') && (str_contains($pattern, '{ÖREN}') || str_contains($pattern, '{OREN}'))
    ) || (
        preg_match('/\(\?<KRONOR>/u', $pattern) === 1
        && preg_match('/\(\?<(?:ÖREN|OREN)>/u', $pattern) === 1
    );
}

function extraction_field_match_group(array $match, array $names): ?array
{
    foreach ($names as $name) {
        if (!is_string($name) || $name === '' || !array_key_exists($name, $match)) {
            continue;
        }
        $group = $match[$name];
        if (!is_array($group) || !is_string($group[0] ?? null)) {
            continue;
        }

        $value = trim((string) $group[0]);
        if ($value !== '') {
            return [
                'value' => $value,
                'start' => is_int($group[1] ?? null) ? (int) $group[1] : -1,
                'length' => strlen((string) $group[0]),
            ];
        }
    }

    return null;
}

function extraction_field_semantic_amount_value_span(array $match, string $text): ?array
{
    $kronorGroup = extraction_field_match_group($match, ['docflow_kronor', 'KRONOR']);
    $orenGroup = extraction_field_match_group($match, ['docflow_oren', 'ÖREN', 'OREN']);
    if ($kronorGroup === null || $orenGroup === null) {
        return null;
    }

    $kronor = is_string($kronorGroup['value'] ?? null) ? (string) $kronorGroup['value'] : '';
    $oren = is_string($orenGroup['value'] ?? null) ? (string) $orenGroup['value'] : '';
    $combinedAmount = $kronor !== '' && $oren !== ''
        ? normalize_extraction_field_split_amount_value($kronor, $oren)
        : null;
    if ($combinedAmount === null) {
        return null;
    }

    $kronorStart = is_int($kronorGroup['start'] ?? null) ? (int) $kronorGroup['start'] : -1;
    $orenStart = is_int($orenGroup['start'] ?? null) ? (int) $orenGroup['start'] : -1;
    $kronorLength = is_int($kronorGroup['length'] ?? null) ? (int) $kronorGroup['length'] : 0;
    $orenLength = is_int($orenGroup['length'] ?? null) ? (int) $orenGroup['length'] : 0;
    if ($kronorStart < 0 || $orenStart < 0 || $kronorLength <= 0 || $orenLength <= 0) {
        return null;
    }

    $spanStart = min($kronorStart, $orenStart);
    $spanEnd = max($kronorStart + $kronorLength, $orenStart + $orenLength);
    if ($spanEnd <= $spanStart) {
        return null;
    }

    return [
        'value' => $combinedAmount,
        'raw' => substr($text, $spanStart, $spanEnd - $spanStart),
        'start' => $spanStart,
    ];
}

function extraction_field_match_has_semantic_amount_groups(array $match): bool
{
    $hasKronor = array_key_exists('docflow_kronor', $match) || array_key_exists('KRONOR', $match);
    $hasOren = array_key_exists('docflow_oren', $match) || array_key_exists('ÖREN', $match) || array_key_exists('OREN', $match);
    return $hasKronor && $hasOren;
}

function normalize_extraction_field_capture_group(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    if (!is_numeric($value)) {
        return null;
    }
    $group = (int) $value;
    return $group >= 0 ? $group : null;
}

function extraction_field_capture_group_value(array $match, int $group): ?string
{
    if ($group === 0) {
        $fullMatch = $match[0] ?? null;
        return is_array($fullMatch) && is_string($fullMatch[0] ?? null) ? (string) $fullMatch[0] : null;
    }

    $captureGroup = $match[$group] ?? null;
    if (!is_array($captureGroup) || !is_string($captureGroup[0] ?? null)) {
        return null;
    }

    $value = (string) $captureGroup[0];
    return $value !== '' ? $value : null;
}

function extraction_field_capture_group_ranges(array $match, ?array $selectedGroups = null): array
{
    $fullMatch = $match[0] ?? null;
    if (!is_array($fullMatch) || !is_string($fullMatch[0] ?? null) || !is_int($fullMatch[1] ?? null)) {
        return [];
    }

    $raw = (string) $fullMatch[0];
    $fullStart = (int) $fullMatch[1];
    if ($raw === '' || $fullStart < 0) {
        return [];
    }

    $captureIndexes = $selectedGroups !== null
        ? array_values(array_filter(array_map(
            static fn ($value): int => (int) $value,
            $selectedGroups
        ), static fn (int $value): bool => $value > 0))
        : [];
    if ($captureIndexes === []) {
        foreach ($match as $key => $value) {
            if (is_int($key) && $key > 0) {
                $captureIndexes[] = $key;
            }
        }
    }
    if ($captureIndexes === []) {
        return [];
    }

    $captureIndexes = array_values(array_unique($captureIndexes));
    sort($captureIndexes, SORT_NUMERIC);

    $ranges = [];
    $rawByteLength = strlen($raw);
    foreach ($captureIndexes as $captureIndex) {
        $captureGroup = $match[$captureIndex] ?? null;
        if (!is_array($captureGroup) || !is_string($captureGroup[0] ?? null) || !is_int($captureGroup[1] ?? null)) {
            continue;
        }

        $captureValue = (string) $captureGroup[0];
        $captureStart = (int) $captureGroup[1];
        if ($captureValue === '' || $captureStart < $fullStart) {
            continue;
        }

        $localByteStart = $captureStart - $fullStart;
        $captureByteLength = strlen($captureValue);
        $localByteEnd = $localByteStart + $captureByteLength;
        if ($localByteStart < 0 || $localByteEnd > $rawByteLength || $localByteEnd <= $localByteStart) {
            continue;
        }

        $charStart = utf8_strlen_safe(substr($raw, 0, $localByteStart));
        $charLength = utf8_strlen_safe(substr($raw, $localByteStart, $captureByteLength));
        if ($charLength <= 0) {
            continue;
        }

        $ranges[] = [
            'start' => $charStart,
            'end' => $charStart + $charLength,
        ];
    }

    return $ranges;
}

function extraction_field_match_selected_capture_value(array $match, array $captureSelection): ?array
{
    $captureGroup = normalize_extraction_field_capture_group($captureSelection['captureGroup'] ?? null);
    if ($captureGroup === null) {
        return null;
    }

    $value = extraction_field_capture_group_value($match, $captureGroup);
    if ($value === null) {
        return null;
    }
    $group = is_array($match[$captureGroup] ?? null) ? $match[$captureGroup] : null;
    $start = is_int($group[1] ?? null) ? (int) $group[1] : (is_int($match[0][1] ?? null) ? (int) $match[0][1] : -1);

    return [
        'value' => $value,
        'raw' => $value,
        'start' => $start,
        'ranges' => $captureGroup > 0 ? extraction_field_capture_group_ranges($match, [$captureGroup]) : [],
    ];
}

function extraction_field_match_selected_amount_value(array $match, array $captureSelection): ?array
{
    $wholeGroup = normalize_extraction_field_capture_group($captureSelection['amountWholeGroup'] ?? null);
    $fractionGroup = normalize_extraction_field_capture_group($captureSelection['amountFractionGroup'] ?? null);
    if ($wholeGroup === null || $fractionGroup === null) {
        return null;
    }

    $wholeValue = extraction_field_capture_group_value($match, $wholeGroup);
    $fractionValue = extraction_field_capture_group_value($match, $fractionGroup);
    if ($wholeValue === null || $fractionValue === null) {
        return null;
    }

    if ($wholeGroup === $fractionGroup) {
        $amount = normalize_swedish_amount($wholeValue);
        if (!is_float($amount)) {
            return null;
        }
        $group = is_array($match[$wholeGroup] ?? null) ? $match[$wholeGroup] : null;
        return [
            'value' => number_format($amount, 2, '.', ''),
            'raw' => $wholeValue,
            'start' => is_int($group[1] ?? null) ? (int) $group[1] : (is_int($match[0][1] ?? null) ? (int) $match[0][1] : -1),
            'ranges' => $wholeGroup > 0 ? extraction_field_capture_group_ranges($match, [$wholeGroup]) : [],
        ];
    }

    $amountValue = normalize_extraction_field_split_amount_value($wholeValue, $fractionValue);
    if ($amountValue === null) {
        return null;
    }

    $ranges = extraction_field_capture_group_ranges($match, [$wholeGroup, $fractionGroup]);
    $wholeSource = is_array($match[$wholeGroup] ?? null) ? $match[$wholeGroup] : null;
    $fractionSource = is_array($match[$fractionGroup] ?? null) ? $match[$fractionGroup] : null;
    $starts = array_values(array_filter([
        is_int($wholeSource[1] ?? null) ? (int) $wholeSource[1] : null,
        is_int($fractionSource[1] ?? null) ? (int) $fractionSource[1] : null,
    ], static fn ($value): bool => is_int($value) && $value >= 0));
    return [
        'value' => $amountValue,
        'raw' => extraction_field_raw_from_capture_ranges($match, $ranges) ?? ($wholeValue . $fractionValue),
        'start' => $starts !== [] ? min($starts) : (is_int($match[0][1] ?? null) ? (int) $match[0][1] : -1),
        'ranges' => $ranges,
    ];
}

function extraction_field_raw_from_capture_ranges(array $match, array $ranges): ?string
{
    $fullMatch = $match[0] ?? null;
    if (!is_array($fullMatch) || !is_string($fullMatch[0] ?? null) || $ranges === []) {
        return null;
    }
    $raw = (string) $fullMatch[0];
    $start = min(array_map(static fn (array $range): int => (int) ($range['start'] ?? 0), $ranges));
    $end = max(array_map(static fn (array $range): int => (int) ($range['end'] ?? 0), $ranges));
    if ($end <= $start) {
        return null;
    }
    $chars = preg_split('//u', $raw, -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($chars)) {
        return null;
    }
    return implode('', array_slice($chars, $start, $end - $start));
}

function extraction_field_runtime_rule_set(array $ruleSet, ?string $valueType = null, array $valuePatternsById = []): array
{
    $runtimeRuleSet = $ruleSet;
    $runtimeRuleSet['unboundedValuePatternSpan'] = normalize_extraction_field_unbounded_value_pattern_span($runtimeRuleSet);
    $resolvedValueType = normalize_extraction_field_value_type($valueType, null, $ruleSet);
    $valuePattern = is_string($runtimeRuleSet['valuePattern'] ?? null) ? trim((string) $runtimeRuleSet['valuePattern']) : '';
    $referencedPattern = resolve_reusable_value_pattern($runtimeRuleSet, $valuePatternsById);
    if ($referencedPattern !== null) {
        $valuePattern = reusable_value_pattern_regex_source($referencedPattern);
        $runtimeRuleSet['valuePattern'] = $valuePattern;
        $runtimeRuleSet['isRegex'] = true;
    } elseif (normalize_value_pattern_source($runtimeRuleSet['patternSource'] ?? null) === 'reference') {
        $runtimeRuleSet['valuePattern'] = '';
    }
    $usesValuePattern = normalize_extraction_field_use_value_pattern($runtimeRuleSet['useValuePattern'] ?? null, $valuePattern);

    if ($usesValuePattern && $valuePattern !== '') {
        $runtimeRuleSet['valuePattern'] = expand_extraction_field_value_pattern_macros($valuePattern);
    } elseif ($resolvedValueType === 'date') {
        $datePosition = extraction_field_rule_set_position($ruleSet, 'date');
        $runtimeRuleSet['valuePattern'] = extraction_field_date_value_pattern($datePosition);
        $runtimeRuleSet['useValuePattern'] = true;
        $runtimeRuleSet['captureGroup'] = 1;
    }

    return $runtimeRuleSet;
}

function normalize_extraction_field_type(mixed $value, ?array $legacyField = null, ?array $legacyRuleSet = null): string
{
    $normalized = is_string($value) ? trim(strtolower($value)) : '';
    if (in_array($normalized, ['regex', 'date', 'amount'], true)) {
        return $normalized;
    }

    $legacy = is_array($legacyField) ? $legacyField : [];
    $legacyRule = is_array($legacyRuleSet) ? $legacyRuleSet : [];
    $legacyKey = is_string($legacy['predefinedFieldKey'] ?? null)
        ? trim((string) $legacy['predefinedFieldKey'])
        : (is_string($legacy['key'] ?? null) ? trim((string) $legacy['key']) : '');
    $legacyExtractor = valid_extraction_field_extractor(
        is_string($legacy['extractor'] ?? null)
            ? (string) $legacy['extractor']
            : (is_string($legacyRule['extractor'] ?? null) ? (string) $legacyRule['extractor'] : 'generic_label')
    );

    if ($legacyKey === 'amount' || $legacyExtractor === 'amount') {
        return 'amount';
    }

    if (in_array($legacyKey, ['due_date', 'primary_date'], true) || in_array($legacyExtractor, ['due_date', 'primary_date'], true)) {
        return 'date';
    }

    return 'regex';
}

function normalize_extraction_field_position(mixed $value): string
{
    $normalized = is_string($value) ? trim(strtolower($value)) : '';
    return in_array($normalized, ['first', 'second', 'last'], true) ? $normalized : 'first';
}

function normalize_extraction_field_use_search_text(mixed $value, bool $fallback = true): bool
{
    if ($value === null) {
        return $fallback;
    }

    return $value === true || $value === 1 || $value === '1';
}

function default_extraction_field_span_building(): array
{
    return [
        'unboundedValuePatternSpan' => false,
    ];
}

function normalize_extraction_field_unbounded_value_pattern_span(mixed $input): bool
{
    $row = is_array($input) ? $input : [];
    $value = $row['unboundedValuePatternSpan'] ?? null;
    if ($value === null && is_array($row['spanBuilding'] ?? null)) {
        $value = $row['spanBuilding']['unboundedValuePatternSpan'] ?? null;
    }

    return $value === true || $value === 1 || $value === '1';
}

function default_extraction_field_rule_set(array $overrides = []): array
{
    $defaults = [
        'useSearchText' => true,
        'requiresSearchTerms' => true,
        'searchTerms' => [],
        'isRegex' => false,
        'useValuePattern' => false,
        'patternSource' => 'manual',
        'valuePatternId' => '',
        'valuePattern' => '',
        'normalizationType' => 'none',
        'normalizationChars' => '',
        'normalizationReplacements' => [],
        'datePosition' => 'first',
        'amountPosition' => 'first',
        'captureGroup' => null,
        'amountWholeGroup' => null,
        'amountFractionGroup' => null,
        'scope' => null,
        'unboundedValuePatternSpan' => false,
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

        $requiresSearchTerms = array_key_exists('useSearchText', $row)
            ? normalize_extraction_field_use_search_text($row['useSearchText'] ?? null, true)
            : (
                !array_key_exists('requiresSearchTerms', $row)
                || $row['requiresSearchTerms'] === true
                || $row['requiresSearchTerms'] === 1
                || $row['requiresSearchTerms'] === '1'
            );
        $legacyIsRegex = normalize_extraction_field_is_regex($row['isRegex'] ?? false);
        $searchTerms = normalize_extraction_field_search_terms($row['searchTerms'] ?? null, $legacyIsRegex);
        $datePosition = normalize_extraction_field_position($row['datePosition'] ?? null);
        $scope = normalize_extraction_field_rule_scope($row['scope'] ?? null);
        $valuePattern = is_string($row['valuePattern'] ?? null)
            ? trim((string) $row['valuePattern'])
            : (
                is_string($row['searchString'] ?? null)
                    ? trim((string) $row['searchString'])
                    : (is_string($row['query'] ?? null) ? trim((string) $row['query']) : '')
            );

        $normalized[] = default_extraction_field_rule_set([
            'useSearchText' => $requiresSearchTerms,
            'requiresSearchTerms' => $requiresSearchTerms,
            'searchTerms' => $searchTerms,
            'isRegex' => false,
            'useValuePattern' => normalize_extraction_field_use_value_pattern($row['useValuePattern'] ?? null, $valuePattern),
            'patternSource' => normalize_value_pattern_source($row['patternSource'] ?? null),
            'valuePatternId' => is_string($row['valuePatternId'] ?? null) ? trim((string) $row['valuePatternId']) : '',
            'valuePattern' => $valuePattern,
            'normalizationType' => normalize_extraction_field_normalization_type($row['normalizationType'] ?? null),
            'normalizationChars' => normalize_extraction_field_normalization_chars($row['normalizationChars'] ?? null),
            'normalizationReplacements' => normalize_extraction_field_normalization_replacements($row['normalizationReplacements'] ?? null),
            'datePosition' => $datePosition,
            'amountPosition' => normalize_extraction_field_position($row['amountPosition'] ?? null),
            'captureGroup' => normalize_extraction_field_capture_group($row['captureGroup'] ?? null),
            'amountWholeGroup' => normalize_extraction_field_capture_group($row['amountWholeGroup'] ?? null),
            'amountFractionGroup' => normalize_extraction_field_capture_group($row['amountFractionGroup'] ?? null),
            'scope' => $scope,
            'unboundedValuePatternSpan' => normalize_extraction_field_unbounded_value_pattern_span($row),
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
    $normalizationType = normalize_extraction_field_normalization_type($legacy['normalizationType'] ?? null);
    $normalizationReplacements = normalize_extraction_field_normalization_replacements($legacy['normalizationReplacements'] ?? null);
    if ($legacyPattern !== '' || $legacyAliases !== [] || $legacy !== []) {
        $datePosition = normalize_extraction_field_position($legacy['datePosition'] ?? null);
        return [default_extraction_field_rule_set([
            'useSearchText' => $requiresSearchTerms,
            'requiresSearchTerms' => $requiresSearchTerms,
            'searchTerms' => $requiresSearchTerms ? $legacyAliases : [],
            'isRegex' => false,
            'useValuePattern' => $legacyPattern !== '',
            'patternSource' => 'manual',
            'valuePatternId' => '',
            'valuePattern' => $legacyPattern,
            'normalizationType' => $normalizationType,
            'normalizationChars' => normalize_extraction_field_normalization_chars($legacy['normalizationChars'] ?? null),
            'normalizationReplacements' => $normalizationReplacements,
            'datePosition' => $datePosition,
            'amountPosition' => normalize_extraction_field_position($legacy['amountPosition'] ?? null),
            'scope' => null,
            'unboundedValuePatternSpan' => false,
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
        $legacyFirstRuleSet = is_array($row['ruleSets'][0] ?? null) ? $row['ruleSets'][0] : [];
        $valueType = normalize_extraction_field_value_type($row['valueType'] ?? null, $row, $legacyFirstRuleSet);
        $fieldForRuleSets = array_merge($row, ['valueType' => $valueType]);
        $ruleSets = normalize_extraction_field_rule_sets($row['ruleSets'] ?? null, $fieldForRuleSets);

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
            'type' => legacy_extraction_field_type_for_value_type($valueType),
            'valueType' => $valueType,
            'normalizationType' => $valueType === 'text' ? normalize_extraction_field_normalization_type($row['normalizationType'] ?? null) : 'none',
            'normalizationChars' => $valueType === 'text' ? normalize_extraction_field_normalization_chars($row['normalizationChars'] ?? null) : '',
            'normalizationReplacements' => $valueType === 'text' ? normalize_extraction_field_normalization_replacements($row['normalizationReplacements'] ?? null) : [],
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

    $legacyFirstRuleSet = is_array($field['ruleSets'][0] ?? null)
        ? $field['ruleSets'][0]
        : (is_array($defaults['ruleSets'][0] ?? null) ? $defaults['ruleSets'][0] : []);
    $valueType = normalize_extraction_field_value_type($field['valueType'] ?? ($defaults['valueType'] ?? null), $field ?: $defaults, $legacyFirstRuleSet);
    $fieldForRuleSets = array_merge($field, ['valueType' => $valueType]);
    $ruleSets = array_key_exists('ruleSets', $field)
        ? normalize_extraction_field_rule_sets($field['ruleSets'] ?? null, $fieldForRuleSets)
        : normalize_extraction_field_rule_sets(null, $fieldForRuleSets);
    $defaultRuleSets = predefined_extraction_field_default_rule_sets($defaults);
    $ruleSets = $ruleSets !== [] ? $ruleSets : $defaultRuleSets;
        if (!array_key_exists('ruleSets', $field)) {
            $hasLegacyOverrides = array_key_exists('aliases', $field)
                || array_key_exists('searchString', $field)
                || array_key_exists('query', $field)
                || array_key_exists('normalizationType', $field)
                || array_key_exists('normalizationChars', $field)
                || array_key_exists('normalizationReplacements', $field);
            if (!$hasLegacyOverrides) {
                $ruleSets = $defaultRuleSets;
            }
    }
    if ($key === 'amount') {
        $ruleSets = array_map('normalize_predefined_amount_rule_set', $ruleSets);
    }
    if ($key === 'bankgiro' && !array_key_exists('ruleSets', $field)) {
        $ruleSets = $defaultRuleSets;
    }
    $normalizationType = $valueType === 'text'
        ? normalize_extraction_field_normalization_type($field['normalizationType'] ?? ($defaults['normalizationType'] ?? null))
        : 'none';
    $normalizationChars = $valueType === 'text'
        ? normalize_extraction_field_normalization_chars($field['normalizationChars'] ?? ($defaults['normalizationChars'] ?? null))
        : '';
    $normalizationReplacements = $valueType === 'text'
        ? normalize_extraction_field_normalization_replacements($field['normalizationReplacements'] ?? ($defaults['normalizationReplacements'] ?? null))
        : [];
    if ($key === 'bankgiro') {
        $normalizationType = 'whitelist';
        $normalizationChars = '0123456789';
        $normalizationReplacements = [];
    }

    return [
        'key' => $key,
        'name' => $name,
        'type' => legacy_extraction_field_type_for_value_type($valueType),
        'valueType' => $valueType,
        'normalizationType' => $normalizationType,
        'normalizationChars' => $normalizationChars,
        'normalizationReplacements' => $normalizationReplacements,
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
    $valueType = normalize_extraction_field_value_type(
        array_key_exists('valueType', $field) ? $field['valueType'] : ($defaults['valueType'] ?? null),
        $field ?: $defaults,
        ['type' => $field['type'] ?? ($defaults['type'] ?? null)]
    );

    $normalized = [
        'key' => $key,
        'name' => $name,
        'type' => legacy_extraction_field_type_for_value_type($valueType),
        'valueType' => $valueType,
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
        'normalizationReplacements' => normalize_extraction_field_normalization_replacements(
            array_key_exists('normalizationReplacements', $field)
                ? $field['normalizationReplacements']
                : ($defaults['normalizationReplacements'] ?? [])
        ),
        'extractor' => valid_extraction_field_extractor(
            is_string($field['extractor'] ?? null) ? (string) $field['extractor'] : (string) ($defaults['extractor'] ?? 'generic_label'),
            valid_extraction_field_extractor((string) ($defaults['extractor'] ?? 'generic_label'))
        ),
        'systemFieldKey' => $key,
        'isSystemField' => true,
    ];
    if ($key === 'primary_date') {
        $normalized['primaryDateHeuristics'] = normalize_primary_date_heuristics(
            array_key_exists('primaryDateHeuristics', $field)
                ? $field['primaryDateHeuristics']
                : ($defaults['primaryDateHeuristics'] ?? null)
        );
    }
    if ($key === 'title') {
        $normalized['titleHeuristics'] = normalize_title_heuristics(
            array_key_exists('titleHeuristics', $field)
                ? $field['titleHeuristics']
                : ($defaults['titleHeuristics'] ?? null)
        );
    }
    return $normalized;
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

function known_job_extraction_field_keys(): array
{
    $known = [];
    $rules = load_active_archiving_rules();
    foreach (array_merge(
        is_array($rules['predefinedFields'] ?? null) ? $rules['predefinedFields'] : [],
        is_array($rules['systemFields'] ?? null) ? $rules['systemFields'] : [],
        is_array($rules['fields'] ?? null) ? $rules['fields'] : []
    ) as $field) {
        if (!is_array($field)) {
            continue;
        }
        $fieldKey = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        if ($fieldKey !== '') {
            $known[$fieldKey] = true;
        }
    }

    return $known;
}

function normalize_job_extraction_field_value_list(mixed $value): array
{
    $items = [];
    if (is_array($value)) {
        if (array_key_exists('values', $value) && is_array($value['values'])) {
            $items = array_values($value['values']);
        } elseif (array_key_exists('manualValues', $value) && is_array($value['manualValues'])) {
            $items = array_values($value['manualValues']);
        } elseif (array_key_exists('value', $value)) {
            $items = [$value['value']];
        } else {
            $items = array_values($value);
        }
    } else {
        $items = [$value];
    }

    $resolved = [];
    $seen = [];
    foreach ($items as $item) {
        $text = is_scalar($item) ? trim((string) $item) : '';
        if ($text === '' || isset($seen[$text])) {
            continue;
        }
        $seen[$text] = true;
        $resolved[] = $text;
    }

    return $resolved;
}

function normalize_job_extraction_field_selection(mixed $value): ?array
{
    if (!is_array($value)) {
        return null;
    }

    $manualValues = normalize_job_extraction_field_value_list($value['manualValues'] ?? []);
    $excludedValues = normalize_job_extraction_field_value_list($value['excludedValues'] ?? []);
    $primaryValue = is_string($value['primaryValue'] ?? null)
        ? trim((string) $value['primaryValue'])
        : (is_scalar($value['primaryValue'] ?? null) ? trim((string) $value['primaryValue']) : '');

    if ($manualValues === [] && $excludedValues === [] && $primaryValue === '') {
        return null;
    }

    return [
        'manualValues' => $manualValues,
        'excludedValues' => $excludedValues,
        'primaryValue' => $primaryValue !== '' ? $primaryValue : null,
    ];
}

function normalize_selected_job_extraction_field_values_payload(mixed $value, array $knownFieldKeys): array
{
    if (!is_array($value)) {
        throw new RuntimeException('Ogiltiga datafält');
    }

    $resolved = [];
    foreach ($value as $fieldKey => $entry) {
        $normalizedFieldKey = is_string($fieldKey) ? trim((string) $fieldKey) : '';
        if ($normalizedFieldKey === '' || !isset($knownFieldKeys[$normalizedFieldKey])) {
            continue;
        }
        $selection = normalize_job_extraction_field_selection($entry);
        if ($selection === null) {
            continue;
        }
        $resolved[$normalizedFieldKey] = $selection;
    }

    ksort($resolved, SORT_NATURAL);
    return $resolved;
}

function normalize_stored_job_extraction_field_values(mixed $value): array
{
    if (!is_array($value)) {
        return [];
    }

    $knownFieldKeys = known_job_extraction_field_keys();
    $resolved = [];
    foreach ($value as $fieldKey => $entry) {
        $normalizedFieldKey = is_string($fieldKey) ? trim((string) $fieldKey) : '';
        if ($normalizedFieldKey === '') {
            continue;
        }
        $selection = normalize_job_extraction_field_selection($entry);
        if ($selection === null) {
            continue;
        }
        if ($knownFieldKeys !== [] && !isset($knownFieldKeys[$normalizedFieldKey])) {
            continue;
        }
        $resolved[$normalizedFieldKey] = $selection;
    }

    ksort($resolved, SORT_NATURAL);
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

function normalize_archiving_zone(mixed $input, int $fallbackIndex = 0): ?array
{
    if (!is_array($input)) {
        return null;
    }

    $name = is_string($input['name'] ?? null) ? trim((string) $input['name']) : '';
    $pattern = is_string($input['pattern'] ?? null) ? trim((string) $input['pattern']) : '';
    $patternSource = normalize_value_pattern_source($input['patternSource'] ?? null);
    $valuePatternId = is_string($input['valuePatternId'] ?? null) ? trim((string) $input['valuePatternId']) : '';
    if ($name === '' || ($patternSource === 'manual' && $pattern === '') || ($patternSource === 'reference' && $valuePatternId === '')) {
        return null;
    }

    $id = is_string($input['id'] ?? null) ? trim((string) $input['id']) : '';
    if ($id === '') {
        $id = normalize_config_key($name !== '' ? $name : ('zone_' . ($fallbackIndex + 1)));
    }
    if ($id === '') {
        $id = 'zone_' . ($fallbackIndex + 1);
    }

    return [
        'id' => $id,
        'name' => $name,
        'enabled' => ($input['enabled'] ?? true) !== false,
        'pattern' => $pattern,
        'isRegex' => true,
        'patternSource' => $patternSource,
        'valuePatternId' => $patternSource === 'reference' ? $valuePatternId : '',
    ];
}

function normalize_archiving_zones(mixed $input): array
{
    $rows = is_array($input) ? $input : [];
    $zones = [];
    $seen = [];
    foreach ($rows as $index => $row) {
        $zone = normalize_archiving_zone($row, is_int($index) ? $index : count($zones));
        if ($zone === null) {
            continue;
        }
        $baseId = $zone['id'];
        $id = $baseId;
        $suffix = 2;
        while (isset($seen[$id])) {
            $id = $baseId . '_' . $suffix;
            $suffix++;
        }
        $seen[$id] = true;
        $zone['id'] = $id;
        $zones[] = $zone;
    }

    return $zones;
}

function normalize_value_pattern_source(mixed $value): string
{
    return is_string($value) && trim(strtolower($value)) === 'reference' ? 'reference' : 'manual';
}

function normalize_value_pattern_definition(mixed $input, int $fallbackIndex = 0): ?array
{
    if (!is_array($input)) {
        return null;
    }
    $name = is_string($input['name'] ?? null) ? trim((string) $input['name']) : '';
    $pattern = is_string($input['pattern'] ?? null) ? trim((string) $input['pattern']) : '';
    if ($name === '' || $pattern === '') {
        return null;
    }
    $id = is_string($input['id'] ?? null) ? trim((string) $input['id']) : '';
    if ($id === '') {
        $id = normalize_config_key($name !== '' ? $name : ('value_pattern_' . ($fallbackIndex + 1)));
    }
    if ($id === '') {
        $id = 'value_pattern_' . ($fallbackIndex + 1);
    }
    return [
        'id' => $id,
        'name' => $name,
        'pattern' => $pattern,
        'isRegex' => ($input['isRegex'] ?? true) !== false,
        'enabled' => true,
        'description' => is_string($input['description'] ?? null) ? trim((string) $input['description']) : '',
    ];
}

function normalize_value_pattern_definitions(mixed $input): array
{
    $rows = is_array($input) ? $input : [];
    $patterns = [];
    $seen = [];
    foreach ($rows as $index => $row) {
        $pattern = normalize_value_pattern_definition($row, is_int($index) ? $index : count($patterns));
        if ($pattern === null) {
            continue;
        }
        $baseId = $pattern['id'];
        $id = $baseId;
        $suffix = 2;
        while (isset($seen[$id])) {
            $id = $baseId . '_' . $suffix;
            $suffix++;
        }
        $seen[$id] = true;
        $pattern['id'] = $id;
        $patterns[] = $pattern;
    }
    return $patterns;
}

function value_pattern_definitions_by_id(array $patterns): array
{
    $byId = [];
    foreach ($patterns as $pattern) {
        if (!is_array($pattern)) {
            continue;
        }
        $id = is_string($pattern['id'] ?? null) ? trim((string) $pattern['id']) : '';
        if ($id !== '') {
            $byId[$id] = $pattern;
        }
    }
    return $byId;
}

function resolve_reusable_value_pattern(array $owner, array $valuePatternsById): ?array
{
    if (normalize_value_pattern_source($owner['patternSource'] ?? null) !== 'reference') {
        return null;
    }
    $id = is_string($owner['valuePatternId'] ?? null) ? trim((string) $owner['valuePatternId']) : '';
    if ($id === '' || !is_array($valuePatternsById[$id] ?? null)) {
        return null;
    }
    $pattern = $valuePatternsById[$id];
    if (($pattern['enabled'] ?? true) === false) {
        return null;
    }
    $text = is_string($pattern['pattern'] ?? null) ? trim((string) $pattern['pattern']) : '';
    if ($text === '') {
        return null;
    }
    return $pattern;
}

function reusable_value_pattern_regex_source(array $pattern): string
{
    $text = is_string($pattern['pattern'] ?? null) ? trim((string) $pattern['pattern']) : '';
    if (($pattern['isRegex'] ?? true) === false) {
        return literal_pattern_with_whitespace_wildcards($text, '/');
    }
    return $text;
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
        'valuePatterns' => normalize_value_pattern_definitions($decoded['valuePatterns'] ?? []),
        'labels' => normalize_labels(
            is_array($decoded['labels'] ?? null) ? $decoded['labels'] : []
        ),
        'systemLabels' => normalize_system_labels($decoded['systemLabels'] ?? []),
        'fields' => $remainingFields,
        'predefinedFields' => $resolvedPredefinedFields,
        'systemFields' => normalize_system_extraction_fields(
            is_array($decoded['systemFields'] ?? null) ? $decoded['systemFields'] : []
        ),
        'zones' => normalize_archiving_zones(
            is_array($decoded['zones'] ?? null) ? $decoded['zones'] : []
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
        'zones' => [],
        'valuePatterns' => [],
    ]);
    $active = array_key_exists('activeArchivingRules', $decoded)
        ? normalize_archiving_rules_set($decoded['activeArchivingRules'])
        : (
            array_key_exists('draftArchivingRules', $decoded)
                ? normalize_archiving_rules_set($decoded['draftArchivingRules'])
                : $defaults
        );
    $version = filter_var($decoded['activeArchivingRulesVersion'] ?? null, FILTER_VALIDATE_INT);
    if ($version === false || $version === null || $version < 1) {
        $version = 1;
    }

    return [
        'activeArchivingRulesVersion' => (int) $version,
        'activeArchivingRules' => $active,
        // Keep the legacy key aligned for compatibility with older persistence helpers,
        // but runtime no longer treats it as a separate ruleset.
        'draftArchivingRules' => $active,
    ];
}

function hydrate_archiving_rules_state_from_field_repository(
    array $state,
    \Docflow\Archiving\ExtractionFieldRepository $repository
): array {
    $activeFields = $repository->loadScope('active');

    $state['activeArchivingRules']['fields'] = is_array($activeFields['fields'] ?? null) ? $activeFields['fields'] : [];
    $state['activeArchivingRules']['predefinedFields'] = is_array($activeFields['predefinedFields'] ?? null) ? $activeFields['predefinedFields'] : [];

    $state['activeArchivingRules'] = normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);
    $state['draftArchivingRules'] = $state['activeArchivingRules'];

    return $state;
}

function hydrate_archiving_rules_state_from_zone_repository(
    array $state,
    \Docflow\Archiving\ZoneRepository $repository
): array {
    $activeRules = normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);

    if (!$repository->hasAnyRows()) {
        $repository->replaceScopes(
            is_array($activeRules['zones'] ?? null) ? $activeRules['zones'] : [],
            is_array($activeRules['zones'] ?? null) ? $activeRules['zones'] : []
        );
    }

    $activeRules['zones'] = normalize_archiving_zones($repository->loadScope('active'));
    $state['activeArchivingRules'] = normalize_archiving_rules_set($activeRules);
    $state['draftArchivingRules'] = $state['activeArchivingRules'];

    return $state;
}

function hydrate_archiving_rules_state_from_label_repository(
    array $state,
    \Docflow\Archiving\LabelRepository $repository
): array {
    $activeRules = normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);

    if (!$repository->hasAnyRows()) {
        $repository->replaceAll(
            is_array($activeRules['labels'] ?? null) ? $activeRules['labels'] : [],
            is_array($activeRules['systemLabels'] ?? null) ? $activeRules['systemLabels'] : system_labels_template()
        );
    }

    $labelsPayload = $repository->loadAll();
    $activeRules['labels'] = normalize_labels($labelsPayload['labels'] ?? []);
    $activeRules['systemLabels'] = normalize_system_labels($labelsPayload['systemLabels'] ?? []);

    $state['activeArchivingRules'] = normalize_archiving_rules_set($activeRules);
    $state['draftArchivingRules'] = $state['activeArchivingRules'];

    return $state;
}

function archiving_rules_state_without_normalized_tables(array $state, bool $stripLabels, bool $stripFields, bool $stripZones = false): array
{
    $normalized = normalize_archiving_rules_state($state);
    foreach (['activeArchivingRules', 'draftArchivingRules'] as $rulesKey) {
        if (!is_array($normalized[$rulesKey] ?? null)) {
            continue;
        }
        if ($stripLabels) {
            $normalized[$rulesKey]['labels'] = [];
            $normalized[$rulesKey]['systemLabels'] = [];
        }
        if ($stripFields) {
            $normalized[$rulesKey]['fields'] = [];
            $normalized[$rulesKey]['predefinedFields'] = [];
        }
        if ($stripZones) {
            $normalized[$rulesKey]['zones'] = [];
        }
    }

    return $normalized;
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
    $labelRepository = label_repository_instance();
    if ($labelRepository !== null) {
        try {
            $normalized = hydrate_archiving_rules_state_from_label_repository($normalized, $labelRepository);
        } catch (Throwable $e) {
            // Fall back to the inline JSON state if the dedicated label table is unavailable.
        }
    }
    $fieldRepository = extraction_field_repository_instance();
    if ($fieldRepository !== null) {
        try {
            if (!$fieldRepository->hasAnyRows()) {
                $fieldRepository->replaceScopes(
                    is_array($normalized['activeArchivingRules'] ?? null) ? $normalized['activeArchivingRules'] : [],
                    is_array($normalized['activeArchivingRules'] ?? null) ? $normalized['activeArchivingRules'] : []
                );
            }
            $normalized = hydrate_archiving_rules_state_from_field_repository($normalized, $fieldRepository);
        } catch (Throwable $e) {
            // Fall back to the inline JSON state if the dedicated rule-set tables are unavailable.
        }
    }
    $zoneRepository = zone_repository_instance();
    if ($zoneRepository !== null) {
        try {
            $normalized = hydrate_archiving_rules_state_from_zone_repository($normalized, $zoneRepository);
        } catch (Throwable $e) {
            // Fall back to inline JSON state if the dedicated zone table is unavailable.
        }
    }
    return $normalized;
}

function save_archiving_rules_state(array $state): array
{
    $normalized = normalize_archiving_rules_state($state);
    $labelRepository = label_repository_instance();
    $labelsPersistedInRepository = false;
    if ($labelRepository !== null) {
        try {
            $labelRepository->replaceAll(
                is_array($normalized['activeArchivingRules']['labels'] ?? null) ? $normalized['activeArchivingRules']['labels'] : [],
                is_array($normalized['activeArchivingRules']['systemLabels'] ?? null) ? $normalized['activeArchivingRules']['systemLabels'] : system_labels_template()
            );
            $normalized = hydrate_archiving_rules_state_from_label_repository($normalized, $labelRepository);
            $labelsPersistedInRepository = true;
        } catch (Throwable $e) {
            // Keep inline JSON persistence working even if the dedicated label table is temporarily unavailable.
        }
    }
    $fieldRepository = extraction_field_repository_instance();
    $fieldsPersistedInRepository = false;
    if ($fieldRepository !== null) {
        try {
            $fieldRepository->replaceScopes(
                is_array($normalized['activeArchivingRules'] ?? null) ? $normalized['activeArchivingRules'] : [],
                is_array($normalized['activeArchivingRules'] ?? null) ? $normalized['activeArchivingRules'] : []
            );
            $normalized = hydrate_archiving_rules_state_from_field_repository($normalized, $fieldRepository);
            $fieldsPersistedInRepository = true;
        } catch (Throwable $e) {
            // Keep inline JSON persistence working even if the dedicated field tables are temporarily unavailable.
        }
    }
    $zoneRepository = zone_repository_instance();
    $zonesPersistedInRepository = false;
    if ($zoneRepository !== null) {
        try {
            $activeZones = is_array($normalized['activeArchivingRules']['zones'] ?? null) ? $normalized['activeArchivingRules']['zones'] : [];
            $zoneRepository->replaceScopes($activeZones, $activeZones);
            $normalized = hydrate_archiving_rules_state_from_zone_repository($normalized, $zoneRepository);
            $zonesPersistedInRepository = true;
        } catch (Throwable $e) {
            // Keep inline JSON persistence working even if the dedicated zone table is temporarily unavailable.
        }
    }
    $repository = archiving_rules_state_repository_instance();
    if ($repository === null) {
        throw new RuntimeException('Archiving rules state repository is unavailable.');
    }

    $storedState = archiving_rules_state_without_normalized_tables($normalized, $labelsPersistedInRepository, $fieldsPersistedInRepository, $zonesPersistedInRepository);
    $repository->replaceState(
        (int) ($storedState['activeArchivingRulesVersion'] ?? 1),
        is_array($storedState['activeArchivingRules'] ?? null) ? $storedState['activeArchivingRules'] : [],
        is_array($storedState['activeArchivingRules'] ?? null) ? $storedState['activeArchivingRules'] : []
    );
    return $normalized;
}

function load_active_archiving_rules(): array
{
    $state = load_archiving_rules_state();
    return normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);
}

function active_archiving_rules_version(): int
{
    $state = load_archiving_rules_state();
    return (int) ($state['activeArchivingRulesVersion'] ?? 1);
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

function persist_active_archiving_rules_change(
    array $config,
    array $nextActiveRules,
    array $options = []
): array {
    $state = load_archiving_rules_state();
    $previousActiveRules = normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);
    $normalizedNextRules = normalize_archiving_rules_set($nextActiveRules);
    $reviewRelevantChanged = archiving_rules_review_relevant_hash($previousActiveRules) !== archiving_rules_review_relevant_hash($normalizedNextRules);
    $changedSections = $reviewRelevantChanged
        ? archiving_rules_changed_sections($previousActiveRules, $normalizedNextRules)
        : [];
    $templateChanges = $reviewRelevantChanged
        ? archiving_rules_filename_template_changes($previousActiveRules, $normalizedNextRules)
        : [];
    $nextVersion = (int) ($state['activeArchivingRulesVersion'] ?? 1);
    if ($reviewRelevantChanged) {
        $nextVersion += 1;
    }

    $state['activeArchivingRulesVersion'] = max(1, $nextVersion);
    $state['activeArchivingRules'] = $normalizedNextRules;
    $state['draftArchivingRules'] = $normalizedNextRules;
    $stored = save_archiving_rules_state($state);

    $reprocessedJobs = [
        'reprocessedJobIds' => [],
        'reprocessedCount' => 0,
    ];
    if ($reviewRelevantChanged) {
        restart_archiving_update_session(
            $config,
            $previousActiveRules,
            $normalizedNextRules,
            (int) ($stored['activeArchivingRulesVersion'] ?? $nextVersion),
            [
                'reason' => is_string($options['reason'] ?? null) ? trim((string) $options['reason']) : 'rules',
                'changedSections' => $changedSections,
                'templateChanges' => $templateChanges,
            ]
        );
        if (($options['reprocessImmediately'] ?? false) === true) {
            $reprocessedJobs = reprocess_unarchived_jobs_for_active_archiving_rules($config, $previousActiveRules, $normalizedNextRules);
            advance_archiving_update_session($config, 20);
        } else {
            trigger_reanalyze_all_documents_worker();
        }
    }

    maybe_queue_archiving_rules_update_event($config);

    return [
        'stored' => $stored,
        'reviewRelevantChanged' => $reviewRelevantChanged,
        'changedSections' => $changedSections,
        'templateChanges' => $templateChanges,
        'reprocessedJobs' => $reprocessedJobs,
    ];
}

function bump_active_archiving_rules_version(array $config, string $reason = 'manual'): array
{
    $state = load_archiving_rules_state();
    $activeRules = normalize_archiving_rules_set($state['activeArchivingRules'] ?? []);
    $state['activeArchivingRulesVersion'] = max(1, ((int) ($state['activeArchivingRulesVersion'] ?? 1)) + 1);
    $state['draftArchivingRules'] = $activeRules;
    $stored = save_archiving_rules_state($state);

    restart_archiving_update_session(
        $config,
        $activeRules,
        $activeRules,
        (int) ($stored['activeArchivingRulesVersion'] ?? 1),
        [
            'reason' => $reason !== '' ? $reason : 'manual',
            'changedSections' => [],
            'templateChanges' => [],
        ]
    );
    advance_archiving_update_session($config, 20);
    maybe_queue_archiving_rules_update_event($config);

    return [
        'activeArchivingRulesVersion' => (int) ($stored['activeArchivingRulesVersion'] ?? 1),
        'reprocessedJobs' => [
            'reprocessedJobIds' => [],
            'reprocessedCount' => 0,
        ],
    ];
}

function reanalyze_all_documents(array $config, ?array $jobIds = null, string $mode = 'post-ocr', bool $forceOcr = false): array
{
    ensure_job_dispatcher_running($config);

    $reprocessedJobs = is_array($jobIds)
        ? reprocess_job_ids_for_analysis_change($config, $jobIds, $mode, $forceOcr)
        : reprocess_unarchived_jobs_for_analysis_change($config, 'post-ocr', false);

    $activeRules = load_active_archiving_rules();
    $activeVersion = active_archiving_rules_version();
    $currentState = load_archiving_rules_review_state();
    $currentSession = is_array($currentState['updateSession'] ?? null)
        ? $currentState['updateSession']
        : empty_archiving_update_session();

    restart_archiving_update_session($config, $activeRules, $activeRules, $activeVersion, [
        'reason' => is_string($currentSession['reason'] ?? null) ? (string) $currentSession['reason'] : 'manual-reanalysis',
        'changedSections' => is_array($currentSession['changedSections'] ?? null) ? $currentSession['changedSections'] : [],
        'templateChanges' => is_array($currentSession['templateChanges'] ?? null) ? $currentSession['templateChanges'] : [],
        'ignoreDismissed' => false,
    ]);
    collect_archiving_update_review($config, 500);

    return [
        'ok' => true,
        'archivingRules' => build_archiving_rules_state_payload($config),
        'reprocessedJobs' => $reprocessedJobs,
        'lastEventId' => latest_job_event_id(),
    ];
}

function archiving_rules_changed_sections(array $active, array $draft): array
{
    $changedSections = [];
    foreach ([
        'archiveFolders' => 'Arkivstruktur',
        'valuePatterns' => 'Värdemönster',
        'labels' => 'Etiketter',
        'systemLabels' => 'Fördefinierade etiketter',
        'fields' => 'Egna datafält',
        'predefinedFields' => 'Fördefinierade datafält',
        'systemFields' => 'Systemdatafält',
        'zones' => 'Zoner',
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
        $dateFormat = normalize_filename_template_date_format($part['dateFormat'] ?? null);
        if ($dateFormat !== '') {
            $label .= ':' . $dateFormat;
        }
    } elseif ($type === 'folder') {
        $label = 'Mapp (legacy)';
    } elseif ($type === 'labels') {
        $separator = is_string($part['separator'] ?? null) ? (string) $part['separator'] : ', ';
        $label = $separator === ', '
            ? 'Etiketter'
            : sprintf('Etiketter (separator: %s)', $separator);
    } elseif ($type === 'ifLabels') {
        $resolvedLabelNames = [];
        foreach (normalize_label_id_list($part['labelIds'] ?? null) as $labelId) {
            $resolvedLabelNames[] = $nameMaps['label'][$labelId] ?? $labelId;
        }
        $modeLabel = normalize_if_labels_mode($part['mode'] ?? null) === 'all' ? 'alla etiketter' : 'någon etikett';
        $thenLabel = trim(filename_template_parts_review_label(
            is_array($part['thenParts'] ?? null) ? $part['thenParts'] : [],
            $nameMaps
        ));
        $elseLabel = trim(filename_template_parts_review_label(
            is_array($part['elseParts'] ?? null) ? $part['elseParts'] : [],
            $nameMaps
        ));
        $label = 'Om etikett (' . $modeLabel . ')';
        if ($resolvedLabelNames !== []) {
            $label .= ': ' . implode(' / ', array_filter($resolvedLabelNames, static fn ($value): bool => is_string($value) && trim($value) !== ''));
        }
        if ($thenLabel !== '') {
            $label .= ' ? ' . $thenLabel;
        }
        if ($elseLabel !== '') {
            $label .= ' : ' . $elseLabel;
        }
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
    $labelNames = [];
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
        foreach (array_merge(
            array_values(array_filter(is_array($rules['systemLabels'] ?? null) ? $rules['systemLabels'] : [], static fn ($label): bool => is_array($label))),
            array_values(array_filter(is_array($rules['labels'] ?? null) ? $rules['labels'] : [], static fn ($label): bool => is_array($label)))
        ) as $label) {
            $id = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
            $name = is_string($label['name'] ?? null) ? trim((string) $label['name']) : '';
            if ($id !== '' && $name !== '' && !isset($labelNames[$id])) {
                $labelNames[$id] = $name;
            }
        }
    }

    foreach ([
        'bankgiro_name' => 'Bankgiro-namn',
        'plusgiro_name' => 'Plusgiro-namn',
        'organization_number_name' => 'Org.nr.-namn',
    ] as $key => $name) {
        if (!isset($systemFieldNames[$key])) {
            $systemFieldNames[$key] = $name;
        }
    }

    return [
        'dataField' => $dataFieldNames,
        'systemField' => $systemFieldNames,
        'label' => $labelNames,
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

function build_archiving_rules_state_payload(array $config, ?int $pendingArchivedUpdateCount = null): array
{
    $rulesState = load_archiving_rules_state();
    $reviewState = load_archiving_rules_review_state();
    $activeRules = normalize_archiving_rules_set($rulesState['activeArchivingRules'] ?? []);
    $activeVersion = (int) ($rulesState['activeArchivingRulesVersion'] ?? 1);
    $updateSession = is_array($reviewState['updateSession'] ?? null) ? $reviewState['updateSession'] : empty_archiving_update_session();
    $jobIds = archived_job_ids($config);
    if (archiving_update_session_is_current($updateSession, $activeVersion, $activeRules, $jobIds)) {
        archiving_review_session_sync_job_ids($updateSession, $jobIds);
    } else {
        $updateSession = empty_archiving_update_session();
    }
    $updateReview = archiving_update_review_response_from_session($activeVersion, $updateSession);
    $resolvedPendingArchivedUpdateCount = $pendingArchivedUpdateCount ?? count_pending_archiving_update_jobs_in_session($updateSession);
    $hasPendingArchivedUpdates = $resolvedPendingArchivedUpdateCount > 0
        || (
            is_array($updateSession)
            && (string) ($updateSession['status'] ?? 'idle') === 'running'
        );

    return [
        'activeVersion' => $activeVersion,
        'hasPendingArchivedUpdates' => $hasPendingArchivedUpdates,
        'pendingArchivedUpdateCount' => $resolvedPendingArchivedUpdateCount,
        'updateReview' => $updateReview,
        'signature' => archiving_rules_state_payload_hash([
            'activeVersion' => $activeVersion,
            'hasPendingArchivedUpdates' => $hasPendingArchivedUpdates,
            'pendingArchivedUpdateCount' => $resolvedPendingArchivedUpdateCount,
            'updateReview' => $updateReview,
        ]),
    ];
}

function count_pending_archiving_update_jobs_in_session(array $session): int
{
    $count = 0;
    foreach (is_array($session['processedJobs'] ?? null) ? $session['processedJobs'] : [] as $item) {
        if (!is_array($item)) {
            continue;
        }
        $type = is_string($item['classification']['type'] ?? null) ? (string) $item['classification']['type'] : 'unchanged';
        if ($type === 'unchanged') {
            continue;
        }
        if (($item['dismissedForVersion'] ?? false) === true) {
            continue;
        }
        $count++;
    }

    return $count;
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

function job_archived_version(array $job): int
{
    $snapshot = job_archiving_snapshot($job);
    return is_array($snapshot) ? max(0, (int) ($snapshot['approvedWithRulesVersion'] ?? 0)) : 0;
}

function job_dismissed_analysis_version(array $job): int
{
    $version = filter_var($job['dismissedAnalysisVersion'] ?? null, FILTER_VALIDATE_INT);
    if ($version === false || $version === null || $version < 1) {
        return 0;
    }
    return (int) $version;
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
    $defaultBboxSpanBuilding = default_matching_bbox_span_building_settings();
    $defaultPayload = [
        'replacements' => [],
        'positionAdjustment' => $defaultPositionAdjustment,
        'bboxSpanBuilding' => $defaultBboxSpanBuilding,
        'dataFieldAcceptanceThreshold' => 0.5,
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
    $bboxSpanBuilding = normalize_matching_bbox_span_building_settings(
        is_array($decoded['bboxSpanBuilding'] ?? null) ? $decoded['bboxSpanBuilding'] : ($decoded['spanBuilding'] ?? null)
    );

    return [
        'replacements' => $replacements,
        'positionAdjustment' => $positionAdjustment,
        'bboxSpanBuilding' => $bboxSpanBuilding,
        'dataFieldAcceptanceThreshold' => is_numeric($decoded['dataFieldAcceptanceThreshold'] ?? null)
            ? clamp_confidence((float) $decoded['dataFieldAcceptanceThreshold'])
            : 0.5,
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
        'trailingDelimiterPenalty' => 0.25,
        'otherMatchKeyPenalty' => 0.5,
        'rightYOffsetPenalty' => 0.25,
        'downXOffsetPenalty' => 0.25,
        'noisePenaltyPerCharacterCurve' => default_matching_noise_penalty_curve(),
        'rightYOffsetPenaltyCurve' => default_matching_offset_penalty_curve(0.25),
        'downXOffsetPenaltyCurve' => default_matching_offset_penalty_curve(0.25),
        'downYDistancePenaltyCurve' => default_matching_down_y_distance_penalty_curve(),
    ];
}

function default_matching_bbox_span_building_settings(): array
{
    return [
        'maxHorizontalGapMultiplier' => 2.5,
        'maxVerticalOffsetMultiplier' => 0.4,
        'maxLineHeightDifferenceMultiplier' => 0.5,
    ];
}

function default_matching_down_y_distance_penalty_curve(): array
{
    return [
        ['x' => 0.0, 'y' => 0.0],
        ['x' => 2.0, 'y' => 0.0],
        ['x' => 4.0, 'y' => 0.25],
        ['x' => 8.0, 'y' => 0.8],
        ['x' => 10.0, 'y' => 1.0],
    ];
}

function default_matching_noise_penalty_curve(): array
{
    return [
        ['x' => 0.0, 'y' => 0.0],
        ['x' => 100.0, 'y' => 1.0],
    ];
}

function default_matching_offset_penalty_curve(float $penaltyPerLineHeight): array
{
    $weight = normalize_matching_decimal_setting($penaltyPerLineHeight, 0.0, null);
    if ($weight <= 0.0) {
        return [
            ['x' => 0.0, 'y' => 0.0],
            ['x' => 1.0, 'y' => 0.0],
        ];
    }
    return [
        ['x' => 0.0, 'y' => 0.0],
        ['x' => 1.0 / $weight, 'y' => 1.0],
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

function normalize_matching_bbox_span_building_settings(mixed $input): array
{
    $source = is_array($input) ? $input : [];
    $defaults = default_matching_bbox_span_building_settings();

    return [
        'maxHorizontalGapMultiplier' => normalize_matching_decimal_setting(
            $source['maxHorizontalGapMultiplier'] ?? $source['max_horizontal_gap_multiplier'] ?? null,
            $defaults['maxHorizontalGapMultiplier'],
            null
        ),
        'maxVerticalOffsetMultiplier' => normalize_matching_decimal_setting(
            $source['maxVerticalOffsetMultiplier'] ?? $source['max_vertical_offset_multiplier'] ?? null,
            $defaults['maxVerticalOffsetMultiplier'],
            null
        ),
        'maxLineHeightDifferenceMultiplier' => normalize_matching_decimal_setting(
            $source['maxLineHeightDifferenceMultiplier'] ?? $source['max_line_height_difference_multiplier'] ?? null,
            $defaults['maxLineHeightDifferenceMultiplier'],
            null
        ),
    ];
}

function matching_position_settings_from_payload(array $matchingPayload): array
{
    $settings = normalize_matching_position_adjustment_settings(
        is_array($matchingPayload['positionAdjustment'] ?? null) ? $matchingPayload['positionAdjustment'] : []
    );
    $settings['bboxSpanBuilding'] = normalize_matching_bbox_span_building_settings(
        is_array($matchingPayload['bboxSpanBuilding'] ?? null) ? $matchingPayload['bboxSpanBuilding'] : null
    );
    return $settings;
}

function matching_bbox_span_building_from_position_settings(array $positionSettings): array
{
    return normalize_matching_bbox_span_building_settings(
        is_array($positionSettings['bboxSpanBuilding'] ?? null) ? $positionSettings['bboxSpanBuilding'] : null
    );
}

function normalize_matching_position_adjustment_settings(?array $input): array
{
    $defaults = default_matching_position_adjustment_settings();
    $source = is_array($input) ? $input : [];
    $noisePenaltyPerCharacter = normalize_matching_decimal_setting(
        $source['noisePenaltyPerCharacter'] ?? $source['noise_penalty_per_character'] ?? null,
        $defaults['noisePenaltyPerCharacter']
    );
    $trailingDelimiterPenalty = normalize_matching_decimal_setting(
        $source['trailingDelimiterPenalty'] ?? $source['trailing_delimiter_penalty'] ?? null,
        $defaults['trailingDelimiterPenalty'],
        null
    );
    $otherMatchKeyPenalty = normalize_matching_decimal_setting(
        $source['otherMatchKeyPenalty'] ?? $source['other_match_key_penalty'] ?? null,
        $defaults['otherMatchKeyPenalty'],
        null
    );
    $rightYOffsetPenalty = normalize_matching_decimal_setting(
        $source['rightYOffsetPenalty'] ?? $source['right_y_offset_penalty'] ?? $source['downRightPenalty'] ?? $source['down_right_penalty'] ?? null,
        $defaults['rightYOffsetPenalty'],
        null
    );
    $downXOffsetPenalty = normalize_matching_decimal_setting(
        $source['downXOffsetPenalty'] ?? $source['down_x_offset_penalty'] ?? $source['downRightPenalty'] ?? $source['down_right_penalty'] ?? null,
        $defaults['downXOffsetPenalty'],
        null
    );

    return [
        'noisePenaltyPerCharacter' => $noisePenaltyPerCharacter,
        'trailingDelimiterPenalty' => $trailingDelimiterPenalty,
        'otherMatchKeyPenalty' => $otherMatchKeyPenalty,
        'rightYOffsetPenalty' => $rightYOffsetPenalty,
        'downXOffsetPenalty' => $downXOffsetPenalty,
        'noisePenaltyPerCharacterCurve' => normalize_matching_penalty_curve(
            is_array($source['noisePenaltyPerCharacterCurve'] ?? null)
                ? $source['noisePenaltyPerCharacterCurve']
                : (is_array($source['noise_penalty_per_character_curve'] ?? null) ? $source['noise_penalty_per_character_curve'] : null),
            default_matching_legacy_penalty_curve($noisePenaltyPerCharacter, 'noise')
        ),
        'rightYOffsetPenaltyCurve' => normalize_matching_penalty_curve(
            is_array($source['rightYOffsetPenaltyCurve'] ?? null)
                ? $source['rightYOffsetPenaltyCurve']
                : (is_array($source['right_y_offset_penalty_curve'] ?? null) ? $source['right_y_offset_penalty_curve'] : null),
            default_matching_legacy_penalty_curve($rightYOffsetPenalty, 'offset')
        ),
        'downXOffsetPenaltyCurve' => normalize_matching_penalty_curve(
            is_array($source['downXOffsetPenaltyCurve'] ?? null)
                ? $source['downXOffsetPenaltyCurve']
                : (is_array($source['down_x_offset_penalty_curve'] ?? null) ? $source['down_x_offset_penalty_curve'] : null),
            default_matching_legacy_penalty_curve($downXOffsetPenalty, 'offset')
        ),
        'downYDistancePenaltyCurve' => normalize_matching_penalty_curve(
            is_array($source['downYDistancePenaltyCurve'] ?? null)
                ? $source['downYDistancePenaltyCurve']
                : (is_array($source['down_y_distance_penalty_curve'] ?? null) ? $source['down_y_distance_penalty_curve'] : null),
            $defaults['downYDistancePenaltyCurve']
        ),
    ];
}

function normalize_matching_penalty_curve(?array $points, array $fallback): array
{
    $sourcePoints = is_array($points) ? $points : $fallback;
    $normalized = [];

    foreach ($sourcePoints as $point) {
        if (!is_array($point)) {
            continue;
        }
        $x = normalize_matching_decimal_setting($point['x'] ?? null, -1.0, null);
        $y = normalize_matching_decimal_setting($point['y'] ?? null, 0.0, 1.0);
        if ($x < 0.0) {
            continue;
        }
        $normalized[] = [
            'x' => $x,
            'y' => clamp_confidence($y),
        ];
    }

    if (count($normalized) < 2) {
        if ($fallback === []) {
            return [
                ['x' => 0.0, 'y' => 0.0],
                ['x' => 1.0, 'y' => 1.0],
            ];
        }
        return normalize_matching_penalty_curve($fallback, []);
    }

    usort($normalized, static function (array $left, array $right): int {
        $xCompare = ((float) ($left['x'] ?? 0.0)) <=> ((float) ($right['x'] ?? 0.0));
        if ($xCompare !== 0) {
            return $xCompare;
        }
        return ((float) ($left['y'] ?? 0.0)) <=> ((float) ($right['y'] ?? 0.0));
    });

    $deduped = [];
    foreach ($normalized as $point) {
        $xKey = sprintf('%.6F', (float) $point['x']);
        $deduped[$xKey] = $point;
    }

    return array_values($deduped);
}

function default_matching_legacy_penalty_curve(float $value, string $type): array
{
    $scalar = normalize_matching_decimal_setting($value, 0.0, $type === 'noise' ? 1.0 : null);
    if ($type === 'noise') {
        if ($scalar <= 0.0) {
            return [
                ['x' => 0.0, 'y' => 0.0],
                ['x' => 1.0, 'y' => 0.0],
            ];
        }
        return [
            ['x' => 0.0, 'y' => 0.0],
            ['x' => 1.0 / $scalar, 'y' => 1.0],
        ];
    }
    return default_matching_offset_penalty_curve($scalar);
}

function interpolate_matching_penalty_curve(array $points, float $x): float
{
    $curve = normalize_matching_penalty_curve($points, default_matching_down_y_distance_penalty_curve());
    if (count($curve) === 0) {
        return 0.0;
    }

    $resolvedX = max(0.0, $x);
    $first = $curve[0];
    if ($resolvedX <= (float) ($first['x'] ?? 0.0)) {
        return clamp_confidence((float) ($first['y'] ?? 0.0));
    }

    $last = $curve[count($curve) - 1];
    if ($resolvedX >= (float) ($last['x'] ?? 0.0)) {
        return clamp_confidence((float) ($last['y'] ?? 0.0));
    }

    for ($index = 1; $index < count($curve); $index++) {
        $left = $curve[$index - 1];
        $right = $curve[$index];
        $leftX = (float) ($left['x'] ?? 0.0);
        $rightX = (float) ($right['x'] ?? $leftX);
        if ($resolvedX > $rightX) {
            continue;
        }

        $leftY = clamp_confidence((float) ($left['y'] ?? 0.0));
        $rightY = clamp_confidence((float) ($right['y'] ?? $leftY));
        if (abs($rightX - $leftX) < 0.000001) {
            return $rightY;
        }

        $ratio = ($resolvedX - $leftX) / ($rightX - $leftX);
        return clamp_confidence($leftY + (($rightY - $leftY) * $ratio));
    }

    return clamp_confidence((float) ($last['y'] ?? 0.0));
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
            persist_document_metadata_label_ids($jobId, normalize_stored_job_label_ids(job_auto_archiving_result($job)['labels'] ?? []));
        } else {
            $normalizedLabelIds = normalize_selected_job_label_ids_payload($value, $knownLabelIds);
            $job['selectedLabelIds'] = $normalizedLabelIds;
            persist_document_metadata_label_ids($jobId, $normalizedLabelIds);
        }
    }

    if (array_key_exists('selectedExtractionFieldValues', $payload)) {
        $value = $payload['selectedExtractionFieldValues'];
        if ($value === null) {
            unset($job['selectedExtractionFieldValues']);
            persist_document_metadata_data_values(
                $jobId,
                document_actual_data_values_from_selected($job, null)
            );
        } else {
            $normalizedFieldValues = normalize_selected_job_extraction_field_values_payload(
                $value,
                known_job_extraction_field_keys()
            );
            $job['selectedExtractionFieldValues'] = $normalizedFieldValues;
            persist_document_metadata_data_values(
                $jobId,
                document_actual_data_values_from_selected($job, $normalizedFieldValues)
            );
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
        || array_key_exists('selectedExtractionFieldValues', $payload)
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
        $job['updatedAt'] = now_iso();
        unset(
            $job['archivedAt'],
            $job['archivedPdfPath'],
            $job['dismissedAnalysisVersion'],
            $job['needsRuleReview'],
            $job['ruleReviewTargetRulesVersion'],
            $job['lastResolvedArchivingRulesVersion'],
            $job['ruleReviewProposedValue'],
            $job['ruleReviewDiff']
        );
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
    $extractedForSenderCheck = load_json_file($jobDir . DIRECTORY_SEPARATOR . 'extracted.json');
    $actualSenderDataValues = document_metadata_actual_data_values($jobId);
    if (!is_array($actualSenderDataValues) && array_key_exists('selectedExtractionFieldValues', $job)) {
        $actualSenderDataValues = document_actual_data_values_from_selected(
            $job,
            normalize_stored_job_extraction_field_values($job['selectedExtractionFieldValues'])
        );
    }
    if (is_array($extractedForSenderCheck)) {
        $extractedForSenderCheck = sender_summary_extracted_with_actual_data_values(
            $extractedForSenderCheck,
            $actualSenderDataValues
        );
    }
    $senderSummaryForArchive = is_array($extractedForSenderCheck)
        ? build_job_sender_summary($extractedForSenderCheck, $jobDir, null, $selectedSenderId > 0 ? $selectedSenderId : null)
        : null;
    $unknownSenderObservations = is_array($senderSummaryForArchive) && is_array($senderSummaryForArchive['unknownObservations'] ?? null)
        ? array_values(array_filter(
            $senderSummaryForArchive['unknownObservations'],
            static fn (mixed $row): bool => is_array($row)
        ))
        : [];
    if ($unknownSenderObservations !== [] && $selectedSenderId < 1) {
        throw new RuntimeException('Dokumentet innehåller okopplade uppgifter som kan påverka avsändaren.');
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
    $job['archivedAt'] = now_iso();
    $job['archivedPdfPath'] = $targetPath;
    $job['updatedAt'] = now_iso();
    unset($job['dismissedAnalysisVersion']);
    unset(
        $job['needsRuleReview'],
        $job['ruleReviewTargetRulesVersion'],
        $job['lastResolvedArchivingRulesVersion'],
        $job['ruleReviewProposedValue'],
        $job['ruleReviewDiff']
    );
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

function active_ocr_pdf_text_substitutions(array $substitutions): array
{
    return array_values(array_filter(
        sanitize_ocr_pdf_text_substitutions($substitutions),
        static fn (array $row): bool => ($row['enabled'] ?? true) !== false && (string) ($row['from'] ?? '') !== ''
    ));
}

function apply_ocr_pdf_text_substitutions_to_text(string $text, array $substitutions): string
{
    $current = $text;
    foreach (active_ocr_pdf_text_substitutions($substitutions) as $row) {
        $from = (string) ($row['from'] ?? '');
        if ($from === '') {
            continue;
        }
        $to = (string) ($row['to'] ?? '');
        if (($row['isRegex'] ?? false) === true) {
            $pattern = '~' . str_replace('~', '\\~', $from) . '~u';
            $next = @preg_replace($pattern, $to, $current);
            if (is_string($next)) {
                $current = $next;
            }
            continue;
        }

        $current = str_replace($from, $to, $current);
    }

    return $current;
}

function apply_ocr_pdf_text_substitutions_to_debug_words(array $words, array $substitutions): array
{
    if (active_ocr_pdf_text_substitutions($substitutions) === []) {
        return $words;
    }

    return array_map(static function ($word) use ($substitutions) {
        if (!is_array($word) || !is_string($word['text'] ?? null)) {
            return $word;
        }
        $word['text'] = apply_ocr_pdf_text_substitutions_to_text((string) $word['text'], $substitutions);
        return $word;
    }, $words);
}

function write_docflow_ocr_transform_config(array $substitutions): ?string
{
    $normalized = active_ocr_pdf_text_substitutions($substitutions);
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

    $normalizedSubstitutions = active_ocr_pdf_text_substitutions($ocrPdfTextSubstitutions);
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

function ocr_debug_export_directory_config_value(array $config): string
{
    $path = trim((string) ($config['ocrDebugExportDirectory'] ?? DEFAULT_OCR_DEBUG_EXPORT_DIRECTORY));
    return $path !== '' ? $path : DEFAULT_OCR_DEBUG_EXPORT_DIRECTORY;
}

function ocr_debug_export_base_directory(array $config): string
{
    $configured = ocr_debug_export_directory_config_value($config);
    $normalized = str_replace('\\', '/', $configured);
    if ($normalized !== '' && ($normalized[0] === '/' || preg_match('/^[A-Za-z]:\//', $normalized) === 1)) {
        return rtrim(normalized_realpath($configured) ?? $configured, DIRECTORY_SEPARATOR);
    }

    $projectRoot = normalized_realpath(PROJECT_ROOT) ?? rtrim(PROJECT_ROOT, DIRECTORY_SEPARATOR);
    return rtrim($projectRoot . DIRECTORY_SEPARATOR . $configured, DIRECTORY_SEPARATOR);
}

function ocr_debug_export_scope_slug(string $scope): string
{
    $normalized = strtolower(trim($scope));
    $normalized = preg_replace('/[^a-z0-9_-]+/i', '-', $normalized);
    $normalized = is_string($normalized) ? trim($normalized, '-_') : '';
    return $normalized !== '' ? $normalized : 'jobs';
}

function create_ocr_debug_export_directory(array $config, string $scope): string
{
    $baseDirectory = ocr_debug_export_base_directory($config);
    ensure_directory($baseDirectory);

    $timestamp = date('Ymd_His');
    $scopeSlug = ocr_debug_export_scope_slug($scope);
    $exportDirectory = $baseDirectory . DIRECTORY_SEPARATOR . $timestamp . '-' . $scopeSlug;
    $suffix = 2;
    while (is_dir($exportDirectory)) {
        $exportDirectory = $baseDirectory . DIRECTORY_SEPARATOR . $timestamp . '-' . $scopeSlug . '-' . str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
        $suffix++;
    }

    ensure_directory($exportDirectory);
    return $exportDirectory;
}

function ocr_debug_export_document_for_job(string $jobId): ?array
{
    $pages = stored_merged_objects_pages($jobId);
    if ($pages === []) {
        $pages = fallback_merged_objects_pages_from_job_debug($jobId);
    }
    if ($pages === []) {
        return null;
    }

    return [
        'engine' => 'merged_objects',
        'pages' => $pages,
    ];
}

function ocr_debug_export_scope_label(string $scope): string
{
    return match (ocr_debug_export_scope_slug($scope)) {
        'ready' => 'Att granska',
        'archived-review' => 'Arkiverade att granska',
        'processing' => 'Bearbetas',
        'archived' => 'Arkiverade',
        'all' => 'Alla',
        default => 'Jobb',
    };
}

function ocr_debug_export_live_folder_name(): string
{
    return '__current__';
}

function ocr_debug_export_is_live_reference(string $folderName): bool
{
    return trim($folderName) === ocr_debug_export_live_folder_name();
}

function ocr_debug_export_normalize_folder_name(string $folderName): ?string
{
    $basename = basename(trim($folderName));
    if ($basename === '' || preg_match('/^[A-Za-z0-9._-]+$/', $basename) !== 1) {
        return null;
    }

    return $basename;
}

function ocr_debug_export_directory_path_from_name(array $config, string $folderName): ?string
{
    $normalizedFolderName = ocr_debug_export_normalize_folder_name($folderName);
    if ($normalizedFolderName === null) {
        return null;
    }

    $baseDirectory = ocr_debug_export_base_directory($config);
    $resolvedBaseDirectory = normalized_realpath($baseDirectory) ?? $baseDirectory;
    $directory = $baseDirectory . DIRECTORY_SEPARATOR . $normalizedFolderName;
    $resolved = normalized_realpath($directory);
    if ($resolved !== null && path_is_within_directory($resolved, $resolvedBaseDirectory)) {
        return $resolved;
    }

    return is_dir($directory) ? $directory : null;
}

function ocr_debug_export_manifest_path(string $exportDirectory): string
{
    return rtrim($exportDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'manifest.json';
}

function ocr_debug_export_manifest_payload(
    string $exportDirectory,
    string $scope,
    array $jobIds,
    array $createdFiles,
    array $skippedJobIds = []
): array {
    $folderName = basename(rtrim($exportDirectory, DIRECTORY_SEPARATOR));
    $jobIds = array_values(array_filter(
        array_map(static fn ($jobId) => is_string($jobId) ? trim($jobId) : '', $jobIds),
        static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
    ));
    $createdFiles = array_values(array_filter(
        array_map(static fn ($file) => is_string($file) ? trim($file) : '', $createdFiles),
        static fn (string $file): bool => $file !== ''
    ));
    $skippedJobIds = array_values(array_filter(
        array_map(static fn ($jobId) => is_string($jobId) ? trim($jobId) : '', $skippedJobIds),
        static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
    ));
    $exportedAt = gmdate(DATE_ATOM, filemtime($exportDirectory) ?: time());

    return [
        'format' => 'docflow_snapshot',
        'version' => 1,
        'exportedAt' => $exportedAt,
        'filter' => ocr_debug_export_scope_slug($scope),
        'filterLabel' => ocr_debug_export_scope_label($scope),
        'jobCount' => count($jobIds),
        'jobIds' => $jobIds,
        'createdFiles' => $createdFiles,
        'layers' => ['text', 'merged_objects', 'document_metadata'],
        'skippedJobIds' => $skippedJobIds,
        'folderName' => $folderName,
        'exportDirectory' => normalized_realpath($exportDirectory) ?? $exportDirectory,
        'comment' => '',
        'settingsBackupFilename' => '',
    ];
}

function ocr_debug_export_manifest_from_directory(string $exportDirectory): ?array
{
    $manifestPath = ocr_debug_export_manifest_path($exportDirectory);
    if (is_file($manifestPath)) {
        $manifest = load_json_file($manifestPath);
        if (is_array($manifest) && in_array($manifest['format'] ?? null, ['docflow_snapshot', 'docflow_debug_export', 'docflow_ocr_debug_export'], true)) {
            $manifest['folderName'] = is_string($manifest['folderName'] ?? null)
                ? (string) $manifest['folderName']
                : basename(rtrim($exportDirectory, DIRECTORY_SEPARATOR));
            $manifest['exportDirectory'] = normalized_realpath($exportDirectory) ?? $exportDirectory;
            $manifest['sortTimestamp'] = filemtime($manifestPath) ?: (filemtime($exportDirectory) ?: 0);
            $manifest['legacy'] = false;
            $manifest['comment'] = is_string($manifest['comment'] ?? null) ? (string) $manifest['comment'] : '';
            $manifest['settingsBackupFilename'] = is_string($manifest['settingsBackupFilename'] ?? null)
                ? trim((string) $manifest['settingsBackupFilename'])
                : '';
            $manifest['layers'] = is_array($manifest['layers'] ?? null) ? array_values($manifest['layers']) : ['text', 'merged_objects'];
            return $manifest;
        }
    }

    $folderName = basename(rtrim($exportDirectory, DIRECTORY_SEPARATOR));
    $legacyTimestamp = null;
    $legacyScope = 'jobs';
    if (preg_match('/^(\d{8}_\d{6})-([A-Za-z0-9._-]+)$/', $folderName, $matches) === 1) {
        $legacyTimestamp = DateTimeImmutable::createFromFormat('Ymd_His', $matches[1]) ?: null;
        $legacyScope = $matches[2];
    }

    $jobIds = [];
    $createdFiles = [];
    $jsonPaths = glob(rtrim($exportDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.json') ?: [];
    foreach ($jsonPaths as $path) {
        if (!is_string($path)) {
            continue;
        }
        $basename = basename($path);
        if ($basename === 'manifest.json') {
            continue;
        }
        if (preg_match('/^([A-Za-z0-9_-]+)\.json$/', $basename, $matches) === 1) {
            $jobIds[] = $matches[1];
        }
        $createdFiles[] = $basename;
    }
    $txtPaths = glob(rtrim($exportDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.txt') ?: [];
    foreach ($txtPaths as $path) {
        if (!is_string($path)) {
            continue;
        }
        $createdFiles[] = basename($path);
    }
    $jobIds = array_values(array_unique(array_filter($jobIds, static fn (string $jobId): bool => is_valid_job_id($jobId))));
    sort($createdFiles, SORT_NATURAL);
    $sortTimestamp = $legacyTimestamp instanceof DateTimeImmutable
        ? $legacyTimestamp->getTimestamp()
        : (filemtime($exportDirectory) ?: 0);

    return [
        'format' => 'docflow_snapshot',
        'version' => 1,
        'exportedAt' => $legacyTimestamp instanceof DateTimeImmutable ? $legacyTimestamp->format(DATE_ATOM) : gmdate(DATE_ATOM, $sortTimestamp),
        'filter' => ocr_debug_export_scope_slug($legacyScope),
        'filterLabel' => ocr_debug_export_scope_label($legacyScope),
        'jobCount' => count($jobIds),
        'jobIds' => $jobIds,
        'createdFiles' => $createdFiles,
        'layers' => ['text', 'merged_objects'],
        'skippedJobIds' => [],
        'folderName' => $folderName,
        'exportDirectory' => normalized_realpath($exportDirectory) ?? $exportDirectory,
        'comment' => '',
        'settingsBackupFilename' => '',
        'sortTimestamp' => $sortTimestamp,
        'legacy' => true,
    ];
}

function list_ocr_debug_exports(array $config): array
{
    $baseDirectory = ocr_debug_export_base_directory($config);
    ensure_directory($baseDirectory);
    $baseDirectory = normalized_realpath($baseDirectory) ?? $baseDirectory;

    $paths = glob($baseDirectory . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
    if (!is_array($paths)) {
        return [];
    }

    $entries = [];
    foreach ($paths as $path) {
        if (!is_string($path)) {
            continue;
        }
        $realPath = normalized_realpath($path);
        if ($realPath === null || !path_is_within_directory($realPath, $baseDirectory)) {
            continue;
        }
        $entry = ocr_debug_export_manifest_from_directory($realPath);
        if ($entry === null) {
          continue;
        }
        $entries[] = $entry;
    }

    usort(
        $entries,
        static function (array $left, array $right): int {
            $rightSort = (int) ($right['sortTimestamp'] ?? 0);
            $leftSort = (int) ($left['sortTimestamp'] ?? 0);
            if ($rightSort === $leftSort) {
                return strcmp((string) ($right['folderName'] ?? ''), (string) ($left['folderName'] ?? ''));
            }
            return $rightSort <=> $leftSort;
        }
    );

    return array_map(static function (array $entry): array {
        $createdFiles = array_values(array_filter(
            array_map(static fn ($file) => is_string($file) ? trim($file) : '', $entry['createdFiles'] ?? []),
            static fn (string $file): bool => $file !== ''
        ));
        $jobIds = array_values(array_filter(
            array_map(static fn ($jobId) => is_string($jobId) ? trim($jobId) : '', $entry['jobIds'] ?? []),
            static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
        ));
        $exportDirectory = is_string($entry['exportDirectory'] ?? null) ? (string) $entry['exportDirectory'] : '';
        $folderName = is_string($entry['folderName'] ?? null) ? (string) $entry['folderName'] : basename($exportDirectory);
        $sortTimestamp = isset($entry['sortTimestamp']) && is_numeric($entry['sortTimestamp'])
            ? (int) $entry['sortTimestamp']
            : (filemtime($exportDirectory) ?: 0);

        return [
            'folderName' => $folderName,
            'exportDirectory' => normalized_realpath($exportDirectory) ?? $exportDirectory,
            'comment' => is_string($entry['comment'] ?? null) ? (string) $entry['comment'] : '',
            'settingsBackupFilename' => is_string($entry['settingsBackupFilename'] ?? null) ? trim((string) $entry['settingsBackupFilename']) : '',
            'exportedAt' => is_string($entry['exportedAt'] ?? null) ? (string) $entry['exportedAt'] : null,
            'filter' => is_string($entry['filter'] ?? null) ? (string) $entry['filter'] : 'jobs',
            'filterLabel' => is_string($entry['filterLabel'] ?? null) ? (string) $entry['filterLabel'] : ocr_debug_export_scope_label((string) ($entry['filter'] ?? 'jobs')),
            'jobCount' => isset($entry['jobCount']) && is_numeric($entry['jobCount']) ? (int) $entry['jobCount'] : count($jobIds),
            'jobIds' => $jobIds,
            'createdFiles' => $createdFiles,
            'layers' => array_values(array_filter(
                array_map(static fn ($layer) => is_string($layer) ? trim($layer) : '', $entry['layers'] ?? []),
                static fn (string $layer): bool => $layer !== ''
            )),
            'fileCount' => count($createdFiles),
            'skippedJobIds' => array_values(array_filter(
                array_map(static fn ($jobId) => is_string($jobId) ? trim($jobId) : '', $entry['skippedJobIds'] ?? []),
                static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
            )),
            'sortTimestamp' => $sortTimestamp,
            'legacy' => ($entry['legacy'] ?? false) === true,
        ];
    }, $entries);
}

function ocr_debug_runs_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $pdo = \Docflow\Database\Connection::make();
    ocr_debug_runs_ensure_schema($pdo);
    return $pdo;
}

function sqlite_table_has_column(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->query('PRAGMA table_info(' . preg_replace('/[^A-Za-z0-9_]/', '', $table) . ')');
    $rows = $statement !== false ? $statement->fetchAll() : [];
    foreach (is_array($rows) ? $rows : [] as $row) {
        if (is_array($row) && is_string($row['name'] ?? null) && $row['name'] === $column) {
            return true;
        }
    }
    return false;
}

function ocr_debug_runs_ensure_schema(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS analysis_snapshots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            folder_name TEXT NOT NULL UNIQUE,
            status TEXT NOT NULL,
            scope TEXT NOT NULL DEFAULT 'jobs',
            filter_label TEXT NOT NULL DEFAULT 'Jobb',
            export_directory TEXT NOT NULL,
            job_ids_json TEXT NOT NULL DEFAULT '[]',
            skipped_job_ids_json TEXT NOT NULL DEFAULT '[]',
            total_jobs INTEGER NOT NULL DEFAULT 0,
            completed_jobs INTEGER NOT NULL DEFAULT 0,
            failed_jobs INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            completed_at TEXT NULL,
            created_from_rules_version INTEGER NULL,
            requires_reanalysis INTEGER NOT NULL DEFAULT 1,
            error_message TEXT NULL
        )"
    );
    if (!sqlite_table_has_column($pdo, 'analysis_snapshots', 'settings_backup_filename')) {
        $pdo->exec('ALTER TABLE analysis_snapshots ADD COLUMN settings_backup_filename TEXT NULL');
    }
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS analysis_snapshot_jobs (
            snapshot_id INTEGER NOT NULL,
            job_id TEXT NOT NULL,
            status TEXT NOT NULL,
            error_message TEXT NULL,
            completed_at TEXT NULL,
            PRIMARY KEY (snapshot_id, job_id),
            FOREIGN KEY (snapshot_id) REFERENCES analysis_snapshots(id) ON DELETE CASCADE
        )"
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_analysis_snapshot_jobs_job_status ON analysis_snapshot_jobs (job_id, status)');
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS comparison_runs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            left_folder_name TEXT NOT NULL,
            right_folder_name TEXT NOT NULL,
            live_side TEXT NOT NULL,
            status TEXT NOT NULL,
            scope TEXT NOT NULL DEFAULT 'jobs',
            filter_label TEXT NOT NULL DEFAULT 'Jobb',
            job_ids_json TEXT NOT NULL DEFAULT '[]',
            total_jobs INTEGER NOT NULL DEFAULT 0,
            completed_jobs INTEGER NOT NULL DEFAULT 0,
            failed_jobs INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            completed_at TEXT NULL,
            result_json TEXT NULL,
            error_message TEXT NULL
        )"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS comparison_run_jobs (
            comparison_run_id INTEGER NOT NULL,
            job_id TEXT NOT NULL,
            status TEXT NOT NULL,
            error_message TEXT NULL,
            completed_at TEXT NULL,
            PRIMARY KEY (comparison_run_id, job_id),
            FOREIGN KEY (comparison_run_id) REFERENCES comparison_runs(id) ON DELETE CASCADE
        )"
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_comparison_run_jobs_job_status ON comparison_run_jobs (job_id, status)');
    $ensured = true;
}

function ocr_debug_json_encode_array(array $value): string
{
    $json = json_encode(array_values($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        throw new RuntimeException('Kunde inte serialisera snapshotdata.');
    }
    return $json;
}

function ocr_debug_json_decode_array(mixed $value): array
{
    if (!is_string($value) || trim($value) === '') {
        return [];
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? array_values($decoded) : [];
}

function ocr_debug_normalize_existing_job_ids(array $config, array $jobIds): array
{
    $jobsDirectory = is_string($config['jobsDirectory'] ?? null) ? trim((string) $config['jobsDirectory']) : '';
    if ($jobsDirectory === '' || !is_dir($jobsDirectory)) {
        throw new RuntimeException('Jobbkatalogen är inte tillgänglig.');
    }

    $normalized = [];
    foreach ($jobIds as $jobId) {
        if (!is_string($jobId) && !is_numeric($jobId)) {
            continue;
        }
        $normalizedJobId = trim((string) $jobId);
        if ($normalizedJobId === '' || !is_valid_job_id($normalizedJobId)) {
            continue;
        }
        if (!is_file(rtrim($jobsDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $normalizedJobId . DIRECTORY_SEPARATOR . 'job.json')) {
            continue;
        }
        $normalized[$normalizedJobId] = true;
    }

    $result = array_keys($normalized);
    sort($result, SORT_NATURAL);
    if ($result === []) {
        throw new RuntimeException('Inga jobb valda.');
    }
    return $result;
}

function ocr_debug_snapshot_public_row(array $row): array
{
    $status = is_string($row['status'] ?? null) ? (string) $row['status'] : 'processing';
    $jobIds = ocr_debug_json_decode_array($row['job_ids_json'] ?? null);
    $skippedJobIds = ocr_debug_json_decode_array($row['skipped_job_ids_json'] ?? null);
    return [
        'id' => isset($row['id']) ? (int) $row['id'] : null,
        'folderName' => is_string($row['folder_name'] ?? null) ? (string) $row['folder_name'] : '',
        'exportDirectory' => is_string($row['export_directory'] ?? null) ? (string) $row['export_directory'] : '',
        'exportedAt' => is_string($row['completed_at'] ?? null) && trim((string) $row['completed_at']) !== ''
            ? (string) $row['completed_at']
            : (is_string($row['created_at'] ?? null) ? (string) $row['created_at'] : null),
        'filter' => is_string($row['scope'] ?? null) ? (string) $row['scope'] : 'jobs',
        'filterLabel' => is_string($row['filter_label'] ?? null) ? (string) $row['filter_label'] : ocr_debug_export_scope_label((string) ($row['scope'] ?? 'jobs')),
        'jobCount' => isset($row['total_jobs']) ? (int) $row['total_jobs'] : count($jobIds),
        'jobIds' => $jobIds,
        'createdFiles' => [],
        'layers' => ['text', 'merged_objects', 'document_metadata'],
        'fileCount' => 0,
        'skippedJobIds' => $skippedJobIds,
        'sortTimestamp' => strtotime((string) ($row['created_at'] ?? '')) ?: time(),
        'legacy' => false,
        'status' => $status,
        'totalJobs' => isset($row['total_jobs']) ? (int) $row['total_jobs'] : count($jobIds),
        'completedJobs' => isset($row['completed_jobs']) ? (int) $row['completed_jobs'] : 0,
        'failedJobs' => isset($row['failed_jobs']) ? (int) $row['failed_jobs'] : 0,
        'requiresReanalysis' => ((int) ($row['requires_reanalysis'] ?? 1)) === 1,
        'completedAt' => is_string($row['completed_at'] ?? null) ? (string) $row['completed_at'] : null,
        'errorMessage' => is_string($row['error_message'] ?? null) ? (string) $row['error_message'] : '',
        'settingsBackupFilename' => is_string($row['settings_backup_filename'] ?? null) ? trim((string) $row['settings_backup_filename']) : '',
    ];
}

function ocr_debug_snapshot_row_by_id(int $snapshotId): ?array
{
    $pdo = ocr_debug_runs_pdo();
    $statement = $pdo->prepare('SELECT * FROM analysis_snapshots WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $snapshotId]);
    $row = $statement->fetch();
    return is_array($row) ? $row : null;
}

function ocr_debug_snapshot_row_by_folder_name(string $folderName): ?array
{
    $normalizedFolderName = trim($folderName);
    if ($normalizedFolderName === '') {
        return null;
    }
    $pdo = ocr_debug_runs_pdo();
    $statement = $pdo->prepare('SELECT * FROM analysis_snapshots WHERE folder_name = :folder_name LIMIT 1');
    $statement->execute([':folder_name' => $normalizedFolderName]);
    $row = $statement->fetch();
    return is_array($row) ? $row : null;
}

function ocr_debug_update_manifest_settings_backup(string $exportDirectory, string $backupFilename): void
{
    $normalizedExportDirectory = rtrim($exportDirectory, DIRECTORY_SEPARATOR);
    if ($normalizedExportDirectory === '' || !is_dir($normalizedExportDirectory)) {
        return;
    }

    $manifestPath = ocr_debug_export_manifest_path($normalizedExportDirectory);
    if (!is_file($manifestPath)) {
        return;
    }

    $manifest = load_json_file($manifestPath);
    if (!is_array($manifest)) {
        return;
    }
    $manifest['settingsBackupFilename'] = trim($backupFilename);
    write_json_file($manifestPath, $manifest);
}

function ocr_debug_link_settings_backup_to_snapshot(int $snapshotId): string
{
    $snapshotRow = ocr_debug_snapshot_row_by_id($snapshotId);
    if (!is_array($snapshotRow)) {
        throw new RuntimeException('Snapshot kunde inte hittas för inställningsbackup.');
    }

    $existingFilename = is_string($snapshotRow['settings_backup_filename'] ?? null)
        ? trim((string) $snapshotRow['settings_backup_filename'])
        : '';
    if ($existingFilename !== '') {
        $exportDirectory = is_string($snapshotRow['export_directory'] ?? null) ? (string) $snapshotRow['export_directory'] : '';
        ocr_debug_update_manifest_settings_backup($exportDirectory, $existingFilename);
        return $existingFilename;
    }

    $backup = create_configuration_backup_for_ocr_debug_snapshot($snapshotRow);
    $backupFilename = is_string($backup['filename'] ?? null) ? trim((string) $backup['filename']) : '';
    if ($backupFilename === '') {
        throw new RuntimeException('Inställningsbackup saknar filnamn.');
    }

    $pdo = ocr_debug_runs_pdo();
    $statement = $pdo->prepare(
        'UPDATE analysis_snapshots SET settings_backup_filename = :settings_backup_filename WHERE id = :id'
    );
    $statement->execute([
        ':id' => $snapshotId,
        ':settings_backup_filename' => $backupFilename,
    ]);

    $exportDirectory = is_string($snapshotRow['export_directory'] ?? null) ? (string) $snapshotRow['export_directory'] : '';
    ocr_debug_update_manifest_settings_backup($exportDirectory, $backupFilename);

    return $backupFilename;
}

function list_processing_ocr_debug_snapshots(): array
{
    $pdo = ocr_debug_runs_pdo();
    $rows = $pdo->query(
        "SELECT *
        FROM analysis_snapshots
        ORDER BY created_at DESC"
    )->fetchAll();
    if (!is_array($rows)) {
        return [];
    }
    return array_map(
        static fn (array $row): array => ocr_debug_snapshot_public_row($row),
        array_values(array_filter($rows, static fn ($row): bool => is_array($row)))
    );
}

function list_ocr_debug_exports_with_runs(array $config): array
{
    $exports = list_ocr_debug_exports($config);
    $processing = list_processing_ocr_debug_snapshots();
    if ($processing === []) {
        return $exports;
    }
    $folderNames = [];
    foreach ($exports as $index => $entry) {
        if (is_string($entry['folderName'] ?? null)) {
            $folderNames[(string) $entry['folderName']] = $index;
        }
    }
    foreach ($processing as $entry) {
        $folderName = is_string($entry['folderName'] ?? null) ? (string) $entry['folderName'] : '';
        if ($folderName !== '' && isset($folderNames[$folderName])) {
            $exports[$folderNames[$folderName]] = array_merge($exports[$folderNames[$folderName]], $entry);
            continue;
        }
        if ($folderName !== '') {
            $exports[] = $entry;
        }
    }
    usort($exports, static fn (array $left, array $right): int => ((int) ($right['sortTimestamp'] ?? 0)) <=> ((int) ($left['sortTimestamp'] ?? 0)));
    return $exports;
}

function ocr_debug_queue_reanalysis_for_run_jobs(array $config, array $jobIds, string $mode = 'post-ocr'): array
{
    $failed = [];
    foreach ($jobIds as $jobId) {
        try {
            reprocess_job_by_id($config, $jobId, $mode, false, true);
        } catch (Throwable $e) {
            $failed[$jobId] = $e->getMessage();
        }
    }
    return $failed;
}

function create_ocr_debug_snapshot_run(array $config, array $jobIds, string $scope = '', bool $requiresReanalysis = true): array
{
    if (!$requiresReanalysis) {
        $result = export_ocr_debug_data($config, $jobIds, $scope);
        $recordedSnapshot = ocr_debug_record_completed_snapshot_run($result, $scope, false);
        $snapshot = is_array($recordedSnapshot) ? array_merge($result, $recordedSnapshot) : $result;
        return [
            'snapshot' => [
                ...$snapshot,
                'status' => 'completed',
                'totalJobs' => (int) ($result['exportedCount'] ?? 0),
                'completedJobs' => (int) ($result['exportedCount'] ?? 0),
                'failedJobs' => (int) ($result['skippedCount'] ?? 0),
                'requiresReanalysis' => false,
            ],
            'completed' => true,
        ];
    }

    $normalizedJobIds = ocr_debug_normalize_existing_job_ids($config, $jobIds);
    $exportDirectory = create_ocr_debug_export_directory($config, $scope);
    $folderName = basename($exportDirectory);
    $now = now_iso();
    $scopeSlug = ocr_debug_export_scope_slug($scope);
    $filterLabel = ocr_debug_export_scope_label($scopeSlug);
    $pdo = ocr_debug_runs_pdo();

    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            "INSERT INTO analysis_snapshots (
                folder_name, status, scope, filter_label, export_directory, job_ids_json,
                total_jobs, completed_jobs, failed_jobs, created_at, created_from_rules_version,
                requires_reanalysis
            ) VALUES (
                :folder_name, 'processing', :scope, :filter_label, :export_directory, :job_ids_json,
                :total_jobs, 0, 0, :created_at, :created_from_rules_version, 1
            )"
        );
        $statement->execute([
            ':folder_name' => $folderName,
            ':scope' => $scopeSlug,
            ':filter_label' => $filterLabel,
            ':export_directory' => $exportDirectory,
            ':job_ids_json' => ocr_debug_json_encode_array($normalizedJobIds),
            ':total_jobs' => count($normalizedJobIds),
            ':created_at' => $now,
            ':created_from_rules_version' => active_archiving_rules_version(),
        ]);
        $snapshotId = (int) $pdo->lastInsertId();
        $jobStatement = $pdo->prepare(
            "INSERT INTO analysis_snapshot_jobs (snapshot_id, job_id, status)
            VALUES (:snapshot_id, :job_id, 'processing')"
        );
        foreach ($normalizedJobIds as $jobId) {
            $jobStatement->execute([
                ':snapshot_id' => $snapshotId,
                ':job_id' => $jobId,
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    ocr_debug_link_settings_backup_to_snapshot($snapshotId);

    $queueFailures = ocr_debug_queue_reanalysis_for_run_jobs($config, $normalizedJobIds);
    foreach ($queueFailures as $jobId => $message) {
        ocr_debug_mark_snapshot_job_failed($config, $jobId, $message);
    }

    $snapshot = ocr_debug_snapshot_by_id($snapshotId);
    return [
        'snapshot' => is_array($snapshot) ? $snapshot : [
            'id' => $snapshotId,
            'folderName' => $folderName,
            'exportDirectory' => $exportDirectory,
            'status' => 'processing',
            'totalJobs' => count($normalizedJobIds),
            'completedJobs' => 0,
            'failedJobs' => count($queueFailures),
        ],
        'completed' => false,
    ];
}

function ocr_debug_record_completed_snapshot_run(array $result, string $scope, bool $requiresReanalysis): ?array
{
    $folderName = is_string($result['folderName'] ?? null) ? (string) $result['folderName'] : '';
    $exportDirectory = is_string($result['exportDirectory'] ?? null) ? (string) $result['exportDirectory'] : '';
    if ($folderName === '' || $exportDirectory === '') {
        return null;
    }
    $jobIds = array_values(array_filter(
        is_array($result['exportedJobIds'] ?? null) ? $result['exportedJobIds'] : [],
        static fn ($jobId): bool => is_string($jobId) && $jobId !== ''
    ));
    $skippedJobIds = array_values(array_filter(
        is_array($result['skippedJobIds'] ?? null) ? $result['skippedJobIds'] : [],
        static fn ($jobId): bool => is_string($jobId) && $jobId !== ''
    ));
    $now = now_iso();
    $pdo = ocr_debug_runs_pdo();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            "INSERT OR IGNORE INTO analysis_snapshots (
                folder_name, status, scope, filter_label, export_directory, job_ids_json, skipped_job_ids_json,
                total_jobs, completed_jobs, failed_jobs, created_at, completed_at, created_from_rules_version,
                requires_reanalysis
            ) VALUES (
                :folder_name, 'completed', :scope, :filter_label, :export_directory, :job_ids_json, :skipped_job_ids_json,
                :total_jobs, :completed_jobs, :failed_jobs, :created_at, :completed_at, :created_from_rules_version,
                :requires_reanalysis
            )"
        );
        $statement->execute([
            ':folder_name' => $folderName,
            ':scope' => ocr_debug_export_scope_slug($scope),
            ':filter_label' => ocr_debug_export_scope_label($scope),
            ':export_directory' => $exportDirectory,
            ':job_ids_json' => ocr_debug_json_encode_array($jobIds),
            ':skipped_job_ids_json' => ocr_debug_json_encode_array($skippedJobIds),
            ':total_jobs' => count($jobIds) + count($skippedJobIds),
            ':completed_jobs' => count($jobIds),
            ':failed_jobs' => count($skippedJobIds),
            ':created_at' => $now,
            ':completed_at' => $now,
            ':created_from_rules_version' => active_archiving_rules_version(),
            ':requires_reanalysis' => $requiresReanalysis ? 1 : 0,
        ]);
        $snapshotId = (int) $pdo->lastInsertId();
        if ($snapshotId > 0) {
            $jobStatement = $pdo->prepare(
                "INSERT OR IGNORE INTO analysis_snapshot_jobs (snapshot_id, job_id, status, completed_at)
                VALUES (:snapshot_id, :job_id, 'completed', :completed_at)"
            );
            foreach ($jobIds as $jobId) {
                $jobStatement->execute([
                    ':snapshot_id' => $snapshotId,
                    ':job_id' => $jobId,
                    ':completed_at' => $now,
                ]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $snapshotRow = $snapshotId > 0
        ? ocr_debug_snapshot_row_by_id($snapshotId)
        : ocr_debug_snapshot_row_by_folder_name($folderName);
    if (is_array($snapshotRow) && isset($snapshotRow['id'])) {
        ocr_debug_link_settings_backup_to_snapshot((int) $snapshotRow['id']);
        return ocr_debug_snapshot_by_id((int) $snapshotRow['id']);
    }

    return null;
}

function ocr_debug_snapshot_by_id(int $snapshotId): ?array
{
    $pdo = ocr_debug_runs_pdo();
    $statement = $pdo->prepare('SELECT * FROM analysis_snapshots WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $snapshotId]);
    $row = $statement->fetch();
    return is_array($row) ? ocr_debug_snapshot_public_row($row) : null;
}

function ocr_debug_mark_snapshot_job_failed(array $config, string $jobId, string $message): void
{
    unset($config);
    $pdo = ocr_debug_runs_pdo();
    $statement = $pdo->prepare(
        "UPDATE analysis_snapshot_jobs
        SET status = 'failed', error_message = :error_message, completed_at = :completed_at
        WHERE job_id = :job_id AND status IN ('queued', 'processing')"
    );
    $statement->execute([
        ':job_id' => $jobId,
        ':error_message' => $message,
        ':completed_at' => now_iso(),
    ]);
    ocr_debug_refresh_all_snapshot_run_statuses();
}

function ocr_debug_mark_snapshot_job_completed(array $config, string $jobId): void
{
    unset($config);
    $pdo = ocr_debug_runs_pdo();
    $statement = $pdo->prepare(
        "UPDATE analysis_snapshot_jobs
        SET status = 'completed', error_message = NULL, completed_at = :completed_at
        WHERE job_id = :job_id AND status IN ('queued', 'processing')"
    );
    $statement->execute([
        ':job_id' => $jobId,
        ':completed_at' => now_iso(),
    ]);
    ocr_debug_refresh_all_snapshot_run_statuses();
}

function ocr_debug_refresh_all_snapshot_run_statuses(): void
{
    $pdo = ocr_debug_runs_pdo();
    $rows = $pdo->query("SELECT id FROM analysis_snapshots WHERE status = 'processing'")->fetchAll();
    if (!is_array($rows)) {
        return;
    }
    foreach ($rows as $row) {
        if (isset($row['id'])) {
            ocr_debug_refresh_snapshot_run_status((int) $row['id']);
        }
    }
}

function ocr_debug_refresh_snapshot_run_status(int $snapshotId): void
{
    $pdo = ocr_debug_runs_pdo();
    $countsStatement = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_jobs,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_jobs,
            COUNT(*) AS total_jobs
        FROM analysis_snapshot_jobs
        WHERE snapshot_id = :snapshot_id"
    );
    $countsStatement->execute([':snapshot_id' => $snapshotId]);
    $counts = $countsStatement->fetch();
    if (!is_array($counts)) {
        return;
    }
    $completedJobs = (int) ($counts['completed_jobs'] ?? 0);
    $failedJobs = (int) ($counts['failed_jobs'] ?? 0);
    $totalJobs = (int) ($counts['total_jobs'] ?? 0);
    $status = 'processing';
    $completedAt = null;
    if ($totalJobs > 0 && ($completedJobs + $failedJobs) >= $totalJobs) {
        $status = $failedJobs > 0 ? ($completedJobs > 0 ? 'completed_with_errors' : 'failed') : 'completed';
        $completedAt = now_iso();
    }

    if ($status === 'completed' || $status === 'completed_with_errors') {
        ocr_debug_finalize_snapshot_run($snapshotId);
        $statusStatement = $pdo->prepare('SELECT status FROM analysis_snapshots WHERE id = :id LIMIT 1');
        $statusStatement->execute([':id' => $snapshotId]);
        $statusRow = $statusStatement->fetch();
        if (is_array($statusRow) && ($statusRow['status'] ?? null) === 'failed') {
            return;
        }
    }

    $statement = $pdo->prepare(
        "UPDATE analysis_snapshots
        SET status = :status,
            completed_jobs = :completed_jobs,
            failed_jobs = :failed_jobs,
            total_jobs = :total_jobs,
            completed_at = COALESCE(completed_at, :completed_at)
        WHERE id = :id"
    );
    $statement->execute([
        ':id' => $snapshotId,
        ':status' => $status,
        ':completed_jobs' => $completedJobs,
        ':failed_jobs' => $failedJobs,
        ':total_jobs' => $totalJobs,
        ':completed_at' => $completedAt,
    ]);
}

function ocr_debug_finalize_snapshot_run(int $snapshotId): void
{
    $pdo = ocr_debug_runs_pdo();
    $snapshotStatement = $pdo->prepare('SELECT * FROM analysis_snapshots WHERE id = :id LIMIT 1');
    $snapshotStatement->execute([':id' => $snapshotId]);
    $snapshot = $snapshotStatement->fetch();
    if (!is_array($snapshot) || is_string($snapshot['completed_at'] ?? null)) {
        return;
    }
    $jobStatement = $pdo->prepare(
        "SELECT job_id
        FROM analysis_snapshot_jobs
        WHERE snapshot_id = :snapshot_id AND status = 'completed'
        ORDER BY job_id"
    );
    $jobStatement->execute([':snapshot_id' => $snapshotId]);
    $jobIds = array_values(array_filter(
        array_map(static fn (array $row): string => is_string($row['job_id'] ?? null) ? (string) $row['job_id'] : '', $jobStatement->fetchAll() ?: []),
        static fn (string $jobId): bool => $jobId !== ''
    ));
    if ($jobIds === []) {
        return;
    }

    try {
        export_ocr_debug_data(
            load_config(),
            $jobIds,
            is_string($snapshot['scope'] ?? null) ? (string) $snapshot['scope'] : 'jobs',
            is_string($snapshot['export_directory'] ?? null) ? (string) $snapshot['export_directory'] : null
        );
        ocr_debug_link_settings_backup_to_snapshot($snapshotId);
    } catch (Throwable $e) {
        $statement = $pdo->prepare("UPDATE analysis_snapshots SET status = 'failed', error_message = :error_message WHERE id = :id");
        $statement->execute([
            ':id' => $snapshotId,
            ':error_message' => $e->getMessage(),
        ]);
    }
}

function ocr_debug_comparison_public_row(array $row): array
{
    $result = null;
    if (is_string($row['result_json'] ?? null) && trim((string) $row['result_json']) !== '') {
        $decoded = json_decode((string) $row['result_json'], true);
        $result = is_array($decoded) ? $decoded : null;
    }
    return [
        'id' => isset($row['id']) ? (int) $row['id'] : null,
        'leftFolderName' => is_string($row['left_folder_name'] ?? null) ? (string) $row['left_folder_name'] : '',
        'rightFolderName' => is_string($row['right_folder_name'] ?? null) ? (string) $row['right_folder_name'] : '',
        'liveSide' => is_string($row['live_side'] ?? null) ? (string) $row['live_side'] : '',
        'status' => is_string($row['status'] ?? null) ? (string) $row['status'] : 'processing',
        'scope' => is_string($row['scope'] ?? null) ? (string) $row['scope'] : 'jobs',
        'filterLabel' => is_string($row['filter_label'] ?? null) ? (string) $row['filter_label'] : ocr_debug_export_scope_label((string) ($row['scope'] ?? 'jobs')),
        'jobIds' => ocr_debug_json_decode_array($row['job_ids_json'] ?? null),
        'totalJobs' => isset($row['total_jobs']) ? (int) $row['total_jobs'] : 0,
        'completedJobs' => isset($row['completed_jobs']) ? (int) $row['completed_jobs'] : 0,
        'failedJobs' => isset($row['failed_jobs']) ? (int) $row['failed_jobs'] : 0,
        'createdAt' => is_string($row['created_at'] ?? null) ? (string) $row['created_at'] : '',
        'completedAt' => is_string($row['completed_at'] ?? null) ? (string) $row['completed_at'] : null,
        'errorMessage' => is_string($row['error_message'] ?? null) ? (string) $row['error_message'] : '',
        'result' => $result,
    ];
}

function list_ocr_debug_comparison_runs(): array
{
    $pdo = ocr_debug_runs_pdo();
    $rows = $pdo->query(
        "SELECT *
        FROM comparison_runs
        WHERE status IN ('processing', 'completed', 'completed_with_errors', 'failed')
        ORDER BY created_at DESC
        LIMIT 5"
    )->fetchAll();
    if (!is_array($rows)) {
        return [];
    }
    return array_map(
        static fn (array $row): array => ocr_debug_comparison_public_row($row),
        array_values(array_filter($rows, static fn ($row): bool => is_array($row)))
    );
}

function ocr_debug_comparison_run_by_id(int $comparisonRunId): ?array
{
    $pdo = ocr_debug_runs_pdo();
    $statement = $pdo->prepare('SELECT * FROM comparison_runs WHERE id = :id LIMIT 1');
    $statement->execute([':id' => $comparisonRunId]);
    $row = $statement->fetch();
    return is_array($row) ? ocr_debug_comparison_public_row($row) : null;
}

function create_ocr_debug_comparison_run(array $config, string $leftFolderName, string $rightFolderName, array $jobIds, string $scope = ''): array
{
    $leftIsLive = ocr_debug_export_is_live_reference($leftFolderName);
    $rightIsLive = ocr_debug_export_is_live_reference($rightFolderName);
    if (!$leftIsLive && !$rightIsLive) {
        return [
            'comparison' => compare_ocr_debug_exports($config, $leftFolderName, $rightFolderName),
            'completed' => true,
        ];
    }
    if ($leftIsLive && $rightIsLive) {
        throw new RuntimeException('Välj en sparad snapshot och Aktuellt läge.');
    }

    $normalizedJobIds = ocr_debug_normalize_existing_job_ids($config, $jobIds);
    $scopeSlug = ocr_debug_export_scope_slug($scope);
    $now = now_iso();
    $pdo = ocr_debug_runs_pdo();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            "INSERT INTO comparison_runs (
                left_folder_name, right_folder_name, live_side, status, scope, filter_label,
                job_ids_json, total_jobs, completed_jobs, failed_jobs, created_at
            ) VALUES (
                :left_folder_name, :right_folder_name, :live_side, 'processing', :scope, :filter_label,
                :job_ids_json, :total_jobs, 0, 0, :created_at
            )"
        );
        $statement->execute([
            ':left_folder_name' => $leftFolderName,
            ':right_folder_name' => $rightFolderName,
            ':live_side' => $leftIsLive ? 'left' : 'right',
            ':scope' => $scopeSlug,
            ':filter_label' => ocr_debug_export_scope_label($scopeSlug),
            ':job_ids_json' => ocr_debug_json_encode_array($normalizedJobIds),
            ':total_jobs' => count($normalizedJobIds),
            ':created_at' => $now,
        ]);
        $runId = (int) $pdo->lastInsertId();
        $jobStatement = $pdo->prepare(
            "INSERT INTO comparison_run_jobs (comparison_run_id, job_id, status)
            VALUES (:comparison_run_id, :job_id, 'processing')"
        );
        foreach ($normalizedJobIds as $jobId) {
            $jobStatement->execute([
                ':comparison_run_id' => $runId,
                ':job_id' => $jobId,
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $queueFailures = ocr_debug_queue_reanalysis_for_run_jobs($config, $normalizedJobIds);
    foreach ($queueFailures as $jobId => $message) {
        ocr_debug_mark_comparison_job_failed($config, $jobId, $message);
    }

    return [
        'comparisonRun' => ocr_debug_comparison_run_by_id($runId),
        'completed' => false,
    ];
}

function ocr_debug_mark_comparison_job_failed(array $config, string $jobId, string $message): void
{
    unset($config);
    $pdo = ocr_debug_runs_pdo();
    $statement = $pdo->prepare(
        "UPDATE comparison_run_jobs
        SET status = 'failed', error_message = :error_message, completed_at = :completed_at
        WHERE job_id = :job_id AND status IN ('queued', 'processing')"
    );
    $statement->execute([
        ':job_id' => $jobId,
        ':error_message' => $message,
        ':completed_at' => now_iso(),
    ]);
    ocr_debug_refresh_all_comparison_run_statuses();
}

function ocr_debug_mark_comparison_job_completed(array $config, string $jobId): void
{
    unset($config);
    $pdo = ocr_debug_runs_pdo();
    $statement = $pdo->prepare(
        "UPDATE comparison_run_jobs
        SET status = 'completed', error_message = NULL, completed_at = :completed_at
        WHERE job_id = :job_id AND status IN ('queued', 'processing')"
    );
    $statement->execute([
        ':job_id' => $jobId,
        ':completed_at' => now_iso(),
    ]);
    ocr_debug_refresh_all_comparison_run_statuses();
}

function ocr_debug_refresh_all_comparison_run_statuses(): void
{
    $pdo = ocr_debug_runs_pdo();
    $rows = $pdo->query("SELECT id FROM comparison_runs WHERE status = 'processing'")->fetchAll();
    if (!is_array($rows)) {
        return;
    }
    foreach ($rows as $row) {
        if (isset($row['id'])) {
            ocr_debug_refresh_comparison_run_status((int) $row['id']);
        }
    }
}

function ocr_debug_refresh_comparison_run_status(int $runId): void
{
    $pdo = ocr_debug_runs_pdo();
    $countsStatement = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_jobs,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_jobs,
            COUNT(*) AS total_jobs
        FROM comparison_run_jobs
        WHERE comparison_run_id = :comparison_run_id"
    );
    $countsStatement->execute([':comparison_run_id' => $runId]);
    $counts = $countsStatement->fetch();
    if (!is_array($counts)) {
        return;
    }
    $completedJobs = (int) ($counts['completed_jobs'] ?? 0);
    $failedJobs = (int) ($counts['failed_jobs'] ?? 0);
    $totalJobs = (int) ($counts['total_jobs'] ?? 0);
    $status = 'processing';
    $completedAt = null;
    if ($totalJobs > 0 && ($completedJobs + $failedJobs) >= $totalJobs) {
        $status = $failedJobs > 0 ? ($completedJobs > 0 ? 'completed_with_errors' : 'failed') : 'completed';
        $completedAt = now_iso();
    }

    if ($status === 'completed' || $status === 'completed_with_errors') {
        ocr_debug_finalize_comparison_run($runId);
        $statusStatement = $pdo->prepare('SELECT status FROM comparison_runs WHERE id = :id LIMIT 1');
        $statusStatement->execute([':id' => $runId]);
        $statusRow = $statusStatement->fetch();
        if (is_array($statusRow) && ($statusRow['status'] ?? null) === 'failed') {
            return;
        }
    }

    $statement = $pdo->prepare(
        "UPDATE comparison_runs
        SET status = :status,
            completed_jobs = :completed_jobs,
            failed_jobs = :failed_jobs,
            total_jobs = :total_jobs,
            completed_at = COALESCE(completed_at, :completed_at)
        WHERE id = :id"
    );
    $statement->execute([
        ':id' => $runId,
        ':status' => $status,
        ':completed_jobs' => $completedJobs,
        ':failed_jobs' => $failedJobs,
        ':total_jobs' => $totalJobs,
        ':completed_at' => $completedAt,
    ]);
}

function ocr_debug_finalize_comparison_run(int $runId): void
{
    $pdo = ocr_debug_runs_pdo();
    $runStatement = $pdo->prepare('SELECT * FROM comparison_runs WHERE id = :id LIMIT 1');
    $runStatement->execute([':id' => $runId]);
    $run = $runStatement->fetch();
    if (!is_array($run) || is_string($run['result_json'] ?? null)) {
        return;
    }

    $jobStatement = $pdo->prepare(
        "SELECT job_id
        FROM comparison_run_jobs
        WHERE comparison_run_id = :comparison_run_id AND status = 'completed'
        ORDER BY job_id"
    );
    $jobStatement->execute([':comparison_run_id' => $runId]);
    $jobIds = array_values(array_filter(
        array_map(static fn (array $row): string => is_string($row['job_id'] ?? null) ? (string) $row['job_id'] : '', $jobStatement->fetchAll() ?: []),
        static fn (string $jobId): bool => $jobId !== ''
    ));
    if ($jobIds === []) {
        return;
    }

    try {
        $config = load_config();
        $result = compare_ocr_debug_exports_with_live(
            $config,
            is_string($run['left_folder_name'] ?? null) ? (string) $run['left_folder_name'] : '',
            is_string($run['right_folder_name'] ?? null) ? (string) $run['right_folder_name'] : '',
            $jobIds,
            is_string($run['scope'] ?? null) ? (string) $run['scope'] : 'jobs'
        );
        $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('Kunde inte serialisera jämförelseresultat.');
        }
        $statement = $pdo->prepare('UPDATE comparison_runs SET result_json = :result_json WHERE id = :id');
        $statement->execute([
            ':id' => $runId,
            ':result_json' => $json,
        ]);
    } catch (Throwable $e) {
        $statement = $pdo->prepare("UPDATE comparison_runs SET status = 'failed', error_message = :error_message WHERE id = :id");
        $statement->execute([
            ':id' => $runId,
            ':error_message' => $e->getMessage(),
        ]);
    }
}

function ocr_debug_handle_job_processing_finished(array $config, string $jobId, bool $success, ?string $errorMessage = null): void
{
    try {
        if ($success) {
            ocr_debug_mark_snapshot_job_completed($config, $jobId);
            ocr_debug_mark_comparison_job_completed($config, $jobId);
            return;
        }
        $message = is_string($errorMessage) && trim($errorMessage) !== '' ? trim($errorMessage) : 'Omanalys misslyckades.';
        ocr_debug_mark_snapshot_job_failed($config, $jobId, $message);
        ocr_debug_mark_comparison_job_failed($config, $jobId, $message);
    } catch (Throwable $e) {
        // Snapshot/comparison progress is best-effort relative to the job state itself.
    }
}

function update_ocr_debug_export_comment(array $config, string $folderName, string $comment): array
{
    $exportDirectory = ocr_debug_export_directory_path_from_name($config, $folderName);
    if ($exportDirectory === null || !is_dir($exportDirectory)) {
        throw new RuntimeException('Export folder not found');
    }

    $manifestPath = ocr_debug_export_manifest_path($exportDirectory);
    $manifest = is_file($manifestPath) ? load_json_file($manifestPath) : null;
    if (!is_array($manifest)) {
        $manifest = ocr_debug_export_manifest_from_directory($exportDirectory);
    }
    if (!is_array($manifest)) {
        throw new RuntimeException('Kunde inte läsa snapshotens manifest.');
    }

    $manifest['comment'] = trim($comment);
    write_json_file($manifestPath, $manifest);

    return ocr_debug_export_manifest_from_directory($exportDirectory) ?? $manifest;
}

function debug_export_string_list(mixed $value): array
{
    if (!is_array($value)) {
        return [];
    }

    $items = [];
    foreach ($value as $item) {
        if (is_string($item) || is_numeric($item)) {
            $text = trim((string) $item);
            if ($text !== '') {
                $items[] = $text;
            }
        }
    }

    return array_values(array_unique($items));
}

function debug_export_scalar_text(mixed $value): string
{
    return is_string($value) || is_numeric($value) ? trim((string) $value) : '';
}

function debug_export_scalar_string(mixed $value): string
{
    return is_string($value) || is_numeric($value) ? (string) $value : '';
}

function debug_export_float_or_null(mixed $value): ?float
{
    return is_numeric($value) ? (float) $value : null;
}

function debug_export_int_or_null(mixed $value): ?int
{
    return is_int($value) ? $value : (is_numeric($value) ? (int) $value : null);
}

function debug_export_bbox_or_null(mixed $bbox): ?array
{
    if (!is_array($bbox)) {
        return null;
    }

    $normalized = [];
    foreach (['x0', 'y0', 'x1', 'y1'] as $key) {
        if (!is_numeric($bbox[$key] ?? null)) {
            return null;
        }
        $normalized[$key] = (float) $bbox[$key];
    }

    return $normalized;
}

function debug_export_bbox_signature(mixed $bbox): string
{
    $normalized = debug_export_bbox_or_null($bbox);
    if ($normalized === null) {
        return '';
    }

    return implode(',', array_map(
        static fn (float $value): string => rtrim(rtrim(sprintf('%.3F', $value), '0'), '.'),
        [$normalized['x0'], $normalized['y0'], $normalized['x1'], $normalized['y1']]
    ));
}

function debug_export_bbox_word_indexes_for_page(array $page, mixed $bbox): array
{
    $normalizedBbox = debug_export_bbox_or_null($bbox);
    $words = is_array($page['words'] ?? null) ? $page['words'] : [];
    if ($normalizedBbox === null || $words === []) {
        return [];
    }

    $indexes = [];
    foreach ($words as $fallbackIndex => $word) {
        if (!is_array($word)) {
            continue;
        }
        $wordBbox = debug_export_bbox_or_null($word['bbox'] ?? $word);
        if ($wordBbox === null) {
            continue;
        }

        $wordIndex = debug_export_int_or_null($word['index'] ?? null);
        if ($wordIndex === null) {
            $wordIndex = is_int($fallbackIndex) ? $fallbackIndex : null;
        }
        if ($wordIndex === null || $wordIndex < 0) {
            continue;
        }

        $centerX = ($wordBbox['x0'] + $wordBbox['x1']) / 2;
        $centerY = ($wordBbox['y0'] + $wordBbox['y1']) / 2;
        if (
            $centerX >= $normalizedBbox['x0']
            && $centerX <= $normalizedBbox['x1']
            && $centerY >= $normalizedBbox['y0']
            && $centerY <= $normalizedBbox['y1']
        ) {
            $indexes[$wordIndex + 1] = true;
            continue;
        }

        $overlapWidth = max(0.0, min($wordBbox['x1'], $normalizedBbox['x1']) - max($wordBbox['x0'], $normalizedBbox['x0']));
        $overlapHeight = max(0.0, min($wordBbox['y1'], $normalizedBbox['y1']) - max($wordBbox['y0'], $normalizedBbox['y0']));
        $overlapArea = $overlapWidth * $overlapHeight;
        $wordArea = max(1.0, ($wordBbox['x1'] - $wordBbox['x0']) * ($wordBbox['y1'] - $wordBbox['y0']));
        if (($overlapArea / $wordArea) >= 0.35) {
            $indexes[$wordIndex + 1] = true;
        }
    }

    $result = array_keys($indexes);
    sort($result, SORT_NUMERIC);
    return array_values($result);
}

function debug_export_page_for_match(array $pagesByNumber, array $match): ?array
{
    $pageNumber = debug_export_int_or_null($match['pageNumber'] ?? null);
    if ($pageNumber !== null && is_array($pagesByNumber[$pageNumber] ?? null)) {
        return $pagesByNumber[$pageNumber];
    }

    if (count($pagesByNumber) === 1) {
        $page = reset($pagesByNumber);
        return is_array($page) ? $page : null;
    }

    return null;
}

function debug_export_accepted_candidate(array $match, int $matchIndex, ?array $page = null): array
{
    $confidence = debug_export_float_or_null($match['finalConfidence'] ?? null)
        ?? debug_export_float_or_null($match['confidence'] ?? null)
        ?? 0.0;
    $labelBbox = debug_export_bbox_or_null($match['labelBbox'] ?? null);
    $valueBbox = debug_export_bbox_or_null($match['valueBbox'] ?? null);

    $candidate = [
        'value' => debug_export_scalar_text($match['value'] ?? ''),
        'confidence' => clamp_confidence($confidence),
        'matchIndex' => $matchIndex,
        'raw' => debug_export_scalar_string($match['raw'] ?? ''),
        'extractedRaw' => debug_export_scalar_string($match['extractedRaw'] ?? ''),
        'searchTerm' => debug_export_scalar_text($match['searchTerm'] ?? ''),
        'labelText' => debug_export_scalar_text($match['labelText'] ?? ''),
        'matchText' => debug_export_scalar_string($match['matchText'] ?? ($match['raw'] ?? '')),
        'source' => debug_export_scalar_text($match['source'] ?? ''),
        'between' => debug_export_scalar_string($match['between'] ?? ''),
        'noiseText' => debug_export_scalar_string($match['noiseText'] ?? ''),
        'matchType' => debug_export_scalar_text($match['matchType'] ?? ''),
        'scopeType' => debug_export_scalar_text($match['scopeType'] ?? ''),
        'scopeText' => debug_export_scalar_string($match['scopeText'] ?? ''),
        'scopeMatchedText' => debug_export_scalar_string($match['scopeMatchedText'] ?? ''),
    ];

    foreach ([
        'baseConfidence',
        'finalConfidence',
        'score',
        'fullConfidenceScore',
        'yRatio',
        'centerDistance',
        'relativeTextSize',
        'uppercaseRatio',
        'textDensityRatio',
        'noisePenalty',
        'trailingDelimiterPenalty',
        'positionPenalty',
        'verticalDistancePenalty',
        'verticalDistance',
        'verticalNormalizedDistance',
        'otherMatchKeyPenalty',
        'positionDiff',
        'positionNormalizedDiff',
    ] as $key) {
        $number = debug_export_float_or_null($match[$key] ?? null);
        if ($number !== null) {
            $candidate[$key] = in_array($key, ['score', 'fullConfidenceScore', 'yRatio', 'centerDistance', 'relativeTextSize', 'uppercaseRatio', 'textDensityRatio', 'verticalDistance', 'verticalNormalizedDistance', 'positionDiff', 'positionNormalizedDiff'], true)
                ? $number
                : clamp_confidence($number);
        }
    }
    foreach (['pageNumber', 'lineIndex', 'labelLineIndex', 'start', 'ruleSetIndex', 'scopeLineIndex', 'wordCount'] as $key) {
        $number = debug_export_int_or_null($match[$key] ?? null);
        if ($number !== null) {
            $candidate[$key] = $number;
        }
    }
    foreach (['positionPenaltyAxis', 'mainDirection', 'invalidReason'] as $key) {
        $text = debug_export_scalar_text($match[$key] ?? '');
        if ($text !== '') {
            $candidate[$key] = $text;
        }
    }
    if (array_key_exists('scopeIsRegex', $match)) {
        $candidate['scopeIsRegex'] = ($match['scopeIsRegex'] ?? false) === true;
    }
    if (is_array($match['noiseSegments'] ?? null)) {
        $candidate['noiseSegments'] = array_values($match['noiseSegments']);
    }
    if (is_array($match['captureRanges'] ?? null)) {
        $candidate['captureRanges'] = array_values($match['captureRanges']);
    }
    if ($labelBbox !== null) {
        $candidate['keyBBox'] = $labelBbox;
        if (is_array($page)) {
            $keyBBoxIndexes = debug_export_bbox_word_indexes_for_page($page, $labelBbox);
            if ($keyBBoxIndexes !== []) {
                $candidate['keyBBoxIndexes'] = $keyBBoxIndexes;
            }
        }
    }
    if ($valueBbox !== null) {
        $candidate['valueBBox'] = $valueBbox;
        if (is_array($page)) {
            $valueBBoxIndexes = debug_export_bbox_word_indexes_for_page($page, $valueBbox);
            if ($valueBBoxIndexes !== []) {
                $candidate['valueBBoxIndexes'] = $valueBBoxIndexes;
            }
        }
    }

    return $candidate;
}

function debug_export_match_rejected_by_zone_barrier(array $match, array $zoneMatches): bool
{
    if ($zoneMatches === []) {
        return false;
    }

    $labelBbox = normalize_debug_word_bbox($match['labelBbox'] ?? null);
    $valueBbox = normalize_debug_word_bbox($match['valueBbox'] ?? null);
    if ($labelBbox === null || $valueBbox === null) {
        return false;
    }

    $pageNumber = is_int($match['pageNumber'] ?? null) ? (int) $match['pageNumber'] : null;
    return candidate_crosses_zone_barrier(
        $labelBbox,
        $valueBbox,
        connector_points_between_bboxes($labelBbox, $valueBbox),
        $zoneMatches,
        $pageNumber
    );
}

function debug_export_data_field_snapshot(string $fieldKey, mixed $values, array $meta, float $acceptanceThreshold, array $zoneMatches = [], array $pagesByNumber = []): array
{
    $name = is_string($meta['name'] ?? null) && trim((string) $meta['name']) !== ''
        ? trim((string) $meta['name'])
        : $fieldKey;
    $valueList = debug_export_string_list($values);
    $matches = is_array($meta['matches'] ?? null) ? array_values($meta['matches']) : [];
    $acceptedCandidates = [];

    foreach ($matches as $matchIndex => $match) {
        if (!is_array($match)) {
            continue;
        }
        if (debug_export_match_rejected_by_zone_barrier($match, $zoneMatches)) {
            continue;
        }
        $confidence = debug_export_float_or_null($match['finalConfidence'] ?? null)
            ?? debug_export_float_or_null($match['confidence'] ?? null)
            ?? 0.0;
        $accepted = ($match['accepted'] ?? false) === true || clamp_confidence($confidence) >= $acceptanceThreshold;
        if (!$accepted) {
            continue;
        }
        $candidate = debug_export_accepted_candidate($match, $matchIndex, debug_export_page_for_match($pagesByNumber, $match));
        if ($candidate['value'] === '') {
            continue;
        }
        $acceptedCandidates[] = $candidate;
    }

    return [
        'key' => $fieldKey,
        'name' => $name,
        'fieldKey' => $fieldKey,
        'fieldName' => $name,
        'values' => $valueList,
        'canonicalValue' => $valueList[0] ?? '',
        'acceptedCandidates' => $acceptedCandidates,
    ];
}

function debug_export_label_name_map(array $extractedData): array
{
    $map = [];
    foreach (['systemLabelMatches', 'labelMatches'] as $key) {
        $matches = is_array($extractedData[$key] ?? null) ? $extractedData[$key] : [];
        foreach ($matches as $match) {
            if (!is_array($match)) {
                continue;
            }
            $id = is_string($match['id'] ?? null) ? trim((string) $match['id']) : '';
            $name = is_string($match['name'] ?? null) ? trim((string) $match['name']) : '';
            if ($id !== '') {
                $map[$id] = $name !== '' ? $name : $id;
            }
        }
    }
    return $map;
}

function debug_export_document_metadata_for_job(string $jobId, string $jobDir): array
{
    $extractedPath = rtrim($jobDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'extracted.json';
    $jobPath = rtrim($jobDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'job.json';
    $jobData = load_json_file($jobPath);
    $jobData = is_array($jobData) ? $jobData : [];
    $extractedData = load_json_file($extractedPath);
    if (!is_array($extractedData)) {
        $analysis = is_array($jobData['analysis'] ?? null) ? $jobData['analysis'] : [];
        $extractedData = $analysis;
    }
    static $rules = null;
    static $displayMaps = null;
    static $dataFieldAcceptanceThreshold = null;
    if (!is_array($rules)) {
        $rules = load_active_archiving_rules();
        $displayMaps = archiving_review_display_maps($rules, $rules);
    }
    if (!is_float($dataFieldAcceptanceThreshold)) {
        $matchingPayload = load_matching_settings_payload();
        $dataFieldAcceptanceThreshold = is_numeric($matchingPayload['dataFieldAcceptanceThreshold'] ?? null)
            ? clamp_confidence((float) $matchingPayload['dataFieldAcceptanceThreshold'])
            : 0.5;
    }
    $displayMaps = is_array($displayMaps) ? $displayMaps : archiving_review_display_maps($rules, $rules);
    $zoneMatches = [];
    try {
        if (is_array($rules) && is_array($jobData)) {
            $ocrText = load_job_analysis_text($jobDir, null);
            $matchingPayload = load_matching_settings_payload();
            $replacementMap = replacement_map(
                is_array($matchingPayload['replacements'] ?? null) ? $matchingPayload['replacements'] : []
            );
            $zoneMatches = detect_configured_zone_matches(
                split_lines_for_matching($ocrText),
                is_array($rules['zones'] ?? null) ? $rules['zones'] : [],
                $replacementMap,
                build_matching_line_geometries_for_job($jobData, $ocrText),
                is_array($rules['valuePatterns'] ?? null) ? $rules['valuePatterns'] : []
            );
        }
    } catch (Throwable $e) {
        $zoneMatches = is_array($extractedData['zoneMatches'] ?? null) ? $extractedData['zoneMatches'] : [];
    }
    $autoResult = is_array($extractedData['autoArchivingResult'] ?? null)
        ? normalize_auto_archiving_result($extractedData['autoArchivingResult'])
        : [];

    $scalarMetadata = [];
    $appendScalarMetadata = static function (string $key, string $label, mixed $value) use (&$scalarMetadata): void {
        $text = is_scalar($value) || $value === null ? trim((string) $value) : '';
        $scalarMetadata[] = [
            'key' => $key,
            'label' => $label,
            'value' => $text,
        ];
    };

    $selectedClientDirName = is_string($jobData['selectedClientDirName'] ?? null) ? trim((string) $jobData['selectedClientDirName']) : '';
    $selectedSenderId = isset($jobData['selectedSenderId']) ? (int) $jobData['selectedSenderId'] : 0;
    $selectedFolderId = is_string($jobData['selectedFolderId'] ?? null) ? trim((string) $jobData['selectedFolderId']) : '';
    $clientId = $selectedClientDirName !== '' ? $selectedClientDirName : (is_string($autoResult['clientId'] ?? null) ? trim((string) $autoResult['clientId']) : '');
    $senderId = $selectedSenderId > 0 ? (string) $selectedSenderId : (isset($autoResult['senderId']) ? (string) ((int) $autoResult['senderId']) : '');
    $folderId = $selectedFolderId !== '' ? $selectedFolderId : (is_string($autoResult['folderId'] ?? null) ? trim((string) $autoResult['folderId']) : '');
    $filenameTemplateId = is_string($autoResult['filenameTemplateId'] ?? null) ? trim((string) $autoResult['filenameTemplateId']) : '';

    $appendScalarMetadata('originalFilename', 'Ursprunglig fil', $jobData['originalFilename'] ?? '');
    $appendScalarMetadata('filename', 'Filnamn', $jobData['filename'] ?? ($autoResult['filename'] ?? ''));
    $appendScalarMetadata('createdAt', 'Skapad/importerad', $jobData['createdAt'] ?? '');
    $appendScalarMetadata('clientId', 'Huvudman', $clientId !== '' ? archiving_review_display_value('clientId', $clientId, $displayMaps) : '');
    $appendScalarMetadata('senderId', 'Avsändare', $senderId !== '' ? archiving_review_display_value('senderId', $senderId, $displayMaps) : '');
    $appendScalarMetadata('folderId', 'Mapp', $folderId !== '' ? archiving_review_display_value('folderId', $folderId, $displayMaps) : '');
    $appendScalarMetadata('filenameTemplateId', 'Filnamnsmall', $filenameTemplateId !== '' ? archiving_review_display_value('filenameTemplateId', $filenameTemplateId, $displayMaps) : '');
    $appendScalarMetadata('archiveFolderPath', 'Föreslagen arkivsökväg', $autoResult['archiveFolderPath'] ?? '');
    $appendScalarMetadata('proposedFilename', 'Föreslaget arkivnamn', $autoResult['filename'] ?? '');

    $labelNameMap = debug_export_label_name_map($extractedData);
    $labels = [];
    foreach (debug_export_string_list($extractedData['labels'] ?? []) as $labelId) {
        $labels[] = [
            'id' => $labelId,
            'name' => $labelNameMap[$labelId] ?? $labelId,
        ];
    }
    usort($labels, static fn (array $left, array $right): int => strnatcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? '')));

    $fields = [];
    $extractionFields = is_array($extractedData['extractionFields'] ?? null) ? $extractedData['extractionFields'] : [];
    $fieldMeta = is_array($extractedData['extractionFieldMeta'] ?? null) ? $extractedData['extractionFieldMeta'] : [];
    $pagesByNumber = [];
    $mergedObjectPages = stored_merged_objects_pages($jobId);
    if ($mergedObjectPages === []) {
        $mergedObjectPages = fallback_merged_objects_pages_from_job_debug($jobId);
    }
    foreach ($mergedObjectPages as $page) {
        if (!is_array($page)) {
            continue;
        }
        $pageNumber = debug_export_int_or_null($page['pageNumber'] ?? null);
        if ($pageNumber !== null && $pageNumber > 0) {
            $pagesByNumber[$pageNumber] = $page;
        }
    }
    foreach ($extractionFields as $fieldKey => $values) {
        if (!is_string($fieldKey) && !is_numeric($fieldKey)) {
            continue;
        }
        $normalizedKey = trim((string) $fieldKey);
        if ($normalizedKey === '') {
            continue;
        }
        $meta = is_array($fieldMeta[$normalizedKey] ?? null) ? $fieldMeta[$normalizedKey] : [];
        $fields[] = debug_export_data_field_snapshot($normalizedKey, $values, $meta, $dataFieldAcceptanceThreshold, $zoneMatches, $pagesByNumber);
    }
    usort($fields, static fn (array $left, array $right): int => strnatcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? '')));

    return [
        'jobId' => $jobId,
        'document' => [
            'jobId' => $jobId,
            'originalFilename' => is_string($jobData['originalFilename'] ?? null) ? trim((string) $jobData['originalFilename']) : '',
            'filename' => is_string($jobData['filename'] ?? null) ? trim((string) $jobData['filename']) : '',
            'createdAt' => is_string($jobData['createdAt'] ?? null) ? trim((string) $jobData['createdAt']) : '',
        ],
        'metadata' => $scalarMetadata,
        'labels' => $labels,
        'dataFields' => $fields,
    ];
}

function ocr_debug_export_relative_files(string $exportDirectory): array
{
    $exportDirectory = rtrim($exportDirectory, DIRECTORY_SEPARATOR);
    if (!is_dir($exportDirectory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($exportDirectory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $item) {
        if (!$item->isFile()) {
            continue;
        }
        $path = $item->getPathname();
        $relativePath = substr($path, strlen($exportDirectory) + 1);
        if (!is_string($relativePath) || $relativePath === '') {
            continue;
        }
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
        if ($relativePath === 'manifest.json' || str_starts_with($relativePath, 'document_metadata/')) {
            continue;
        }
        $files[$relativePath] = $path;
    }

    ksort($files, SORT_NATURAL);
    return $files;
}

function ocr_debug_export_meld_command(string $leftPath, string $rightPath): string
{
    $leftPath = normalized_realpath($leftPath) ?? $leftPath;
    $rightPath = normalized_realpath($rightPath) ?? $rightPath;

    $quote = static function (string $path): string {
        return '"' . str_replace(['\\', '"', '$', '`'], ['\\\\', '\\"', '\\$', '\\`'], $path) . '"';
    };

    return 'meld ' . $quote($leftPath) . ' ' . $quote($rightPath);
}

function debug_export_metadata_files(string $exportDirectory): array
{
    $metadataDirectory = rtrim($exportDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'document_metadata';
    if (!is_dir($metadataDirectory)) {
        return [];
    }

    $files = [];
    $paths = glob($metadataDirectory . DIRECTORY_SEPARATOR . '*.json') ?: [];
    foreach ($paths as $path) {
        if (!is_string($path)) {
            continue;
        }
        $basename = basename($path);
        if (preg_match('/^([A-Za-z0-9_-]+)\.json$/', $basename, $matches) === 1) {
            $files[$matches[1]] = $path;
        }
    }
    ksort($files, SORT_NATURAL);
    return $files;
}

function debug_export_label_diff(array $leftLabels, array $rightLabels): array
{
    $normalize = static function (array $labels): array {
        $map = [];
        foreach ($labels as $label) {
            if (!is_array($label)) {
                continue;
            }
            $id = is_string($label['id'] ?? null) ? trim((string) $label['id']) : '';
            $name = is_string($label['name'] ?? null) ? trim((string) $label['name']) : '';
            if ($id !== '') {
                $map[$id] = $name !== '' ? $name : $id;
            }
        }
        ksort($map, SORT_NATURAL);
        return $map;
    };

    $left = $normalize($leftLabels);
    $right = $normalize($rightLabels);
    $added = [];
    $removed = [];
    foreach ($right as $id => $name) {
        if (!array_key_exists($id, $left)) {
            $added[] = $name;
        }
    }
    foreach ($left as $id => $name) {
        if (!array_key_exists($id, $right)) {
            $removed[] = $name;
        }
    }
    sort($added, SORT_NATURAL | SORT_FLAG_CASE);
    sort($removed, SORT_NATURAL | SORT_FLAG_CASE);
    return ['added' => $added, 'removed' => $removed];
}

function debug_export_data_field_diff(array $leftFields, array $rightFields): array
{
    $normalizeBBoxIndexes = static function (mixed $indexes): array {
        if (!is_array($indexes)) {
            return [];
        }
        $normalized = [];
        foreach ($indexes as $index) {
            $number = debug_export_int_or_null($index);
            if ($number !== null && $number > 0) {
                $normalized[$number] = true;
            }
        }
        $result = array_keys($normalized);
        sort($result, SORT_NUMERIC);
        return array_values($result);
    };

    $normalizeCandidate = static function (mixed $candidate, int $fallbackIndex) use ($normalizeBBoxIndexes): ?array {
        if (!is_array($candidate)) {
            return null;
        }
        $value = debug_export_scalar_text($candidate['value'] ?? '');
        if ($value === '') {
            return null;
        }

        $normalized = [
            'value' => $value,
            'confidence' => clamp_confidence(debug_export_float_or_null($candidate['confidence'] ?? null) ?? 0.0),
            'matchIndex' => debug_export_int_or_null($candidate['matchIndex'] ?? null) ?? $fallbackIndex,
            'raw' => debug_export_scalar_string($candidate['raw'] ?? ''),
            'extractedRaw' => debug_export_scalar_string($candidate['extractedRaw'] ?? ''),
            'searchTerm' => debug_export_scalar_text($candidate['searchTerm'] ?? ''),
            'labelText' => debug_export_scalar_text($candidate['labelText'] ?? ''),
            'matchText' => debug_export_scalar_string($candidate['matchText'] ?? ''),
            'source' => debug_export_scalar_text($candidate['source'] ?? ''),
            'between' => debug_export_scalar_string($candidate['between'] ?? ''),
            'noiseText' => debug_export_scalar_string($candidate['noiseText'] ?? ''),
            'matchType' => debug_export_scalar_text($candidate['matchType'] ?? ''),
            'scopeType' => debug_export_scalar_text($candidate['scopeType'] ?? ''),
            'scopeText' => debug_export_scalar_string($candidate['scopeText'] ?? ''),
            'scopeMatchedText' => debug_export_scalar_string($candidate['scopeMatchedText'] ?? ''),
        ];
        foreach ([
            'baseConfidence',
            'finalConfidence',
            'score',
            'fullConfidenceScore',
            'yRatio',
            'centerDistance',
            'relativeTextSize',
            'uppercaseRatio',
            'textDensityRatio',
            'noisePenalty',
            'trailingDelimiterPenalty',
            'positionPenalty',
            'verticalDistancePenalty',
            'verticalDistance',
            'verticalNormalizedDistance',
            'otherMatchKeyPenalty',
            'positionDiff',
            'positionNormalizedDiff',
        ] as $key) {
            $number = debug_export_float_or_null($candidate[$key] ?? null);
            if ($number !== null) {
                $normalized[$key] = in_array($key, ['score', 'fullConfidenceScore', 'yRatio', 'centerDistance', 'relativeTextSize', 'uppercaseRatio', 'textDensityRatio', 'verticalDistance', 'verticalNormalizedDistance', 'positionDiff', 'positionNormalizedDiff'], true)
                    ? $number
                    : clamp_confidence($number);
            }
        }
        foreach (['pageNumber', 'lineIndex', 'labelLineIndex', 'start', 'ruleSetIndex', 'scopeLineIndex', 'wordCount'] as $key) {
            $number = debug_export_int_or_null($candidate[$key] ?? null);
            if ($number !== null) {
                $normalized[$key] = $number;
            }
        }
        foreach (['positionPenaltyAxis', 'mainDirection', 'invalidReason'] as $key) {
            $text = debug_export_scalar_text($candidate[$key] ?? '');
            if ($text !== '') {
                $normalized[$key] = $text;
            }
        }
        if (array_key_exists('scopeIsRegex', $candidate)) {
            $normalized['scopeIsRegex'] = ($candidate['scopeIsRegex'] ?? false) === true;
        }
        if (is_array($candidate['noiseSegments'] ?? null)) {
            $normalized['noiseSegments'] = array_values($candidate['noiseSegments']);
        }
        if (is_array($candidate['captureRanges'] ?? null)) {
            $normalized['captureRanges'] = array_values($candidate['captureRanges']);
        }
        $keyBbox = debug_export_bbox_or_null($candidate['keyBBox'] ?? ($candidate['labelBbox'] ?? null));
        $valueBbox = debug_export_bbox_or_null($candidate['valueBBox'] ?? null);
        if ($keyBbox !== null) {
            $normalized['keyBBox'] = $keyBbox;
        }
        if ($valueBbox !== null) {
            $normalized['valueBBox'] = $valueBbox;
        }
        $keyBBoxIndexes = $normalizeBBoxIndexes($candidate['keyBBoxIndexes'] ?? null);
        if ($keyBBoxIndexes !== []) {
            $normalized['keyBBoxIndexes'] = $keyBBoxIndexes;
        }
        $valueBBoxIndexes = $normalizeBBoxIndexes($candidate['valueBBoxIndexes'] ?? null);
        if ($valueBBoxIndexes !== []) {
            $normalized['valueBBoxIndexes'] = $valueBBoxIndexes;
        }

        return $normalized;
    };

    $normalizeCandidates = static function (array $field) use ($normalizeCandidate): array {
        $sourceCandidates = is_array($field['acceptedCandidates'] ?? null) ? array_values($field['acceptedCandidates']) : [];
        if ($sourceCandidates === []) {
            $sourceCandidates = array_map(
                static fn (string $value): array => ['value' => $value, 'confidence' => 0.0],
                debug_export_string_list($field['values'] ?? [])
            );
        }

        $map = [];
        foreach ($sourceCandidates as $index => $candidate) {
            $normalized = $normalizeCandidate($candidate, $index);
            if ($normalized === null) {
                continue;
            }
            $map[] = $normalized;
        }
        usort($map, static function (array $left, array $right): int {
            $leftConfidence = debug_export_float_or_null($left['finalConfidence'] ?? null)
                ?? debug_export_float_or_null($left['confidence'] ?? null)
                ?? 0.0;
            $rightConfidence = debug_export_float_or_null($right['finalConfidence'] ?? null)
                ?? debug_export_float_or_null($right['confidence'] ?? null)
                ?? 0.0;
            $confidenceCompare = $rightConfidence <=> $leftConfidence;
            if ($confidenceCompare !== 0) {
                return $confidenceCompare;
            }
            $valueCompare = strnatcasecmp((string) ($left['value'] ?? ''), (string) ($right['value'] ?? ''));
            if ($valueCompare !== 0) {
                return $valueCompare;
            }
            $pageCompare = ((int) ($left['pageNumber'] ?? PHP_INT_MAX)) <=> ((int) ($right['pageNumber'] ?? PHP_INT_MAX));
            if ($pageCompare !== 0) {
                return $pageCompare;
            }
            return strnatcasecmp((string) ($left['searchTerm'] ?? ''), (string) ($right['searchTerm'] ?? ''));
        });

        return $map;
    };

    $publicCandidate = static function (array $candidate): array {
        unset($candidate['matchIndex']);
        return $candidate;
    };

    $markSelectedCandidates = static function (array $candidates) use ($publicCandidate): array {
        $public = [];
        $selectedIndex = $candidates !== [] ? 0 : null;

        foreach ($candidates as $index => $candidate) {
            $candidate['selected'] = $selectedIndex !== null && $index === $selectedIndex;
            $public[] = $publicCandidate($candidate);
        }

        return $public;
    };

    $candidateListComparable = static function (array $candidates): array {
        return array_map(static function (array $candidate): array {
            $confidence = debug_export_float_or_null($candidate['finalConfidence'] ?? null)
                ?? debug_export_float_or_null($candidate['confidence'] ?? null)
                ?? 0.0;
            $value = debug_export_scalar_text($candidate['value'] ?? '');
            $searchTerm = debug_export_scalar_text($candidate['searchTerm'] ?? '');
            $labelText = debug_export_scalar_text($candidate['labelText'] ?? '');
            return [
                'value' => function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value),
                'confidence' => round(clamp_confidence($confidence), 6),
                'pageNumber' => debug_export_int_or_null($candidate['pageNumber'] ?? null),
                'searchTerm' => function_exists('mb_strtolower') ? mb_strtolower($searchTerm, 'UTF-8') : strtolower($searchTerm),
                'labelText' => function_exists('mb_strtolower') ? mb_strtolower($labelText, 'UTF-8') : strtolower($labelText),
                'selected' => ($candidate['selected'] ?? false) === true,
            ];
        }, $candidates);
    };

    $normalize = static function (array $fields): array {
        $map = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $key = is_string($field['fieldKey'] ?? null) ? trim((string) $field['fieldKey']) : '';
            if ($key === '') {
                $key = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
            }
            if ($key === '') {
                continue;
            }
            $name = is_string($field['fieldName'] ?? null) && trim((string) $field['fieldName']) !== ''
                ? trim((string) $field['fieldName'])
                : (is_string($field['name'] ?? null) && trim((string) $field['name']) !== ''
                ? trim((string) $field['name'])
                : $key);
            $values = debug_export_string_list($field['values'] ?? []);
            sort($values, SORT_NATURAL);
            $canonicalValue = debug_export_scalar_text($field['canonicalValue'] ?? '');
            if ($canonicalValue === '') {
                $canonicalValue = $values[0] ?? '';
            }
            $map[$key] = [
                'key' => $key,
                'name' => $name,
                'values' => $values,
                'canonicalValue' => $canonicalValue,
                'acceptedCandidates' => is_array($field['acceptedCandidates'] ?? null) ? array_values($field['acceptedCandidates']) : [],
            ];
        }
        ksort($map, SORT_NATURAL);
        return $map;
    };

    $left = $normalize($leftFields);
    $right = $normalize($rightFields);
    $keys = array_values(array_unique(array_merge(array_keys($left), array_keys($right))));
    sort($keys, SORT_NATURAL);
    $diffs = [];
    foreach ($keys as $key) {
        $leftField = $left[$key] ?? null;
        $rightField = $right[$key] ?? null;
        $name = is_array($rightField) ? (string) $rightField['name'] : (is_array($leftField) ? (string) $leftField['name'] : $key);
        $leftCandidates = is_array($leftField) ? $normalizeCandidates($leftField) : [];
        $rightCandidates = is_array($rightField) ? $normalizeCandidates($rightField) : [];
        $leftCandidates = $markSelectedCandidates($leftCandidates);
        $rightCandidates = $markSelectedCandidates($rightCandidates);
        if ($candidateListComparable($leftCandidates) === $candidateListComparable($rightCandidates)) {
            continue;
        }
        $diffs[] = [
            'key' => $key,
            'name' => $name,
            'leftCandidates' => $leftCandidates,
            'rightCandidates' => $rightCandidates,
        ];
    }
    return $diffs;
}

function debug_export_metadata_scalar_diff(array $leftMetadata, array $rightMetadata): array
{
    $normalize = static function (array $items): array {
        $map = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $key = is_string($item['key'] ?? null) ? trim((string) $item['key']) : '';
            if ($key === '') {
                continue;
            }
            $label = is_string($item['label'] ?? null) && trim((string) $item['label']) !== ''
                ? trim((string) $item['label'])
                : $key;
            $value = is_scalar($item['value'] ?? null) || ($item['value'] ?? null) === null
                ? trim((string) ($item['value'] ?? ''))
                : '';
            $map[$key] = [
                'key' => $key,
                'label' => $label,
                'value' => $value,
            ];
        }
        ksort($map, SORT_NATURAL);
        return $map;
    };

    $left = $normalize($leftMetadata);
    $right = $normalize($rightMetadata);
    $keys = array_values(array_unique(array_merge(array_keys($left), array_keys($right))));
    sort($keys, SORT_NATURAL);

    $diffs = [];
    foreach ($keys as $key) {
        $leftItem = $left[$key] ?? null;
        $rightItem = $right[$key] ?? null;
        $label = is_array($rightItem) ? (string) $rightItem['label'] : (is_array($leftItem) ? (string) $leftItem['label'] : $key);
        $leftValue = is_array($leftItem) ? (string) $leftItem['value'] : '';
        $rightValue = is_array($rightItem) ? (string) $rightItem['value'] : '';
        if ($leftValue === $rightValue) {
            continue;
        }
        $diffs[] = [
            'key' => $key,
            'label' => $label,
            'from' => $leftValue,
            'to' => $rightValue,
        ];
    }
    return $diffs;
}

function debug_export_metadata_scalar_value(array $metadata, string $key): ?array
{
    foreach ($metadata as $item) {
        if (!is_array($item)) {
            continue;
        }
        $itemKey = is_string($item['key'] ?? null) ? trim((string) $item['key']) : '';
        if ($itemKey !== $key) {
            continue;
        }
        $label = is_string($item['label'] ?? null) && trim((string) $item['label']) !== ''
            ? trim((string) $item['label'])
            : $key;
        $value = is_scalar($item['value'] ?? null) || ($item['value'] ?? null) === null
            ? trim((string) ($item['value'] ?? ''))
            : '';
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
        ];
    }

    return null;
}

function compare_debug_export_document_metadata(string $leftDirectory, string $rightDirectory): array
{
    $leftFiles = debug_export_metadata_files($leftDirectory);
    $rightFiles = debug_export_metadata_files($rightDirectory);
    $jobIds = array_values(array_unique(array_merge(array_keys($leftFiles), array_keys($rightFiles))));
    sort($jobIds, SORT_NATURAL);

    $diffs = [];
    foreach ($jobIds as $jobId) {
        $left = isset($leftFiles[$jobId]) ? load_json_file($leftFiles[$jobId]) : null;
        $right = isset($rightFiles[$jobId]) ? load_json_file($rightFiles[$jobId]) : null;
        $left = is_array($left) ? $left : ['jobId' => $jobId, 'labels' => [], 'dataFields' => []];
        $right = is_array($right) ? $right : ['jobId' => $jobId, 'labels' => [], 'dataFields' => []];
        $labelDiff = debug_export_label_diff(
            is_array($left['labels'] ?? null) ? $left['labels'] : [],
            is_array($right['labels'] ?? null) ? $right['labels'] : []
        );
        $fieldDiff = debug_export_data_field_diff(
            is_array($left['dataFields'] ?? null) ? $left['dataFields'] : [],
            is_array($right['dataFields'] ?? null) ? $right['dataFields'] : []
        );
        $leftHasScalarMetadata = array_key_exists('metadata', $left) && is_array($left['metadata']);
        $rightHasScalarMetadata = array_key_exists('metadata', $right) && is_array($right['metadata']);
        $metadataDiff = $leftHasScalarMetadata && $rightHasScalarMetadata
            ? debug_export_metadata_scalar_diff($left['metadata'], $right['metadata'])
            : [];
        if ($leftHasScalarMetadata && $rightHasScalarMetadata) {
            $leftProposed = debug_export_metadata_scalar_value($left['metadata'], 'proposedFilename');
            $rightProposed = debug_export_metadata_scalar_value($right['metadata'], 'proposedFilename');
            $leftProposedValue = is_array($leftProposed) ? (string) ($leftProposed['value'] ?? '') : '';
            $rightProposedValue = is_array($rightProposed) ? (string) ($rightProposed['value'] ?? '') : '';
            $alreadyChanged = array_filter(
                $metadataDiff,
                static fn (array $item): bool => (string) ($item['key'] ?? '') === 'proposedFilename'
            ) !== [];
            $hasOtherDiff = $metadataDiff !== []
                || ($labelDiff['added'] ?? []) !== []
                || ($labelDiff['removed'] ?? []) !== []
                || $fieldDiff !== [];
            if (!$alreadyChanged && $hasOtherDiff && ($leftProposedValue !== '' || $rightProposedValue !== '')) {
                $metadataDiff[] = [
                    'key' => 'proposedFilename',
                    'label' => is_array($rightProposed) && (string) ($rightProposed['label'] ?? '') !== ''
                        ? (string) $rightProposed['label']
                        : (is_array($leftProposed) ? (string) ($leftProposed['label'] ?? 'Föreslaget arkivnamn') : 'Föreslaget arkivnamn'),
                    'from' => $leftProposedValue,
                    'to' => $rightProposedValue,
                    'unchanged' => $leftProposedValue === $rightProposedValue,
                ];
            }
        }
        if (($labelDiff['added'] ?? []) === [] && ($labelDiff['removed'] ?? []) === [] && $fieldDiff === [] && $metadataDiff === []) {
            continue;
        }
        $diffs[] = [
            'jobId' => $jobId,
            'status' => 'metadataChanged',
            'leftDocument' => is_array($left['document'] ?? null) ? $left['document'] : ['jobId' => $jobId],
            'rightDocument' => is_array($right['document'] ?? null) ? $right['document'] : ['jobId' => $jobId],
            'metadata' => $metadataDiff,
            'labels' => $labelDiff,
            'dataFields' => $fieldDiff,
        ];
    }
    return $diffs;
}

function compare_ocr_debug_exports(array $config, string $leftFolderName, string $rightFolderName): array
{
    $leftDirectory = ocr_debug_export_directory_path_from_name($config, $leftFolderName);
    $rightDirectory = ocr_debug_export_directory_path_from_name($config, $rightFolderName);
    if ($leftDirectory === null || !is_dir($leftDirectory)) {
        throw new RuntimeException('Export A hittades inte.');
    }
    if ($rightDirectory === null || !is_dir($rightDirectory)) {
        throw new RuntimeException('Export B hittades inte.');
    }
    if ($leftDirectory === $rightDirectory) {
        throw new RuntimeException('Välj två olika snapshots att jämföra.');
    }

    $leftDirectory = normalized_realpath($leftDirectory) ?? $leftDirectory;
    $rightDirectory = normalized_realpath($rightDirectory) ?? $rightDirectory;
    return compare_ocr_debug_export_directories($leftDirectory, $rightDirectory);
}

function compare_ocr_debug_export_directories(
    string $leftDirectory,
    string $rightDirectory,
    array $leftInfo = [],
    array $rightInfo = []
): array {
    if (!is_dir($leftDirectory)) {
        throw new RuntimeException('Export A hittades inte.');
    }
    if (!is_dir($rightDirectory)) {
        throw new RuntimeException('Export B hittades inte.');
    }
    if ($leftDirectory === $rightDirectory) {
        throw new RuntimeException('Välj två olika snapshots att jämföra.');
    }

    $leftDirectory = normalized_realpath($leftDirectory) ?? $leftDirectory;
    $rightDirectory = normalized_realpath($rightDirectory) ?? $rightDirectory;
    $leftFiles = ocr_debug_export_relative_files($leftDirectory);
    $rightFiles = ocr_debug_export_relative_files($rightDirectory);
    $metadataDiffs = compare_debug_export_document_metadata($leftDirectory, $rightDirectory);
    $relativeFiles = array_values(array_unique(array_merge(array_keys($leftFiles), array_keys($rightFiles))));
    sort($relativeFiles, SORT_NATURAL);

    $counts = [
        'identical' => 0,
        'changed' => 0,
        'onlyInA' => 0,
        'onlyInB' => 0,
    ];
    $files = [];

    foreach ($relativeFiles as $relativePath) {
        $leftPath = $leftFiles[$relativePath] ?? null;
        $rightPath = $rightFiles[$relativePath] ?? null;
        if ($leftPath !== null && $rightPath !== null) {
            $leftHash = hash_file('sha256', $leftPath);
            $rightHash = hash_file('sha256', $rightPath);
            $status = $leftHash === $rightHash ? 'identical' : 'changed';
        } elseif ($leftPath !== null) {
            $status = 'onlyInA';
        } else {
            $status = 'onlyInB';
        }

        $counts[$status]++;
        $leftDisplayPath = is_string($leftPath) ? (normalized_realpath($leftPath) ?? $leftPath) : null;
        $rightDisplayPath = is_string($rightPath) ? (normalized_realpath($rightPath) ?? $rightPath) : null;
        $files[] = [
            'relativePath' => $relativePath,
            'status' => $status,
            'leftPath' => $leftDisplayPath,
            'rightPath' => $rightDisplayPath,
            'meldCommand' => (($leftInfo['isLive'] ?? false) === true || ($rightInfo['isLive'] ?? false) === true)
                ? null
                : ($leftDisplayPath !== null && $rightDisplayPath !== null
                ? ocr_debug_export_meld_command($leftDisplayPath, $rightDisplayPath)
                : null),
        ];
    }

    usort($files, static function (array $left, array $right): int {
        $rank = [
            'changed' => 0,
            'onlyInA' => 1,
            'onlyInB' => 2,
            'identical' => 3,
        ];
        $leftRank = $rank[(string) ($left['status'] ?? '')] ?? 9;
        $rightRank = $rank[(string) ($right['status'] ?? '')] ?? 9;
        if ($leftRank !== $rightRank) {
            return $leftRank <=> $rightRank;
        }
        return strnatcmp((string) ($left['relativePath'] ?? ''), (string) ($right['relativePath'] ?? ''));
    });

    $leftManifest = ocr_debug_export_manifest_from_directory($leftDirectory);
    $rightManifest = ocr_debug_export_manifest_from_directory($rightDirectory);
    $leftIsLive = ($leftInfo['isLive'] ?? false) === true;
    $rightIsLive = ($rightInfo['isLive'] ?? false) === true;

    return [
        'left' => [
            'folderName' => is_string($leftInfo['folderName'] ?? null) ? (string) $leftInfo['folderName'] : basename($leftDirectory),
            'exportDirectory' => $leftIsLive ? null : $leftDirectory,
            'exportedAt' => is_string($leftInfo['exportedAt'] ?? null) ? (string) $leftInfo['exportedAt'] : (is_array($leftManifest) ? ($leftManifest['exportedAt'] ?? null) : null),
            'filterLabel' => is_string($leftInfo['filterLabel'] ?? null) ? (string) $leftInfo['filterLabel'] : (is_array($leftManifest) ? ($leftManifest['filterLabel'] ?? null) : null),
            'isLive' => $leftIsLive,
        ],
        'right' => [
            'folderName' => is_string($rightInfo['folderName'] ?? null) ? (string) $rightInfo['folderName'] : basename($rightDirectory),
            'exportDirectory' => $rightIsLive ? null : $rightDirectory,
            'exportedAt' => is_string($rightInfo['exportedAt'] ?? null) ? (string) $rightInfo['exportedAt'] : (is_array($rightManifest) ? ($rightManifest['exportedAt'] ?? null) : null),
            'filterLabel' => is_string($rightInfo['filterLabel'] ?? null) ? (string) $rightInfo['filterLabel'] : (is_array($rightManifest) ? ($rightManifest['filterLabel'] ?? null) : null),
            'isLive' => $rightIsLive,
        ],
        'counts' => $counts,
        'files' => $files,
        'metadataDiffs' => $metadataDiffs,
        'metadataChanged' => count($metadataDiffs),
        'meldDirectoryCommand' => ($leftIsLive || $rightIsLive) ? null : ocr_debug_export_meld_command($leftDirectory, $rightDirectory),
    ];
}

function create_ocr_debug_live_export_directory(): string
{
    $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'docflow-current-snapshot-' . bin2hex(random_bytes(6));
    ensure_directory($base);
    return $base;
}

function compare_ocr_debug_exports_with_live(
    array $config,
    string $leftFolderName,
    string $rightFolderName,
    array $jobIds,
    string $scope = ''
): array {
    $leftIsLive = ocr_debug_export_is_live_reference($leftFolderName);
    $rightIsLive = ocr_debug_export_is_live_reference($rightFolderName);
    if (!$leftIsLive && !$rightIsLive) {
        return compare_ocr_debug_exports($config, $leftFolderName, $rightFolderName);
    }
    if ($leftIsLive && $rightIsLive) {
        throw new RuntimeException('Välj en sparad snapshot och Aktuellt läge.');
    }

    $tempDirectories = [];
    try {
        if ($leftIsLive) {
            $leftDirectory = create_ocr_debug_live_export_directory();
            $tempDirectories[] = $leftDirectory;
            export_ocr_debug_data($config, $jobIds, $scope, $leftDirectory);
        } else {
            $leftDirectory = ocr_debug_export_directory_path_from_name($config, $leftFolderName);
            if ($leftDirectory === null || !is_dir($leftDirectory)) {
                throw new RuntimeException('Export A hittades inte.');
            }
        }

        if ($rightIsLive) {
            $rightDirectory = create_ocr_debug_live_export_directory();
            $tempDirectories[] = $rightDirectory;
            export_ocr_debug_data($config, $jobIds, $scope, $rightDirectory);
        } else {
            $rightDirectory = ocr_debug_export_directory_path_from_name($config, $rightFolderName);
            if ($rightDirectory === null || !is_dir($rightDirectory)) {
                throw new RuntimeException('Export B hittades inte.');
            }
        }

        return compare_ocr_debug_export_directories(
            normalized_realpath($leftDirectory) ?? $leftDirectory,
            normalized_realpath($rightDirectory) ?? $rightDirectory,
            $leftIsLive ? [
                'folderName' => ocr_debug_export_live_folder_name(),
                'filterLabel' => ocr_debug_export_scope_label($scope),
                'isLive' => true,
            ] : [],
            $rightIsLive ? [
                'folderName' => ocr_debug_export_live_folder_name(),
                'filterLabel' => ocr_debug_export_scope_label($scope),
                'isLive' => true,
            ] : []
        );
    } finally {
        foreach ($tempDirectories as $tempDirectory) {
            if (is_string($tempDirectory) && is_dir($tempDirectory)) {
                delete_directory_recursive($tempDirectory);
            }
        }
    }
}

function ocr_debug_export_safe_relative_file_path(string $relativePath): ?string
{
    $normalized = trim(str_replace('\\', '/', $relativePath));
    if ($normalized === '' || str_starts_with($normalized, '/') || str_contains($normalized, "\0")) {
        return null;
    }

    $parts = explode('/', $normalized);
    foreach ($parts as $part) {
        if ($part === '' || $part === '.' || $part === '..') {
            return null;
        }
    }

    if ($normalized === 'manifest.json') {
        return null;
    }

    return $normalized;
}

function launch_ocr_debug_export_meld(
    array $config,
    string $leftFolderName,
    string $rightFolderName,
    ?string $relativePath = null
): array {
    $leftIsLive = ocr_debug_export_is_live_reference($leftFolderName);
    $rightIsLive = ocr_debug_export_is_live_reference($rightFolderName);
    if ($leftIsLive && $rightIsLive) {
        throw new RuntimeException('Välj en sparad snapshot och Aktuellt läge.');
    }

    $safeRelativePath = null;
    if ($relativePath !== null && trim($relativePath) !== '') {
        $safeRelativePath = ocr_debug_export_safe_relative_file_path($relativePath);
        if ($safeRelativePath === null) {
            throw new RuntimeException('Ogiltig filsökväg för jämförelse.');
        }
    }
    if (($leftIsLive || $rightIsLive) && $safeRelativePath === null) {
        throw new RuntimeException('Aktuellt läge kan bara jämföras per fil.');
    }

    $meldBinary = trim((string) shell_exec('command -v meld 2>/dev/null'));
    if ($meldBinary === '') {
        throw new RuntimeException('Meld kunde inte hittas på servern.');
    }

    $tempDirectories = [];
    $liveJobId = null;
    if ($leftIsLive || $rightIsLive) {
        if (preg_match('/^(?:text|merged_objects|document_metadata)\/([A-Za-z0-9_-]+)\.(?:txt|json)$/', (string) $safeRelativePath, $matches) !== 1) {
            throw new RuntimeException('Aktuellt läge kan bara jämföras för OCR-filer.');
        }
        $liveJobId = $matches[1];
    }

    if ($leftIsLive) {
        $leftDirectory = create_ocr_debug_live_export_directory();
        $tempDirectories[] = $leftDirectory;
        export_ocr_debug_data($config, [$liveJobId], 'jobs', $leftDirectory);
    } else {
        $leftDirectory = ocr_debug_export_directory_path_from_name($config, $leftFolderName);
        if ($leftDirectory === null || !is_dir($leftDirectory)) {
            throw new RuntimeException('Export A hittades inte.');
        }
    }

    if ($rightIsLive) {
        $rightDirectory = create_ocr_debug_live_export_directory();
        $tempDirectories[] = $rightDirectory;
        export_ocr_debug_data($config, [$liveJobId], 'jobs', $rightDirectory);
    } else {
        $rightDirectory = ocr_debug_export_directory_path_from_name($config, $rightFolderName);
        if ($rightDirectory === null || !is_dir($rightDirectory)) {
            throw new RuntimeException('Export B hittades inte.');
        }
    }

    $leftDirectory = normalized_realpath($leftDirectory) ?? $leftDirectory;
    $rightDirectory = normalized_realpath($rightDirectory) ?? $rightDirectory;
    $leftPath = $leftDirectory;
    $rightPath = $rightDirectory;
    if ($safeRelativePath !== null) {
        $leftPath = normalized_realpath($leftPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeRelativePath)) ?? '';
        $rightPath = normalized_realpath($rightPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeRelativePath)) ?? '';
        if (
            $leftPath === ''
            || $rightPath === ''
            || !is_file($leftPath)
            || !is_file($rightPath)
            || !path_is_within_directory($leftPath, $leftDirectory)
            || !path_is_within_directory($rightPath, $rightDirectory)
        ) {
            throw new RuntimeException(($leftIsLive || $rightIsLive) ? 'Filen finns inte i både snapshot och aktuellt läge.' : 'Filen finns inte i båda snapshots.');
        }
    }

    $cleanupCommand = '';
    foreach ($tempDirectories as $tempDirectory) {
        if (is_string($tempDirectory) && $tempDirectory !== '') {
            $cleanupCommand .= ' rm -rf -- ' . escapeshellarg($tempDirectory) . ';';
        }
    }
    $command = $cleanupCommand === ''
        ? sprintf(
            '%s %s %s > /dev/null 2>&1 & echo $!',
            escapeshellarg($meldBinary),
            escapeshellarg($leftPath),
            escapeshellarg($rightPath)
        )
        : sprintf(
            '( %s %s %s; %s ) > /dev/null 2>&1 & echo $!',
            escapeshellarg($meldBinary),
            escapeshellarg($leftPath),
            escapeshellarg($rightPath),
            $cleanupCommand
        );
    exec($command, $output, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException('Meld kunde inte startas.');
    }

    return [
        'leftPath' => $leftPath,
        'rightPath' => $rightPath,
        'pid' => isset($output[0]) && is_numeric(trim((string) $output[0])) ? (int) trim((string) $output[0]) : null,
    ];
}

function export_ocr_debug_data(array $config, array $jobIds, string $scope = '', ?string $targetDirectory = null): array
{
    $jobsDirectory = is_string($config['jobsDirectory'] ?? null) ? trim((string) $config['jobsDirectory']) : '';
    if ($jobsDirectory === '' || !is_dir($jobsDirectory)) {
        throw new RuntimeException('Jobbkatalogen är inte tillgänglig.');
    }

    $normalizedJobIds = [];
    foreach ($jobIds as $jobId) {
        if (!is_string($jobId)) {
            continue;
        }
        $normalizedJobId = trim($jobId);
        if ($normalizedJobId === '' || !is_valid_job_id($normalizedJobId)) {
            continue;
        }
        $normalizedJobIds[$normalizedJobId] = true;
    }
    $normalizedJobIds = array_keys($normalizedJobIds);
    if ($normalizedJobIds === []) {
        throw new RuntimeException('Inga jobb valda för export.');
    }

    $exportDirectory = is_string($targetDirectory) && trim($targetDirectory) !== ''
        ? rtrim($targetDirectory, DIRECTORY_SEPARATOR)
        : create_ocr_debug_export_directory($config, $scope);
    ensure_directory($exportDirectory);
    ensure_directory($exportDirectory . DIRECTORY_SEPARATOR . 'text');
    ensure_directory($exportDirectory . DIRECTORY_SEPARATOR . 'merged_objects');
    ensure_directory($exportDirectory . DIRECTORY_SEPARATOR . 'document_metadata');
    $exportedJobIds = [];
    $skippedJobIds = [];
    $createdFiles = [];

    foreach ($normalizedJobIds as $jobId) {
        $jobDir = rtrim($jobsDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
        $jobPath = $jobDir . '/job.json';
        if (!is_dir($jobDir) || !is_file($jobPath)) {
            $skippedJobIds[] = $jobId;
            continue;
        }

        $document = ocr_debug_export_document_for_job($jobId);
        if ($document === null) {
            $skippedJobIds[] = $jobId;
            continue;
        }

        $pages = is_array($document['pages'] ?? null) ? $document['pages'] : [];
        if ($pages === []) {
            $skippedJobIds[] = $jobId;
            continue;
        }

        $mergedObjectsPath = $exportDirectory . DIRECTORY_SEPARATOR . 'merged_objects' . DIRECTORY_SEPARATOR . $jobId . '.json';
        write_json_file($mergedObjectsPath, $document);
        $createdFiles[] = 'merged_objects/' . $jobId . '.json';

        $mergedTextPages = ensure_merged_objects_text_from_storage($jobDir, $jobId);
        $mergedText = build_merged_objects_txt_from_pages($mergedTextPages !== [] ? $mergedTextPages : $pages);
        $textPath = $exportDirectory . DIRECTORY_SEPARATOR . 'text' . DIRECTORY_SEPARATOR . $jobId . '.txt';
        file_put_contents($textPath, $mergedText === '' ? '' : $mergedText . "\n");
        $createdFiles[] = 'text/' . $jobId . '.txt';

        $metadata = debug_export_document_metadata_for_job($jobId, $jobDir);
        $metadataPath = $exportDirectory . DIRECTORY_SEPARATOR . 'document_metadata' . DIRECTORY_SEPARATOR . $jobId . '.json';
        write_json_file($metadataPath, $metadata);
        $createdFiles[] = 'document_metadata/' . $jobId . '.json';
        $exportedJobIds[] = $jobId;
    }

    $manifest = ocr_debug_export_manifest_payload($exportDirectory, $scope, $exportedJobIds, array_merge(['manifest.json'], $createdFiles), $skippedJobIds);
    write_json_file(ocr_debug_export_manifest_path($exportDirectory), $manifest);
    $createdFiles[] = 'manifest.json';

    return [
        'exportDirectory' => normalized_realpath($exportDirectory) ?? $exportDirectory,
        'folderName' => basename($exportDirectory),
        'exportedJobIds' => $exportedJobIds,
        'skippedJobIds' => $skippedJobIds,
        'createdFiles' => $createdFiles,
        'exportedCount' => count($exportedJobIds),
        'skippedCount' => count($skippedJobIds),
        'scope' => $scope,
        'scopeLabel' => ocr_debug_export_scope_label($scope),
    ];
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

    foreach ($words as $wordIndex => $word) {
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
            'wordIndex' => is_numeric($word['index'] ?? null) ? (int) $word['index'] : (is_int($wordIndex) ? $wordIndex : count($normalized)),
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
    $blankLineGapFactor = .55;//0.4 - 0.7 is fine during testing
    $extraLayoutBreakGapFactor = 1.3;
    $rows = ocr_layout_group_words_into_rows($normalized);

    if ($rows === []) {
        return [];
    }

    $grid = [];
    $gridSegments = [];
    $gridMeta = [];
    foreach ($rows as $rowWords) {
        $wordTop = min(array_map(static fn(array $word): float => (float) ($word['y0'] ?? 0.0), $rowWords));
        $wordBottom = max(array_map(static fn(array $word): float => (float) ($word['y1'] ?? 0.0), $rowWords));
        $candidateRow = (int) round($wordTop / max($lineHeight, 1.0));
        while (isset($gridMeta[$candidateRow]) && abs(((float) $gridMeta[$candidateRow]['top']) - $wordTop) > ($lineHeight * 0.35)) {
            $candidateRow++;
        }
        if (!isset($grid[$candidateRow])) {
            $grid[$candidateRow] = '';
            $gridSegments[$candidateRow] = [];
            $gridMeta[$candidateRow] = [
                'slot' => $candidateRow,
                'top' => $wordTop,
                'bottom' => $wordBottom,
                'height' => max(1.0, $wordBottom - $wordTop),
            ];
        } else {
            $gridMeta[$candidateRow]['slot'] = $candidateRow;
            $gridMeta[$candidateRow]['top'] = min((float) $gridMeta[$candidateRow]['top'], $wordTop);
            $gridMeta[$candidateRow]['bottom'] = max((float) $gridMeta[$candidateRow]['bottom'], $wordBottom);
            $gridMeta[$candidateRow]['height'] = max(
                1.0,
                (float) $gridMeta[$candidateRow]['bottom'] - (float) $gridMeta[$candidateRow]['top']
            );
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
                'wordIndex' => is_numeric($word['wordIndex'] ?? null) ? (int) $word['wordIndex'] : null,
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
        $gridMeta[$candidateRow] = [
            'slot' => $candidateRow,
            'top' => isset($gridMeta[$candidateRow]['top']) ? min((float) $gridMeta[$candidateRow]['top'], $wordTop) : $wordTop,
            'bottom' => isset($gridMeta[$candidateRow]['bottom']) ? max((float) $gridMeta[$candidateRow]['bottom'], $wordBottom) : $wordBottom,
            'height' => max(
                1.0,
                (isset($gridMeta[$candidateRow]['bottom']) ? max((float) $gridMeta[$candidateRow]['bottom'], $wordBottom) : $wordBottom)
                - (isset($gridMeta[$candidateRow]['top']) ? min((float) $gridMeta[$candidateRow]['top'], $wordTop) : $wordTop)
            ),
        ];
    }

    $pageLines = [];
    $previousMeta = null;
    ksort($grid, SORT_NUMERIC);
    foreach ($grid as $rowIndex => $buffer) {
        $currentMeta = is_array($gridMeta[$rowIndex] ?? null) ? $gridMeta[$rowIndex] : null;
        if ($previousMeta !== null && $currentMeta !== null) {
            $previousSlot = (int) ($previousMeta['slot'] ?? $rowIndex);
            $currentSlot = (int) ($currentMeta['slot'] ?? $rowIndex);
            $previousBottom = (float) ($previousMeta['bottom'] ?? $previousMeta['top'] ?? 0.0);
            $currentTop = (float) ($currentMeta['top'] ?? $currentMeta['bottom'] ?? 0.0);
            $previousHeight = max(1.0, (float) ($previousMeta['height'] ?? ($previousBottom - (float) ($previousMeta['top'] ?? $previousBottom))));
            $gap = max(0.0, $currentTop - $previousBottom);
            $emptySlots = max(0, $currentSlot - $previousSlot - 1);
            $blankLinesToInsert = 0;
            if ($emptySlots > 0 && $gap >= ($previousHeight * $blankLineGapFactor)) {
                $blankLinesToInsert = 1;
                if ($emptySlots > 1 && $gap >= ($previousHeight * $extraLayoutBreakGapFactor)) {
                    $blankLinesToInsert = $emptySlots;
                }
            }
            for ($i = 0; $i < $blankLinesToInsert; $i++) {
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
        $previousMeta = $currentMeta;
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
        'å', 'ä', 'à', 'á', 'â', 'ã', 'ā', 'ă', 'ą' => 'a',
        'ö', 'ò', 'ó', 'ô', 'õ', 'ø', 'ō', 'ŏ', 'ő' => 'o',
        'ü', 'ù', 'ú', 'û', 'ū' => 'u',
        'é', 'è', 'ê', 'ë', 'ē' => 'e',
        'í', 'ì', 'î', 'ï', 'ī' => 'i',
        default => $lower,
    };
}

function source_char_supports_swedish_diacritic_transfer(string $sourceChar, string $truthChar): bool
{
    $sourceLower = lowercase_text($sourceChar);
    $truthLower = lowercase_text($truthChar);

    return match ($truthLower) {
        'å', 'ä' => in_array($sourceLower, ['à', 'á', 'â', 'ã', 'ä', 'å', 'ā', 'ă', 'ą'], true),
        'ö' => in_array($sourceLower, ['ò', 'ó', 'ô', 'õ', 'ö', 'ō', 'ŏ', 'ő'], true),
        default => false,
    };
}

function is_noise_character(string $char): bool
{
    return preg_match('/[\p{L}\p{N}]/u', $char) !== 1;
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
    if (preg_match('/\s/u', $sourceText) === 1 && preg_match('/\s/u', $truthText) === 1) {
        $sourceParts = preg_split('/(\s+)/u', $sourceText, -1, PREG_SPLIT_DELIM_CAPTURE);
        $truthParts = preg_split('/(\s+)/u', $truthText, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (is_array($sourceParts) && is_array($truthParts) && count($sourceParts) === count($truthParts)) {
            $transferredParts = [];
            foreach (array_map(null, $sourceParts, $truthParts) as [$sourcePart, $truthPart]) {
                if (preg_match('/^\s+$/u', $sourcePart) === 1 || preg_match('/^\s+$/u', $truthPart) === 1) {
                    $transferredParts[] = $sourcePart;
                } else {
                    $transferredParts[] = transfer_swedish_diacritics_token($sourcePart, $truthPart);
                }
            }

            return implode('', $transferredParts);
        }
    }

    return transfer_swedish_diacritics_token($sourceText, $truthText);
}

function transfer_swedish_diacritics_token(string $sourceText, string $truthText): string
{
    $sourceChars = utf8_chars($sourceText);
    $truthChars = utf8_chars($truthText);
    $sourceCount = count($sourceChars);
    $truthCount = count($truthChars);
    if ($sourceCount === 0 || $truthCount === 0) {
        return $sourceText;
    }

    $sourceLower = array_map('lowercase_text', $sourceChars);
    $truthLower = array_map('lowercase_text', $truthChars);
    $sourceFolded = array_map('fold_char_for_diacritic_match', $sourceChars);
    $truthFolded = array_map('fold_char_for_diacritic_match', $truthChars);
    $memo = [];

    $solve = function (int $sourceIndex, int $truthIndex, bool $matchedAnySource) use (
        &$solve,
        &$memo,
        $sourceChars,
        $truthChars,
        $sourceLower,
        $truthLower,
        $sourceFolded,
        $truthFolded
    ): ?array {
        $memoKey = $sourceIndex . '|' . $truthIndex . '|' . ($matchedAnySource ? '1' : '0');
        if (array_key_exists($memoKey, $memo)) {
            return $memo[$memoKey];
        }

        $sourceCount = count($sourceChars);
        $truthCount = count($truthChars);
        if ($sourceIndex >= $sourceCount && $truthIndex >= $truthCount) {
            return $memo[$memoKey] = ['score' => 0.0, 'text' => ''];
        }

        $best = null;

        if ($sourceIndex < $sourceCount && $truthIndex < $truthCount) {
            $sourceChar = $sourceChars[$sourceIndex];
            $truthChar = $truthChars[$truthIndex];
            $exactMatch = $sourceLower[$sourceIndex] === $truthLower[$truthIndex];
            $diacriticMatch = !$exactMatch
                && is_swedish_diacritic_char($truthChar)
                && $sourceFolded[$sourceIndex] === $truthFolded[$truthIndex]
                && source_char_supports_swedish_diacritic_transfer($sourceChar, $truthChar);

            if ($exactMatch || $diacriticMatch) {
                $tail = $solve($sourceIndex + 1, $truthIndex + 1, true);
                if ($tail !== null) {
                    $candidate = [
                        'score' => (float) $tail['score'] + ($diacriticMatch ? 0.05 : 0.0),
                        'text' => ($diacriticMatch ? $truthChar : $sourceChar) . (string) $tail['text'],
                    ];
                    $best = $candidate;
                }
            }
        }

        if ($truthIndex < $truthCount && is_swedish_diacritic_char($truthChars[$truthIndex])) {
            $tail = $solve($sourceIndex, $truthIndex + 1, $matchedAnySource);
            if ($tail !== null) {
                $candidate = [
                    'score' => (float) $tail['score'] + 0.10,
                    'text' => $truthChars[$truthIndex] . (string) $tail['text'],
                ];
                if ($best === null || $candidate['score'] < $best['score']) {
                    $best = $candidate;
                }
            }
        }

        if (
            $sourceIndex < $sourceCount
            && is_noise_character($sourceChars[$sourceIndex])
        ) {
            $tail = $solve($sourceIndex + 1, $truthIndex, $matchedAnySource);
            if ($tail !== null) {
                $candidate = [
                    'score' => (float) $tail['score'] + 0.15,
                    'text' => $sourceChars[$sourceIndex] . (string) $tail['text'],
                ];
                if ($best === null || $candidate['score'] < $best['score']) {
                    $best = $candidate;
                }
            }
        }

        return $memo[$memoKey] = $best;
    };

    $result = $solve(0, 0, false);
    return is_array($result) && is_string($result['text'] ?? null) ? (string) $result['text'] : $sourceText;
}

function text_added_swedish_diacritic(string $sourceText, string $adjustedText): bool
{
    $sourceChars = utf8_chars($sourceText);
    $adjustedChars = utf8_chars($adjustedText);
    if (count($adjustedChars) <= count($sourceChars)) {
        return false;
    }

    $sourceCount = 0;
    foreach ($sourceChars as $char) {
        if (is_swedish_diacritic_char($char)) {
            $sourceCount++;
        }
    }

    $adjustedCount = 0;
    foreach ($adjustedChars as $char) {
        if (is_swedish_diacritic_char($char)) {
            $adjustedCount++;
        }
    }

    return $adjustedCount > $sourceCount;
}

function bbox_union(array $left, array $right): array
{
    return [
        'x0' => min((float) ($left['x0'] ?? 0.0), (float) ($right['x0'] ?? 0.0)),
        'y0' => min((float) ($left['y0'] ?? 0.0), (float) ($right['y0'] ?? 0.0)),
        'x1' => max((float) ($left['x1'] ?? 0.0), (float) ($right['x1'] ?? 0.0)),
        'y1' => max((float) ($left['y1'] ?? 0.0), (float) ($right['y1'] ?? 0.0)),
    ];
}

function bbox_is_near_or_overlapping(array $left, array $right): bool
{
    if (bbox_intersection_area($left, $right) > 0.0) {
        return true;
    }

    return bbox_center_distance_ratio($left, $right) <= 0.9;
}

function expand_bbox_for_added_text(array $sourceBbox, string $sourceText, string $adjustedText, ?array $truthBbox = null): array
{
    $sourceLength = count(utf8_chars($sourceText));
    $adjustedLength = count(utf8_chars($adjustedText));
    if ($adjustedLength <= $sourceLength) {
        return $sourceBbox;
    }

    if ($truthBbox !== null && bbox_is_near_or_overlapping($sourceBbox, $truthBbox)) {
        return bbox_union($sourceBbox, $truthBbox);
    }

    $extraChars = $adjustedLength - $sourceLength;
    $width = max(0.0, (float) ($sourceBbox['x1'] ?? 0.0) - (float) ($sourceBbox['x0'] ?? 0.0));
    $charWidth = $width / max($sourceLength, 1);
    $expanded = $sourceBbox;
    $expanded['x1'] = max((float) ($sourceBbox['x1'] ?? 0.0), (float) ($sourceBbox['x1'] ?? 0.0) + ($charWidth * $extraChars));
    return $expanded;
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

            $adjustedCandidateText = transfer_swedish_diacritics($segmentText, $candidateText);
            if ($adjustedCandidateText === $segmentText && $segmentText !== $candidateText) {
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
            if (text_added_swedish_diacritic($segmentText, $adjustedText)) {
                $segments[$index]['bbox'] = expand_bbox_for_added_text(
                    $segmentBbox,
                    $segmentText,
                    $adjustedText,
                    normalize_debug_word_bbox($bestCandidate['bbox'] ?? null)
                );
            }
        }
    }

    return $segments;
}

function debug_word_line_bbox(array $words): ?array
{
    $bbox = null;
    foreach ($words as $word) {
        if (!is_array($word)) {
            continue;
        }
        $wordBbox = normalize_debug_word_bbox($word['bbox'] ?? null);
        if ($wordBbox === null) {
            continue;
        }
        if ($bbox === null) {
            $bbox = $wordBbox;
            continue;
        }
        $bbox = [
            'x0' => min($bbox['x0'], $wordBbox['x0']),
            'y0' => min($bbox['y0'], $wordBbox['y0']),
            'x1' => max($bbox['x1'], $wordBbox['x1']),
            'y1' => max($bbox['y1'], $wordBbox['y1']),
        ];
    }

    return $bbox;
}

function debug_words_average_score(array $words): ?float
{
    $scores = [];
    foreach ($words as $word) {
        if (!is_array($word)) {
            continue;
        }
        $score = $word['score'] ?? null;
        if (is_int($score) || is_float($score) || (is_string($score) && is_numeric($score))) {
            $scores[] = max(0.0, min(1.0, (float) $score));
        }
    }

    return $scores === [] ? null : array_sum($scores) / count($scores);
}

function debug_line_y_overlap_ratio(array $left, array $right): float
{
    $overlap = max(0.0, min((float) $left['y1'], (float) $right['y1']) - max((float) $left['y0'], (float) $right['y0']));
    $height = min(
        max(1.0, (float) $left['y1'] - (float) $left['y0']),
        max(1.0, (float) $right['y1'] - (float) $right['y0'])
    );
    return $overlap / $height;
}

function debug_line_x_overlap_ratio(array $left, array $right): float
{
    $overlap = max(0.0, min((float) $left['x1'], (float) $right['x1']) - max((float) $left['x0'], (float) $right['x0']));
    $width = min(
        max(1.0, (float) $left['x1'] - (float) $left['x0']),
        max(1.0, (float) $right['x1'] - (float) $right['x0'])
    );
    return $overlap / $width;
}

function normalize_line_text_for_duplicate_check(string $text): string
{
    return normalize_text_for_diacritic_match($text);
}

function debug_line_texts_are_duplicates(string $left, string $right): bool
{
    $normalizedLeft = normalize_line_text_for_duplicate_check($left);
    $normalizedRight = normalize_line_text_for_duplicate_check($right);
    if ($normalizedLeft === '' || $normalizedRight === '') {
        return false;
    }
    if ($normalizedLeft === $normalizedRight) {
        return true;
    }

    $leftLength = utf8_strlen_safe($normalizedLeft);
    $rightLength = utf8_strlen_safe($normalizedRight);
    $maxLength = max($leftLength, $rightLength);
    $minLength = min($leftLength, $rightLength);
    if ($maxLength < 12 || $minLength < (int) floor($maxLength * 0.78)) {
        return false;
    }

    return utf8_levenshtein_distance($normalizedLeft, $normalizedRight) <= max(2, (int) floor($maxLength * 0.12));
}

function tesseract_line_is_covered_by_rapidocr(array $tesseractLine, array $rapidocrLines): bool
{
    $lineBbox = normalize_debug_word_bbox($tesseractLine['bbox'] ?? null);
    if ($lineBbox === null) {
        return true;
    }

    foreach ($rapidocrLines as $rapidocrLine) {
        if (!is_array($rapidocrLine)) {
            continue;
        }

        $rapidocrText = is_string($rapidocrLine['text'] ?? null) ? (string) $rapidocrLine['text'] : '';
        $tesseractText = is_string($tesseractLine['text'] ?? null) ? (string) $tesseractLine['text'] : '';
        if (debug_line_texts_are_duplicates($tesseractText, $rapidocrText)) {
            return true;
        }

        $rapidocrBbox = normalize_debug_word_bbox($rapidocrLine['bbox'] ?? null);
        if ($rapidocrBbox === null) {
            continue;
        }

        $yOverlap = debug_line_y_overlap_ratio($lineBbox, $rapidocrBbox);
        $xOverlap = debug_line_x_overlap_ratio($lineBbox, $rapidocrBbox);
        if (($yOverlap >= 0.50 && $xOverlap >= 0.10) || bbox_iou($lineBbox, $rapidocrBbox) >= 0.05) {
            return true;
        }
    }

    return false;
}

function tesseract_only_line_is_usable(array $line): bool
{
    $text = is_string($line['text'] ?? null) ? trim((string) $line['text']) : '';
    if ($text === '' || utf8_strlen_safe($text) < 4 || preg_match('/[\p{L}\p{N}]/u', $text) !== 1) {
        return false;
    }

    $bbox = normalize_debug_word_bbox($line['bbox'] ?? null);
    if ($bbox === null || bbox_area($bbox) <= 4.0) {
        return false;
    }

    $score = $line['score'] ?? null;
    if ((is_int($score) || is_float($score)) && (float) $score < 0.45) {
        return false;
    }

    return true;
}

function build_tesseract_line_candidates(array $tesseractWords): array
{
    $flatWords = [];
    foreach ($tesseractWords as $word) {
        if (!is_array($word)) {
            continue;
        }
        $text = is_string($word['text'] ?? null) ? trim((string) $word['text']) : '';
        $bbox = normalize_debug_word_bbox($word['bbox'] ?? null);
        if ($text === '' || $bbox === null) {
            continue;
        }
        $flatWords[] = [
            'text' => $text,
            'x0' => $bbox['x0'],
            'y0' => $bbox['y0'],
            'x1' => $bbox['x1'],
            'y1' => $bbox['y1'],
            'bbox' => $bbox,
            'score' => $word['score'] ?? null,
            'engine' => $word['engine'] ?? 'tesseract',
            'index' => $word['index'] ?? null,
        ];
    }

    $rows = ocr_layout_group_words_into_rows($flatWords);
    $lines = [];
    foreach ($rows as $rowWords) {
        $words = [];
        foreach ($rowWords as $word) {
            $wordBbox = normalize_debug_word_bbox($word['bbox'] ?? null);
            if ($wordBbox === null) {
                continue;
            }
            $words[] = [
                'engine' => 'tesseract',
                'source' => 'tesseract_only',
                'index' => is_int($word['index'] ?? null) ? (int) $word['index'] : null,
                'text' => (string) ($word['text'] ?? ''),
                'bbox' => $wordBbox,
                'score' => $word['score'] ?? null,
            ];
        }
        if ($words === []) {
            continue;
        }
        $text = implode(' ', array_map(static fn(array $word): string => (string) ($word['text'] ?? ''), $words));
        $lines[] = [
            'text' => trim($text),
            'bbox' => debug_word_line_bbox($words),
            'score' => debug_words_average_score($words),
            'words' => $words,
        ];
    }

    return $lines;
}

function append_tesseract_only_missing_lines(array $mergedWords, array $rapidocrLines, array $tesseractWords): array
{
    if ($tesseractWords === []) {
        return $mergedWords;
    }

    $addedAny = false;
    foreach (build_tesseract_line_candidates($tesseractWords) as $line) {
        if (!tesseract_only_line_is_usable($line) || tesseract_line_is_covered_by_rapidocr($line, $rapidocrLines)) {
            continue;
        }

        foreach (is_array($line['words'] ?? null) ? $line['words'] : [] as $word) {
            if (is_array($word)) {
                $mergedWords[] = $word;
                $addedAny = true;
            }
        }
    }

    if (!$addedAny) {
        return $mergedWords;
    }

    usort($mergedWords, static function (array $left, array $right): int {
        $leftBbox = normalize_debug_word_bbox($left['bbox'] ?? null);
        $rightBbox = normalize_debug_word_bbox($right['bbox'] ?? null);
        if ($leftBbox === null || $rightBbox === null) {
            return 0;
        }
        $yCompare = $leftBbox['y0'] <=> $rightBbox['y0'];
        if (abs((float) $leftBbox['y0'] - (float) $rightBbox['y0']) > 6.0 && $yCompare !== 0) {
            return $yCompare;
        }
        return $leftBbox['x0'] <=> $rightBbox['x0'];
    });

    return $mergedWords;
}

function build_merged_objects_payload_from_rapidocr_page(
    array $rapidocrPayload,
    int $pageNumber,
    array $tesseractPayload = [],
    array $ocrPdfTextSubstitutions = []
): array
{
    $pageWidth = is_numeric($rapidocrPayload['pageWidth'] ?? null) ? (float) $rapidocrPayload['pageWidth'] : null;
    $pageHeight = is_numeric($rapidocrPayload['pageHeight'] ?? null) ? (float) $rapidocrPayload['pageHeight'] : null;
    $sourceImage = is_string($rapidocrPayload['sourceImage'] ?? null) ? $rapidocrPayload['sourceImage'] : null;
    $tesseractWords = normalize_debug_words_for_merge($tesseractPayload, 'tesseract');

    $mergedWords = [];
    $rapidocrLines = [];
    foreach (is_array($rapidocrPayload['lines'] ?? null) ? $rapidocrPayload['lines'] : [] as $line) {
        if (!is_array($line)) {
            continue;
        }
        $segments = merge_rapidocr_line_into_segments($line);
        $segments = apply_tesseract_swedish_truth_to_segments($segments, $tesseractWords);
        $lineText = implode(' ', array_values(array_filter(array_map(
            static fn(array $segment): string => is_string($segment['text'] ?? null) ? trim((string) $segment['text']) : '',
            $segments
        ), static fn(string $text): bool => $text !== '')));
        $lineBbox = debug_word_line_bbox($segments) ?? normalize_debug_word_bbox($line['bbox'] ?? null);
        if ($lineText !== '' && $lineBbox !== null) {
            $rapidocrLines[] = [
                'text' => $lineText,
                'bbox' => $lineBbox,
            ];
        }
        foreach ($segments as $segment) {
            $mergedWords[] = $segment;
        }
    }
    $mergedWords = append_tesseract_only_missing_lines($mergedWords, $rapidocrLines, $tesseractWords);
    $mergedWords = apply_ocr_pdf_text_substitutions_to_debug_words($mergedWords, $ocrPdfTextSubstitutions);

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

function build_merged_objects_document_from_rapidocr_pages(
    array $rapidocrPages,
    array $tesseractPages = [],
    array $ocrPdfTextSubstitutions = []
): ?array
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
        $pages[] = build_merged_objects_payload_from_rapidocr_page(
            $rapidocrPage,
            $pageNumber,
            $tesseractPage,
            $ocrPdfTextSubstitutions
        );
    }

    if ($pages === []) {
        return null;
    }

    return [
        'engine' => 'merged_objects',
        'pages' => $pages,
    ];
}

function write_merged_object_debug_files_from_rapidocr(string $jobDir, array $ocrPdfTextSubstitutions = []): void
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

    $document = build_merged_objects_document_from_rapidocr_pages(
        $rapidocrPages,
        $tesseractPages,
        $ocrPdfTextSubstitutions
    );
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

function normalize_client_match_haystack(string $text): string
{
    return normalize_inline_whitespace(lowercase_text($text));
}

function client_name_match_variants(array $client): array
{
    $firstName = is_string($client['firstName'] ?? null) ? trim((string) $client['firstName']) : '';
    $lastName = is_string($client['lastName'] ?? null) ? trim((string) $client['lastName']) : '';
    if ($firstName === '' || $lastName === '') {
        return [];
    }

    $variants = [];
    $appendVariant = static function (string $value) use (&$variants): void {
        $normalized = normalize_client_match_haystack($value);
        if ($normalized === '' || isset($variants[$normalized])) {
            return;
        }
        $variants[$normalized] = $value;
    };

    $firstNameVariants = [$firstName];
    $preferredFirstName = client_preferred_first_name($client);
    if (is_string($preferredFirstName) && trim($preferredFirstName) !== '') {
        $firstNameVariants[] = trim($preferredFirstName);
    }

    foreach (array_values(array_unique(array_filter(
        $firstNameVariants,
        static fn ($value): bool => is_string($value) && trim($value) !== ''
    ))) as $firstNameVariant) {
        $appendVariant(trim($firstNameVariant . ' ' . $lastName));
        $appendVariant(trim($lastName . ' ' . $firstNameVariant));
        $appendVariant(trim($lastName . ', ' . $firstNameVariant));
    }

    uasort(
        $variants,
        static function (string $left, string $right): int {
            $leftLength = strlen(normalize_client_match_haystack($left));
            $rightLength = strlen(normalize_client_match_haystack($right));
            if ($leftLength !== $rightLength) {
                return $rightLength <=> $leftLength;
            }
            return strcmp($left, $right);
        }
    );

    return array_values($variants);
}

function find_client_matches(string $ocrText, array $clients): array
{
    $normalizedText = preg_replace('/\D+/', '', $ocrText);
    if (!is_string($normalizedText)) {
        $normalizedText = '';
    }
    $normalizedHaystack = normalize_client_match_haystack($ocrText);

    $matches = [];
    foreach (array_values($clients) as $clientIndex => $client) {
        if (!is_array($client)) {
            continue;
        }

        $dirName = is_string($client['dirName'] ?? null) ? trim((string) $client['dirName']) : '';
        $pin = is_string($client['personalIdentityNumber'] ?? null) ? trim((string) $client['personalIdentityNumber']) : '';
        $lastName = is_string($client['lastName'] ?? null) ? trim((string) $client['lastName']) : '';
        if ($dirName === '' || $pin === '') {
            continue;
        }

        $matchedSignals = [];
        $bestPosition = PHP_INT_MAX;
        $score = 0;

        $pinNoHyphen = str_replace('-', '', $pin);
        $pinDigits = preg_replace('/\D+/', '', $pinNoHyphen);
        if (!is_string($pinDigits)) {
            $pinDigits = '';
        }
        $pinPosition = null;
        if ($pin !== '' && str_contains($ocrText, $pin)) {
            $pinPosition = strpos($ocrText, $pin);
        } elseif ($pinNoHyphen !== '' && str_contains($ocrText, $pinNoHyphen)) {
            $pinPosition = strpos($ocrText, $pinNoHyphen);
        } elseif ($pinDigits !== '' && str_contains($normalizedText, $pinDigits)) {
            $pinPosition = strpos($normalizedText, $pinDigits);
        }
        if ($pinPosition !== false && $pinPosition !== null) {
            $matchedSignals[] = [
                'type' => 'personal_identity_number',
                'label' => 'Personnummer',
                'value' => $pin,
            ];
            $bestPosition = min($bestPosition, (int) $pinPosition);
            $score += 2;
        }

        $nameVariants = client_name_match_variants($client);
        foreach ($nameVariants as $formattedName) {
            $normalizedNeedle = normalize_client_match_haystack($formattedName);
            $namePosition = $normalizedNeedle !== '' ? strpos($normalizedHaystack, $normalizedNeedle) : false;
            if ($namePosition === false) {
                continue;
            }
            $matchedSignals[] = [
                'type' => 'name',
                'label' => 'Namn',
                'value' => $formattedName,
            ];
            $bestPosition = min($bestPosition, (int) $namePosition);
            $score += 1;
            break;
        }

        if ($matchedSignals === []) {
            continue;
        }

        $matches[] = [
            'dirName' => $dirName,
            'displayName' => $dirName,
            'matchedName' => null,
            'matchedPersonalIdentityNumber' => null,
            'matchedSignals' => $matchedSignals,
            '_score' => $score,
            '_position' => $bestPosition,
            '_clientIndex' => $clientIndex,
        ];
    }

    usort($matches, static function (array $left, array $right): int {
        $scoreCompare = ((int) ($right['_score'] ?? 0)) <=> ((int) ($left['_score'] ?? 0));
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }

        $positionCompare = ((int) ($left['_position'] ?? PHP_INT_MAX)) <=> ((int) ($right['_position'] ?? PHP_INT_MAX));
        if ($positionCompare !== 0) {
            return $positionCompare;
        }

        return ((int) ($left['_clientIndex'] ?? PHP_INT_MAX)) <=> ((int) ($right['_clientIndex'] ?? PHP_INT_MAX));
    });

    return array_values(array_map(static function (array $match): array {
        foreach ($match['matchedSignals'] as $signal) {
            if (!is_array($signal)) {
                continue;
            }
            if (($signal['type'] ?? null) === 'name' && is_string($signal['value'] ?? null)) {
                $match['matchedName'] = trim((string) $signal['value']);
            }
            if (($signal['type'] ?? null) === 'personal_identity_number' && is_string($signal['value'] ?? null)) {
                $match['matchedPersonalIdentityNumber'] = trim((string) $signal['value']);
            }
        }

        unset($match['_score'], $match['_position'], $match['_clientIndex']);
        return $match;
    }, $matches));
}

function match_client_dir_name(string $ocrText, array $clients): ?string
{
    $matches = find_client_matches($ocrText, $clients);
    $firstMatch = is_array($matches[0] ?? null) ? $matches[0] : null;
    $dirName = is_string($firstMatch['dirName'] ?? null) ? trim((string) $firstMatch['dirName']) : '';
    return $dirName !== '' ? $dirName : null;
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

    return delimit_ocr_text_regex(implode('', $parts), '/', 'iu', false);
}

function ocr_text_regex_flags(string $flags = 'iu'): string
{
    $normalized = '';
    foreach (str_split($flags) as $flag) {
        if ($flag !== '' && strpos($normalized, $flag) === false) {
            $normalized .= $flag;
        }
    }

    return strpos($normalized, 'm') === false ? $normalized . 'm' : $normalized;
}

function delimit_ocr_text_regex(string $pattern, string $delimiter = '/', string $flags = 'iu', bool $escapeDelimiter = true): string
{
    $body = $escapeDelimiter
        ? str_replace($delimiter, '\\' . $delimiter, $pattern)
        : $pattern;

    return $delimiter
        . $body
        . $delimiter
        . ocr_text_regex_flags($flags);
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

    $literal = delimit_ocr_text_regex(preg_quote($ruleText, '/'), '/', 'iu', false);
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
        $name = is_string($senderRow['displayName'] ?? null) && trim((string) $senderRow['displayName']) !== ''
            ? trim((string) $senderRow['displayName'])
            : (is_string($senderRow['name'] ?? null) ? trim((string) $senderRow['name']) : '');
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
        $name = is_string($senderRow['displayName'] ?? null) && trim((string) $senderRow['displayName']) !== ''
            ? trim((string) $senderRow['displayName'])
            : (is_string($senderRow['name'] ?? null) ? trim((string) $senderRow['name']) : '');
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
            $ruleScore = signed_int($rule['score'] ?? 1, 1);
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
    $chars = utf8_chars($pattern);
    if ($chars === []) {
        return $pattern;
    }

    $result = '';
    $inCharacterClass = false;
    $count = count($chars);
    for ($index = 0; $index < $count; $index++) {
        $char = $chars[$index];

        if ($char === '\\') {
            $next = $index + 1 < $count ? $chars[$index + 1] : null;
            $result .= $char;
            if ($next !== null) {
                $result .= $next;
                $index++;
            }
            continue;
        }

        if ($char === '[' && !$inCharacterClass) {
            $inCharacterClass = true;
            $result .= $char;
            continue;
        }

        if ($char === ']' && $inCharacterClass) {
            $inCharacterClass = false;
            $result .= $char;
            continue;
        }

        if (!$inCharacterClass && preg_match('/\s/u', $char) === 1) {
            while ($index + 1 < $count && preg_match('/\s/u', $chars[$index + 1]) === 1) {
                $index++;
            }
            $result .= '\s+';
            continue;
        }

        $result .= $char;
    }

    return $result;
}

function literal_pattern_with_whitespace_wildcards(string $text, string $delimiter): string
{
    $segments = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($segments) || $segments === []) {
        return preg_quote($text, $delimiter);
    }

    return implode('\s+', array_map(
        static fn (string $segment): string => preg_quote($segment, $delimiter),
        $segments
    ));
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

        $delimiterPosition = strrpos($segmentPattern, '/');
        $body = $delimiterPosition !== false && $delimiterPosition > 0
            ? substr($segmentPattern, 1, $delimiterPosition - 1)
            : '';
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
        return delimit_ocr_text_regex('\b' . $joined . '\b', '/', 'iu', false);
    }

    return delimit_ocr_text_regex($joined, '/', 'iu', false);
}

function build_label_rule_text_regex(string $ruleText, bool $isRegex, array $replacementMap): ?string
{
    $trimmed = trim($ruleText);
    if ($trimmed === '') {
        return null;
    }

    if ($isRegex) {
        return delimit_ocr_text_regex(regex_pattern_with_whitespace_wildcards($trimmed));
    }

    return build_literal_space_flexible_regex($trimmed, $replacementMap, false);
}

function build_archiving_zone_regex(string $pattern): ?string
{
    $trimmed = trim($pattern);
    if ($trimmed === '') {
        return null;
    }

    return delimit_ocr_text_regex(regex_pattern_with_whitespace_wildcards($trimmed));
}

function build_data_field_search_term_regex(string $searchTerm, array $replacementMap, bool $isRegex = false): ?string
{
    $trimmed = trim($searchTerm);
    if ($trimmed === '') {
        return null;
    }

    if ($isRegex) {
        return delimit_ocr_text_regex(regex_pattern_with_whitespace_wildcards($trimmed));
    }

    return build_literal_space_flexible_regex($trimmed, $replacementMap, true);
}

function find_label_hits(
    array $lines,
    array $labels,
    array $replacementMap,
    bool $isRegex = false,
    array $lineGeometries = [],
    array $spanSettings = []
): array
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

        $lineGeometry = is_int($index) && is_array($lineGeometries[$index] ?? null) ? $lineGeometries[$index] : null;
        $searchSpans = $lineGeometry !== null
            ? extraction_field_layout_value_spans_for_line($line, $lineGeometry, 0, $spanSettings)
            : [[
                'text' => $line,
                'start' => 0,
                'end' => strlen($line),
            ]];

        $lineHits = [];
        foreach ($compiled as $item) {
            $pattern = $item['pattern'];
            foreach ($searchSpans as $searchSpan) {
                if (!is_array($searchSpan)) {
                    continue;
                }
                $spanText = is_string($searchSpan['text'] ?? null) ? (string) $searchSpan['text'] : '';
                $spanStart = is_int($searchSpan['start'] ?? null) ? (int) $searchSpan['start'] : 0;
                if ($spanText === '') {
                    continue;
                }

                $labelMatches = [];
                if (@preg_match_all($pattern, $spanText, $labelMatches, PREG_OFFSET_CAPTURE) !== 1) {
                    continue;
                }

                $fullMatches = $labelMatches[0] ?? null;
                if (!is_array($fullMatches)) {
                    continue;
                }

                foreach ($fullMatches as $matched) {
                    $matchedText = is_array($matched) && is_string($matched[0] ?? null) ? (string) $matched[0] : '';
                    $labelStart = $spanStart + (is_array($matched) && is_int($matched[1] ?? null) ? (int) $matched[1] : 0);
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

function find_line_range_for_document_offset(array $lineRanges, int $offset): ?array
{
    foreach ($lineRanges as $range) {
        if (!is_array($range)) {
            continue;
        }
        $start = is_int($range['start'] ?? null) ? (int) $range['start'] : -1;
        $end = is_int($range['end'] ?? null) ? (int) $range['end'] : -1;
        if ($start < 0 || $end < $start) {
            continue;
        }
        if ($offset >= $start && $offset <= $end) {
            return $range;
        }
    }

    return null;
}

function find_document_label_hits(array $lines, array $labels, array $replacementMap, bool $isRegex = false): array
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

    if ($compiled === []) {
        return [];
    }

    $documentLines = [];
    $lineRanges = [];
    $offset = 0;
    foreach ($lines as $index => $line) {
        if (!is_int($index)) {
            continue;
        }
        $resolvedLine = is_string($line) ? (string) $line : '';
        $documentLines[] = $resolvedLine;
        $length = strlen($resolvedLine);
        $lineRanges[] = [
            'index' => $index,
            'start' => $offset,
            'end' => $offset + $length,
            'length' => $length,
        ];
        $offset += $length + 1;
    }

    $documentText = implode("\n", $documentLines);
    if ($documentText === '') {
        return [];
    }

    $hits = [];
    foreach ($compiled as $item) {
        $matches = [];
        if (@preg_match_all((string) $item['pattern'], $documentText, $matches, PREG_OFFSET_CAPTURE) < 1) {
            continue;
        }

        $fullMatches = is_array($matches[0] ?? null) ? $matches[0] : [];
        foreach ($fullMatches as $matched) {
            if (!is_array($matched)) {
                continue;
            }

            $matchedText = is_string($matched[0] ?? null) ? (string) $matched[0] : '';
            $matchStart = is_int($matched[1] ?? null) ? (int) $matched[1] : -1;
            if ($matchedText === '' || $matchStart < 0) {
                continue;
            }

            $matchEnd = $matchStart + strlen($matchedText);
            $startRange = find_line_range_for_document_offset($lineRanges, $matchStart);
            $endRange = find_line_range_for_document_offset($lineRanges, max($matchStart, $matchEnd - 1));
            if (!is_array($endRange)) {
                continue;
            }

            $hitLineIndex = is_int($endRange['index'] ?? null) ? (int) $endRange['index'] : -1;
            if ($hitLineIndex < 0) {
                continue;
            }

            $line = is_string($lines[$hitLineIndex] ?? null) ? (string) $lines[$hitLineIndex] : '';
            $lineStart = is_int($endRange['start'] ?? null) ? (int) $endRange['start'] : 0;
            $lineLength = is_int($endRange['length'] ?? null) ? (int) $endRange['length'] : strlen($line);
            $labelStart = is_array($startRange) && (int) ($startRange['index'] ?? -1) === $hitLineIndex
                ? max(0, $matchStart - $lineStart)
                : 0;
            $labelEnd = min($lineLength, max($labelStart, $matchEnd - $lineStart));
            if ($labelEnd <= $labelStart) {
                continue;
            }

            $hits[] = [
                'index' => $hitLineIndex,
                'line' => $line,
                'pattern' => (string) $item['pattern'],
                'label' => $item['label'],
                'labelStart' => $labelStart,
                'labelEnd' => $labelEnd,
                'documentLabelText' => $matchedText,
            ];
        }
    }

    return $hits;
}

function union_bboxes(?array $left, ?array $right): ?array
{
    if ($left === null) {
        return $right;
    }
    if ($right === null) {
        return $left;
    }

    return [
        'x0' => min((float) ($left['x0'] ?? 0.0), (float) ($right['x0'] ?? 0.0)),
        'y0' => min((float) ($left['y0'] ?? 0.0), (float) ($right['y0'] ?? 0.0)),
        'x1' => max((float) ($left['x1'] ?? 0.0), (float) ($right['x1'] ?? 0.0)),
        'y1' => max((float) ($left['y1'] ?? 0.0), (float) ($right['y1'] ?? 0.0)),
    ];
}

function detect_configured_zone_matches(array $lines, array $zones, array $replacementMap, array $lineGeometries, array $valuePatterns = []): array
{
    $valuePatternsById = value_pattern_definitions_by_id($valuePatterns);
    $activeZones = array_values(array_filter($zones, static function ($zone): bool {
        return is_array($zone) && ($zone['enabled'] ?? true) !== false;
    }));
    if ($activeZones === [] || $lineGeometries === []) {
        return [];
    }

    $compiled = [];
    foreach ($activeZones as $zone) {
        $referencedPattern = resolve_reusable_value_pattern($zone, $valuePatternsById);
        if ($referencedPattern !== null) {
            $patternText = reusable_value_pattern_regex_source($referencedPattern);
        } elseif (normalize_value_pattern_source($zone['patternSource'] ?? null) === 'reference') {
            continue;
        } else {
            $patternText = (string) ($zone['pattern'] ?? '');
        }
        $pattern = build_archiving_zone_regex($patternText);
        if (!is_string($pattern)) {
            continue;
        }
        $compiled[] = [
            'zone' => $zone,
            'pattern' => $pattern,
        ];
    }
    if ($compiled === []) {
        return [];
    }

    $documentLines = [];
    $lineRanges = [];
    $offset = 0;
    foreach ($lines as $index => $line) {
        if (!is_int($index)) {
            continue;
        }
        $text = is_string($line) ? (string) $line : '';
        $documentLines[] = $text;
        $length = strlen($text);
        $lineRanges[] = [
            'index' => $index,
            'start' => $offset,
            'end' => $offset + $length,
        ];
        $offset += $length + 1;
    }
    $documentText = implode("\n", $documentLines);
    if ($documentText === '') {
        return [];
    }

    $matches = [];
    foreach ($compiled as $item) {
        $pregMatches = [];
        if (@preg_match_all((string) $item['pattern'], $documentText, $pregMatches, PREG_OFFSET_CAPTURE) < 1) {
            continue;
        }
        foreach (is_array($pregMatches[0] ?? null) ? $pregMatches[0] : [] as $match) {
            if (!is_array($match)) {
                continue;
            }
            $matchedText = is_string($match[0] ?? null) ? (string) $match[0] : '';
            $matchStart = is_int($match[1] ?? null) ? (int) $match[1] : -1;
            if ($matchedText === '' || $matchStart < 0) {
                continue;
            }
            $matchEnd = $matchStart + strlen($matchedText);
            $bbox = null;
            $bboxIndexes = [];
            $pageNumber = null;
            foreach ($lineRanges as $range) {
                $lineStart = (int) ($range['start'] ?? 0);
                $lineEnd = (int) ($range['end'] ?? 0);
                if ($lineEnd < $matchStart || $lineStart > $matchEnd) {
                    continue;
                }
                $lineIndex = (int) ($range['index'] ?? -1);
                $geometry = is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
                if ($geometry === null) {
                    continue;
                }
                $localStart = max(0, $matchStart - $lineStart);
                $localEnd = max($localStart, min($lineEnd, $matchEnd) - $lineStart);
                foreach (is_array($geometry['segments'] ?? null) ? $geometry['segments'] : [] as $segment) {
                    if (!is_array($segment)) {
                        continue;
                    }
                    $segmentStart = is_int($segment['start'] ?? null) ? (int) $segment['start'] : -1;
                    $segmentEnd = is_int($segment['end'] ?? null) ? (int) $segment['end'] : -1;
                    if ($segmentStart < 0 || $segmentEnd <= $segmentStart || $segmentEnd <= $localStart || $segmentStart >= $localEnd) {
                        continue;
                    }
                    $segmentBbox = normalize_debug_word_bbox($segment['bbox'] ?? null);
                    if ($segmentBbox === null) {
                        continue;
                    }
                    $bbox = union_bboxes($bbox, $segmentBbox);
                    if (is_numeric($segment['wordIndex'] ?? null)) {
                        $bboxIndexes[] = ((int) $segment['wordIndex']) + 1;
                    }
                }
                if ($pageNumber === null && is_numeric($geometry['pageNumber'] ?? null)) {
                    $pageNumber = (int) $geometry['pageNumber'];
                }
            }
            if ($bbox === null || $pageNumber === null) {
                continue;
            }
            $zone = is_array($item['zone'] ?? null) ? $item['zone'] : [];
            $matches[] = [
                'zoneId' => is_string($zone['id'] ?? null) ? trim((string) $zone['id']) : '',
                'zoneName' => is_string($zone['name'] ?? null) ? trim((string) $zone['name']) : '',
                'pageNumber' => $pageNumber,
                'bboxIndexes' => array_values(array_unique($bboxIndexes)),
                'boundingRect' => $bbox,
                'matchedText' => normalize_inline_whitespace($matchedText),
            ];
        }
    }

    return $matches;
}

function find_all_label_hits(
    array $lines,
    array $labels,
    array $replacementMap,
    bool $isRegex = false,
    array $lineGeometries = [],
    array $spanSettings = []
): array
{
    $hitsByKey = [];
    foreach (array_merge(
        find_label_hits($lines, $labels, $replacementMap, $isRegex, $lineGeometries, $spanSettings),
        find_document_label_hits($lines, $labels, $replacementMap, $isRegex)
    ) as $hit) {
        if (!is_array($hit)) {
            continue;
        }

        $hitKey = implode('|', [
            (string) ((int) ($hit['index'] ?? -1)),
            (string) ((int) ($hit['labelStart'] ?? -1)),
            (string) ((int) ($hit['labelEnd'] ?? -1)),
            is_string($hit['label'] ?? null) ? (string) $hit['label'] : '',
        ]);
        if (!isset($hitsByKey[$hitKey])) {
            $hitsByKey[$hitKey] = $hit;
        }
    }

    return array_values($hitsByKey);
}

function matched_label_text_from_hit(array $hit): ?string
{
    if (is_string($hit['documentLabelText'] ?? null) && trim((string) $hit['documentLabelText']) !== '') {
        return trim((string) $hit['documentLabelText']);
    }

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
        $pages = fallback_merged_objects_pages_from_job_debug($jobId);
    }
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
        $pageWidth = is_numeric($page['pageWidth'] ?? null) ? (float) $page['pageWidth'] : null;
        $pageHeight = is_numeric($page['pageHeight'] ?? null) ? (float) $page['pageHeight'] : null;

        $lineEntries[] = [
            'text' => '=== PAGE ' . $pageNumber . ' ===',
            'segments' => [],
            'pageNumber' => $pageNumber,
            'pageWidth' => $pageWidth,
            'pageHeight' => $pageHeight,
        ];

        $wordLines = build_grid_text_lines_from_debug_words(
            is_array($page['words'] ?? null) ? $page['words'] : []
        );
        if ($wordLines !== []) {
            foreach ($wordLines as $line) {
                $lineEntries[] = [
                    'text' => is_string($line['text'] ?? null) ? (string) $line['text'] : '',
                    'segments' => is_array($line['segments'] ?? null) ? $line['segments'] : [],
                    'pageNumber' => $pageNumber,
                    'pageWidth' => $pageWidth,
                    'pageHeight' => $pageHeight,
                ];
            }
            continue;
        }

        $pageText = is_string($page['text'] ?? null) ? rtrim((string) $page['text'], "\r\n") : '';
        foreach (ocr_text_lines($pageText) as $textLine) {
            $lineEntries[] = [
                'text' => is_string($textLine) ? $textLine : '',
                'segments' => [],
                'pageNumber' => $pageNumber,
                'pageWidth' => $pageWidth,
                'pageHeight' => $pageHeight,
            ];
        }

        if ($pageIndex < (count($pages) - 1)) {
            $lineEntries[] = [
                'text' => '',
                'segments' => [],
                'pageNumber' => $pageNumber,
                'pageWidth' => $pageWidth,
                'pageHeight' => $pageHeight,
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
                'pageWidth' => is_numeric($pages[count($pages) - 1]['pageWidth'] ?? null) ? (float) $pages[count($pages) - 1]['pageWidth'] : null,
                'pageHeight' => is_numeric($pages[count($pages) - 1]['pageHeight'] ?? null) ? (float) $pages[count($pages) - 1]['pageHeight'] : null,
            ];
        }
        $ocrLines = split_lines_for_matching($ocrText);
        $geometryTexts = array_map(
            static fn(array $entry): string => is_string($entry['text'] ?? null) ? (string) $entry['text'] : '',
            $lineEntries
        );
        if ($ocrLines !== $geometryTexts) {
            $alignedEntries = align_matching_line_geometries_to_ocr_lines($ocrLines, $lineEntries);
            if ($alignedEntries === null) {
                return [];
            }
            return $alignedEntries;
        }
    }

    return $lineEntries;
}

function blank_matching_line_geometry_entry(?array $referenceEntry = null): array
{
    $pageNumber = is_numeric($referenceEntry['pageNumber'] ?? null) ? (int) $referenceEntry['pageNumber'] : 1;
    if ($pageNumber < 1) {
        $pageNumber = 1;
    }

    return [
        'text' => '',
        'segments' => [],
        'pageNumber' => $pageNumber,
        'pageWidth' => is_numeric($referenceEntry['pageWidth'] ?? null) ? (float) $referenceEntry['pageWidth'] : null,
        'pageHeight' => is_numeric($referenceEntry['pageHeight'] ?? null) ? (float) $referenceEntry['pageHeight'] : null,
    ];
}

function align_matching_line_geometries_to_ocr_lines(array $ocrLines, array $lineEntries): ?array
{
    $aligned = [];
    $ocrIndex = 0;
    $geometryIndex = 0;
    $ocrCount = count($ocrLines);
    $geometryCount = count($lineEntries);

    while ($ocrIndex < $ocrCount && $geometryIndex < $geometryCount) {
        $ocrLine = is_string($ocrLines[$ocrIndex] ?? null) ? (string) $ocrLines[$ocrIndex] : '';
        $geometryEntry = is_array($lineEntries[$geometryIndex] ?? null) ? $lineEntries[$geometryIndex] : null;
        $geometryText = is_string($geometryEntry['text'] ?? null) ? (string) $geometryEntry['text'] : '';

        if ($geometryEntry !== null && $ocrLine === $geometryText) {
            $aligned[] = $geometryEntry;
            $ocrIndex++;
            $geometryIndex++;
            continue;
        }

        if ($ocrLine === '') {
            $referenceEntry = is_array($lineEntries[$geometryIndex] ?? null)
                ? $lineEntries[$geometryIndex]
                : (($aligned[count($aligned) - 1] ?? null) ?: null);
            $aligned[] = blank_matching_line_geometry_entry(is_array($referenceEntry) ? $referenceEntry : null);
            $ocrIndex++;
            continue;
        }

        if ($geometryText === '') {
            $geometryIndex++;
            continue;
        }

        return null;
    }

    while ($ocrIndex < $ocrCount) {
        $ocrLine = is_string($ocrLines[$ocrIndex] ?? null) ? (string) $ocrLines[$ocrIndex] : '';
        if ($ocrLine !== '') {
            return null;
        }
        $referenceEntry = is_array($lineEntries[$geometryIndex] ?? null)
            ? $lineEntries[$geometryIndex]
            : (($aligned[count($aligned) - 1] ?? null) ?: null);
        $aligned[] = blank_matching_line_geometry_entry(is_array($referenceEntry) ? $referenceEntry : null);
        $ocrIndex++;
    }

    while ($geometryIndex < $geometryCount) {
        $geometryEntry = is_array($lineEntries[$geometryIndex] ?? null) ? $lineEntries[$geometryIndex] : null;
        $geometryText = is_string($geometryEntry['text'] ?? null) ? (string) $geometryEntry['text'] : '';
        if ($geometryText !== '') {
            return null;
        }
        $geometryIndex++;
    }

    return $aligned;
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

function line_geometry_span_word_bbox_indexes(?array $lineGeometry, int $start, int $end): array
{
    if (!is_array($lineGeometry) || $start < 0 || $end <= $start) {
        return [];
    }

    $indexes = [];
    foreach (is_array($lineGeometry['segments'] ?? null) ? $lineGeometry['segments'] : [] as $segment) {
        if (!is_array($segment)) {
            continue;
        }

        $segmentStart = is_int($segment['start'] ?? null) ? (int) $segment['start'] : -1;
        $segmentEnd = is_int($segment['end'] ?? null) ? (int) $segment['end'] : -1;
        $wordIndex = is_int($segment['wordIndex'] ?? null) ? (int) $segment['wordIndex'] : null;
        if ($segmentStart < 0 || $segmentEnd <= $segmentStart || $wordIndex === null || $wordIndex < 0) {
            continue;
        }
        if ($segmentEnd <= $start || $segmentStart >= $end) {
            continue;
        }
        $indexes[$wordIndex + 1] = true;
    }

    $result = array_keys($indexes);
    sort($result, SORT_NUMERIC);
    return array_values($result);
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

function point_in_bbox(array $point, array $bbox): bool
{
    $x = (float) ($point['x'] ?? 0.0);
    $y = (float) ($point['y'] ?? 0.0);
    return $x >= (float) ($bbox['x0'] ?? 0.0)
        && $x <= (float) ($bbox['x1'] ?? 0.0)
        && $y >= (float) ($bbox['y0'] ?? 0.0)
        && $y <= (float) ($bbox['y1'] ?? 0.0);
}

function zone_id_for_bbox_center(array $bbox, array $zoneMatches, ?int $pageNumber): ?string
{
    $center = bbox_center_point($bbox);
    foreach ($zoneMatches as $zone) {
        if (!is_array($zone)) {
            continue;
        }
        if ($pageNumber !== null && is_numeric($zone['pageNumber'] ?? null) && (int) $zone['pageNumber'] !== $pageNumber) {
            continue;
        }
        $rect = normalize_debug_word_bbox($zone['boundingRect'] ?? null);
        if ($rect === null || !point_in_bbox($center, $rect)) {
            continue;
        }
        $zoneId = is_string($zone['zoneId'] ?? null) ? trim((string) $zone['zoneId']) : '';
        return $zoneId !== '' ? $zoneId : null;
    }

    return null;
}

function candidate_crosses_zone_barrier(array $labelBbox, array $candidateBbox, ?array $connector, array $zoneMatches, ?int $pageNumber): bool
{
    if ($zoneMatches === []) {
        return false;
    }

    $labelZoneId = zone_id_for_bbox_center($labelBbox, $zoneMatches, $pageNumber);
    $candidateZoneId = zone_id_for_bbox_center($candidateBbox, $zoneMatches, $pageNumber);
    if ($labelZoneId !== null || $candidateZoneId !== null) {
        return $labelZoneId !== $candidateZoneId;
    }
    if (!is_array($connector)) {
        return false;
    }

    $start = is_array($connector['start'] ?? null) ? $connector['start'] : [];
    $end = is_array($connector['end'] ?? null) ? $connector['end'] : [];
    foreach ($zoneMatches as $zone) {
        if (!is_array($zone)) {
            continue;
        }
        if ($pageNumber !== null && is_numeric($zone['pageNumber'] ?? null) && (int) $zone['pageNumber'] !== $pageNumber) {
            continue;
        }
        $rect = normalize_debug_word_bbox($zone['boundingRect'] ?? null);
        if ($rect !== null && line_segment_intersects_bbox($start, $end, $rect)) {
            return true;
        }
    }

    return false;
}

function candidate_is_rejected_by_zone_barrier(
    array $hit,
    int $candidateStart,
    int $candidateLineIndex,
    ?string $candidateSpanText,
    array $lineGeometries,
    array $zoneMatches
): bool {
    if ($zoneMatches === []) {
        return false;
    }
    $relation = resolve_candidate_geometry_relation($hit, $candidateStart, $candidateLineIndex, $candidateSpanText, $lineGeometries);
    if (!is_array($relation)) {
        return false;
    }

    $labelBbox = is_array($relation['labelBbox'] ?? null) ? $relation['labelBbox'] : null;
    $candidateBbox = is_array($relation['candidateBbox'] ?? null) ? $relation['candidateBbox'] : null;
    if ($labelBbox === null || $candidateBbox === null) {
        return false;
    }

    return candidate_crosses_zone_barrier(
        $labelBbox,
        $candidateBbox,
        is_array($relation['connector'] ?? null) ? $relation['connector'] : null,
        $zoneMatches,
        is_numeric($relation['pageNumber'] ?? null) ? (int) $relation['pageNumber'] : null
    );
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
    $overlapTolerance = 2.0;

    $labelX0 = (float) ($labelBbox['x0'] ?? 0.0);
    $labelY0 = (float) ($labelBbox['y0'] ?? 0.0);
    $labelX1 = (float) ($labelBbox['x1'] ?? 0.0);
    $labelY1 = (float) ($labelBbox['y1'] ?? 0.0);
    $candidateX0 = (float) ($candidateBbox['x0'] ?? 0.0);
    $candidateY0 = (float) ($candidateBbox['y0'] ?? 0.0);
    $candidateX1 = (float) ($candidateBbox['x1'] ?? 0.0);
    $candidateY1 = (float) ($candidateBbox['y1'] ?? 0.0);

    $overlapX = bbox_horizontal_overlap($labelBbox, $candidateBbox);
    $overlapY = bbox_overlap_length($labelY0, $labelY1, $candidateY0, $candidateY1);
    $candidateIsBelow = $candidateY0 >= ($labelY1 - $overlapTolerance);
    $candidateIsAbove = $candidateY1 <= ($labelY0 + $overlapTolerance);
    $candidateIsRight = $candidateX0 >= ($labelX1 - $overlapTolerance);
    $candidateIsLeft = $candidateX1 <= ($labelX0 + $overlapTolerance);

    if ($overlapX > $overlapTolerance) {
        if ($candidateIsBelow) {
            return 'down';
        }
        if ($candidateIsAbove) {
            return 'up';
        }
    }

    if ($overlapY > $overlapTolerance) {
        if ($candidateIsRight) {
            return 'right';
        }
        if ($candidateIsLeft) {
            return 'left';
        }
    }

    $vector = bbox_gap_vector($labelBbox, $candidateBbox);
    $dx = (float) ($vector['dx'] ?? 0.0);
    $dy = (float) ($vector['dy'] ?? 0.0);
    if (abs($dx) < 0.001 && abs($dy) < 0.001) {
        $labelCenter = bbox_center_point($labelBbox);
        $candidateCenter = bbox_center_point($candidateBbox);
        $dx = ((float) ($candidateCenter['x'] ?? 0.0)) - ((float) ($labelCenter['x'] ?? 0.0));
        $dy = ((float) ($candidateCenter['y'] ?? 0.0)) - ((float) ($labelCenter['y'] ?? 0.0));
        if (abs($dx) < 0.001 && abs($dy) < 0.001) {
            return 'right';
        }
    }

    if (abs($dx) >= abs($dy)) {
        return $dx >= 0.0 ? 'right' : 'left';
    }

    if ($labelLineIndex !== null && $candidateLineIndex !== null) {
        if ($candidateLineIndex > $labelLineIndex && $dy >= 0.0) {
            return 'down';
        }

        if ($candidateLineIndex < $labelLineIndex && $dy <= 0.0) {
            return 'up';
        }
    }

    return $dy >= 0.0 ? 'down' : 'up';
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

function position_penalty_min_line_height(array $labelBbox, array $candidateBbox): float
{
    $labelHeight = bbox_height($labelBbox);
    $candidateHeight = bbox_height($candidateBbox);
    $positiveHeights = array_values(array_filter([$labelHeight, $candidateHeight], static fn (float $height): bool => $height > 0.0));
    if ($positiveHeights !== []) {
        return max(1.0, min($positiveHeights));
    }

    return position_penalty_line_height($labelBbox, $candidateBbox);
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
        'verticalDistancePenalty' => 0.0,
        'verticalDistance' => 0.0,
        'verticalNormalizedDistance' => 0.0,
        'mainDirection' => null,
        'axis' => null,
        'diff' => 0.0,
        'normalizedDiff' => 0.0,
        'labelBbox' => null,
        'valueBbox' => null,
        'pageNumber' => null,
        'invalidReason' => null,
    ];

    $relation = resolve_candidate_geometry_relation($hit, $candidateStart, $candidateLineIndex, $candidateSpanText, $lineGeometries);
    if (!is_array($relation)) {
        return [
            ...$empty,
            'penalty' => 1.0,
            'axis' => 'invalid_bbox',
            'invalidReason' => 'Ogiltig bbox',
        ];
    }

    $labelBbox = is_array($relation['labelBbox'] ?? null) ? $relation['labelBbox'] : null;
    $candidateBbox = is_array($relation['candidateBbox'] ?? null) ? $relation['candidateBbox'] : null;
    $connector = is_array($relation['connector'] ?? null) ? $relation['connector'] : null;
    if ($labelBbox === null || $candidateBbox === null) {
        return [
            ...$empty,
            'penalty' => 1.0,
            'axis' => 'invalid_bbox',
            'invalidReason' => 'Ogiltig bbox',
        ];
    }

    $pageNumber = is_numeric($relation['pageNumber'] ?? null) ? (int) $relation['pageNumber'] : null;
    $geometryContext = [
        'labelBbox' => $labelBbox,
        'valueBbox' => $candidateBbox,
        'pageNumber' => $pageNumber,
    ];
    $settings = normalize_matching_position_adjustment_settings($positionSettings);
    $hitIndex = is_int($hit['index'] ?? null) ? (int) $hit['index'] : null;
    $mainDirection = bbox_main_direction($labelBbox, $candidateBbox, $hitIndex, $candidateLineIndex);
    $labelCenter = bbox_center_point($labelBbox);
    $candidateCenter = bbox_center_point($candidateBbox);
    $lineHeight = position_penalty_line_height($labelBbox, $candidateBbox);
    $minLineHeight = position_penalty_min_line_height($labelBbox, $candidateBbox);
    if ($mainDirection === 'left' || $mainDirection === 'up') {
        return [
            ...$geometryContext,
            'penalty' => 1.0,
            'verticalDistancePenalty' => 0.0,
            'verticalDistance' => 0.0,
            'verticalNormalizedDistance' => 0.0,
            'mainDirection' => $mainDirection,
            'axis' => 'invalid',
            'diff' => 0.0,
            'normalizedDiff' => 0.0,
            'invalidReason' => 'Fel riktning',
        ];
    }

    if ($mainDirection === 'right') {
        $diff = abs((float) ($candidateBbox['y1'] ?? 0.0) - (float) ($labelBbox['y1'] ?? 0.0));
        $normalizedDiff = $lineHeight > 0.0 ? ($diff / $lineHeight) : 0.0;
        $penalty = interpolate_matching_penalty_curve(
            is_array($settings['rightYOffsetPenaltyCurve'] ?? null) ? $settings['rightYOffsetPenaltyCurve'] : [],
            $normalizedDiff
        );
        return [
            ...$geometryContext,
            'penalty' => max(0.0, $penalty),
            'verticalDistancePenalty' => 0.0,
            'verticalDistance' => 0.0,
            'verticalNormalizedDistance' => 0.0,
            'mainDirection' => $mainDirection,
            'axis' => 'y',
            'diff' => $diff,
            'normalizedDiff' => $normalizedDiff,
        ];
    }

    $diff = bbox_best_horizontal_alignment_diff($labelBbox, $candidateBbox);
    $normalizedDiff = $lineHeight > 0.0 ? ($diff / $lineHeight) : 0.0;
    $verticalDistance = max(0.0, (float) ($candidateBbox['y0'] ?? 0.0) - (float) ($labelBbox['y1'] ?? 0.0));
    $verticalNormalizedDistance = $minLineHeight > 0.0 ? ($verticalDistance / $minLineHeight) : 0.0;
    $verticalDistancePenalty = interpolate_matching_penalty_curve(
        is_array($settings['downYDistancePenaltyCurve'] ?? null) ? $settings['downYDistancePenaltyCurve'] : [],
        $verticalNormalizedDistance
    );
    $xOffsetPenalty = interpolate_matching_penalty_curve(
        is_array($settings['downXOffsetPenaltyCurve'] ?? null) ? $settings['downXOffsetPenaltyCurve'] : [],
        $normalizedDiff
    );

    return [
        ...$geometryContext,
        'penalty' => max(0.0, $xOffsetPenalty),
        'verticalDistancePenalty' => clamp_confidence($verticalDistancePenalty),
        'verticalDistance' => $verticalDistance,
        'verticalNormalizedDistance' => $verticalNormalizedDistance,
        'mainDirection' => $mainDirection,
        'axis' => 'x',
        'diff' => $diff,
        'normalizedDiff' => $normalizedDiff,
    ];
}

function normalized_field_matching_text(mixed $value): string
{
    if (!is_scalar($value)) {
        return '';
    }

    $text = trim((string) $value);
    if ($text === '') {
        return '';
    }

    return normalize_inline_whitespace(lowercase_text($text));
}

function normalized_field_matching_key_text(mixed $value): string
{
    $normalized = normalized_field_matching_text($value);
    if ($normalized === '') {
        return '';
    }

    $stripped = preg_replace('/[:;]+\s*\z/u', '', $normalized);
    if (!is_string($stripped)) {
        return $normalized;
    }

    return trim($stripped);
}

function candidate_trailing_delimiter_penalty(?string $candidateSpanText, array $settings): float
{
    $text = is_string($candidateSpanText) ? trim($candidateSpanText) : '';
    if ($text === '') {
        return 0.0;
    }

    if (@preg_match('/[:;]\z/u', $text) !== 1) {
        return 0.0;
    }

    return max(0.0, (float) ($settings['trailingDelimiterPenalty'] ?? 0.0));
}

function extraction_field_match_candidate_lookup_text(array $match): string
{
    $candidates = [
        is_string($match['matchText'] ?? null) ? (string) $match['matchText'] : null,
        is_string($match['raw'] ?? null) ? (string) $match['raw'] : null,
        is_scalar($match['value'] ?? null) ? (string) $match['value'] : null,
    ];

    foreach ($candidates as $candidate) {
        $normalized = normalized_field_matching_key_text($candidate);
        if ($normalized !== '') {
            return $normalized;
        }
    }

    return '';
}

function sort_extraction_field_matches_by_confidence(array $matches): array
{
    usort($matches, static function (array $left, array $right): int {
        $leftConfidence = isset($left['finalConfidence']) && is_numeric($left['finalConfidence'])
            ? (float) $left['finalConfidence']
            : (isset($left['confidence']) ? (float) $left['confidence'] : (isset($left['baseConfidence']) ? (float) $left['baseConfidence'] : 0.0));
        $rightConfidence = isset($right['finalConfidence']) && is_numeric($right['finalConfidence'])
            ? (float) $right['finalConfidence']
            : (isset($right['confidence']) ? (float) $right['confidence'] : (isset($right['baseConfidence']) ? (float) $right['baseConfidence'] : 0.0));
        $confidenceCompare = $rightConfidence <=> $leftConfidence;
        if ($confidenceCompare !== 0) {
            return $confidenceCompare;
        }

        $lineCompare = ((int) ($left['lineIndex'] ?? PHP_INT_MAX)) <=> ((int) ($right['lineIndex'] ?? PHP_INT_MAX));
        if ($lineCompare !== 0) {
            return $lineCompare;
        }

        return ((int) ($left['start'] ?? PHP_INT_MAX)) <=> ((int) ($right['start'] ?? PHP_INT_MAX));
    });

    return array_values($matches);
}

function extraction_field_match_dedupe_value(array $match): string
{
    $value = $match['value'] ?? null;
    if (is_scalar($value)) {
        return normalized_field_matching_text($value);
    }

    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return is_string($encoded) ? normalized_field_matching_text($encoded) : '';
}

function extraction_field_bbox_overlap_ratio(?array $left, ?array $right): float
{
    if ($left === null || $right === null) {
        return 0.0;
    }

    $intersection = bbox_intersection_area($left, $right);
    if ($intersection <= 0.0) {
        return 0.0;
    }

    $smallestArea = min(bbox_area($left), bbox_area($right));
    if ($smallestArea <= 0.0) {
        return 0.0;
    }

    return $intersection / $smallestArea;
}

function extraction_field_bboxes_clearly_overlap(?array $left, ?array $right, float $minRatio = 0.45): bool
{
    if ($left === null || $right === null) {
        return false;
    }

    return extraction_field_bbox_overlap_ratio($left, $right) >= $minRatio
        || bbox_iou($left, $right) >= 0.2;
}

function extraction_field_match_same_value_region(array $left, array $right): bool
{
    $leftValueBbox = is_array($left['valueBbox'] ?? null) ? $left['valueBbox'] : null;
    $rightValueBbox = is_array($right['valueBbox'] ?? null) ? $right['valueBbox'] : null;
    if (extraction_field_bboxes_clearly_overlap($leftValueBbox, $rightValueBbox, 0.55)) {
        return true;
    }

    if ($leftValueBbox !== null || $rightValueBbox !== null) {
        return false;
    }

    return (int) ($left['lineIndex'] ?? PHP_INT_MIN) === (int) ($right['lineIndex'] ?? PHP_INT_MAX)
        && (int) ($left['start'] ?? PHP_INT_MIN) === (int) ($right['start'] ?? PHP_INT_MAX);
}

function extraction_field_match_same_key_region(array $left, array $right): bool
{
    $leftLabelBbox = is_array($left['labelBbox'] ?? null) ? $left['labelBbox'] : null;
    $rightLabelBbox = is_array($right['labelBbox'] ?? null) ? $right['labelBbox'] : null;
    if (extraction_field_bboxes_clearly_overlap($leftLabelBbox, $rightLabelBbox, 0.2)) {
        return true;
    }

    if ($leftLabelBbox !== null && $rightLabelBbox !== null) {
        $leftValueBbox = is_array($left['valueBbox'] ?? null) ? $left['valueBbox'] : null;
        $rightValueBbox = is_array($right['valueBbox'] ?? null) ? $right['valueBbox'] : null;
        $leftRegion = union_bboxes($leftLabelBbox, $leftValueBbox);
        $rightRegion = union_bboxes($rightLabelBbox, $rightValueBbox);
        if (extraction_field_bboxes_clearly_overlap($leftRegion, $rightRegion, 0.6)) {
            return true;
        }
    }

    if ($leftLabelBbox !== null || $rightLabelBbox !== null) {
        return false;
    }

    $leftLabelLine = is_int($left['labelLineIndex'] ?? null) ? (int) $left['labelLineIndex'] : null;
    $rightLabelLine = is_int($right['labelLineIndex'] ?? null) ? (int) $right['labelLineIndex'] : null;
    return $leftLabelLine !== null
        && $rightLabelLine !== null
        && $leftLabelLine === $rightLabelLine;
}

function extraction_field_matches_are_alternative_interpretations(array $left, array $right): bool
{
    $leftValue = extraction_field_match_dedupe_value($left);
    if ($leftValue === '' || $leftValue !== extraction_field_match_dedupe_value($right)) {
        return false;
    }

    if (!extraction_field_match_same_value_region($left, $right)) {
        return false;
    }

    return extraction_field_match_same_key_region($left, $right);
}

function extraction_field_match_specificity_length(array $match): int
{
    $candidates = [
        is_string($match['searchTerm'] ?? null) ? trim((string) $match['searchTerm']) : '',
        is_string($match['labelText'] ?? null) ? trim((string) $match['labelText']) : '',
        is_string($match['matchText'] ?? null) ? trim((string) $match['matchText']) : '',
    ];

    $length = 0;
    foreach ($candidates as $candidate) {
        if ($candidate !== '') {
            $candidateLength = function_exists('mb_strlen') ? mb_strlen($candidate, 'UTF-8') : strlen($candidate);
            $length = max($length, (int) $candidateLength);
        }
    }

    return $length;
}

function extraction_field_match_key_span_score(array $match): int
{
    $text = is_string($match['labelText'] ?? null) && trim((string) $match['labelText']) !== ''
        ? trim((string) $match['labelText'])
        : (is_string($match['searchTerm'] ?? null) ? trim((string) $match['searchTerm']) : '');
    if ($text === '') {
        return 0;
    }

    preg_match_all('/\S+/u', $text, $words);
    return is_array($words[0] ?? null) ? count($words[0]) : 0;
}

function preferred_extraction_field_match(array $left, array $right): array
{
    $leftConfidence = isset($left['finalConfidence']) && is_numeric($left['finalConfidence'])
        ? (float) $left['finalConfidence']
        : (float) ($left['confidence'] ?? ($left['baseConfidence'] ?? 0.0));
    $rightConfidence = isset($right['finalConfidence']) && is_numeric($right['finalConfidence'])
        ? (float) $right['finalConfidence']
        : (float) ($right['confidence'] ?? ($right['baseConfidence'] ?? 0.0));
    if ($leftConfidence !== $rightConfidence) {
        return $leftConfidence > $rightConfidence ? $left : $right;
    }

    $leftKeyScore = extraction_field_match_key_span_score($left);
    $rightKeyScore = extraction_field_match_key_span_score($right);
    if ($leftKeyScore !== $rightKeyScore) {
        return $leftKeyScore > $rightKeyScore ? $left : $right;
    }

    $leftSpecificity = extraction_field_match_specificity_length($left);
    $rightSpecificity = extraction_field_match_specificity_length($right);
    if ($leftSpecificity !== $rightSpecificity) {
        return $leftSpecificity > $rightSpecificity ? $left : $right;
    }

    $leftNoise = is_numeric($left['noisePenalty'] ?? null) ? (float) $left['noisePenalty'] : 0.0;
    $rightNoise = is_numeric($right['noisePenalty'] ?? null) ? (float) $right['noisePenalty'] : 0.0;
    if ($leftNoise !== $rightNoise) {
        return $leftNoise < $rightNoise ? $left : $right;
    }

    $leftPage = is_int($left['pageNumber'] ?? null) ? (int) $left['pageNumber'] : PHP_INT_MAX;
    $rightPage = is_int($right['pageNumber'] ?? null) ? (int) $right['pageNumber'] : PHP_INT_MAX;
    if ($leftPage !== $rightPage) {
        return $leftPage < $rightPage ? $left : $right;
    }

    $leftLine = is_int($left['lineIndex'] ?? null) ? (int) $left['lineIndex'] : PHP_INT_MAX;
    $rightLine = is_int($right['lineIndex'] ?? null) ? (int) $right['lineIndex'] : PHP_INT_MAX;
    if ($leftLine !== $rightLine) {
        return $leftLine < $rightLine ? $left : $right;
    }

    $leftStart = is_int($left['start'] ?? null) ? (int) $left['start'] : PHP_INT_MAX;
    $rightStart = is_int($right['start'] ?? null) ? (int) $right['start'] : PHP_INT_MAX;
    if ($leftStart !== $rightStart) {
        return $leftStart < $rightStart ? $left : $right;
    }

    return $left;
}

function dedupe_extraction_field_matches(array $matches): array
{
    $deduped = [];
    foreach ($matches as $match) {
        if (!is_array($match)) {
            continue;
        }

        $matchedExisting = false;
        foreach ($deduped as $index => $existing) {
            if (!is_array($existing) || !extraction_field_matches_are_alternative_interpretations($existing, $match)) {
                continue;
            }

            $deduped[$index] = preferred_extraction_field_match($existing, $match);
            $matchedExisting = true;
            break;
        }

        if (!$matchedExisting) {
            $deduped[] = $match;
        }
    }

    return sort_extraction_field_matches_by_confidence($deduped);
}

function dedupe_extraction_field_result_matches(array $results): array
{
    foreach ($results as &$result) {
        if (!is_array($result) || !is_array($result['matches'] ?? null)) {
            continue;
        }

        $result['matches'] = dedupe_extraction_field_matches($result['matches']);
    }
    unset($result);

    return $results;
}

function candidate_confidence_score(
    array $components
): float
{
    $base = isset($components['base']) && is_numeric($components['base']) ? (float) $components['base'] : 1.0;
    $noisePenalty = isset($components['noisePenalty']) && is_numeric($components['noisePenalty']) ? (float) $components['noisePenalty'] : 0.0;
    $trailingDelimiterPenalty = isset($components['trailingDelimiterPenalty']) && is_numeric($components['trailingDelimiterPenalty'])
        ? (float) $components['trailingDelimiterPenalty']
        : 0.0;
    $positionPenalty = isset($components['positionPenalty']) && is_numeric($components['positionPenalty']) ? (float) $components['positionPenalty'] : 0.0;
    $verticalDistancePenalty = isset($components['verticalDistancePenalty']) && is_numeric($components['verticalDistancePenalty'])
        ? (float) $components['verticalDistancePenalty']
        : 0.0;
    $contentPenalty = isset($components['contentPenalty']) && is_numeric($components['contentPenalty']) ? (float) $components['contentPenalty'] : 0.0;

    $confidence = $base;
    $confidence *= max(0.0, 1.0 - clamp_confidence($noisePenalty));
    $confidence *= max(0.0, 1.0 - clamp_confidence($trailingDelimiterPenalty));
    $confidence *= max(0.0, 1.0 - clamp_confidence($positionPenalty));
    $confidence *= max(0.0, 1.0 - clamp_confidence($verticalDistancePenalty));
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
    ?string $candidateSpanText = null,
    ?int $contextStart = null,
    ?string $contextSpanText = null
): array
{
    $settings = normalize_matching_position_adjustment_settings($positionSettings);

    $base = 1.0;
    $betweenText = candidate_between_text($hit, $candidateLine, $candidateStart, $candidateLineIndex);
    $noiseStart = is_int($contextStart) && $contextStart >= 0 ? $contextStart : $candidateStart;
    $noiseSpanText = is_string($contextSpanText) && $contextSpanText !== '' ? $contextSpanText : $candidateSpanText;

    $noiseDetails = candidate_noise_details(
        $hit,
        $noiseStart,
        $candidateLineIndex,
        $noiseSpanText,
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
    $noisePenalty = interpolate_matching_penalty_curve(
        is_array($settings['noisePenaltyPerCharacterCurve'] ?? null) ? $settings['noisePenaltyPerCharacterCurve'] : [],
        (float) $noiseCharacters
    );
    $trailingDelimiterPenalty = candidate_trailing_delimiter_penalty($candidateSpanText, $settings);

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
        'trailingDelimiterPenalty' => max(0.0, $trailingDelimiterPenalty),
        'positionPenalty' => max(0.0, (float) ($positionPenaltyDetails['penalty'] ?? 0.0)),
        'verticalDistancePenalty' => is_numeric($positionPenaltyDetails['verticalDistancePenalty'] ?? null)
            ? clamp_confidence((float) $positionPenaltyDetails['verticalDistancePenalty'])
            : null,
        'verticalDistance' => is_numeric($positionPenaltyDetails['verticalDistance'] ?? null) ? (float) $positionPenaltyDetails['verticalDistance'] : null,
        'verticalNormalizedDistance' => is_numeric($positionPenaltyDetails['verticalNormalizedDistance'] ?? null)
            ? (float) $positionPenaltyDetails['verticalNormalizedDistance']
            : null,
        'positionPenaltyAxis' => is_string($positionPenaltyDetails['axis'] ?? null) ? (string) $positionPenaltyDetails['axis'] : null,
        'mainDirection' => is_string($positionPenaltyDetails['mainDirection'] ?? null) ? (string) $positionPenaltyDetails['mainDirection'] : null,
        'positionDiff' => is_numeric($positionPenaltyDetails['diff'] ?? null) ? (float) $positionPenaltyDetails['diff'] : null,
        'positionNormalizedDiff' => is_numeric($positionPenaltyDetails['normalizedDiff'] ?? null) ? (float) ($positionPenaltyDetails['normalizedDiff']) : null,
        'labelBbox' => is_array($positionPenaltyDetails['labelBbox'] ?? null) ? $positionPenaltyDetails['labelBbox'] : null,
        'valueBbox' => is_array($positionPenaltyDetails['valueBbox'] ?? null) ? $positionPenaltyDetails['valueBbox'] : null,
        'pageNumber' => is_numeric($positionPenaltyDetails['pageNumber'] ?? null) ? (int) $positionPenaltyDetails['pageNumber'] : null,
        'invalidReason' => is_string($positionPenaltyDetails['invalidReason'] ?? null) ? (string) $positionPenaltyDetails['invalidReason'] : null,
        'contentPenalty' => max(0.0, $contentPenalty),
        'betweenText' => $betweenText,
        'noiseText' => $noiseText,
        'noiseSegments' => $noiseSegments,
    ];
}

function extraction_field_candidate_match_context(array $candidate, int $fallbackStart, ?string $fallbackSpanText): array
{
    $matchStart = is_int($candidate['matchStart'] ?? null) ? (int) $candidate['matchStart'] : $fallbackStart;
    $matchText = is_string($candidate['matchText'] ?? null) && (string) $candidate['matchText'] !== ''
        ? (string) $candidate['matchText']
        : $fallbackSpanText;

    return [
        'start' => $matchStart >= 0 ? $matchStart : $fallbackStart,
        'spanText' => $matchText,
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

    $hits = find_all_label_hits($lines, $labels, $replacementMap, $labelsAreRegex, $lineGeometries, $spanSettings);
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
                    $matchContext = extraction_field_candidate_match_context($candidate, $start, $spanText);
                    $components = candidate_confidence_components(
                        $hit,
                        $line,
                        $start,
                        $hitIndex,
                        'tail',
                        $positionSettings,
                        $lineGeometries,
                        $spanText,
                        is_int($matchContext['start'] ?? null) ? (int) $matchContext['start'] : null,
                        is_string($matchContext['spanText'] ?? null) ? (string) $matchContext['spanText'] : null
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
                $matchContext = extraction_field_candidate_match_context($candidate, $start, $spanText);
                $components = candidate_confidence_components(
                    $hit,
                    $line,
                    $start,
                    $hitIndex,
                    'line',
                    $positionSettings,
                    $lineGeometries,
                    $spanText,
                    is_int($matchContext['start'] ?? null) ? (int) $matchContext['start'] : null,
                    is_string($matchContext['spanText'] ?? null) ? (string) $matchContext['spanText'] : null
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
                $matchContext = extraction_field_candidate_match_context($candidate, $start, $spanText);
                $components = candidate_confidence_components(
                    $hit,
                    $nearLine,
                    $start,
                    (int) $nearIndex,
                    'nearby',
                    $positionSettings,
                    $lineGeometries,
                    $spanText,
                    is_int($matchContext['start'] ?? null) ? (int) $matchContext['start'] : null,
                    is_string($matchContext['spanText'] ?? null) ? (string) $matchContext['spanText'] : null
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
    if (@preg_match_all('/\b((?:\d{1,8}|\d{1,3}(?:\s+\d{1,3}){1,4})\s*-\s*\d{1,5})\b/u', $text, $matches, PREG_OFFSET_CAPTURE) < 1) {
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

function primary_date_month_lookup(): array
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

function add_primary_date_candidate(array &$candidates, array &$seen, ?string $value, string $raw, int $start, string $format): void
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

function primary_date_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $candidates = [];
    $seen = [];
    $yearFirstSeparatorPattern = '(?:\s*[.\-\/]\s*|\s+)';

    $matches = [];
    if (@preg_match_all('/\b(20\d{2})' . $yearFirstSeparatorPattern . '(\d{1,2})' . $yearFirstSeparatorPattern . '(\d{1,2})\b/u', $text, $matches, PREG_OFFSET_CAPTURE) >= 1) {
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
            add_primary_date_candidate($candidates, $seen, $value, $raw, $offsetBase + $start, 'ymd_numeric');
        }
    }

    $matches = [];
    if (@preg_match_all('/\b(20\d{2})' . $yearFirstSeparatorPattern . '(\d{1,2})\b/u', $text, $matches, PREG_OFFSET_CAPTURE) >= 1) {
        $all = is_array($matches[0] ?? null) ? $matches[0] : [];
        foreach ($all as $index => $group) {
            if (!is_array($group) || count($group) < 2) {
                continue;
            }
            $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
            $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
            $absoluteStart = $offsetBase + $start;
            $localEnd = $start + strlen($raw);
            $nextChar = $localEnd >= 0 && $localEnd < strlen($text) ? substr($text, $localEnd, 1) : '';
            if ($nextChar === '-' || $nextChar === '.' || $nextChar === '/') {
                continue;
            }
            $year = isset($matches[1][$index][0]) ? (int) $matches[1][$index][0] : 0;
            $month = isset($matches[2][$index][0]) ? (int) $matches[2][$index][0] : 0;
            $value = ($month >= 1 && $month <= 12)
                ? sprintf('%04d-%02d', $year, $month)
                : null;
            add_primary_date_candidate($candidates, $seen, $value, $raw, $absoluteStart, 'ym_numeric');
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
            add_primary_date_candidate($candidates, $seen, $value, $raw, $offsetBase + $start, 'dmy_numeric');
        }
    }

    $monthPattern = 'jan(?:uari|uary)?|feb(?:ruari|ruary)?|mar(?:s|ch)?|apr(?:il)?|maj|may|jun(?:i|e)?|jul(?:i|y)?|aug(?:usti|ust)?|sep(?:t(?:ember)?)?|okt(?:ober)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?';
    $matches = [];
    if (@preg_match_all('/\b((?:den\s+)?(\d{1,2})\s+(' . $monthPattern . ')\s+(20\d{2}))\b/iu', $text, $matches, PREG_OFFSET_CAPTURE) >= 1) {
        $all = is_array($matches[1] ?? null) ? $matches[1] : [];
        $monthLookup = primary_date_month_lookup();
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
            add_primary_date_candidate($candidates, $seen, $value, $raw, $offsetBase + $start, 'd_mon_y');
        }
    }

    $matches = [];
    if (@preg_match_all('/\b((' . $monthPattern . ')\s+(20\d{2}))\b/iu', $text, $matches, PREG_OFFSET_CAPTURE) >= 1) {
        $all = is_array($matches[1] ?? null) ? $matches[1] : [];
        $monthLookup = primary_date_month_lookup();
        foreach ($all as $index => $group) {
            if (!is_array($group) || count($group) < 2) {
                continue;
            }
            $raw = is_string($group[0] ?? null) ? (string) $group[0] : '';
            $start = is_int($group[1] ?? null) ? (int) $group[1] : -1;
            $monthKey = isset($matches[2][$index][0]) ? lowercase_text((string) $matches[2][$index][0]) : '';
            $year = isset($matches[3][$index][0]) ? (int) $matches[3][$index][0] : 0;
            $month = $monthLookup[$monthKey] ?? 0;
            $value = ($month > 0 && $year >= 2000)
                ? sprintf('%04d-%02d', $year, $month)
                : null;
            add_primary_date_candidate($candidates, $seen, $value, $raw, $offsetBase + $start, 'mon_y');
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

function normalize_primary_date_lookup_text(string $text): string
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

function primary_date_reference_localities(): array
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
        $normalized = normalize_primary_date_lookup_text($name);
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

function primary_date_place_match(string $prefixText): ?array
{
    $normalizedPrefix = normalize_primary_date_lookup_text($prefixText);
    if ($normalizedPrefix === '') {
        return null;
    }

    $best = null;
    foreach (primary_date_reference_localities() as $normalizedPlace => $rawPlace) {
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
    $lookup = normalize_primary_date_lookup_text($normalized);
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
    ];
    foreach ($headerishPatterns as $pattern) {
        if (@preg_match('/\b' . preg_quote($pattern, '/') . '\b/u', $lookup) === 1) {
            return false;
        }
    }
    return true;
}

function primary_date_linear_bonus(?float $ratio, float $maxPoints, float $fullUntil, float $zeroAfter): float
{
    if ($ratio === null || $maxPoints <= 0.0) {
        return 0.0;
    }
    if ($ratio <= $fullUntil) {
        return $maxPoints;
    }
    if ($ratio >= $zeroAfter || $zeroAfter <= $fullUntil) {
        return 0.0;
    }
    $t = ($ratio - $fullUntil) / ($zeroAfter - $fullUntil);
    return $maxPoints * (1.0 - $t);
}

function primary_date_linear_penalty(?float $ratio, float $maxPenalty, float $startsAt, float $fullAfter): float
{
    if ($ratio === null || $maxPenalty >= 0.0) {
        return 0.0;
    }
    if ($ratio <= $startsAt) {
        return 0.0;
    }
    if ($ratio >= $fullAfter || $fullAfter <= $startsAt) {
        return $maxPenalty;
    }
    $t = ($ratio - $startsAt) / ($fullAfter - $startsAt);
    return $maxPenalty * $t;
}

function primary_date_full_confidence_score(array $heuristics): float
{
    $normalized = normalize_primary_date_heuristics($heuristics);
    return max(1.0, (float) ($normalized['full_confidence_score'] ?? 130.0));
}

function primary_date_line_page_height(array $lineGeometries, int $lineIndex): ?float
{
    $entry = is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
    $pageHeight = is_array($entry) && is_numeric($entry['pageHeight'] ?? null) ? (float) $entry['pageHeight'] : null;
    if ($pageHeight !== null && $pageHeight > 0.0) {
        return $pageHeight;
    }

    $pageNumber = matching_line_page_number($lineGeometries, $lineIndex);
    $maxY = null;
    foreach ($lineGeometries as $geometry) {
        if (!is_array($geometry)) {
            continue;
        }
        $geometryPage = is_numeric($geometry['pageNumber'] ?? null) ? (int) $geometry['pageNumber'] : 1;
        if ($pageNumber !== null && $geometryPage !== $pageNumber) {
            continue;
        }
        foreach (is_array($geometry['segments'] ?? null) ? $geometry['segments'] : [] as $segment) {
            if (!is_array($segment)) {
                continue;
            }
            $bbox = normalize_debug_word_bbox($segment['bbox'] ?? null);
            if ($bbox === null) {
                continue;
            }
            $maxY = $maxY === null ? (float) $bbox['y1'] : max($maxY, (float) $bbox['y1']);
        }
    }
    return $maxY !== null && $maxY > 0.0 ? $maxY : null;
}

function primary_date_candidate_position(array $candidate, array $lineGeometries): array
{
    $lineIndex = is_int($candidate['lineIndex'] ?? null) ? (int) $candidate['lineIndex'] : -1;
    $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
    $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : '';
    $lineGeometry = $lineIndex >= 0 && is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
    $bbox = $lineGeometry !== null && $start >= 0 && $raw !== ''
        ? line_geometry_span_bbox($lineGeometry, $start, $start + max(1, strlen($raw)))
        : null;
    $pageNumber = $lineIndex >= 0 ? matching_line_page_number($lineGeometries, $lineIndex) : null;
    $pageHeight = $lineIndex >= 0 ? primary_date_line_page_height($lineGeometries, $lineIndex) : null;
    $yRatio = null;
    if ($bbox !== null && $pageHeight !== null && $pageHeight > 0.0) {
        $center = bbox_center_point($bbox);
        $yRatio = max(0.0, min(1.0, ((float) $center['y']) / $pageHeight));
    }
    return [
        'bbox' => $bbox,
        'pageNumber' => $pageNumber,
        'pageHeight' => $pageHeight,
        'yRatio' => $yRatio,
    ];
}

function primary_date_has_date_word(string $normalizedText): bool
{
    return $normalizedText !== '' && @preg_match('/\b[a-z0-9]*datum[a-z0-9]*\b/u', $normalizedText) === 1;
}

function primary_date_text_density_signal(string $line, string $prefix, string $suffix, array $settings): array
{
    $wordCount = count(preg_split('/\s+/u', trim($line), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    $freeWords = max(0, (int) ($settings['no_penalty_until_words'] ?? 4));
    $fullWords = max($freeWords + 1, (int) ($settings['full_penalty_from_words'] ?? 12));
    $ratio = max(0.0, min(1.0, ($wordCount - $freeWords) / max(1, $fullWords - $freeWords)));
    $ratio = max(0.0, min(1.0, $ratio));
    return [
        'ratio' => $ratio,
        'wordCount' => $wordCount,
    ];
}

function primary_date_date_word_match(array $candidate, array $lines, array $replacementMap, array $positionSettings, array $lineGeometries): ?array
{
    $lineIndex = is_int($candidate['lineIndex'] ?? null) ? (int) $candidate['lineIndex'] : -1;
    $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
    $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : '';
    if ($lineIndex < 0 || $start < 0 || $raw === '') {
        return null;
    }

    $candidateLine = is_string($lines[$lineIndex] ?? null) ? (string) $lines[$lineIndex] : '';
    if ($candidateLine === '') {
        return null;
    }

    $spanSettings = matching_bbox_span_building_from_position_settings($positionSettings);
    $hits = find_all_label_hits(
        $lines,
        [['text' => 'datum', 'isRegex' => true]],
        $replacementMap,
        false,
        $lineGeometries,
        $spanSettings
    );
    if ($hits === []) {
        return null;
    }

    $spanText = is_string($candidate['spanText'] ?? null)
        ? (string) $candidate['spanText']
        : $raw;
    $best = null;

    foreach ($hits as $hit) {
        if (!is_array($hit)) {
            continue;
        }

        $components = candidate_confidence_components(
            $hit,
            $candidateLine,
            $start,
            $lineIndex,
            'primary_date',
            $positionSettings,
            $lineGeometries,
            $spanText,
            $start,
            $spanText
        );
        $components['trailingDelimiterPenalty'] = 0.0;
        $components['contentPenalty'] = 0.0;
        $confidence = candidate_confidence_score($components);
        if ($confidence <= 0.0) {
            continue;
        }

        $candidateMatch = [
            'confidence' => $confidence,
            'matchedText' => matched_label_text_from_hit($hit) ?? 'datum',
            'components' => $components,
        ];
        if ($best === null || $confidence > (float) ($best['confidence'] ?? 0.0)) {
            $best = $candidateMatch;
        }
    }

    return $best;
}

function primary_date_place_distance_match(array $candidate, array $lines, array $lineGeometries): ?array
{
    $lineIndex = is_int($candidate['lineIndex'] ?? null) ? (int) $candidate['lineIndex'] : -1;
    $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
    $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : '';
    if ($lineIndex < 0 || $start < 0 || $raw === '') {
        return null;
    }

    $candidateLineGeometry = is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
    $candidateBbox = line_geometry_span_bbox($candidateLineGeometry, $start, $start + strlen($raw));
    $best = null;

    $lineIndexes = array_values(array_unique([$lineIndex, $lineIndex - 1, $lineIndex - 2]));
    foreach ($lineIndexes as $contextLineIndex) {
        if ($contextLineIndex < 0 || !is_string($lines[$contextLineIndex] ?? null)) {
            continue;
        }
        $contextLine = (string) $lines[$contextLineIndex];
        foreach (primary_date_reference_localities() as $normalizedPlace => $rawPlace) {
            $quotedRaw = preg_quote($rawPlace, '/');
            if (@preg_match_all('/\b' . $quotedRaw . '\b/iu', $contextLine, $matches, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }
            foreach ($matches[0] as $match) {
                $placeStart = is_int($match[1] ?? null) ? (int) $match[1] : -1;
                $placeText = is_string($match[0] ?? null) ? (string) $match[0] : '';
                if ($placeStart < 0 || $placeText === '') {
                    continue;
                }
                $placeEnd = $placeStart + strlen($placeText);
                $isLeft = $contextLineIndex === $lineIndex && $placeEnd <= $start;
                $isAbove = $contextLineIndex < $lineIndex;
                if (!$isLeft && !$isAbove) {
                    continue;
                }

                $placeLineGeometry = is_array($lineGeometries[$contextLineIndex] ?? null) ? $lineGeometries[$contextLineIndex] : null;
                $placeBbox = line_geometry_span_bbox($placeLineGeometry, $placeStart, $placeEnd);
                if ($candidateBbox !== null && $placeBbox !== null) {
                    $horizontalGap = max(
                        0.0,
                        (float) ($candidateBbox['x0'] ?? 0.0) - (float) ($placeBbox['x1'] ?? 0.0),
                        (float) ($placeBbox['x0'] ?? 0.0) - (float) ($candidateBbox['x1'] ?? 0.0)
                    );
                    $verticalGap = max(
                        0.0,
                        (float) ($candidateBbox['y0'] ?? 0.0) - (float) ($placeBbox['y1'] ?? 0.0),
                        (float) ($placeBbox['y0'] ?? 0.0) - (float) ($candidateBbox['y1'] ?? 0.0)
                    );
                    $lineHeight = position_penalty_min_line_height($placeBbox, $candidateBbox);
                    $distance = sqrt(($horizontalGap * $horizontalGap) + ($verticalGap * $verticalGap)) / max(1.0, $lineHeight);
                } elseif ($contextLineIndex === $lineIndex) {
                    $between = substr($contextLine, $placeEnd, max(0, $start - $placeEnd));
                    $distance = count(preg_split('/\s+/u', trim(is_string($between) ? $between : ''), -1, PREG_SPLIT_NO_EMPTY) ?: []);
                } else {
                    $distance = (float) ($lineIndex - $contextLineIndex);
                }

                $candidateMatch = [
                    'name' => $rawPlace,
                    'normalized' => $normalizedPlace,
                    'distance' => $distance,
                    'context' => $contextLineIndex === $lineIndex ? 'same_line_before' : 'line_above',
                ];
                if ($best === null || $distance < (float) ($best['distance'] ?? INF)) {
                    $best = $candidateMatch;
                }
            }
        }
    }

    return $best;
}

function score_primary_date_candidate(
    array $candidate,
    array $lines,
    array $lineGeometries = [],
    array $heuristics = [],
    array $replacementMap = [],
    array $positionSettings = []
): array
{
    $heuristics = normalize_primary_date_heuristics($heuristics);
    $lineIndex = is_int($candidate['lineIndex'] ?? null) ? (int) $candidate['lineIndex'] : -1;
    $line = is_string($candidate['line'] ?? null) ? (string) $candidate['line'] : '';
    $raw = is_string($candidate['raw'] ?? null) ? trim((string) $candidate['raw']) : '';
    $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;

    $result = $candidate;
    $result['signals'] = [];
    $result['excluded'] = false;
    $result['excludedReason'] = null;
    $result['score'] = 0;
    $result['rawScore'] = 0;
    $result['confidence'] = 0.0;
    $result['fullConfidenceScore'] = primary_date_full_confidence_score($heuristics);

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
    $result['sameLinePrefix'] = normalize_inline_whitespace($prefix);
    $result['sameLineSuffix'] = normalize_inline_whitespace($suffix);
    $result['previousLine'] = normalize_inline_whitespace($previousLine);
    $position = primary_date_candidate_position($candidate, $lineGeometries);
    $candidateYRatio = is_numeric($position['yRatio'] ?? null) ? (float) $position['yRatio'] : null;
    $candidatePageNumber = is_int($position['pageNumber'] ?? null) ? (int) $position['pageNumber'] : null;
    $result['bbox'] = $position['bbox'];
    $result['pageNumber'] = $candidatePageNumber;
    $result['yRatio'] = $candidateYRatio;
    $score = 0.0;
    $signals = [];
    $bonuses = is_array($heuristics['bonuses'] ?? null) ? $heuristics['bonuses'] : [];
    $penalties = is_array($heuristics['penalties'] ?? null) ? $heuristics['penalties'] : [];

    $placeMatch = primary_date_place_distance_match($candidate, $lines, $lineGeometries);
    if (is_array($placeMatch) && ($bonuses['place_near_date']['enabled'] ?? true) === true) {
        $placeDistance = (float) ($placeMatch['distance'] ?? 0.0);
        $points = interpolate_primary_date_score_curve(
            is_array($bonuses['place_near_date']['curve'] ?? null) ? $bonuses['place_near_date']['curve'] : [],
            $placeDistance
        );
        if (abs($points) >= 0.0001) {
            $score += $points;
            $signals[] = [
                'type' => $points >= 0.0 ? 'positive' : 'negative',
                'code' => 'place_near_date',
                'score' => $points,
                'detail' => (string) ($placeMatch['context'] ?? 'near') . ':' . (string) ($placeMatch['name'] ?? '') . ',distance:' . round($placeDistance, 3),
            ];
        }
        $result['matchedPlace'] = (string) ($placeMatch['name'] ?? '');
        $result['matchedPlaceDistance'] = $placeDistance;
    }

    if (($bonuses['document_position']['enabled'] ?? true) === true && $candidateYRatio !== null) {
        $points = interpolate_primary_date_score_curve(
            is_array($bonuses['document_position']['curve'] ?? null) ? $bonuses['document_position']['curve'] : [],
            $candidateYRatio
        );
        if (abs($points) >= 0.0001) {
            $score += $points;
            $signals[] = [
                'type' => $points >= 0.0 ? 'positive' : 'negative',
                'code' => 'document_position',
                'score' => $points,
                'detail' => 'y_ratio:' . round($candidateYRatio, 3),
            ];
        }
    }

    if (($penalties['date_word_nearby']['enabled'] ?? true) === true) {
        $dateWordMatch = primary_date_date_word_match($candidate, $lines, $replacementMap, $positionSettings, $lineGeometries);
        $dateWordConfidence = is_array($dateWordMatch) && is_numeric($dateWordMatch['confidence'] ?? null)
            ? clamp_confidence((float) $dateWordMatch['confidence'])
            : null;
        $dateWordScore = $dateWordConfidence !== null
            ? interpolate_primary_date_score_curve(
                is_array($penalties['date_word_nearby']['curve'] ?? null) ? $penalties['date_word_nearby']['curve'] : [],
                $dateWordConfidence
            )
            : 0.0;
        if (abs($dateWordScore) >= 0.0001) {
            $score += $dateWordScore;
            $signals[] = [
                'type' => $dateWordScore >= 0.0 ? 'positive' : 'negative',
                'code' => 'date_word_nearby',
                'score' => $dateWordScore,
                'detail' => 'confidence:' . round($dateWordConfidence ?? 0.0, 3) . ',label:' . (string) ($dateWordMatch['matchedText'] ?? 'datum'),
            ];
        }
    }

    if (($penalties['text_density']['enabled'] ?? true) === true) {
        $textDensity = primary_date_text_density_signal($line, $prefix, $suffix, $penalties['text_density']);
        $textDensityRatio = (float) ($textDensity['ratio'] ?? 0.0);
        $textDensityScore = interpolate_primary_date_score_curve(
            is_array($penalties['text_density']['curve'] ?? null) ? $penalties['text_density']['curve'] : [],
            $textDensityRatio
        );
        if (abs($textDensityScore) >= 0.0001) {
            $score += $textDensityScore;
            $signals[] = [
                'type' => $textDensityScore >= 0.0 ? 'positive' : 'negative',
                'code' => 'text_density',
                'score' => $textDensityScore,
                'detail' => 'words:' . (int) ($textDensity['wordCount'] ?? 0) . ',ratio:' . round($textDensityRatio, 3),
            ];
        }
    }

    if (($penalties['page_in_document']['enabled'] ?? true) === true && $candidatePageNumber !== null) {
        $points = interpolate_primary_date_score_curve(
            is_array($penalties['page_in_document']['curve'] ?? null) ? $penalties['page_in_document']['curve'] : [],
            (float) $candidatePageNumber
        );
        if (abs($points) >= 0.0001) {
            $score += $points;
            $signals[] = [
                'type' => $points >= 0.0 ? 'positive' : 'negative',
                'code' => 'page_in_document',
                'score' => $points,
                'detail' => 'page:' . $candidatePageNumber,
            ];
        }
    }

    $fullConfidenceScore = primary_date_full_confidence_score($heuristics);
    $result['score'] = $score;
    $result['rawScore'] = $score;
    $result['fullConfidenceScore'] = $fullConfidenceScore;
    $result['confidence'] = clamp_confidence($score / $fullConfidenceScore);
    $result['signals'] = $signals;
    return $result;
}

function extract_primary_date_field_result(
    array $lines,
    array $lineGeometries = [],
    array $heuristics = [],
    array $replacementMap = [],
    array $positionSettings = []
): array
{
    $heuristics = normalize_primary_date_heuristics($heuristics);
    $candidates = [];
    foreach ($lines as $lineIndex => $line) {
        if (!is_string($line)) {
            continue;
        }
        $trimmedLine = trim($line);
        if ($trimmedLine === '') {
            continue;
        }
        $lineCandidates = primary_date_candidates_from_text($line, 0);
        foreach ($lineCandidates as $candidate) {
            $candidate['lineIndex'] = is_int($lineIndex) ? $lineIndex : 0;
            $candidate['line'] = $line;
            $candidates[] = score_primary_date_candidate(
                $candidate,
                $lines,
                $lineGeometries,
                $heuristics,
                $replacementMap,
                $positionSettings
            );
        }
    }

    usort($candidates, static function (array $a, array $b): int {
        $excludedCompare = ((bool) ($a['excluded'] ?? false)) <=> ((bool) ($b['excluded'] ?? false));
        if ($excludedCompare !== 0) {
            return $excludedCompare;
        }
        $scoreCompare = ((float) ($b['score'] ?? 0.0)) <=> ((float) ($a['score'] ?? 0.0));
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }
        $lineCompare = ((int) ($a['lineIndex'] ?? 0)) <=> ((int) ($b['lineIndex'] ?? 0));
        if ($lineCompare !== 0) {
            return $lineCompare;
        }
        $startCompare = ((int) ($a['start'] ?? 0)) <=> ((int) ($b['start'] ?? 0));
        if ($startCompare !== 0) {
            return $startCompare;
        }
        return strlen((string) ($b['raw'] ?? '')) <=> strlen((string) ($a['raw'] ?? ''));
    });

    $selected = null;
    foreach ($candidates as $candidate) {
        if (($candidate['excluded'] ?? false) === true) {
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
            'source' => 'primary_date_heuristic',
            'raw' => null,
            'selectedValue' => null,
            'selectedCandidate' => null,
            'fullConfidenceScore' => primary_date_full_confidence_score($heuristics),
            'candidates' => $candidates,
        ];
    }

    return [
        'value' => is_string($selected['value'] ?? null) ? (string) $selected['value'] : null,
        'confidence' => isset($selected['confidence']) ? clamp_confidence((float) $selected['confidence']) : 0.0,
        'lineIndex' => is_int($selected['lineIndex'] ?? null) ? (int) $selected['lineIndex'] : null,
        'source' => 'primary_date_heuristic',
        'raw' => is_string($selected['raw'] ?? null) ? (string) $selected['raw'] : null,
        'selectedValue' => is_string($selected['value'] ?? null) ? (string) $selected['value'] : null,
        'selectedCandidate' => $selected,
        'fullConfidenceScore' => primary_date_full_confidence_score($heuristics),
        'candidates' => $candidates,
    ];
}

function title_line_page_dimensions(array $lineGeometries, int $lineIndex): array
{
    $entry = is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
    $pageWidth = is_array($entry) && is_numeric($entry['pageWidth'] ?? null) ? (float) $entry['pageWidth'] : null;
    $pageHeight = is_array($entry) && is_numeric($entry['pageHeight'] ?? null) ? (float) $entry['pageHeight'] : null;
    $pageNumber = matching_line_page_number($lineGeometries, $lineIndex);
    $maxX = null;
    $maxY = null;

    foreach ($lineGeometries as $geometry) {
        if (!is_array($geometry)) {
            continue;
        }
        $geometryPage = is_numeric($geometry['pageNumber'] ?? null) ? (int) $geometry['pageNumber'] : 1;
        if ($pageNumber !== null && $geometryPage !== $pageNumber) {
            continue;
        }
        foreach (is_array($geometry['segments'] ?? null) ? $geometry['segments'] : [] as $segment) {
            if (!is_array($segment)) {
                continue;
            }
            $bbox = normalize_debug_word_bbox($segment['bbox'] ?? null);
            if ($bbox === null) {
                continue;
            }
            $maxX = $maxX === null ? (float) $bbox['x1'] : max($maxX, (float) $bbox['x1']);
            $maxY = $maxY === null ? (float) $bbox['y1'] : max($maxY, (float) $bbox['y1']);
        }
    }

    return [
        'width' => $pageWidth !== null && $pageWidth > 0.0 ? $pageWidth : ($maxX !== null && $maxX > 0.0 ? $maxX : null),
        'height' => $pageHeight !== null && $pageHeight > 0.0 ? $pageHeight : ($maxY !== null && $maxY > 0.0 ? $maxY : null),
    ];
}

function title_page_median_line_height(array $lineGeometries, ?int $pageNumber): ?float
{
    $heights = [];
    foreach ($lineGeometries as $geometry) {
        if (!is_array($geometry)) {
            continue;
        }
        $geometryPage = is_numeric($geometry['pageNumber'] ?? null) ? (int) $geometry['pageNumber'] : 1;
        if ($pageNumber !== null && $geometryPage !== $pageNumber) {
            continue;
        }
        $bbox = line_geometry_span_bbox($geometry, 0, strlen(is_string($geometry['text'] ?? null) ? (string) $geometry['text'] : ''));
        if ($bbox === null) {
            continue;
        }
        $height = (float) ($bbox['y1'] ?? 0.0) - (float) ($bbox['y0'] ?? 0.0);
        if ($height > 0.0) {
            $heights[] = $height;
        }
    }
    if ($heights === []) {
        return null;
    }
    sort($heights, SORT_NUMERIC);
    $middle = intdiv(count($heights), 2);
    if (count($heights) % 2 === 1) {
        return (float) $heights[$middle];
    }
    return ((float) $heights[$middle - 1] + (float) $heights[$middle]) / 2.0;
}

function title_candidate_word_count(string $text): int
{
    return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
}

function title_candidate_uppercase_ratio(string $text): float
{
    $letterCount = count_pattern_matches('/\p{L}/u', $text);
    if ($letterCount <= 0) {
        return 0.0;
    }
    return max(0.0, min(1.0, count_pattern_matches('/\p{Lu}/u', $text) / $letterCount));
}

function title_candidate_text_density(array $lines, int $lineIndex, int $wordCount): array
{
    $surroundingWords = 0;
    $lineCount = 0;
    foreach ([$lineIndex - 1, $lineIndex, $lineIndex + 1] as $index) {
        if (!is_string($lines[$index] ?? null)) {
            continue;
        }
        $line = normalize_inline_whitespace((string) $lines[$index]);
        if ($line === '' || preg_match('/^===\s*PAGE\s+\d+\s*===$/iu', $line) === 1) {
            continue;
        }
        $surroundingWords += title_candidate_word_count($line);
        $lineCount++;
    }
    $contextWords = max(0, $surroundingWords - $wordCount);
    $ratio = ($contextWords / 12.0) + (max(0, $wordCount - 4) / 8.0);
    return [
        'ratio' => max(0.0, min(1.0, $ratio)),
        'wordCount' => $wordCount,
        'surroundingWords' => $surroundingWords,
        'lineCount' => $lineCount,
    ];
}

function title_candidates_from_lines(array $lines, array $lineGeometries = [], array $spanSettings = []): array
{
    $settings = normalize_matching_bbox_span_building_settings($spanSettings);
    $candidates = [];
    foreach ($lines as $lineIndex => $line) {
        if (!is_string($line)) {
            continue;
        }
        $lineText = normalize_inline_whitespace($line);
        if ($lineText === '' || preg_match('/^===\s*PAGE\s+\d+\s*===$/iu', $lineText) === 1) {
            continue;
        }
        $lineGeometry = is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
        $spans = extraction_field_layout_value_spans_for_line($line, $lineGeometry, 0, $settings);
        foreach ($spans as $span) {
            if (!is_array($span)) {
                continue;
            }
            $spanText = is_string($span['text'] ?? null) ? (string) $span['text'] : '';
            $spanStart = is_int($span['start'] ?? null) ? (int) $span['start'] : -1;
            $spanEnd = is_int($span['end'] ?? null) ? (int) $span['end'] : -1;
            if ($spanStart < 0 || $spanEnd <= $spanStart || trim($spanText) === '') {
                continue;
            }
            $leadingWhitespaceLength = strlen($spanText) - strlen(ltrim($spanText));
            $trailingWhitespaceLength = strlen($spanText) - strlen(rtrim($spanText));
            $start = $spanStart + max(0, $leadingWhitespaceLength);
            $end = max($start + 1, $spanEnd - max(0, $trailingWhitespaceLength));
            $raw = substr($line, $start, $end - $start);
            $raw = is_string($raw) ? trim($raw) : trim($spanText);
            $text = normalize_inline_whitespace($raw);
            if ($text === '') {
                continue;
            }
            if (count_pattern_matches('/\p{L}/u', $text) < 2 || title_candidate_word_count($text) > 16) {
                continue;
            }
            if (preg_match('/^\W*[\d\s:.,;\/-]+\W*$/u', $text) === 1) {
                continue;
            }
            $bbox = $lineGeometry !== null
                ? line_geometry_span_bbox($lineGeometry, $start, $end)
                : null;
            $valueBBoxIndexes = $lineGeometry !== null
                ? line_geometry_span_word_bbox_indexes($lineGeometry, $start, $end)
                : [];
            $candidates[] = [
                'value' => $text,
                'raw' => $raw,
                'line' => $line,
                'lineIndex' => is_int($lineIndex) ? $lineIndex : 0,
                'start' => $start,
                'end' => $end,
                'bbox' => $bbox,
                'valueBBoxIndexes' => $valueBBoxIndexes,
                'pageNumber' => is_int($lineIndex) ? matching_line_page_number($lineGeometries, $lineIndex) : null,
            ];
        }
    }
    return $candidates;
}

function score_title_candidate(array $candidate, array $lines, array $lineGeometries = [], array $heuristics = []): array
{
    $heuristics = normalize_title_heuristics($heuristics);
    $signals = is_array($heuristics['signals'] ?? null) ? $heuristics['signals'] : [];
    $lineIndex = is_int($candidate['lineIndex'] ?? null) ? (int) $candidate['lineIndex'] : -1;
    $text = is_string($candidate['value'] ?? null) ? (string) $candidate['value'] : '';
    $bbox = is_array($candidate['bbox'] ?? null) ? normalize_debug_word_bbox($candidate['bbox']) : null;
    $pageNumber = $lineIndex >= 0 ? matching_line_page_number($lineGeometries, $lineIndex) : null;
    $dimensions = $lineIndex >= 0 ? title_line_page_dimensions($lineGeometries, $lineIndex) : ['width' => null, 'height' => null];
    $fullConfidenceScore = title_full_confidence_score($heuristics);
    $result = $candidate;
    $result['signals'] = [];
    $result['excluded'] = false;
    $result['excludedReason'] = null;
    $result['score'] = 0.0;
    $result['rawScore'] = 0.0;
    $result['confidence'] = 0.0;
    $result['fullConfidenceScore'] = $fullConfidenceScore;
    $result['pageNumber'] = $pageNumber;
    $result['bbox'] = $bbox;

    if ($lineIndex < 0 || $text === '') {
        return $result;
    }

    $score = 0.0;
    $addSignal = static function (string $code, float $x, string $detail) use (&$score, &$result, $signals): void {
        if (($signals[$code]['enabled'] ?? true) !== true) {
            return;
        }
        $points = interpolate_primary_date_score_curve(
            is_array($signals[$code]['curve'] ?? null) ? $signals[$code]['curve'] : [],
            $x
        );
        if (abs($points) < 0.0001) {
            return;
        }
        $score += $points;
        $result['signals'][] = [
            'type' => $points >= 0.0 ? 'positive' : 'negative',
            'code' => $code,
            'score' => $points,
            'detail' => $detail,
        ];
    };

    if ($bbox !== null) {
        $center = bbox_center_point($bbox);
        $pageHeight = is_numeric($dimensions['height'] ?? null) ? (float) $dimensions['height'] : null;
        if ($pageHeight !== null && $pageHeight > 0.0) {
            $yRatio = max(0.0, min(1.0, ((float) $center['y']) / $pageHeight));
            $result['yRatio'] = $yRatio;
            $addSignal('vertical_position', $yRatio, 'y_ratio:' . round($yRatio, 3));
        }

        $pageWidth = is_numeric($dimensions['width'] ?? null) ? (float) $dimensions['width'] : null;
        if ($pageWidth !== null && $pageWidth > 0.0) {
            $centerDistance = max(0.0, min(1.0, abs(((float) $center['x']) - ($pageWidth / 2.0)) / max(1.0, $pageWidth / 2.0)));
            $result['centerDistance'] = $centerDistance;
            $addSignal('horizontal_position', $centerDistance, 'center_distance:' . round($centerDistance, 3));
        }

        $height = (float) ($bbox['y1'] ?? 0.0) - (float) ($bbox['y0'] ?? 0.0);
        $medianHeight = title_page_median_line_height($lineGeometries, $pageNumber);
        if ($height > 0.0 && $medianHeight !== null && $medianHeight > 0.0) {
            $relativeSize = $height / $medianHeight;
            $result['relativeTextSize'] = $relativeSize;
            $addSignal('text_size', $relativeSize, 'relative_size:' . round($relativeSize, 3));
        }
    }

    $uppercaseRatio = title_candidate_uppercase_ratio($text);
    $result['uppercaseRatio'] = $uppercaseRatio;
    $addSignal('uppercase_ratio', $uppercaseRatio, 'uppercase_ratio:' . round($uppercaseRatio, 3));

    $wordCount = title_candidate_word_count($text);
    $result['wordCount'] = $wordCount;
    $addSignal('brevity', (float) $wordCount, 'words:' . $wordCount);

    $density = title_candidate_text_density($lines, $lineIndex, $wordCount);
    $densityRatio = (float) ($density['ratio'] ?? 0.0);
    $result['textDensityRatio'] = $densityRatio;
    $addSignal(
        'text_density',
        $densityRatio,
        'words:' . (int) ($density['surroundingWords'] ?? $wordCount) . ',ratio:' . round($densityRatio, 3)
    );

    $result['score'] = $score;
    $result['rawScore'] = $score;
    $result['confidence'] = clamp_confidence($score / $fullConfidenceScore);
    return $result;
}

function extract_title_field_result(array $lines, array $lineGeometries = [], array $heuristics = [], array $positionSettings = []): array
{
    $heuristics = normalize_title_heuristics($heuristics);
    $spanSettings = matching_bbox_span_building_from_position_settings($positionSettings);
    $candidates = array_map(
        static fn(array $candidate): array => score_title_candidate($candidate, $lines, $lineGeometries, $heuristics),
        title_candidates_from_lines($lines, $lineGeometries, $spanSettings)
    );
    $candidates = array_values(array_filter(
        $candidates,
        static fn(array $candidate): bool => (float) ($candidate['score'] ?? 0.0) > 0.0
    ));

    usort($candidates, static function (array $a, array $b): int {
        $excludedCompare = ((bool) ($a['excluded'] ?? false)) <=> ((bool) ($b['excluded'] ?? false));
        if ($excludedCompare !== 0) {
            return $excludedCompare;
        }
        $scoreCompare = ((float) ($b['score'] ?? 0.0)) <=> ((float) ($a['score'] ?? 0.0));
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }
        return ((int) ($a['lineIndex'] ?? 0)) <=> ((int) ($b['lineIndex'] ?? 0));
    });

    $selected = null;
    foreach ($candidates as $candidate) {
        if (($candidate['excluded'] ?? false) === true) {
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
            'source' => 'title_heuristic',
            'raw' => null,
            'selectedValue' => null,
            'selectedCandidate' => null,
            'fullConfidenceScore' => title_full_confidence_score($heuristics),
            'candidates' => $candidates,
        ];
    }

    return [
        'value' => is_string($selected['value'] ?? null) ? (string) $selected['value'] : null,
        'confidence' => isset($selected['confidence']) ? clamp_confidence((float) $selected['confidence']) : 0.0,
        'lineIndex' => is_int($selected['lineIndex'] ?? null) ? (int) $selected['lineIndex'] : null,
        'source' => 'title_heuristic',
        'raw' => is_string($selected['raw'] ?? null) ? (string) $selected['raw'] : null,
        'selectedValue' => is_string($selected['value'] ?? null) ? (string) $selected['value'] : null,
        'selectedCandidate' => $selected,
        'fullConfidenceScore' => title_full_confidence_score($heuristics),
        'candidates' => $candidates,
    ];
}

function amount_candidates_from_text(string $text, int $offsetBase = 0): array
{
    $matches = [];
    if (@preg_match_all('/\b((?:\d{1,3}(?:[\s\x{00A0}.]+\d{3})+|\d+),\d{2}|(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2}|\d{1,3}(?:[\s\x{00A0}.]+\d{3})+)\b/u', $text, $matches, PREG_OFFSET_CAPTURE) < 1) {
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
            'matchType' => 'amount',
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

function normalize_extraction_field_regex_pattern(string $pattern): string
{
    $normalized = preg_replace_callback(
        '/\\\\u([0-9A-Fa-f]{4})/',
        static fn (array $matches): string => '\\x{' . strtoupper((string) ($matches[1] ?? '')) . '}',
        $pattern
    );

    return is_string($normalized) ? $normalized : $pattern;
}

function extraction_field_pattern_candidates_from_text(
    string $text,
    string $searchString,
    bool $isRegex,
    int $offsetBase = 0,
    bool $preferCaptureGroupValue = true,
    bool $combineSplitAmountParts = false,
    array $captureSelection = []
): array
{
    $pattern = trim($searchString);
    if ($pattern === '') {
        return generic_text_segment_candidates_from_text($text, $offsetBase);
    }

    $pattern = normalize_extraction_field_regex_pattern($pattern);

    $delimitedPattern = $isRegex
        ? delimit_ocr_text_regex(regex_pattern_with_whitespace_wildcards($pattern), '~')
        : delimit_ocr_text_regex(literal_pattern_with_whitespace_wildcards($pattern, '~'), '~', 'iu', false);

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

        $captureRanges = [];
        $value = $raw;
        $extractedRaw = $raw;
        $valueStart = $start;
        $hasExplicitCaptureSelection = false;
        if ($isRegex && normalize_extraction_field_capture_group($captureSelection['captureGroup'] ?? null) !== null) {
            $hasExplicitCaptureSelection = true;
            $selectedCapture = extraction_field_match_selected_capture_value($match, $captureSelection);
            if ($selectedCapture === null) {
                continue;
            }
            $value = $selectedCapture['value'];
            $extractedRaw = is_string($selectedCapture['raw'] ?? null) ? (string) $selectedCapture['raw'] : (string) $value;
            $valueStart = is_int($selectedCapture['start'] ?? null) ? (int) $selectedCapture['start'] : $start;
            $captureRanges = is_array($selectedCapture['ranges'] ?? null) ? $selectedCapture['ranges'] : [];
        } elseif (
            $isRegex
            && (
                normalize_extraction_field_capture_group($captureSelection['amountWholeGroup'] ?? null) !== null
                || normalize_extraction_field_capture_group($captureSelection['amountFractionGroup'] ?? null) !== null
            )
        ) {
            $hasExplicitCaptureSelection = true;
            $selectedAmount = extraction_field_match_selected_amount_value($match, $captureSelection);
            if ($selectedAmount === null) {
                continue;
            }
            $value = $selectedAmount['value'];
            $extractedRaw = is_string($selectedAmount['raw'] ?? null) ? (string) $selectedAmount['raw'] : (string) $value;
            $valueStart = is_int($selectedAmount['start'] ?? null) ? (int) $selectedAmount['start'] : $start;
            $captureRanges = is_array($selectedAmount['ranges'] ?? null) ? $selectedAmount['ranges'] : [];
        } elseif ($isRegex && $combineSplitAmountParts) {
            $semanticAmountSpan = extraction_field_semantic_amount_value_span($match, $text);
            if ($semanticAmountSpan !== null) {
                $value = is_string($semanticAmountSpan['value'] ?? null) ? (string) $semanticAmountSpan['value'] : null;
                $extractedRaw = is_string($semanticAmountSpan['raw'] ?? null) ? (string) $semanticAmountSpan['raw'] : '';
                $valueStart = is_int($semanticAmountSpan['start'] ?? null) ? (int) $semanticAmountSpan['start'] : -1;
                if ($value === null || $extractedRaw === '' || $valueStart < 0) {
                    continue;
                }
                $candidates[] = [
                    'value' => $value,
                    'raw' => $extractedRaw,
                    'matchText' => $raw,
                    'matchStart' => $offsetBase + $start,
                    'start' => $offsetBase + $valueStart,
                    'spanText' => $extractedRaw,
                    'matchType' => 'pattern',
                    'captureRanges' => $captureRanges,
                ];
                continue;
            }
            if (extraction_field_match_has_semantic_amount_groups($match)) {
                continue;
            }
        }
        $candidates[] = [
            'value' => $value,
            'raw' => $extractedRaw,
            'matchText' => $raw,
            'matchStart' => $offsetBase + $start,
            'start' => $offsetBase + $valueStart,
            'spanText' => $hasExplicitCaptureSelection ? $extractedRaw : $raw,
            'matchType' => 'pattern',
            'captureRanges' => $captureRanges,
        ];
    }

    return $candidates;
}

function extraction_field_document_line_entries(array $lines): array
{
    $entries = [];
    $offset = 0;
    foreach ($lines as $lineIndex => $line) {
        if (!is_int($lineIndex)) {
            continue;
        }

        $text = is_string($line) ? (string) $line : '';
        $length = strlen($text);
        $entries[$lineIndex] = [
            'lineIndex' => $lineIndex,
            'start' => $offset,
            'length' => $length,
            'text' => $text,
        ];
        $offset += $length + 1;
    }

    return $entries;
}

function extraction_field_document_line_index_for_offset(array $lineEntries, int $offset): ?int
{
    $resolvedLineIndex = null;
    foreach ($lineEntries as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $start = is_int($entry['start'] ?? null) ? (int) $entry['start'] : -1;
        if ($start > $offset) {
            break;
        }

        $resolvedLineIndex = is_int($entry['lineIndex'] ?? null) ? (int) $entry['lineIndex'] : null;
    }

    return $resolvedLineIndex;
}

function extraction_field_document_span_bbox(array $lineEntries, array $lineGeometries, int $start, int $end): ?array
{
    if ($start < 0 || $end <= $start) {
        return null;
    }

    $bbox = null;
    foreach ($lineEntries as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $lineIndex = is_int($entry['lineIndex'] ?? null) ? (int) $entry['lineIndex'] : null;
        $lineStart = is_int($entry['start'] ?? null) ? (int) $entry['start'] : -1;
        $lineLength = is_int($entry['length'] ?? null) ? (int) $entry['length'] : -1;
        if ($lineIndex === null || $lineStart < 0 || $lineLength < 0) {
            continue;
        }

        $lineEnd = $lineStart + $lineLength;
        if ($lineEnd <= $start || $lineStart >= $end) {
            continue;
        }

        $lineGeometry = is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
        if ($lineGeometry === null) {
            continue;
        }

        $localStart = max(0, $start - $lineStart);
        $localEnd = min($lineLength, $end - $lineStart);
        if ($localEnd <= $localStart) {
            continue;
        }

        $lineBBox = line_geometry_span_bbox($lineGeometry, $localStart, $localEnd);
        if ($lineBBox === null) {
            continue;
        }

        if ($bbox === null) {
            $bbox = $lineBBox;
            continue;
        }

        $bbox = [
            'x0' => min($bbox['x0'], $lineBBox['x0']),
            'y0' => min($bbox['y0'], $lineBBox['y0']),
            'x1' => max($bbox['x1'], $lineBBox['x1']),
            'y1' => max($bbox['y1'], $lineBBox['y1']),
        ];
    }

    return $bbox;
}

function line_geometry_segment_height(array $segment): float
{
    $bbox = normalize_debug_word_bbox($segment['bbox'] ?? null);
    if ($bbox === null) {
        return 0.0;
    }
    return max(0.0, (float) ($bbox['y1'] ?? 0.0) - (float) ($bbox['y0'] ?? 0.0));
}

function line_geometry_segment_center_y(array $segment): ?float
{
    $bbox = normalize_debug_word_bbox($segment['bbox'] ?? null);
    if ($bbox === null) {
        return null;
    }
    return ((float) ($bbox['y0'] ?? 0.0) + (float) ($bbox['y1'] ?? 0.0)) / 2.0;
}

function line_geometry_segments_can_share_value_span(array $left, array $right, array $spanSettings): bool
{
    $leftBbox = normalize_debug_word_bbox($left['bbox'] ?? null);
    $rightBbox = normalize_debug_word_bbox($right['bbox'] ?? null);
    if ($leftBbox === null || $rightBbox === null) {
        return false;
    }

    $leftHeight = line_geometry_segment_height($left);
    $rightHeight = line_geometry_segment_height($right);
    $averageHeight = max(1.0, (($leftHeight > 0.0 ? $leftHeight : 1.0) + ($rightHeight > 0.0 ? $rightHeight : 1.0)) / 2.0);
    $lineHeightDifference = abs($leftHeight - $rightHeight);
    $horizontalGap = max(0.0, (float) ($rightBbox['x0'] ?? 0.0) - (float) ($leftBbox['x1'] ?? 0.0));
    $leftCenterY = line_geometry_segment_center_y($left);
    $rightCenterY = line_geometry_segment_center_y($right);
    $verticalOffset = $leftCenterY !== null && $rightCenterY !== null ? abs($leftCenterY - $rightCenterY) : 0.0;

    $maxHorizontalGap = $averageHeight * max(0.0, (float) ($spanSettings['maxHorizontalGapMultiplier'] ?? 2.5));
    $maxVerticalOffset = $averageHeight * max(0.0, (float) ($spanSettings['maxVerticalOffsetMultiplier'] ?? 0.4));
    $maxLineHeightDifference = $averageHeight * max(0.0, (float) ($spanSettings['maxLineHeightDifferenceMultiplier'] ?? 0.5));

    return $horizontalGap <= $maxHorizontalGap
        && $verticalOffset <= $maxVerticalOffset
        && $lineHeightDifference <= $maxLineHeightDifference;
}

function extraction_field_layout_value_spans_for_line(
    string $line,
    ?array $lineGeometry,
    int $minStart = 0,
    array $spanSettings = []
): array {
    $minStart = max(0, $minStart);
    $settings = normalize_matching_bbox_span_building_settings($spanSettings);
    $lineLength = strlen($line);
    if ($lineLength <= $minStart) {
        return [];
    }

    $segments = is_array($lineGeometry['segments'] ?? null) ? $lineGeometry['segments'] : [];
    $eligible = [];
    foreach ($segments as $segment) {
        if (!is_array($segment)) {
            continue;
        }
        $start = is_int($segment['start'] ?? null) ? (int) $segment['start'] : -1;
        $end = is_int($segment['end'] ?? null) ? (int) $segment['end'] : -1;
        $text = is_string($segment['text'] ?? null) ? (string) $segment['text'] : '';
        if ($start < 0 || $end <= $start || $end <= $minStart || trim($text) === '') {
            continue;
        }
        if (normalize_debug_word_bbox($segment['bbox'] ?? null) === null) {
            continue;
        }
        $eligible[] = [
            ...$segment,
            'start' => $start,
            'end' => $end,
        ];
    }

    usort($eligible, static fn(array $left, array $right): int => ((int) ($left['start'] ?? 0)) <=> ((int) ($right['start'] ?? 0)));

    if ($eligible === []) {
        $fallbackText = substr($line, $minStart);
        return trim($fallbackText) === '' ? [] : [[
            'text' => $fallbackText,
            'start' => $minStart,
            'end' => $lineLength,
        ]];
    }

    $spans = [];
    $groupStart = null;
    $groupEnd = null;
    $previous = null;
    foreach ($eligible as $segment) {
        if ($previous !== null && !line_geometry_segments_can_share_value_span($previous, $segment, $settings)) {
            if ($groupStart !== null && $groupEnd !== null && $groupEnd > $groupStart) {
                $text = substr($line, $groupStart, $groupEnd - $groupStart);
                if (trim($text) !== '') {
                    $spans[] = [
                        'text' => $text,
                        'start' => $groupStart,
                        'end' => $groupEnd,
                    ];
                }
            }
            $groupStart = null;
            $groupEnd = null;
        }

        $segmentStart = max($minStart, (int) ($segment['start'] ?? 0));
        $segmentEnd = (int) ($segment['end'] ?? 0);
        if ($groupStart === null) {
            $groupStart = $segmentStart;
        }
        $groupEnd = max($groupEnd ?? $segmentEnd, $segmentEnd);
        $previous = $segment;
    }

    if ($groupStart !== null && $groupEnd !== null && $groupEnd > $groupStart) {
        $text = substr($line, $groupStart, $groupEnd - $groupStart);
        if (trim($text) !== '') {
            $spans[] = [
                'text' => $text,
                'start' => $groupStart,
                'end' => $groupEnd,
            ];
        }
    }

    return array_values($spans);
}

function extraction_field_candidates_from_layout_spans(
    string $line,
    ?array $lineGeometry,
    callable $candidateExtractor,
    int $minStart = 0,
    array $spanSettings = []
): array {
    $spans = extraction_field_layout_value_spans_for_line($line, $lineGeometry, $minStart, $spanSettings);
    if ($spans === []) {
        return [];
    }

    $candidates = [];
    foreach ($spans as $span) {
        if (!is_array($span)) {
            continue;
        }
        $spanText = is_string($span['text'] ?? null) ? (string) $span['text'] : '';
        $spanStart = is_int($span['start'] ?? null) ? (int) $span['start'] : -1;
        if ($spanStart < 0 || trim($spanText) === '') {
            continue;
        }
        $spanCandidates = $candidateExtractor($spanText, $spanStart);
        if (!is_array($spanCandidates)) {
            continue;
        }
        foreach ($spanCandidates as $candidate) {
            if (is_array($candidate)) {
                $candidates[] = $candidate;
            }
        }
    }

    return $candidates;
}

function extraction_field_document_pattern_candidates_from_lines(
    array $lines,
    string $searchString,
    bool $isRegex,
    array $lineGeometries = [],
    bool $preferCaptureGroupValue = true,
    bool $combineSplitAmountParts = false,
    array $captureSelection = []
): array
{
    $resolvedPattern = trim($searchString);
    if ($resolvedPattern === '') {
        return [];
    }

    $lineEntries = extraction_field_document_line_entries($lines);
    if ($lineEntries === []) {
        return [];
    }

    $documentText = '';
    foreach ($lineEntries as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $documentText .= is_string($entry['text'] ?? null) ? (string) $entry['text'] : '';
        $documentText .= "\n";
    }

    $matches = extraction_field_pattern_candidates_from_text(
        $documentText,
        $resolvedPattern,
        $isRegex,
        0,
        $preferCaptureGroupValue,
        $combineSplitAmountParts,
        $captureSelection
    );
    if ($matches === []) {
        return [];
    }

    $candidates = [];
    foreach ($matches as $candidate) {
        if (!is_array($candidate)) {
            continue;
        }

        $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null;
        $matchText = is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw;
        $spanText = is_string($candidate['spanText'] ?? null) ? (string) $candidate['spanText'] : null;
        $value = $candidate['value'] ?? null;
        $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
        if ($value === null || $start < 0) {
            continue;
        }

        $rawLength = is_string($spanText) && $spanText !== ''
            ? strlen($spanText)
            : (is_string($raw) ? strlen($raw) : (is_string($matchText) ? strlen($matchText) : (is_scalar($value) ? strlen((string) $value) : 1)));
        $end = $start + max(1, $rawLength);
        $lineIndex = extraction_field_document_line_index_for_offset($lineEntries, $start);
        if ($lineIndex === null) {
            continue;
        }

        $lineEntry = is_array($lineEntries[$lineIndex] ?? null) ? $lineEntries[$lineIndex] : null;
        $lineStart = is_array($lineEntry) && is_int($lineEntry['start'] ?? null) ? (int) $lineEntry['start'] : -1;
        $lineLength = is_array($lineEntry) && is_int($lineEntry['length'] ?? null) ? (int) $lineEntry['length'] : -1;
        $candidateStart = $lineStart >= 0 ? max(0, $start - $lineStart) : 0;
        $candidatePageNumber = matching_line_page_number($lineGeometries, $lineIndex);
        $candidateBbox = extraction_field_document_span_bbox($lineEntries, $lineGeometries, $start, $end);

        $candidates[] = [
            'value' => $value,
            'raw' => $raw,
            'matchText' => $matchText,
            'start' => $candidateStart,
            'spanText' => $spanText,
            'matchType' => is_string($candidate['matchType'] ?? null) ? (string) $candidate['matchType'] : 'pattern',
            'captureRanges' => is_array($candidate['captureRanges'] ?? null) ? $candidate['captureRanges'] : [],
            'lineIndex' => $lineIndex,
            'labelBbox' => null,
            'valueBbox' => $candidateBbox,
            'pageNumber' => $candidatePageNumber,
        ];
    }

    return $candidates;
}

function apply_extraction_field_normalization(mixed $value, array $ruleSet): mixed
{
    if (!is_string($value)) {
        return $value;
    }

    $normalizedType = normalize_extraction_field_normalization_type($ruleSet['normalizationType'] ?? null);
    if ($normalizedType === 'none') {
        return $value;
    }

    if ($normalizedType === 'replacements') {
        return apply_extraction_field_replacements(
            $value,
            normalize_extraction_field_normalization_replacements($ruleSet['normalizationReplacements'] ?? null)
        );
    }

    $chars = is_string($ruleSet['normalizationChars'] ?? null) ? (string) $ruleSet['normalizationChars'] : '';
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

function apply_extraction_field_replacements(string $value, array $replacements): string
{
    $result = $value;
    foreach ($replacements as $replacement) {
        if (!is_array($replacement)) {
            continue;
        }

        $find = is_string($replacement['find'] ?? null) ? trim((string) $replacement['find']) : '';
        if ($find === '') {
            continue;
        }
        $replace = is_string($replacement['replace'] ?? null) ? (string) $replacement['replace'] : '';
        $isRegex = normalize_extraction_field_is_regex($replacement['isRegex'] ?? false);

        if ($isRegex) {
            $pattern = normalize_extraction_field_regex_pattern($find);
            $delimitedPattern = '~' . str_replace('~', '\~', $pattern) . '~u';
            $next = @preg_replace($delimitedPattern, $replace, $result);
            if (is_string($next)) {
                $result = $next;
            }
            continue;
        }

        $result = str_replace($find, $replace, $result);
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
    $candidates = primary_date_candidates_from_text($text, 0);
    foreach ($candidates as $candidate) {
        $value = is_string($candidate['value'] ?? null) ? trim((string) $candidate['value']) : '';
        if (@preg_match('/^20\d{2}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }
    }
    foreach ($candidates as $candidate) {
        $value = is_string($candidate['value'] ?? null) ? trim((string) $candidate['value']) : '';
        if ($value !== '') {
            return $value;
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

    $clean = preg_replace('/[^\d,.\s\-]/u', '', $clean);
    if (!is_string($clean) || trim($clean) === '') {
        return null;
    }

    $clean = preg_replace('/\s+/u', '', $clean);
    if (!is_string($clean) || $clean === '') {
        return null;
    }

    $lastComma = strrpos($clean, ',');
    $lastDot = strrpos($clean, '.');
    if ($lastComma !== false && $lastDot !== false) {
        if ($lastComma > $lastDot) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }
    } elseif ($lastComma !== false) {
        if (@preg_match('/,\d{2}$/', $clean) === 1) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }
    } elseif ($lastDot !== false) {
        if (@preg_match('/\.\d{2}$/', $clean) !== 1) {
            $clean = str_replace('.', '', $clean);
        }
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
    if (@preg_match_all('/\b(?:\d{1,3}(?:[ \x{00A0}.]\d{3})+|\d+),\d{2}\b|\b(?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2}\b/u', $text, $matches) < 1) {
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
    ?array $noiseSegments = null,
    ?float $trailingDelimiterPenalty = null,
    ?float $otherMatchKeyPenalty = null,
    ?float $baseConfidence = null,
    ?float $finalConfidence = null,
    ?float $verticalDistancePenalty = null,
    ?float $verticalDistance = null,
    ?float $verticalNormalizedDistance = null,
    ?string $invalidReason = null,
    ?array $labelBbox = null,
    ?array $valueBbox = null,
    ?int $pageNumber = null,
    ?array $captureRanges = null
): void {
    if ($lineIndex < 0 || $start < 0 || $value === null) {
        return;
    }

    $key = extraction_field_match_storage_key($lineIndex, $start, $value, $raw);
    $resolvedBaseConfidence = clamp_confidence(is_numeric($baseConfidence) ? (float) $baseConfidence : $confidence);
    $resolvedFinalConfidence = clamp_confidence(is_numeric($finalConfidence) ? (float) $finalConfidence : $resolvedBaseConfidence);
    $candidate = [
        'value' => $value,
        'baseConfidence' => $resolvedBaseConfidence,
        'finalConfidence' => $resolvedFinalConfidence,
        'confidence' => $resolvedFinalConfidence,
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
    if (is_numeric($trailingDelimiterPenalty)) {
        $candidate['trailingDelimiterPenalty'] = max(0.0, (float) $trailingDelimiterPenalty);
    }
    if (is_numeric($positionPenalty)) {
        $candidate['positionPenalty'] = max(0.0, (float) $positionPenalty);
    }
    if (is_numeric($verticalDistancePenalty)) {
        $candidate['verticalDistancePenalty'] = clamp_confidence((float) $verticalDistancePenalty);
    }
    if (is_numeric($verticalDistance)) {
        $candidate['verticalDistance'] = max(0.0, (float) $verticalDistance);
    }
    if (is_numeric($verticalNormalizedDistance)) {
        $candidate['verticalNormalizedDistance'] = max(0.0, (float) $verticalNormalizedDistance);
    }
    if (is_string($invalidReason) && trim($invalidReason) !== '') {
        $candidate['invalidReason'] = trim($invalidReason);
    } elseif (is_string($positionPenaltyAxis) && trim($positionPenaltyAxis) === 'invalid_bbox') {
        $candidate['invalidReason'] = 'Ogiltig bbox';
    }
    if (is_array($labelBbox)) {
        $candidate['labelBbox'] = $labelBbox;
    }
    if (is_array($valueBbox)) {
        $candidate['valueBbox'] = $valueBbox;
    }
    if (is_int($pageNumber) && $pageNumber > 0) {
        $candidate['pageNumber'] = $pageNumber;
    }
    if (is_array($captureRanges)) {
        $normalizedCaptureRanges = array_values(array_filter(array_map(
            static function ($range): ?array {
                if (!is_array($range)) {
                    return null;
                }
                $start = is_int($range['start'] ?? null) ? (int) $range['start'] : null;
                $end = is_int($range['end'] ?? null) ? (int) $range['end'] : null;
                if ($start === null || $end === null || $start < 0 || $end <= $start) {
                    return null;
                }
                return [
                    'start' => $start,
                    'end' => $end,
                ];
            },
            $captureRanges
        ), static fn ($range): bool => is_array($range)));
        if ($normalizedCaptureRanges !== []) {
            $candidate['captureRanges'] = $normalizedCaptureRanges;
        }
    }
    if (is_numeric($otherMatchKeyPenalty)) {
        $candidate['otherMatchKeyPenalty'] = max(0.0, (float) $otherMatchKeyPenalty);
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

    $storedConfidence = isset($matchesByKey[$key]['finalConfidence']) && is_numeric($matchesByKey[$key]['finalConfidence'])
        ? (float) $matchesByKey[$key]['finalConfidence']
        : (float) ($matchesByKey[$key]['confidence'] ?? 0.0);
    if (!isset($matchesByKey[$key]) || $storedConfidence < $candidate['finalConfidence']) {
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

        $leftConfidence = isset($left['finalConfidence']) && is_numeric($left['finalConfidence'])
            ? (float) $left['finalConfidence']
            : (float) ($left['confidence'] ?? ($left['baseConfidence'] ?? 0.0));
        $rightConfidence = isset($right['finalConfidence']) && is_numeric($right['finalConfidence'])
            ? (float) $right['finalConfidence']
            : (float) ($right['confidence'] ?? ($right['baseConfidence'] ?? 0.0));
        $confidenceCompare = $rightConfidence <=> $leftConfidence;
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
    bool $labelsAreRegex = false,
    array $spanSettings = []
): array
{
    $matchesByKey = [];

    $hits = find_all_label_hits($lines, $labels, $replacementMap, $labelsAreRegex, $lineGeometries, $spanSettings);
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
            $lineGeometry = is_array($lineGeometries[$hitIndex] ?? null) ? $lineGeometries[$hitIndex] : null;
            $tailCandidates = $lineGeometry !== null
                ? extraction_field_candidates_from_layout_spans($line, $lineGeometry, $candidateExtractor, $labelEnd, $spanSettings)
                : $candidateExtractor($tailText, $tailOffset);
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
                    $matchContext = extraction_field_candidate_match_context($candidate, $start, $spanText);
                    $confidenceComponents = candidate_confidence_components(
                        $hit,
                        $line,
                        $start,
                        $hitIndex,
                        'tail',
                        $positionSettings,
                        $lineGeometries,
                        $spanText,
                        is_int($matchContext['start'] ?? null) ? (int) $matchContext['start'] : null,
                        is_string($matchContext['spanText'] ?? null) ? (string) $matchContext['spanText'] : null
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
                        is_array($confidenceComponents['noiseSegments'] ?? null) ? $confidenceComponents['noiseSegments'] : null,
                        is_numeric($confidenceComponents['trailingDelimiterPenalty'] ?? null) ? (float) $confidenceComponents['trailingDelimiterPenalty'] : null,
                        null,
                        null,
                        null,
                        is_numeric($confidenceComponents['verticalDistancePenalty'] ?? null) ? (float) $confidenceComponents['verticalDistancePenalty'] : null,
                        is_numeric($confidenceComponents['verticalDistance'] ?? null) ? (float) $confidenceComponents['verticalDistance'] : null,
                        is_numeric($confidenceComponents['verticalNormalizedDistance'] ?? null) ? (float) $confidenceComponents['verticalNormalizedDistance'] : null,
                        is_string($confidenceComponents['invalidReason'] ?? null) ? (string) $confidenceComponents['invalidReason'] : null,
                        is_array($confidenceComponents['labelBbox'] ?? null) ? $confidenceComponents['labelBbox'] : null,
                        is_array($confidenceComponents['valueBbox'] ?? null) ? $confidenceComponents['valueBbox'] : null,
                        is_numeric($confidenceComponents['pageNumber'] ?? null) ? (int) $confidenceComponents['pageNumber'] : null
                    );
                }
            }
        }

        $lineGeometry = is_array($lineGeometries[$hitIndex] ?? null) ? $lineGeometries[$hitIndex] : null;
        $lineCandidates = $lineGeometry !== null
            ? extraction_field_candidates_from_layout_spans($line, $lineGeometry, $candidateExtractor, $labelEnd, $spanSettings)
            : $candidateExtractor($line, 0);
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
                $matchContext = extraction_field_candidate_match_context($candidate, $start, $spanText);
                $confidenceComponents = candidate_confidence_components(
                    $hit,
                    $line,
                    $start,
                    $hitIndex,
                    'line',
                    $positionSettings,
                    $lineGeometries,
                    $spanText,
                    is_int($matchContext['start'] ?? null) ? (int) $matchContext['start'] : null,
                    is_string($matchContext['spanText'] ?? null) ? (string) $matchContext['spanText'] : null
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
                    is_array($confidenceComponents['noiseSegments'] ?? null) ? $confidenceComponents['noiseSegments'] : null,
                    is_numeric($confidenceComponents['trailingDelimiterPenalty'] ?? null) ? (float) $confidenceComponents['trailingDelimiterPenalty'] : null,
                    null,
                    null,
                    null,
                    is_numeric($confidenceComponents['verticalDistancePenalty'] ?? null) ? (float) $confidenceComponents['verticalDistancePenalty'] : null,
                    is_numeric($confidenceComponents['verticalDistance'] ?? null) ? (float) $confidenceComponents['verticalDistance'] : null,
                    is_numeric($confidenceComponents['verticalNormalizedDistance'] ?? null) ? (float) $confidenceComponents['verticalNormalizedDistance'] : null,
                    is_string($confidenceComponents['invalidReason'] ?? null) ? (string) $confidenceComponents['invalidReason'] : null,
                    is_array($confidenceComponents['labelBbox'] ?? null) ? $confidenceComponents['labelBbox'] : null,
                    is_array($confidenceComponents['valueBbox'] ?? null) ? $confidenceComponents['valueBbox'] : null,
                    is_numeric($confidenceComponents['pageNumber'] ?? null) ? (int) $confidenceComponents['pageNumber'] : null
                );
            }
        }

        $nearIndexes = nearby_line_indexes($lines, $hitIndex, $nearbyDistance);
        foreach ($nearIndexes as $nearIndex) {
            $nearLine = is_string($lines[$nearIndex] ?? null) ? (string) $lines[$nearIndex] : '';
            if ($nearLine === '') {
                continue;
            }

            $nearGeometry = is_array($lineGeometries[$nearIndex] ?? null) ? $lineGeometries[$nearIndex] : null;
            $nearCandidates = $nearGeometry !== null
                ? extraction_field_candidates_from_layout_spans($nearLine, $nearGeometry, $candidateExtractor, 0, $spanSettings)
                : $candidateExtractor($nearLine, 0);
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
                $matchContext = extraction_field_candidate_match_context($candidate, $start, $spanText);
                $confidenceComponents = candidate_confidence_components(
                    $hit,
                    $nearLine,
                    $start,
                    (int) $nearIndex,
                    'nearby',
                    $positionSettings,
                    $lineGeometries,
                    $spanText,
                    is_int($matchContext['start'] ?? null) ? (int) $matchContext['start'] : null,
                    is_string($matchContext['spanText'] ?? null) ? (string) $matchContext['spanText'] : null
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
                    is_array($confidenceComponents['noiseSegments'] ?? null) ? $confidenceComponents['noiseSegments'] : null,
                    is_numeric($confidenceComponents['trailingDelimiterPenalty'] ?? null) ? (float) $confidenceComponents['trailingDelimiterPenalty'] : null,
                    null,
                    null,
                    null,
                    is_numeric($confidenceComponents['verticalDistancePenalty'] ?? null) ? (float) $confidenceComponents['verticalDistancePenalty'] : null,
                    is_numeric($confidenceComponents['verticalDistance'] ?? null) ? (float) $confidenceComponents['verticalDistance'] : null,
                    is_numeric($confidenceComponents['verticalNormalizedDistance'] ?? null) ? (float) $confidenceComponents['verticalNormalizedDistance'] : null,
                    is_string($confidenceComponents['invalidReason'] ?? null) ? (string) $confidenceComponents['invalidReason'] : null,
                    is_array($confidenceComponents['labelBbox'] ?? null) ? $confidenceComponents['labelBbox'] : null,
                    is_array($confidenceComponents['valueBbox'] ?? null) ? $confidenceComponents['valueBbox'] : null,
                    is_numeric($confidenceComponents['pageNumber'] ?? null) ? (int) $confidenceComponents['pageNumber'] : null
                );
            }
        }
    }

    return sort_extraction_field_matches(array_values($matchesByKey));
}

function matching_line_page_number(array $lineGeometries, int $lineIndex): ?int
{
    $lineGeometry = is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
    if ($lineGeometry === null || !is_numeric($lineGeometry['pageNumber'] ?? null)) {
        return null;
    }

    $pageNumber = (int) $lineGeometry['pageNumber'];
    return $pageNumber > 0 ? $pageNumber : null;
}

function anchored_candidate_line_is_in_scope(array $lineGeometries, int $hitLineIndex, int $candidateLineIndex): bool
{
    $hitPageNumber = matching_line_page_number($lineGeometries, $hitLineIndex);
    $candidatePageNumber = matching_line_page_number($lineGeometries, $candidateLineIndex);
    if ($hitPageNumber === null || $candidatePageNumber === null) {
        return $candidateLineIndex >= $hitLineIndex;
    }

    return $hitPageNumber === $candidatePageNumber;
}

function apply_anchored_position_geometry_policy(array $components, int $hitLineIndex, int $candidateLineIndex): array
{
    if ($candidateLineIndex <= $hitLineIndex) {
        return $components;
    }

    $mainDirection = is_string($components['mainDirection'] ?? null) ? (string) $components['mainDirection'] : '';
    $positionPenaltyAxis = is_string($components['positionPenaltyAxis'] ?? null) ? (string) $components['positionPenaltyAxis'] : '';
    if ($positionPenaltyAxis === 'invalid' || $positionPenaltyAxis === 'invalid_bbox') {
        return $components;
    }
    $lineDistance = max(0, $candidateLineIndex - $hitLineIndex);
    if ($mainDirection === 'down' || $mainDirection === 'right') {
        $existingPenalty = is_numeric($components['positionPenalty'] ?? null) ? (float) $components['positionPenalty'] : 0.0;
        $anchoredPenaltyCap = min(1, ($lineDistance * 0.06));
        $components['positionPenalty'] = min($existingPenalty, $anchoredPenaltyCap);
        return $components;
    }

    if ($mainDirection === 'left' || $mainDirection === 'up') {
        return $components;
    }

    $components['positionPenalty'] = 1.0;
    $components['positionPenaltyAxis'] = 'invalid_bbox';
    $components['invalidReason'] = 'Ogiltig bbox';

    return $components;
}

function add_anchored_extraction_field_match(
    array &$matchesByKey,
    array $hit,
    string $candidateLine,
    int $candidateLineIndex,
    array $candidate,
    string $source,
    ?string $searchTerm,
    ?string $matchedSearchText,
    array $positionSettings,
    array $lineGeometries
): void {
    $value = $candidate['value'] ?? null;
    $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
    $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null;
    if ($start < 0 || $value === null) {
        return;
    }

    $spanText = is_string($candidate['spanText'] ?? null)
        ? (string) $candidate['spanText']
        : (is_string($raw) && $raw !== '' ? $raw : (is_scalar($value) ? (string) $value : null));
    $matchContext = extraction_field_candidate_match_context($candidate, $start, $spanText);
    $hitLineIndex = is_int($hit['index'] ?? null) ? (int) $hit['index'] : $candidateLineIndex;
    $confidenceComponents = candidate_confidence_components(
        $hit,
        $candidateLine,
        $start,
        $candidateLineIndex,
        $source,
        $positionSettings,
        $lineGeometries,
        $spanText,
        is_int($matchContext['start'] ?? null) ? (int) $matchContext['start'] : null,
        is_string($matchContext['spanText'] ?? null) ? (string) $matchContext['spanText'] : null
    );
    $zoneMatches = is_array($positionSettings['zoneMatches'] ?? null) ? $positionSettings['zoneMatches'] : [];
    if (candidate_is_rejected_by_zone_barrier($hit, $start, $candidateLineIndex, $spanText, $lineGeometries, $zoneMatches)) {
        return;
    }
    $confidenceComponents = apply_anchored_position_geometry_policy(
        $confidenceComponents,
        $hitLineIndex,
        $candidateLineIndex
    );

    add_extraction_field_match(
        $matchesByKey,
        $candidateLineIndex,
        $start,
        $value,
        $raw,
        is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw,
        $source,
        candidate_confidence_score($confidenceComponents),
        is_string($candidate['matchType'] ?? null) ? (string) $candidate['matchType'] : null,
        $searchTerm,
        null,
        is_numeric($confidenceComponents['noisePenalty'] ?? null) ? (float) $confidenceComponents['noisePenalty'] : null,
        is_numeric($confidenceComponents['positionPenalty'] ?? null) ? (float) $confidenceComponents['positionPenalty'] : null,
        is_string($confidenceComponents['positionPenaltyAxis'] ?? null) ? (string) $confidenceComponents['positionPenaltyAxis'] : null,
        is_string($confidenceComponents['mainDirection'] ?? null) ? (string) $confidenceComponents['mainDirection'] : null,
        is_numeric($confidenceComponents['positionDiff'] ?? null) ? (float) $confidenceComponents['positionDiff'] : null,
        is_numeric($confidenceComponents['positionNormalizedDiff'] ?? null) ? (float) $confidenceComponents['positionNormalizedDiff'] : null,
        is_string($confidenceComponents['betweenText'] ?? null) ? (string) $confidenceComponents['betweenText'] : null,
        is_string($confidenceComponents['noiseText'] ?? null) ? (string) $confidenceComponents['noiseText'] : null,
        $matchedSearchText,
        $hitLineIndex,
        is_array($confidenceComponents['noiseSegments'] ?? null) ? $confidenceComponents['noiseSegments'] : null,
        is_numeric($confidenceComponents['trailingDelimiterPenalty'] ?? null) ? (float) $confidenceComponents['trailingDelimiterPenalty'] : null,
        null,
        null,
        null,
        is_numeric($confidenceComponents['verticalDistancePenalty'] ?? null) ? (float) $confidenceComponents['verticalDistancePenalty'] : null,
        is_numeric($confidenceComponents['verticalDistance'] ?? null) ? (float) $confidenceComponents['verticalDistance'] : null,
        is_numeric($confidenceComponents['verticalNormalizedDistance'] ?? null) ? (float) $confidenceComponents['verticalNormalizedDistance'] : null,
        is_string($confidenceComponents['invalidReason'] ?? null) ? (string) $confidenceComponents['invalidReason'] : null,
        is_array($confidenceComponents['labelBbox'] ?? null) ? $confidenceComponents['labelBbox'] : null,
        is_array($confidenceComponents['valueBbox'] ?? null) ? $confidenceComponents['valueBbox'] : null,
        is_numeric($confidenceComponents['pageNumber'] ?? null) ? (int) $confidenceComponents['pageNumber'] : null
    );
}

function collect_anchored_candidate_matches(
    array $lines,
    array $labels,
    array $replacementMap,
    callable $candidateExtractor,
    array $positionSettings = [],
    array $lineGeometries = [],
    bool $labelsAreRegex = false,
    array $spanSettings = []
): array {
    $matchesByKey = [];

    $hits = find_all_label_hits($lines, $labels, $replacementMap, $labelsAreRegex, $lineGeometries, $spanSettings);
    foreach ($hits as $hit) {
        $line = is_string($hit['line'] ?? null) ? (string) $hit['line'] : '';
        $label = is_string($hit['label'] ?? null) ? trim((string) $hit['label']) : '';
        $matchedLabelText = matched_label_text_from_hit($hit);
        $hitIndex = is_int($hit['index'] ?? null) ? (int) $hit['index'] : -1;
        $labelEnd = is_int($hit['labelEnd'] ?? null) ? (int) $hit['labelEnd'] : 0;
        if ($line === '' || $hitIndex < 0) {
            continue;
        }

        foreach ($lines as $candidateLineIndex => $candidateLine) {
            if (!is_int($candidateLineIndex) || $candidateLineIndex < 0) {
                continue;
            }
            if (!anchored_candidate_line_is_in_scope($lineGeometries, $hitIndex, $candidateLineIndex)) {
                continue;
            }

            $resolvedCandidateLine = is_string($candidateLine) ? (string) $candidateLine : '';
            if ($resolvedCandidateLine === '') {
                continue;
            }

            $lineGeometry = is_array($lineGeometries[$candidateLineIndex] ?? null) ? $lineGeometries[$candidateLineIndex] : null;
            $minStart = $candidateLineIndex === $hitIndex ? $labelEnd : 0;
            $candidates = $lineGeometry !== null
                ? extraction_field_candidates_from_layout_spans($resolvedCandidateLine, $lineGeometry, $candidateExtractor, $minStart, $spanSettings)
                : $candidateExtractor($resolvedCandidateLine, 0);
            if (!is_array($candidates)) {
                continue;
            }

            foreach ($candidates as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }

                $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
                if ($candidateLineIndex === $hitIndex && $start < $labelEnd) {
                    continue;
                }

                add_anchored_extraction_field_match(
                    $matchesByKey,
                    $hit,
                    $resolvedCandidateLine,
                    $candidateLineIndex,
                    $candidate,
                    $candidateLineIndex === $hitIndex ? 'tail' : 'nearby',
                    $label !== '' ? $label : null,
                    $matchedLabelText,
                    $positionSettings,
                    $lineGeometries
                );
            }
        }
    }

    return sort_extraction_field_matches_by_confidence(array_values($matchesByKey));
}

function collect_anchored_pattern_candidate_matches_from_segments(
    array $lines,
    array $labels,
    array $replacementMap,
    string $pattern,
    bool $preferCaptureGroupValue = true,
    array $positionSettings = [],
    array $lineGeometries = [],
    bool $labelsAreRegex = false,
    bool $combineSplitAmountParts = false,
    array $captureSelection = [],
    array $spanSettings = [],
    bool $unboundedValuePatternSpan = false
): array {
    $matchesByKey = [];

    $hits = find_all_label_hits($lines, $labels, $replacementMap, $labelsAreRegex);
    foreach ($hits as $hit) {
        $line = is_string($hit['line'] ?? null) ? (string) $hit['line'] : '';
        $label = is_string($hit['label'] ?? null) ? trim((string) $hit['label']) : '';
        $matchedLabelText = matched_label_text_from_hit($hit);
        $hitIndex = is_int($hit['index'] ?? null) ? (int) $hit['index'] : -1;
        $labelEnd = is_int($hit['labelEnd'] ?? null) ? (int) $hit['labelEnd'] : 0;
        if ($line === '' || $hitIndex < 0) {
            continue;
        }

        $hitPageNumber = matching_line_page_number($lineGeometries, $hitIndex);
        foreach ($lines as $candidateLineIndex => $candidateLine) {
            if (!is_int($candidateLineIndex) || $candidateLineIndex < 0) {
                continue;
            }
            if ($hitPageNumber !== null) {
                $candidatePageNumber = matching_line_page_number($lineGeometries, $candidateLineIndex);
                if ($candidatePageNumber !== null && $candidatePageNumber !== $hitPageNumber) {
                    continue;
                }
            }

            $resolvedCandidateLine = is_string($candidateLine) ? (string) $candidateLine : '';
            if ($resolvedCandidateLine === '') {
                continue;
            }

            $lineGeometry = is_array($lineGeometries[$candidateLineIndex] ?? null) ? $lineGeometries[$candidateLineIndex] : null;
            if (!$unboundedValuePatternSpan && $lineGeometry !== null) {
                $patternExtractor = static function (string $text, int $offsetBase) use ($pattern, $preferCaptureGroupValue, $combineSplitAmountParts, $captureSelection): array {
                    return extraction_field_pattern_candidates_from_text(
                        $text,
                        $pattern,
                        true,
                        $offsetBase,
                        $preferCaptureGroupValue,
                        $combineSplitAmountParts,
                        $captureSelection
                    );
                };
                $minStart = $candidateLineIndex === $hitIndex ? $labelEnd : 0;
                $candidates = extraction_field_candidates_from_layout_spans(
                    $resolvedCandidateLine,
                    $lineGeometry,
                    $patternExtractor,
                    $minStart,
                    $spanSettings
                );
            } else {
                $candidates = extraction_field_pattern_candidates_from_text(
                    $resolvedCandidateLine,
                    $pattern,
                    true,
                    0,
                    $preferCaptureGroupValue,
                    $combineSplitAmountParts,
                    $captureSelection
                );
            }
            if ($candidates === [] && $unboundedValuePatternSpan) {
                $lineGeometry = is_array($lineGeometries[$candidateLineIndex] ?? null) ? $lineGeometries[$candidateLineIndex] : null;
                if ($lineGeometry !== null && is_array($lineGeometry['segments'] ?? null)) {
                    foreach ($lineGeometry['segments'] as $segment) {
                        if (!is_array($segment)) {
                            continue;
                        }

                        $segmentText = is_string($segment['text'] ?? null) ? trim((string) $segment['text']) : '';
                        $segmentStart = is_int($segment['start'] ?? null) ? (int) $segment['start'] : -1;
                        if ($segmentText === '' || $segmentStart < 0) {
                            continue;
                        }

                        $segmentMatches = extraction_field_pattern_candidates_from_text(
                            $segmentText,
                            $pattern,
                            true,
                            $segmentStart,
                            $preferCaptureGroupValue,
                            $combineSplitAmountParts,
                            $captureSelection
                        );
                        if ($segmentMatches === []) {
                            continue;
                        }

                        foreach ($segmentMatches as $segmentMatch) {
                            if (!is_array($segmentMatch)) {
                                continue;
                            }
                            $candidates[] = $segmentMatch;
                        }
                    }
                }
            }
            if (!is_array($candidates) || $candidates === []) {
                continue;
            }

            foreach ($candidates as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }

                $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
                $value = $candidate['value'] ?? null;
                $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null;
                if ($candidateLineIndex === $hitIndex && $start < $labelEnd) {
                    continue;
                }
                if ($start < 0 || $value === null) {
                    continue;
                }

                $spanText = is_string($candidate['spanText'] ?? null)
                    ? (string) $candidate['spanText']
                    : (is_string($raw) && $raw !== '' ? $raw : (is_scalar($value) ? (string) $value : null));
                $matchContext = extraction_field_candidate_match_context($candidate, $start, $spanText);
                $zoneMatches = is_array($positionSettings['zoneMatches'] ?? null) ? $positionSettings['zoneMatches'] : [];
                if (candidate_is_rejected_by_zone_barrier($hit, $start, $candidateLineIndex, $spanText, $lineGeometries, $zoneMatches)) {
                    continue;
                }
                $confidenceComponents = candidate_confidence_components(
                    $hit,
                    $resolvedCandidateLine,
                    $start,
                    $candidateLineIndex,
                    $candidateLineIndex === $hitIndex ? 'line' : 'nearby',
                    $positionSettings,
                    $lineGeometries,
                    $spanText,
                    is_int($matchContext['start'] ?? null) ? (int) $matchContext['start'] : null,
                    is_string($matchContext['spanText'] ?? null) ? (string) $matchContext['spanText'] : null
                );

                add_extraction_field_match(
                    $matchesByKey,
                    $candidateLineIndex,
                    $start,
                    $value,
                    $raw,
                    is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw,
                    $candidateLineIndex === $hitIndex ? 'line' : 'nearby',
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
                    is_array($confidenceComponents['noiseSegments'] ?? null) ? $confidenceComponents['noiseSegments'] : null,
                    is_numeric($confidenceComponents['trailingDelimiterPenalty'] ?? null) ? (float) $confidenceComponents['trailingDelimiterPenalty'] : null,
                    null,
                    null,
                    null,
                    is_numeric($confidenceComponents['verticalDistancePenalty'] ?? null) ? (float) $confidenceComponents['verticalDistancePenalty'] : null,
                    is_numeric($confidenceComponents['verticalDistance'] ?? null) ? (float) $confidenceComponents['verticalDistance'] : null,
                    is_numeric($confidenceComponents['verticalNormalizedDistance'] ?? null) ? (float) $confidenceComponents['verticalNormalizedDistance'] : null,
                    is_string($confidenceComponents['invalidReason'] ?? null) ? (string) $confidenceComponents['invalidReason'] : null,
                    is_array($confidenceComponents['labelBbox'] ?? null) ? $confidenceComponents['labelBbox'] : null,
                    is_array($confidenceComponents['valueBbox'] ?? null) ? $confidenceComponents['valueBbox'] : null,
                    is_numeric($confidenceComponents['pageNumber'] ?? null) ? (int) $confidenceComponents['pageNumber'] : null,
                    is_array($candidate['captureRanges'] ?? null) ? $candidate['captureRanges'] : null
                );
            }
        }
    }

    return sort_extraction_field_matches(array_values($matchesByKey));
}

function apply_cross_matching_key_penalties(array $results, array $positionSettings = []): array
{
    $settings = normalize_matching_position_adjustment_settings($positionSettings);
    $penaltyWeight = normalize_matching_decimal_setting($settings['otherMatchKeyPenalty'] ?? null, 0.5, null);

    $labelsByText = [];
    foreach ($results as $fieldKey => $result) {
        if (!is_array($result) || !is_array($result['matches'] ?? null)) {
            continue;
        }

        foreach (array_values($result['matches']) as $matchIndex => $match) {
            if (!is_array($match)) {
                continue;
            }

            $baseConfidence = isset($match['baseConfidence']) && is_numeric($match['baseConfidence'])
                ? clamp_confidence((float) $match['baseConfidence'])
                : clamp_confidence((float) ($match['confidence'] ?? 0.0));
            $labelText = normalized_field_matching_key_text($match['labelText'] ?? null);
            if ($labelText === '') {
                continue;
            }

            $matchId = (string) $fieldKey . '|' . (string) $matchIndex;
            $labelsByText[$labelText][] = [
                'id' => $matchId,
                'baseConfidence' => $baseConfidence,
            ];
        }
    }

    foreach ($results as $fieldKey => &$result) {
        if (!is_array($result) || !is_array($result['matches'] ?? null)) {
            continue;
        }

        foreach (array_values($result['matches']) as $matchIndex => $match) {
            if (!is_array($match)) {
                continue;
            }

            $baseConfidence = isset($match['baseConfidence']) && is_numeric($match['baseConfidence'])
                ? clamp_confidence((float) $match['baseConfidence'])
                : clamp_confidence((float) ($match['confidence'] ?? 0.0));
            $match['baseConfidence'] = $baseConfidence;
            $match['finalConfidence'] = $baseConfidence;
            $match['confidence'] = $baseConfidence;

            if ($penaltyWeight <= 0.0 || $labelsByText === []) {
                $result['matches'][$matchIndex] = $match;
                continue;
            }

            $candidateText = extraction_field_match_candidate_lookup_text($match);
            if ($candidateText === '' || !isset($labelsByText[$candidateText])) {
                $result['matches'][$matchIndex] = $match;
                continue;
            }

            $matchId = (string) $fieldKey . '|' . (string) $matchIndex;
            $otherConfidence = 0.0;
            foreach ($labelsByText[$candidateText] as $labelMatch) {
                if (!is_array($labelMatch) || ($labelMatch['id'] ?? '') === $matchId) {
                    continue;
                }
                $otherConfidence = max($otherConfidence, clamp_confidence((float) ($labelMatch['baseConfidence'] ?? 0.0)));
            }

            if ($otherConfidence <= 0.0) {
                $result['matches'][$matchIndex] = $match;
                continue;
            }

            $penalty = max(0.0, $penaltyWeight * $otherConfidence);
            $match['otherMatchKeyPenalty'] = $penalty;
            $match['finalConfidence'] = clamp_confidence(
                $baseConfidence
                * max(0.0, 1.0 - clamp_confidence($penalty))
            );
            $match['confidence'] = $match['finalConfidence'];
            $result['matches'][$matchIndex] = $match;
        }

        $rankedMatches = sort_extraction_field_matches_by_confidence(array_values(array_filter(
            $result['matches'],
            static fn ($match): bool => is_array($match)
        )));
        $result['matches'] = $rankedMatches;
        $result['values'] = array_values(array_map(
            static fn (array $match): mixed => $match['value'] ?? null,
            $rankedMatches
        ));
        $primaryMatch = is_array($rankedMatches[0] ?? null) ? $rankedMatches[0] : null;
        if (is_array($primaryMatch)) {
            $result['value'] = $primaryMatch['value'] ?? null;
            $result['baseConfidence'] = isset($primaryMatch['baseConfidence']) ? clamp_confidence((float) $primaryMatch['baseConfidence']) : 0.0;
            $result['finalConfidence'] = isset($primaryMatch['finalConfidence']) ? clamp_confidence((float) $primaryMatch['finalConfidence']) : (isset($primaryMatch['confidence']) ? clamp_confidence((float) $primaryMatch['confidence']) : 0.0);
            $result['confidence'] = $result['finalConfidence'];
            $result['lineIndex'] = is_int($primaryMatch['lineIndex'] ?? null) ? (int) $primaryMatch['lineIndex'] : null;
            $result['source'] = is_string($primaryMatch['source'] ?? null) ? (string) $primaryMatch['source'] : 'none';
            $result['raw'] = is_string($primaryMatch['raw'] ?? null) ? (string) $primaryMatch['raw'] : null;
            $result['matchText'] = is_string($primaryMatch['matchText'] ?? null)
                ? (string) $primaryMatch['matchText']
                : (is_string($primaryMatch['raw'] ?? null) ? (string) $primaryMatch['raw'] : null);
        } else {
            $result['baseConfidence'] = 0.0;
            $result['finalConfidence'] = 0.0;
            $result['confidence'] = 0.0;
        }
    }
    unset($result);

    return $results;
}

function configured_field_selection_rule_set(array $result): array
{
    $ruleSets = is_array($result['ruleSets'] ?? null) ? $result['ruleSets'] : [];
    $matchedRuleSetIndex = is_int($result['matchedRuleSetIndex'] ?? null) ? (int) $result['matchedRuleSetIndex'] : null;
    if ($matchedRuleSetIndex !== null && is_array($ruleSets[$matchedRuleSetIndex] ?? null)) {
        return $ruleSets[$matchedRuleSetIndex];
    }
    return is_array($ruleSets[0] ?? null) ? $ruleSets[0] : default_extraction_field_rule_set();
}

function selected_occurrence_match(array $matches, string $position): ?array
{
    $ordered = sort_extraction_field_matches(array_values(array_filter(
        $matches,
        static fn ($match): bool => is_array($match)
    )));
    if ($ordered === []) {
        return null;
    }
    if ($position === 'last') {
        return is_array($ordered[count($ordered) - 1] ?? null) ? $ordered[count($ordered) - 1] : null;
    }
    if ($position === 'second') {
        return is_array($ordered[1] ?? null) ? $ordered[1] : null;
    }
    return is_array($ordered[0] ?? null) ? $ordered[0] : null;
}

function apply_extraction_field_acceptance_threshold(array $results, float $acceptanceThreshold = 0.5): array
{
    $resolvedThreshold = clamp_confidence($acceptanceThreshold);

    foreach ($results as &$result) {
        if (!is_array($result) || !is_array($result['matches'] ?? null)) {
            continue;
        }

        $rankedMatches = sort_extraction_field_matches_by_confidence(array_values(array_filter(
            $result['matches'],
            static fn ($match): bool => is_array($match)
        )));
        $result['matches'] = $rankedMatches;

        $acceptedMatches = array_values(array_filter(
            $rankedMatches,
            static fn (array $match): bool => ($match['finalConfidence'] ?? 0) >= $resolvedThreshold
        ));
        $selectionRuleSet = configured_field_selection_rule_set($result);
        $selectionType = extraction_field_rule_set_type($selectionRuleSet, $result);
        $matchedRuleSetIndex = is_int($result['matchedRuleSetIndex'] ?? null) ? (int) $result['matchedRuleSetIndex'] : null;
        $selectionMatches = $matchedRuleSetIndex !== null
            ? array_values(array_filter(
                $acceptedMatches,
                static fn (array $match): bool => is_int($match['ruleSetIndex'] ?? null)
                    && (int) $match['ruleSetIndex'] === $matchedRuleSetIndex
            ))
            : [];
        if ($selectionMatches === []) {
            $selectionMatches = $acceptedMatches;
        }
        if (($result['extractor'] ?? null) === 'title') {
            $primaryMatch = is_array($selectionMatches[0] ?? null) ? $selectionMatches[0] : null;
            $result['candidateMatches'] = $acceptedMatches;
            $result['values'] = is_array($primaryMatch) && array_key_exists('value', $primaryMatch)
                ? [$primaryMatch['value']]
                : [];
        } elseif ($selectionType === 'date') {
            $selectedMatch = is_array($selectionMatches[0] ?? null) ? $selectionMatches[0] : null;
            $result['candidateMatches'] = $acceptedMatches;
            $result['matches'] = is_array($selectedMatch) ? [$selectedMatch] : [];
            $result['values'] = is_array($selectedMatch) && array_key_exists('value', $selectedMatch)
                ? [$selectedMatch['value']]
                : [];
            $primaryMatch = $selectedMatch;
        } elseif ($selectionType === 'amount') {
            $selectedMatch = selected_occurrence_match(
                $selectionMatches,
                extraction_field_rule_set_position($selectionRuleSet, $selectionType)
            );
            $result['candidateMatches'] = $acceptedMatches;
            $result['matches'] = is_array($selectedMatch) ? [$selectedMatch] : [];
            $result['values'] = is_array($selectedMatch) && array_key_exists('value', $selectedMatch)
                ? [$selectedMatch['value']]
                : [];
            $primaryMatch = $selectedMatch;
        } else {
            $result['values'] = array_values(array_map(
                static fn (array $match): mixed => $match['value'] ?? null,
                $acceptedMatches
            ));
            $primaryMatch = is_array($acceptedMatches[0] ?? null) ? $acceptedMatches[0] : null;
        }

        if (is_array($primaryMatch)) {
            $result['value'] = $primaryMatch['value'] ?? null;
            $result['baseConfidence'] = isset($primaryMatch['baseConfidence']) ? clamp_confidence((float) $primaryMatch['baseConfidence']) : 0.0;
            $result['finalConfidence'] = isset($primaryMatch['finalConfidence']) ? clamp_confidence((float) $primaryMatch['finalConfidence']) : (isset($primaryMatch['confidence']) ? clamp_confidence((float) $primaryMatch['confidence']) : 0.0);
            $result['confidence'] = $result['finalConfidence'];
            $result['lineIndex'] = is_int($primaryMatch['lineIndex'] ?? null) ? (int) $primaryMatch['lineIndex'] : null;
            $result['source'] = is_string($primaryMatch['source'] ?? null) ? (string) $primaryMatch['source'] : 'none';
            $result['raw'] = is_string($primaryMatch['raw'] ?? null) ? (string) $primaryMatch['raw'] : null;
            $result['matchText'] = is_string($primaryMatch['matchText'] ?? null)
                ? (string) $primaryMatch['matchText']
                : (is_string($primaryMatch['raw'] ?? null) ? (string) $primaryMatch['raw'] : null);
            continue;
        }

        $result['value'] = null;
        $result['values'] = [];
        $result['baseConfidence'] = 0.0;
        $result['finalConfidence'] = 0.0;
        $result['confidence'] = 0.0;
        $result['lineIndex'] = null;
        $result['source'] = 'none';
        $result['raw'] = null;
        $result['matchText'] = null;
    }
    unset($result);

    return $results;
}

function primary_date_result_matches(array $result, array $lineGeometries = []): array
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

        $confidence = isset($candidate['confidence'])
            ? clamp_confidence((float) $candidate['confidence'])
            : (
                is_numeric($candidate['fullConfidenceScore'] ?? null) && (float) $candidate['fullConfidenceScore'] > 0.0
                    ? clamp_confidence(((float) ($candidate['score'] ?? 0.0)) / (float) $candidate['fullConfidenceScore'])
                    : 0.0
            );
        $candidateBbox = null;
        $candidatePageNumber = null;
        $lineGeometry = is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
        if ($lineGeometry !== null) {
            $candidateLength = is_string($raw) && $raw !== '' ? strlen($raw) : (is_string($value) ? strlen($value) : 1);
            $candidateBbox = line_geometry_span_bbox($lineGeometry, $start, $start + max(1, $candidateLength));
            $candidatePageNumber = matching_line_page_number($lineGeometries, $lineIndex);
        }
        add_extraction_field_match(
            matchesByKey: $matchesByKey,
            lineIndex: $lineIndex,
            start: $start,
            value: $value,
            raw: $raw,
            matchText: $raw,
            source: 'primary_date_heuristic',
            confidence: $confidence,
            matchType: 'primary_date_heuristic',
            score: is_numeric($candidate['rawScore'] ?? null)
                ? (float) $candidate['rawScore']
                : (float) ((int) ($candidate['score'] ?? 0)),
            baseConfidence: $confidence,
            finalConfidence: $confidence,
            labelBbox: $candidateBbox,
            valueBbox: $candidateBbox,
            pageNumber: $candidatePageNumber
        );
        $matchKey = extraction_field_match_storage_key($lineIndex, $start, $value, $raw);
        if (isset($matchesByKey[$matchKey]) && is_array($candidate['signals'] ?? null)) {
            $matchesByKey[$matchKey]['signals'] = $candidate['signals'];
        }
        if (isset($matchesByKey[$matchKey]) && is_numeric($candidate['fullConfidenceScore'] ?? null)) {
            $matchesByKey[$matchKey]['fullConfidenceScore'] = (float) $candidate['fullConfidenceScore'];
        }
        if (isset($matchesByKey[$matchKey]) && is_numeric($candidate['yRatio'] ?? null)) {
            $matchesByKey[$matchKey]['yRatio'] = (float) $candidate['yRatio'];
        }
    }

    if ($matchesByKey === [] && ($result['value'] ?? null) !== null) {
        $lineIndex = is_int($result['lineIndex'] ?? null) ? (int) $result['lineIndex'] : -1;
        if ($lineIndex >= 0) {
            $confidence = isset($result['confidence']) ? clamp_confidence((float) $result['confidence']) : 0.0;
            $rawScore = is_numeric($result['selectedCandidate']['rawScore'] ?? null)
                ? (float) $result['selectedCandidate']['rawScore']
                : (is_numeric($result['selectedCandidate']['score'] ?? null)
                    ? (float) $result['selectedCandidate']['score']
                    : null);
            add_extraction_field_match(
                matchesByKey: $matchesByKey,
                lineIndex: $lineIndex,
                start: 0,
                value: $result['value'],
                raw: is_string($result['raw'] ?? null) ? (string) $result['raw'] : null,
                matchText: is_string($result['raw'] ?? null) ? (string) $result['raw'] : null,
                source: is_string($result['source'] ?? null) ? (string) $result['source'] : 'primary_date_heuristic',
                confidence: $confidence,
                matchType: 'primary_date_heuristic',
                score: $rawScore,
                baseConfidence: $confidence,
                finalConfidence: $confidence,
                pageNumber: $lineIndex >= 0 ? matching_line_page_number($lineGeometries, $lineIndex) : null
            );
            $matchKey = extraction_field_match_storage_key($lineIndex, 0, $result['value'], is_string($result['raw'] ?? null) ? (string) $result['raw'] : null);
            if (isset($matchesByKey[$matchKey]) && is_numeric($result['fullConfidenceScore'] ?? null)) {
                $matchesByKey[$matchKey]['fullConfidenceScore'] = (float) $result['fullConfidenceScore'];
            }
        }
    }

    return sort_extraction_field_matches(array_values($matchesByKey));
}

function title_result_matches(array $result, array $lineGeometries = []): array
{
    $matchesByKey = [];
    $candidates = is_array($result['candidates'] ?? null) ? $result['candidates'] : [];
    foreach ($candidates as $candidate) {
        if (!is_array($candidate) || ($candidate['excluded'] ?? false) === true) {
            continue;
        }

        $value = is_string($candidate['value'] ?? null) ? (string) $candidate['value'] : null;
        $lineIndex = is_int($candidate['lineIndex'] ?? null) ? (int) $candidate['lineIndex'] : -1;
        $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : 0;
        $end = is_int($candidate['end'] ?? null) ? (int) $candidate['end'] : -1;
        $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : $value;
        if ($lineIndex < 0 || $value === null || $value === '') {
            continue;
        }

        $confidence = isset($candidate['confidence'])
            ? clamp_confidence((float) $candidate['confidence'])
            : (
                is_numeric($candidate['fullConfidenceScore'] ?? null) && (float) $candidate['fullConfidenceScore'] > 0.0
                    ? clamp_confidence(((float) ($candidate['score'] ?? 0.0)) / (float) $candidate['fullConfidenceScore'])
                    : 0.0
            );
        $candidateBbox = is_array($candidate['bbox'] ?? null) ? normalize_debug_word_bbox($candidate['bbox']) : null;
        if ($candidateBbox === null && is_array($lineGeometries[$lineIndex] ?? null)) {
            $candidateBbox = $end > $start
                ? line_geometry_span_bbox($lineGeometries[$lineIndex], $start, $end)
                : line_geometry_span_bbox($lineGeometries[$lineIndex], 0, strlen(is_string($candidate['line'] ?? null) ? (string) $candidate['line'] : $value));
        }
        $candidatePageNumber = matching_line_page_number($lineGeometries, $lineIndex);

        add_extraction_field_match(
            matchesByKey: $matchesByKey,
            lineIndex: $lineIndex,
            start: $start,
            value: $value,
            raw: $raw,
            matchText: $raw,
            source: 'title_heuristic',
            confidence: $confidence,
            matchType: 'title_heuristic',
            score: is_numeric($candidate['rawScore'] ?? null)
                ? (float) $candidate['rawScore']
                : (float) ((int) ($candidate['score'] ?? 0)),
            baseConfidence: $confidence,
            finalConfidence: $confidence,
            labelBbox: $candidateBbox,
            valueBbox: $candidateBbox,
            pageNumber: $candidatePageNumber
        );
        $matchKey = extraction_field_match_storage_key($lineIndex, $start, $value, $raw);
        if (isset($matchesByKey[$matchKey]) && is_array($candidate['signals'] ?? null)) {
            $matchesByKey[$matchKey]['signals'] = $candidate['signals'];
        }
        foreach (['fullConfidenceScore', 'yRatio', 'centerDistance', 'relativeTextSize', 'uppercaseRatio', 'textDensityRatio'] as $numericKey) {
            if (isset($matchesByKey[$matchKey]) && is_numeric($candidate[$numericKey] ?? null)) {
                $matchesByKey[$matchKey][$numericKey] = (float) $candidate[$numericKey];
            }
        }
        if (isset($matchesByKey[$matchKey]) && is_array($candidate['valueBBoxIndexes'] ?? null)) {
            $matchesByKey[$matchKey]['valueBBoxIndexes'] = array_values(array_filter(
                $candidate['valueBBoxIndexes'],
                static fn($index): bool => is_int($index) && $index > 0
            ));
        }
        if (isset($matchesByKey[$matchKey]) && is_numeric($candidate['wordCount'] ?? null)) {
            $matchesByKey[$matchKey]['wordCount'] = (int) $candidate['wordCount'];
        }
    }

    return sort_extraction_field_matches(array_values($matchesByKey));
}

function extraction_field_rule_set_type(array $ruleSet, ?array $legacyField = null): string
{
    $valueType = normalize_extraction_field_value_type($legacyField['valueType'] ?? null, $legacyField, null);
    return legacy_extraction_field_type_for_value_type($valueType);
}

function legacy_extraction_field_type_for_value_type(string $valueType): string
{
    return match ($valueType) {
        'date' => 'date',
        'amount' => 'amount',
        default => 'regex',
    };
}

function extraction_field_rule_set_uses_search_text(array $ruleSet): bool
{
    if (array_key_exists('useSearchText', $ruleSet)) {
        return normalize_extraction_field_use_search_text($ruleSet['useSearchText'] ?? null, true);
    }

    return !array_key_exists('requiresSearchTerms', $ruleSet)
        || $ruleSet['requiresSearchTerms'] === true
        || $ruleSet['requiresSearchTerms'] === 1
        || $ruleSet['requiresSearchTerms'] === '1';
}

function extraction_field_rule_set_position(array $ruleSet, string $type): string
{
    if ($type === 'date') {
        return normalize_extraction_field_position($ruleSet['datePosition'] ?? null);
    }
    if ($type === 'amount') {
        return normalize_extraction_field_position($ruleSet['amountPosition'] ?? null);
    }
    return 'first';
}

function extraction_field_rule_set_capture_selection(array $ruleSet, string $valueType): array
{
    if ($valueType === 'amount') {
        return [
            'amountWholeGroup' => normalize_extraction_field_capture_group($ruleSet['amountWholeGroup'] ?? null),
            'amountFractionGroup' => normalize_extraction_field_capture_group($ruleSet['amountFractionGroup'] ?? null),
        ];
    }

    return [
        'captureGroup' => normalize_extraction_field_capture_group($ruleSet['captureGroup'] ?? null),
    ];
}

function resolve_extraction_field_candidate_value(mixed $value, array $match, array $ruleSet, array $field = []): mixed
{
    $valueType = $field !== []
        ? extraction_field_value_type($field)
        : extraction_field_rule_set_value_type($ruleSet);
    $rawText = is_string($match['raw'] ?? null) ? (string) $match['raw'] : '';
    $matchText = is_string($match['matchText'] ?? null) ? (string) $match['matchText'] : $rawText;
    $candidateText = is_string($value) ? $value : (is_scalar($value) ? (string) $value : '');
    $lookupText = trim($candidateText) !== '' ? $candidateText : ($matchText !== '' ? $matchText : $rawText);
    $lookupText = apply_extraction_field_normalization($lookupText, $ruleSet);
    if ($field !== []) {
        $lookupText = apply_extraction_field_normalization($lookupText, $field);
    }

    if ($valueType === 'amount') {
        if (trim($lookupText) === '' && is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }
        $amount = normalize_swedish_amount($lookupText);
        return is_float($amount) ? number_format($amount, 2, '.', '') : null;
    }

    if ($valueType === 'date') {
        return extract_date_from_text($lookupText);
    }

    if (is_string($value) || trim($lookupText) !== '') {
        return $lookupText;
    }

    return $value;
}

function collect_document_candidate_matches(
    array $lines,
    callable $candidateExtractor,
    float $confidence = 0.55,
    array $lineGeometries = [],
    array $spanSettings = []
): array {
    $matchesByKey = [];
    foreach ($lines as $lineIndex => $line) {
        $resolvedLine = is_string($line) ? $line : '';
        if ($resolvedLine === '' || !is_int($lineIndex)) {
            continue;
        }

        $lineGeometry = is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
        $candidates = $lineGeometry !== null
            ? extraction_field_candidates_from_layout_spans($resolvedLine, $lineGeometry, $candidateExtractor, 0, $spanSettings)
            : $candidateExtractor($resolvedLine, 0);
        if (!is_array($candidates)) {
            continue;
        }

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

            $candidateBbox = null;
            $candidatePageNumber = null;
            $lineGeometry = is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
            if ($lineGeometry !== null) {
                $candidateLength = is_string($raw) && $raw !== ''
                    ? strlen($raw)
                    : (is_string($candidate['matchText'] ?? null)
                        ? strlen((string) $candidate['matchText'])
                        : (is_scalar($value) ? strlen((string) $value) : 1));
                $candidateBbox = line_geometry_span_bbox($lineGeometry, $start, $start + max(1, $candidateLength));
                $candidatePageNumber = matching_line_page_number($lineGeometries, $lineIndex);
            }

            add_extraction_field_match(
                matchesByKey: $matchesByKey,
                lineIndex: $lineIndex,
                start: $start,
                value: $value,
                raw: $raw,
                matchText: is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw,
                source: 'pattern',
                confidence: $confidence,
                matchType: is_string($candidate['matchType'] ?? null) ? (string) $candidate['matchType'] : null,
                valueBbox: $candidateBbox,
                pageNumber: $candidatePageNumber
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
    array $lineGeometries = [],
    bool $preferCaptureGroupValue = true
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
        static function (string $text, int $offsetBase) use ($pattern, $preferCaptureGroupValue): array {
            return extraction_field_pattern_candidates_from_text($text, $pattern, true, $offsetBase, $preferCaptureGroupValue);
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
    array $lineGeometries = [],
    bool $preferCaptureGroupValue = true
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

    return collect_anchored_candidate_matches(
        $lines,
        $resolvedAliases,
        $replacementMap,
        static function (string $text, int $offsetBase) use ($pattern, $preferCaptureGroupValue): array {
            return extraction_field_pattern_candidates_from_text($text, $pattern, true, $offsetBase, $preferCaptureGroupValue);
        },
        $positionSettings,
        $lineGeometries
    );
}

function extract_unlabeled_pattern_field_result(
    array $lines,
    string $pattern,
    bool $preferCaptureGroupValue = true,
    bool $combineSplitAmountParts = false
): array
{
    $candidates = extraction_field_document_pattern_candidates_from_lines(
        $lines,
        $pattern,
        true,
        [],
        $preferCaptureGroupValue,
        $combineSplitAmountParts
    );
    if ($candidates === []) {
        return empty_extraction_field_result();
    }

    $candidate = is_array($candidates[0] ?? null) ? $candidates[0] : null;
    if ($candidate === null) {
        return empty_extraction_field_result();
    }

    return [
        'value' => $candidate['value'] ?? null,
        'confidence' => 1.0,
        'lineIndex' => is_int($candidate['lineIndex'] ?? null) ? (int) $candidate['lineIndex'] : null,
        'source' => 'pattern',
        'raw' => is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null,
        'matchText' => is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : (is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null),
        'captureRanges' => is_array($candidate['captureRanges'] ?? null) ? $candidate['captureRanges'] : [],
        'labelBbox' => is_array($candidate['labelBbox'] ?? null) ? $candidate['labelBbox'] : null,
        'valueBbox' => is_array($candidate['valueBbox'] ?? null) ? $candidate['valueBbox'] : null,
        'pageNumber' => is_int($candidate['pageNumber'] ?? null) ? (int) $candidate['pageNumber'] : null,
    ];
}

function extract_unlabeled_pattern_field_matches(
    array $lines,
    string $pattern,
    bool $preferCaptureGroupValue = true,
    array $lineGeometries = [],
    bool $combineSplitAmountParts = false,
    array $captureSelection = [],
    array $spanSettings = [],
    bool $unboundedValuePatternSpan = false
): array
{
    if ($unboundedValuePatternSpan) {
        $candidates = extraction_field_document_pattern_candidates_from_lines(
            $lines,
            $pattern,
            true,
            $lineGeometries,
            $preferCaptureGroupValue,
            $combineSplitAmountParts,
            $captureSelection
        );
    } else {
        $patternExtractor = static function (string $text, int $offsetBase) use ($pattern, $preferCaptureGroupValue, $combineSplitAmountParts, $captureSelection): array {
            return extraction_field_pattern_candidates_from_text(
                $text,
                $pattern,
                true,
                $offsetBase,
                $preferCaptureGroupValue,
                $combineSplitAmountParts,
                $captureSelection
            );
        };
        $candidates = [];
        foreach ($lines as $lineIndex => $line) {
            if (!is_int($lineIndex)) {
                continue;
            }
            $resolvedLine = is_string($line) ? (string) $line : '';
            if ($resolvedLine === '') {
                continue;
            }
            $lineGeometry = is_array($lineGeometries[$lineIndex] ?? null) ? $lineGeometries[$lineIndex] : null;
            $lineCandidates = $lineGeometry !== null
                ? extraction_field_candidates_from_layout_spans($resolvedLine, $lineGeometry, $patternExtractor, 0, $spanSettings)
                : $patternExtractor($resolvedLine, 0);
            foreach ($lineCandidates as $lineCandidate) {
                if (is_array($lineCandidate)) {
                    $lineCandidate['lineIndex'] = $lineIndex;
                    $lineCandidate['valueBbox'] = line_geometry_span_bbox(
                        $lineGeometry,
                        is_int($lineCandidate['start'] ?? null) ? (int) $lineCandidate['start'] : 0,
                        (is_int($lineCandidate['start'] ?? null) ? (int) $lineCandidate['start'] : 0) + max(1, strlen(is_string($lineCandidate['spanText'] ?? null) ? (string) $lineCandidate['spanText'] : (is_string($lineCandidate['raw'] ?? null) ? (string) $lineCandidate['raw'] : '')))
                    );
                    $lineCandidate['pageNumber'] = matching_line_page_number($lineGeometries, $lineIndex);
                    $candidates[] = $lineCandidate;
                }
            }
        }
    }
    if ($candidates === []) {
        return [];
    }

    $matchesByKey = [];
    foreach ($candidates as $candidate) {
        if (!is_array($candidate)) {
            continue;
        }

        $value = $candidate['value'] ?? null;
        $start = is_int($candidate['start'] ?? null) ? (int) $candidate['start'] : -1;
        $raw = is_string($candidate['raw'] ?? null) ? (string) $candidate['raw'] : null;
        $lineIndex = is_int($candidate['lineIndex'] ?? null) ? (int) $candidate['lineIndex'] : -1;
        if ($value === null || $start < 0 || $lineIndex < 0) {
            continue;
        }

        add_extraction_field_match(
            matchesByKey: $matchesByKey,
            lineIndex: $lineIndex,
            start: $start,
            value: $value,
            raw: $raw,
            matchText: is_string($candidate['matchText'] ?? null) ? (string) $candidate['matchText'] : $raw,
            source: 'pattern',
            confidence: 1.0,
            matchType: is_string($candidate['matchType'] ?? null) ? (string) $candidate['matchType'] : 'pattern',
            labelBbox: is_array($candidate['labelBbox'] ?? null) ? $candidate['labelBbox'] : null,
            valueBbox: is_array($candidate['valueBbox'] ?? null) ? $candidate['valueBbox'] : null,
            pageNumber: is_int($candidate['pageNumber'] ?? null) ? (int) $candidate['pageNumber'] : null,
            captureRanges: is_array($candidate['captureRanges'] ?? null) ? $candidate['captureRanges'] : null
        );
    }

    return sort_extraction_field_matches(array_values($matchesByKey));
}

function apply_extraction_field_after_text_scope(array $lines, array $replacementMap, array $scope): ?array
{
    $scopeText = is_string($scope['text'] ?? null) ? trim((string) $scope['text']) : '';
    if ($scopeText === '') {
        return [
            'lines' => $lines,
            'debug' => null,
        ];
    }

    $hits = find_document_label_hits($lines, [[
        'text' => $scopeText,
        'isRegex' => normalize_extraction_field_is_regex($scope['isRegex'] ?? false),
    ]], $replacementMap);
    if ($hits === []) {
        return null;
    }

    usort($hits, static function (array $left, array $right): int {
        $lineCompare = ((int) ($left['index'] ?? 0)) <=> ((int) ($right['index'] ?? 0));
        if ($lineCompare !== 0) {
            return $lineCompare;
        }
        return ((int) ($left['labelStart'] ?? 0)) <=> ((int) ($right['labelStart'] ?? 0));
    });

    $hit = $hits[0];
    $hitLineIndex = is_int($hit['index'] ?? null) ? (int) $hit['index'] : -1;
    $labelEnd = is_int($hit['labelEnd'] ?? null) ? max(0, (int) $hit['labelEnd']) : 0;
    if ($hitLineIndex < 0) {
        return null;
    }

    $scopedLines = [];
    foreach ($lines as $lineIndex => $line) {
        if (!is_int($lineIndex) || $lineIndex < $hitLineIndex) {
            continue;
        }

        $resolvedLine = is_string($line) ? (string) $line : '';
        if ($lineIndex === $hitLineIndex) {
            $lineLength = strlen($resolvedLine);
            $end = min($labelEnd, $lineLength);
            $resolvedLine = str_repeat(' ', $end) . substr($resolvedLine, $end);
        }
        $scopedLines[$lineIndex] = $resolvedLine;
    }

    return [
        'lines' => $scopedLines,
        'debug' => [
            'type' => 'after_text',
            'text' => $scopeText,
            'isRegex' => normalize_extraction_field_is_regex($scope['isRegex'] ?? false),
            'matchedText' => matched_label_text_from_hit($hit),
            'lineIndex' => $hitLineIndex,
        ],
    ];
}

function annotate_extraction_field_matches_with_scope(array $matches, ?array $scopeDebug): array
{
    if ($scopeDebug === null) {
        return $matches;
    }

    return array_values(array_map(static function ($match) use ($scopeDebug): array {
        $resolved = is_array($match) ? $match : [];
        $resolved['scopeType'] = 'after_text';
        $resolved['scopeText'] = is_string($scopeDebug['text'] ?? null) ? (string) $scopeDebug['text'] : '';
        $resolved['scopeIsRegex'] = ($scopeDebug['isRegex'] ?? false) === true;
        if (is_string($scopeDebug['matchedText'] ?? null) && trim((string) $scopeDebug['matchedText']) !== '') {
            $resolved['scopeMatchedText'] = trim((string) $scopeDebug['matchedText']);
        }
        if (is_int($scopeDebug['lineIndex'] ?? null)) {
            $resolved['scopeLineIndex'] = (int) $scopeDebug['lineIndex'];
        }
        return $resolved;
    }, $matches));
}

function match_field_rule(
    array $lines,
    array $replacementMap,
    array $ruleSet,
    array $positionSettings = [],
    array $lineGeometries = []
): array {
    return extract_configured_rule_set_field_matches(
        $lines,
        $replacementMap,
        $ruleSet,
        $positionSettings,
        $lineGeometries
    );
}

function extract_configured_rule_set_field_matches(
    array $lines,
    array $replacementMap,
    array $ruleSet,
    array $positionSettings = [],
    array $lineGeometries = [],
    array $field = []
): array
{
    $valueType = $field !== []
        ? extraction_field_value_type($field)
        : extraction_field_rule_set_value_type($ruleSet);
    $runtimeRuleSet = extraction_field_runtime_rule_set(
        $ruleSet,
        $valueType,
        value_pattern_definitions_by_id(is_array($field['_valuePatterns'] ?? null) ? $field['_valuePatterns'] : [])
    );
    $scope = normalize_extraction_field_rule_scope($runtimeRuleSet['scope'] ?? null);
    $scopeDebug = null;
    if ($scope !== null) {
        $scoped = apply_extraction_field_after_text_scope($lines, $replacementMap, $scope);
        if ($scoped === null) {
            return [];
        }
        $lines = is_array($scoped['lines'] ?? null) ? $scoped['lines'] : [];
        $scopeDebug = is_array($scoped['debug'] ?? null) ? $scoped['debug'] : null;
    }
    $requiresSearchTerms = extraction_field_rule_set_uses_search_text($runtimeRuleSet);
    $searchTerms = normalize_extraction_field_search_terms(
        $runtimeRuleSet['searchTerms'] ?? null,
        normalize_extraction_field_is_regex($runtimeRuleSet['isRegex'] ?? false)
    );
    $labelsAreRegex = normalize_extraction_field_is_regex($runtimeRuleSet['isRegex'] ?? false);
    $valuePattern = is_string($runtimeRuleSet['valuePattern'] ?? null) ? trim((string) $runtimeRuleSet['valuePattern']) : '';
    $usesValuePattern = normalize_extraction_field_use_value_pattern($runtimeRuleSet['useValuePattern'] ?? null, $valuePattern)
        && $valuePattern !== '';
    $combineSplitAmountParts = $valueType === 'amount' && extraction_field_value_pattern_uses_semantic_amount_parts($valuePattern);
    $captureSelection = extraction_field_rule_set_capture_selection($runtimeRuleSet, $valueType);
    $spanSettings = matching_bbox_span_building_from_position_settings($positionSettings);
    $unboundedValuePatternSpan = $usesValuePattern && normalize_extraction_field_unbounded_value_pattern_span($runtimeRuleSet);

    $preferCaptureGroupValue = true;
    $candidateExtractor = match ($valueType) {
        'amount' => 'amount_candidates_from_text',
        default => static function (string $text, int $offsetBase) use ($valuePattern, $preferCaptureGroupValue, $captureSelection): array {
            return extraction_field_pattern_candidates_from_text($text, $valuePattern, true, $offsetBase, $preferCaptureGroupValue, false, $captureSelection);
        },
    };

    if (!$usesValuePattern && $valueType === 'text') {
        if (!$requiresSearchTerms || $searchTerms === []) {
            return [];
        }

        return annotate_extraction_field_matches_with_scope(collect_labeled_candidate_matches(
            $lines,
            $searchTerms,
            $replacementMap,
            'generic_text_segment_candidates_from_text',
            1,
            $positionSettings,
            $lineGeometries,
            $labelsAreRegex,
            $spanSettings
        ), $scopeDebug);
    }

    if (!$requiresSearchTerms) {
        if ($usesValuePattern || $valueType === 'date') {
            return annotate_extraction_field_matches_with_scope(
                extract_unlabeled_pattern_field_matches(
                    $lines,
                    $valuePattern,
                    $preferCaptureGroupValue,
                    $lineGeometries,
                    $combineSplitAmountParts,
                    $captureSelection,
                    $spanSettings,
                    $unboundedValuePatternSpan
                ),
                $scopeDebug
            );
        }
        if ($valueType !== 'amount') {
            return [];
        }
        return annotate_extraction_field_matches_with_scope(
            collect_document_candidate_matches($lines, $candidateExtractor, 0.55, $lineGeometries, $spanSettings),
            $scopeDebug
        );
    }

    if ($searchTerms === []) {
        return [];
    }

    if ($usesValuePattern || $valueType === 'date') {
        return annotate_extraction_field_matches_with_scope(collect_anchored_pattern_candidate_matches_from_segments(
            $lines,
            $searchTerms,
            $replacementMap,
            $valuePattern,
            $preferCaptureGroupValue,
            $positionSettings,
            $lineGeometries,
            $labelsAreRegex,
            $combineSplitAmountParts,
            $captureSelection,
            $spanSettings,
            $unboundedValuePatternSpan
        ), $scopeDebug);
    }

    return annotate_extraction_field_matches_with_scope(collect_anchored_candidate_matches(
        $lines,
        $searchTerms,
        $replacementMap,
        $candidateExtractor,
        $positionSettings,
        $lineGeometries,
        $labelsAreRegex,
        $spanSettings
    ), $scopeDebug);
}

function extract_configured_rule_set_field_result(
    array $lines,
    array $replacementMap,
    array $ruleSet,
    array $positionSettings = [],
    array $lineGeometries = [],
    array $field = []
): array
{
    $matches = extract_configured_rule_set_field_matches($lines, $replacementMap, $ruleSet, $positionSettings, $lineGeometries, $field);
    return is_array($matches[0] ?? null) ? $matches[0] : empty_extraction_field_result();
}

function extract_configured_text_field_results(
    array $lines,
    array $replacementMap,
    array $fields,
    array $positionSettings = [],
    array $lineGeometries = [],
    float $acceptanceThreshold = 0.5
): array
{
    $results = [];

    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }

        $key = is_string($field['key'] ?? null) ? trim((string) $field['key']) : '';
        $name = is_string($field['name'] ?? null) ? trim((string) $field['name']) : '';
        $valueType = extraction_field_value_type($field);
        $field = array_merge($field, [
            'type' => legacy_extraction_field_type_for_value_type($valueType),
            'valueType' => $valueType,
        ]);
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
        if ($extractor === 'primary_date') {
            $result = extract_primary_date_field_result(
                $lines,
                $lineGeometries,
                is_array($field['primaryDateHeuristics'] ?? null) ? $field['primaryDateHeuristics'] : [],
                $replacementMap,
                $positionSettings
            );
            $ruleSets = [];
            $matches = primary_date_result_matches($result, $lineGeometries);
        } elseif ($extractor === 'title') {
            $result = extract_title_field_result(
                $lines,
                $lineGeometries,
                is_array($field['titleHeuristics'] ?? null) ? $field['titleHeuristics'] : [],
                $positionSettings
            );
            $ruleSets = [];
            $matches = title_result_matches($result, $lineGeometries);
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
                    $lineGeometries,
                    $field
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
            $normalizationRuleSet = extraction_field_runtime_rule_set(
                $normalizationRuleSet,
                extraction_field_value_type($field),
                value_pattern_definitions_by_id(is_array($field['_valuePatterns'] ?? null) ? $field['_valuePatterns'] : [])
            );

            $resolvedValue = $match['value'] ?? null;
            $resolvedValue = resolve_extraction_field_candidate_value($resolvedValue, $match, $normalizationRuleSet, $field);

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

        $resolvedMatches = sort_extraction_field_matches_by_confidence($resolvedMatches);
        $acceptedMatches = array_filter($resolvedMatches, static fn (array $match): bool => ($match['finalConfidence'] ?? 0) >= $acceptanceThreshold);
        $resolvedValues = array_values(array_map(
            static fn (array $match): mixed => $match['value'] ?? null,
            $acceptedMatches
        ));
        $primaryMatch = is_array($acceptedMatches[0] ?? null) ? $acceptedMatches[0] : null;
        if ($extractor === 'title' && is_array($primaryMatch)) {
            $resolvedValues = [$primaryMatch['value'] ?? null];
        }
        $resolvedMatchedRuleSetIndex = $matchedRuleSetIndex;
        if (is_array($primaryMatch) && is_int($primaryMatch['ruleSetIndex'] ?? null)) {
            $resolvedMatchedRuleSetIndex = (int) $primaryMatch['ruleSetIndex'];
        }

        $results[$key] = [
            'key' => $key,
            'name' => $name,
            'type' => legacy_extraction_field_type_for_value_type($valueType),
            'valueType' => $valueType,
            'ruleSets' => $ruleSets,
            'extractor' => $extractor,
            'value' => $primaryMatch['value'] ?? null,
            'values' => $resolvedValues,
            'confidence' => is_array($primaryMatch) && isset($primaryMatch['confidence'])
                ? clamp_confidence((float) $primaryMatch['confidence'])
                : 0.0,
            'baseConfidence' => is_array($primaryMatch) && isset($primaryMatch['baseConfidence'])
                ? clamp_confidence((float) $primaryMatch['baseConfidence'])
                : (is_array($primaryMatch) && isset($primaryMatch['confidence']) ? clamp_confidence((float) $primaryMatch['confidence']) : 0.0),
            'finalConfidence' => is_array($primaryMatch) && isset($primaryMatch['finalConfidence'])
                ? clamp_confidence((float) $primaryMatch['finalConfidence'])
                : (is_array($primaryMatch) && isset($primaryMatch['confidence']) ? clamp_confidence((float) $primaryMatch['confidence']) : 0.0),
            'lineIndex' => is_array($primaryMatch) && is_int($primaryMatch['lineIndex'] ?? null) ? (int) $primaryMatch['lineIndex'] : null,
            'source' => is_array($primaryMatch) && is_string($primaryMatch['source'] ?? null) ? (string) $primaryMatch['source'] : 'none',
            'raw' => is_array($primaryMatch) && is_string($primaryMatch['raw'] ?? null) ? (string) $primaryMatch['raw'] : null,
            'matchText' => is_array($primaryMatch) && is_string($primaryMatch['matchText'] ?? null) ? (string) $primaryMatch['matchText'] : (is_array($primaryMatch) && is_string($primaryMatch['raw'] ?? null) ? (string) $primaryMatch['raw'] : null),
            'matchedRuleSetIndex' => $resolvedMatchedRuleSetIndex,
            'matches' => $resolvedMatches,
        ];
        if (is_array($result['selectedCandidate'] ?? null)) {
            $results[$key]['selectedCandidate'] = $result['selectedCandidate'];
        }
        if (is_array($result['candidates'] ?? null)) {
            $results[$key]['candidates'] = $result['candidates'];
        }
    }

    return apply_extraction_field_acceptance_threshold(
        dedupe_extraction_field_result_matches(apply_cross_matching_key_penalties($results, $positionSettings)),
        $acceptanceThreshold
    );
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

function simplify_extraction_field_meta(array $results, float $acceptanceThreshold = 0.5): array
{
    $resolvedThreshold = clamp_confidence($acceptanceThreshold);
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
        if (isset($result['confidence'])) {
            $fieldMeta['confidence'] = clamp_confidence((float) $result['confidence']);
        }
        if (isset($result['baseConfidence'])) {
            $fieldMeta['baseConfidence'] = clamp_confidence((float) $result['baseConfidence']);
        }
        if (isset($result['finalConfidence'])) {
            $fieldMeta['finalConfidence'] = clamp_confidence((float) $result['finalConfidence']);
        }
        $matchesForMeta = is_array($result['matches'] ?? null) ? $result['matches'] : null;
        $selectionRuleSet = configured_field_selection_rule_set($result);
        if (
            extraction_field_rule_set_type($selectionRuleSet, $result) === 'amount'
            && is_array($result['candidateMatches'] ?? null)
            && $result['candidateMatches'] !== []
        ) {
            $matchesForMeta = $result['candidateMatches'];
        }
        if (is_array($matchesForMeta)) {
            $fieldMeta['matches'] = array_values(array_map(
                static function (array $match) use ($resolvedThreshold): array {
                    return [
                        'value' => $match['value'] ?? null,
                        'raw' => is_string($match['raw'] ?? null) ? (string) $match['raw'] : null,
                        'matchText' => is_string($match['matchText'] ?? null) ? (string) $match['matchText'] : (is_string($match['raw'] ?? null) ? (string) $match['raw'] : null),
                        'source' => is_string($match['source'] ?? null) ? (string) $match['source'] : null,
                        'labelText' => is_string($match['labelText'] ?? null) ? trim((string) $match['labelText']) : null,
                        'between' => is_string($match['between'] ?? null) ? (string) $match['between'] : null,
                        'confidence' => isset($match['confidence']) ? clamp_confidence((float) $match['confidence']) : 0.0,
                        'baseConfidence' => isset($match['baseConfidence']) ? clamp_confidence((float) $match['baseConfidence']) : (isset($match['confidence']) ? clamp_confidence((float) $match['confidence']) : 0.0),
                        'finalConfidence' => isset($match['finalConfidence']) ? clamp_confidence((float) $match['finalConfidence']) : (isset($match['confidence']) ? clamp_confidence((float) $match['confidence']) : 0.0),
                        'lineIndex' => is_int($match['lineIndex'] ?? null) ? (int) $match['lineIndex'] : null,
                        'labelLineIndex' => is_int($match['labelLineIndex'] ?? null) ? (int) $match['labelLineIndex'] : null,
                        'start' => is_int($match['start'] ?? null) ? (int) $match['start'] : null,
                        'ruleSetIndex' => is_int($match['ruleSetIndex'] ?? null) ? (int) $match['ruleSetIndex'] : null,
                        'matchType' => is_string($match['matchType'] ?? null) ? trim((string) $match['matchType']) : null,
                        'searchTerm' => is_string($match['searchTerm'] ?? null) ? trim((string) $match['searchTerm']) : null,
                        'scopeType' => is_string($match['scopeType'] ?? null) ? trim((string) $match['scopeType']) : null,
                        'scopeText' => is_string($match['scopeText'] ?? null) ? trim((string) $match['scopeText']) : null,
                        'scopeIsRegex' => ($match['scopeIsRegex'] ?? false) === true,
                        'scopeMatchedText' => is_string($match['scopeMatchedText'] ?? null) ? trim((string) $match['scopeMatchedText']) : null,
                        'scopeLineIndex' => is_int($match['scopeLineIndex'] ?? null) ? (int) $match['scopeLineIndex'] : null,
                        'score' => is_numeric($match['score'] ?? null) ? (float) $match['score'] : null,
                        'fullConfidenceScore' => is_numeric($match['fullConfidenceScore'] ?? null) ? (float) $match['fullConfidenceScore'] : null,
                        'yRatio' => is_numeric($match['yRatio'] ?? null) ? (float) $match['yRatio'] : null,
                        'centerDistance' => is_numeric($match['centerDistance'] ?? null) ? (float) $match['centerDistance'] : null,
                        'relativeTextSize' => is_numeric($match['relativeTextSize'] ?? null) ? (float) $match['relativeTextSize'] : null,
                        'uppercaseRatio' => is_numeric($match['uppercaseRatio'] ?? null) ? (float) $match['uppercaseRatio'] : null,
                        'textDensityRatio' => is_numeric($match['textDensityRatio'] ?? null) ? (float) $match['textDensityRatio'] : null,
                        'wordCount' => is_int($match['wordCount'] ?? null) ? (int) $match['wordCount'] : null,
                        'signals' => is_array($match['signals'] ?? null) ? array_values(array_filter(
                            $match['signals'],
                            static fn($signal): bool => is_array($signal)
                        )) : [],
                        'accepted' => ($match['finalConfidence'] ?? 0) >= $resolvedThreshold,
                        'noisePenalty' => is_numeric($match['noisePenalty'] ?? null) ? clamp_confidence((float) $match['noisePenalty']) : null,
                        'trailingDelimiterPenalty' => is_numeric($match['trailingDelimiterPenalty'] ?? null) ? max(0.0, (float) $match['trailingDelimiterPenalty']) : null,
                        'otherMatchKeyPenalty' => is_numeric($match['otherMatchKeyPenalty'] ?? null) ? max(0.0, (float) $match['otherMatchKeyPenalty']) : null,
                        'positionPenalty' => is_numeric($match['positionPenalty'] ?? null) ? max(0.0, (float) $match['positionPenalty']) : (is_numeric($match['directionPenalty'] ?? null) ? max(0.0, (float) $match['directionPenalty']) : null),
                        'verticalDistancePenalty' => is_numeric($match['verticalDistancePenalty'] ?? null) ? clamp_confidence((float) $match['verticalDistancePenalty']) : null,
                        'verticalDistance' => is_numeric($match['verticalDistance'] ?? null) ? max(0.0, (float) $match['verticalDistance']) : null,
                        'verticalNormalizedDistance' => is_numeric($match['verticalNormalizedDistance'] ?? null) ? max(0.0, (float) $match['verticalNormalizedDistance']) : null,
                        'positionPenaltyAxis' => is_string($match['positionPenaltyAxis'] ?? null) ? trim((string) $match['positionPenaltyAxis']) : null,
                        'mainDirection' => is_string($match['mainDirection'] ?? null) ? trim((string) $match['mainDirection']) : null,
                        'invalidReason' => is_string($match['invalidReason'] ?? null) ? trim((string) $match['invalidReason']) : null,
                        'positionDiff' => is_numeric($match['positionDiff'] ?? null) ? (float) $match['positionDiff'] : null,
                        'positionNormalizedDiff' => is_numeric($match['positionNormalizedDiff'] ?? null) ? (float) $match['positionNormalizedDiff'] : null,
                        'labelBbox' => is_array($match['labelBbox'] ?? null) ? $match['labelBbox'] : null,
                        'valueBbox' => is_array($match['valueBbox'] ?? null) ? $match['valueBbox'] : null,
                        'valueBBoxIndexes' => is_array($match['valueBBoxIndexes'] ?? null) ? array_values(array_filter(
                            $match['valueBBoxIndexes'],
                            static fn($index): bool => is_int($index) && $index > 0
                        )) : [],
                        'pageNumber' => is_int($match['pageNumber'] ?? null) ? (int) $match['pageNumber'] : null,
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
                        'captureRanges' => is_array($match['captureRanges'] ?? null) ? $match['captureRanges'] : [],
                    ];
                },
                array_values(array_filter($matchesForMeta, static fn ($match): bool => is_array($match)))
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

function parse_filename_template_date_value(mixed $value): ?array
{
    $text = is_scalar($value) || $value === null ? trim((string) $value) : '';
    if ($text === '') {
        return null;
    }
    if (preg_match('/^(\d{4})(?:[-\/.](\d{1,2})(?:[-\/.](\d{1,2}))?)?$/u', $text, $matches) !== 1) {
        return null;
    }

    $year = (int) $matches[1];
    $month = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : null;
    $day = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : null;
    if ($year < 2000 || $year > 2099) {
        return null;
    }
    if ($month !== null && ($month < 1 || $month > 12)) {
        return null;
    }
    if ($day !== null && ($month === null || !checkdate($month, $day, $year))) {
        return null;
    }

    return [
        'year' => $year,
        'month' => $month,
        'day' => $day,
    ];
}

function format_filename_template_date_value(mixed $value, mixed $format = null): string
{
    $text = is_scalar($value) || $value === null ? trim((string) $value) : '';
    $parsed = parse_filename_template_date_value($text);
    if ($parsed === null) {
        return $text;
    }

    $resolvedFormat = normalize_filename_template_date_format($format, filename_template_date_format_default());
    $yearText = sprintf('%04d', (int) $parsed['year']);
    $month = is_int($parsed['month']) ? $parsed['month'] : null;
    $day = is_int($parsed['day']) ? $parsed['day'] : null;
    $monthText = $month !== null ? sprintf('%02d', $month) : '';
    $dayText = $day !== null ? sprintf('%02d', $day) : '';
    $shortMonths = ['JAN', 'FEB', 'MAR', 'APR', 'MAJ', 'JUN', 'JUL', 'AUG', 'SEP', 'OKT', 'NOV', 'DEC'];
    $longMonths = ['januari', 'februari', 'mars', 'april', 'maj', 'juni', 'juli', 'augusti', 'september', 'oktober', 'november', 'december'];

    if ($resolvedFormat === 'YYYY') {
        return $yearText;
    }
    if ($resolvedFormat === 'YYYY-MM') {
        return $month !== null ? ($yearText . '-' . $monthText) : $yearText;
    }
    if ($resolvedFormat === 'MMM-YYYY') {
        return $month !== null ? ($shortMonths[$month - 1] . '-' . $yearText) : $yearText;
    }
    if ($resolvedFormat === 'MMMM YYYY') {
        return $month !== null ? ($longMonths[$month - 1] . ' ' . $yearText) : $yearText;
    }
    if ($resolvedFormat === 'DD MMM YYYY') {
        return $month !== null && $day !== null ? ($dayText . ' ' . $shortMonths[$month - 1] . ' ' . $yearText) : $text;
    }
    if ($month === null) {
        return $yearText;
    }
    return $day !== null ? ($yearText . '-' . $monthText . '-' . $dayText) : ($yearText . '-' . $monthText);
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
            $fieldTypes = is_array($fieldValues['__fieldTypes'] ?? null) ? $fieldValues['__fieldTypes'] : [];
            $valueType = is_string($fieldTypes[$key] ?? null) ? trim((string) $fieldTypes[$key]) : '';
            if ($value !== '' && $valueType === 'date') {
                $value = format_filename_template_date_value($value, $part['dateFormat'] ?? null);
            }
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

        if ($type === 'ifLabels') {
            $selectedLabelIds = array_values(array_filter(array_map(
                static fn ($value): string => is_scalar($value) ? trim((string) $value) : '',
                is_array($fieldValues['__labelIds'] ?? null) ? $fieldValues['__labelIds'] : []
            ), static fn (string $value): bool => $value !== ''));
            $selectedLabelIds = array_values(array_unique($selectedLabelIds));
            $conditionLabelIds = normalize_label_id_list($part['labelIds'] ?? null);
            $mode = normalize_if_labels_mode($part['mode'] ?? null);
            $conditionMatched = $conditionLabelIds !== []
                && ($mode === 'all'
                    ? count(array_diff($conditionLabelIds, $selectedLabelIds)) === 0
                    : count(array_intersect($conditionLabelIds, $selectedLabelIds)) > 0);
            $branchParts = $conditionMatched
                ? (is_array($part['thenParts'] ?? null) ? $part['thenParts'] : [])
                : (is_array($part['elseParts'] ?? null) ? $part['elseParts'] : []);
            $resolved = evaluate_filename_template_parts_backend($branchParts, $fieldValues);
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

function auto_archiving_field_string_values(array $fields, string $key): array
{
    return array_values(array_filter(array_map(
        static fn ($value): string => is_scalar($value) ? trim((string) $value) : '',
        normalize_auto_archiving_field_value_list($fields[$key] ?? null)
    ), static fn (string $value): bool => $value !== ''));
}

function auto_archiving_sender_payment_name(?array $sender, array $allFields, string $type): ?string
{
    $values = auto_archiving_field_string_values($allFields, $type === 'plusgiro' ? 'plusgiro' : 'bankgiro');
    if ($values === []) {
        return null;
    }

    foreach ($values as $value) {
        $observed = observed_sender_payment_summary_row($type, $value);
        $observedName = is_string($observed['payeeName'] ?? null) ? trim((string) $observed['payeeName']) : '';
        if ($observedName !== '') {
            return $observedName;
        }
    }

    $paymentRows = is_array($sender['paymentNumbers'] ?? null) ? $sender['paymentNumbers'] : [];
    foreach ($values as $value) {
        $normalizedValue = $type === 'plusgiro'
            ? \Docflow\Senders\IdentifierNormalizer::normalizePlusgiro($value)
            : \Docflow\Senders\IdentifierNormalizer::normalizeBankgiro($value);
        if ($normalizedValue === null) {
            $normalizedValue = preg_replace('/\D+/', '', $value);
        }
        if ($normalizedValue === null) {
            continue;
        }

        foreach ($paymentRows as $paymentRow) {
            if (!is_array($paymentRow)) {
                continue;
            }
            $paymentType = is_string($paymentRow['type'] ?? null) ? trim(strtolower((string) $paymentRow['type'])) : 'bankgiro';
            if ($paymentType !== $type) {
                continue;
            }
            $normalizedRowValue = $paymentType === 'plusgiro'
                ? \Docflow\Senders\IdentifierNormalizer::normalizePlusgiro((string) ($paymentRow['number'] ?? ''))
                : \Docflow\Senders\IdentifierNormalizer::normalizeBankgiro((string) ($paymentRow['number'] ?? ''));
            if ($normalizedRowValue === null) {
                $normalizedRowValue = preg_replace('/\D+/', '', (string) ($paymentRow['number'] ?? ''));
            }
            $payeeName = is_string($paymentRow['payeeName'] ?? null) ? trim((string) $paymentRow['payeeName']) : '';
            if ($normalizedRowValue !== null && $normalizedRowValue === $normalizedValue && $payeeName !== '') {
                return $payeeName;
            }
        }
    }

    return null;
}

function auto_archiving_sender_organization_name(?array $sender, array $allFields): ?string
{
    $values = auto_archiving_field_string_values($allFields, 'organisationsnummer');
    if ($values === []) {
        return null;
    }

    foreach ($values as $value) {
        $observed = observed_sender_organization_summary_row($value);
        $observedName = is_string($observed['organizationName'] ?? null) ? trim((string) $observed['organizationName']) : '';
        if ($observedName !== '') {
            return $observedName;
        }
    }

    $organizationRows = is_array($sender['organizationNumbers'] ?? null) ? $sender['organizationNumbers'] : [];
    foreach ($values as $value) {
        $normalizedValue = \Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber($value);
        if ($normalizedValue === null) {
            $normalizedValue = preg_replace('/\D+/', '', $value);
        }
        if ($normalizedValue === null) {
            continue;
        }

        foreach ($organizationRows as $organizationRow) {
            if (!is_array($organizationRow)) {
                continue;
            }
            $normalizedRowValue = \Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber((string) ($organizationRow['organizationNumber'] ?? ''));
            if ($normalizedRowValue === null) {
                $normalizedRowValue = preg_replace('/\D+/', '', (string) ($organizationRow['organizationNumber'] ?? ''));
            }
            $organizationName = is_string($organizationRow['organizationName'] ?? null) ? trim((string) $organizationRow['organizationName']) : '';
            if ($normalizedRowValue !== null && $normalizedRowValue === $normalizedValue && $organizationName !== '') {
                return $organizationName;
            }
        }
    }

    return null;
}

function build_auto_archiving_filename_field_values(array $autoResult, array $senders, array $foldersById, array $rules): array
{
    $values = [];
    $fieldTypesByKey = [];
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

    foreach (['predefinedFields', 'fields', 'systemFields'] as $groupKey) {
        foreach (is_array($rules[$groupKey] ?? null) ? $rules[$groupKey] : [] as $fieldDefinition) {
            if (!is_array($fieldDefinition)) {
                continue;
            }
            $fieldKey = is_string($fieldDefinition['key'] ?? null) ? trim((string) $fieldDefinition['key']) : '';
            if ($fieldKey === '') {
                continue;
            }
            $fieldTypesByKey[$fieldKey] = legacy_extraction_field_type_for_value_type(extraction_field_value_type($fieldDefinition));
        }
    }
    if (!isset($fieldTypesByKey['date']) && isset($fieldTypesByKey['due_date'])) {
        $fieldTypesByKey['date'] = $fieldTypesByKey['due_date'];
    }

    $folder = $folderId !== '' ? ($foldersById[$folderId] ?? null) : null;
    $sender = $senderId > 0 ? find_sender_by_id($senders, $senderId) : null;

    $setValue($values, 'folder', is_array($folder) ? archive_folder_display_text($folder) : null);
    $setValue($values, 'client', $clientId);
    $setValue($values, 'main_client', $clientId);
    $setValue($values, 'sender', is_array($sender) ? ($sender['name'] ?? null) : null);
    $setValue($values, 'bankgiro_name', auto_archiving_sender_payment_name($sender, $allFields, 'bankgiro'));
    $setValue($values, 'plusgiro_name', auto_archiving_sender_payment_name($sender, $allFields, 'plusgiro'));
    $setValue($values, 'organization_number_name', auto_archiving_sender_organization_name($sender, $allFields));

    if (array_key_exists('amount', $allFields)) {
        $amount = first_auto_archiving_field_value($allFields['amount']);
        if (is_numeric($amount)) {
            $setValue($values, 'amount', number_format((float) $amount, 2, ',', ''));
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
        'primary_date',
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
        $resolvedValue = first_auto_archiving_field_value($fieldValue);
        if (($fieldTypesByKey[$resolvedKey] ?? '') === 'amount' && is_numeric($resolvedValue)) {
            $resolvedValue = number_format((float) $resolvedValue, 2, ',', '');
        }
        $setValue($values, $resolvedKey, $resolvedValue);
    }

    $labelNames = build_auto_archiving_filename_label_names($autoResult, $rules);
    if ($labelNames !== []) {
        $values['__labels'] = $labelNames;
    }
    $labelIds = array_values(array_filter(array_map(
        static fn ($value): string => is_string($value) ? trim((string) $value) : '',
        is_array($autoResult['labels'] ?? null) ? $autoResult['labels'] : []
    ), static fn (string $value): bool => $value !== ''));
    if ($labelIds !== []) {
        $values['__labelIds'] = array_values(array_unique($labelIds));
    }
    if ($fieldTypesByKey !== []) {
        $values['__fieldTypes'] = $fieldTypesByKey;
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

function job_live_auto_archiving_result(array $job): array
{
    $autoResult = job_auto_archiving_result($job);
    if (($job['archived'] ?? false) === true) {
        return $autoResult;
    }

    $rules = load_active_archiving_rules();
    $senders = load_senders();
    $autoResult['filename'] = generate_auto_archiving_filename($job, $autoResult, $rules, $senders);
    return $autoResult;
}

function normalize_auto_archiving_result_scalar_value(mixed $value): string
{
    if (is_string($value)) {
        return trim($value);
    }

    if (is_int($value) || is_float($value)) {
        return trim((string) $value);
    }

    return '';
}

function normalize_auto_archiving_result_sender_value(mixed $value): ?int
{
    $senderId = is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);
    return $senderId > 0 ? $senderId : null;
}

function job_analysis_payload(array $job): array
{
    $analysis = is_array($job['analysis'] ?? null) ? $job['analysis'] : [];
    $jobId = is_string($job['id'] ?? null) ? trim((string) $job['id']) : '';
    $stored = $jobId !== '' ? job_analysis_snapshot($jobId) : null;
    $autoResult = job_live_auto_archiving_result($job);

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
    $positionSettings = matching_position_settings_from_payload($resolvedMatchingPayload);
    $lineGeometries = build_matching_line_geometries_for_job($job, $ocrText);
    $valuePatterns = is_array($rules['valuePatterns'] ?? null) ? $rules['valuePatterns'] : [];
    $zoneMatches = detect_configured_zone_matches(
        split_lines_for_matching($ocrText),
        is_array($rules['zones'] ?? null) ? $rules['zones'] : [],
        $replacementMap,
        $lineGeometries,
        $valuePatterns
    );
    $positionSettings['zoneMatches'] = $zoneMatches;
    $systemLabels = is_array($rules['systemLabels'] ?? null) ? $rules['systemLabels'] : [];
    $labels = is_array($rules['labels'] ?? null) ? $rules['labels'] : [];
    $archiveFolders = is_array($rules['archiveFolders'] ?? null) ? $rules['archiveFolders'] : [];
    $configuredFields = array_values(array_merge(
        is_array($rules['predefinedFields'] ?? null) ? $rules['predefinedFields'] : [],
        is_array($rules['systemFields'] ?? null) ? $rules['systemFields'] : [],
        is_array($rules['fields'] ?? null) ? $rules['fields'] : []
    ));
    $configuredFields = array_map(static function (array $field) use ($valuePatterns): array {
        $field['_valuePatterns'] = $valuePatterns;
        return $field;
    }, $configuredFields);

    $configuredFieldResults = extract_configured_text_field_results(
        split_lines_for_matching($ocrText),
        $replacementMap,
        $configuredFields,
        $positionSettings,
        $lineGeometries,
        is_numeric($matchingPayload['dataFieldAcceptanceThreshold'] ?? null)
            ? (float) $matchingPayload['dataFieldAcceptanceThreshold']
            : 0.5
    );
    $configuredFieldValues = simplify_extraction_field_values($configuredFieldResults);
    $configuredFieldMeta = simplify_extraction_field_meta($configuredFieldResults, is_numeric($matchingPayload['dataFieldAcceptanceThreshold'] ?? null)
        ? (float) $matchingPayload['dataFieldAcceptanceThreshold']
        : 0.5);
    $fieldPartitions = partition_archiving_field_values($configuredFieldValues, $rules);
    $fieldNamesByKey = build_label_matching_field_name_map($configuredFields);

    $clientMatches = find_client_matches($ocrText, $clients);
    $firstClientMatch = is_array($clientMatches[0] ?? null) ? $clientMatches[0] : null;
    $matchedClientDirName = is_string($firstClientMatch['dirName'] ?? null)
        ? trim((string) $firstClientMatch['dirName'])
        : null;
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
        'clientMatches' => $clientMatches,
        'preselectedClient' => $preselectedClient,
        'preselectedSender' => $preselectedSender,
        'systemLabelMatches' => $systemLabelMatches,
        'labelMatches' => $labelMatches,
        'labels' => $resolvedLabels,
        'extractionFieldResults' => $configuredFieldResults,
        'extractionFieldValues' => $configuredFieldValues,
        'extractionFieldMeta' => $configuredFieldMeta,
        'zoneMatches' => $zoneMatches,
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

function load_job_analysis_text(string $jobDir, ?string $pdfPath = null): string
{
    $storedText = load_job_ocr_text($jobDir);
    if (trim($storedText) !== '') {
        return $storedText;
    }

    $resolvedPdfPath = is_string($pdfPath) ? trim($pdfPath) : '';
    if ($resolvedPdfPath !== '' && is_file($resolvedPdfPath)) {
        $pdfText = extract_text_from_pdf($resolvedPdfPath);
        if (is_string($pdfText) && trim($pdfText) !== '') {
            return $pdfText;
        }
    }

    return '';
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

    $analysisPdfPath = job_review_pdf_path($config, $jobId, $loadedJob);
    $ocrText = load_job_analysis_text($jobDir, $analysisPdfPath);
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
    $jobId = is_string($job['id'] ?? null) ? trim((string) $job['id']) : '';
    $storedDocumentLabelIds = $jobId !== '' ? document_metadata_label_ids($jobId) : null;
    $storedDocumentDataValues = $jobId !== '' ? document_metadata_actual_data_values($jobId) : null;
    if (is_array($storedDocumentDataValues)) {
        $normalized = auto_archiving_result_with_document_actual_data_values($normalized, $storedDocumentDataValues);
    } elseif (array_key_exists('selectedExtractionFieldValues', $job)) {
        $normalized = auto_archiving_result_with_document_actual_data_values(
            $normalized,
            document_actual_data_values_from_selected($job, normalize_stored_job_extraction_field_values($job['selectedExtractionFieldValues']))
        );
    }

    $selectedClientDirName = is_string($job['selectedClientDirName'] ?? null) ? trim((string) $job['selectedClientDirName']) : '';
    $selectedSenderId = resolve_active_sender_id(isset($job['selectedSenderId']) ? (int) $job['selectedSenderId'] : 0);
    $selectedFolderId = is_string($job['selectedFolderId'] ?? null) ? trim((string) $job['selectedFolderId']) : '';
    $selectedLabelIds = is_array($storedDocumentLabelIds)
        ? $storedDocumentLabelIds
        : (array_key_exists('selectedLabelIds', $job)
        ? normalize_stored_job_label_ids($job['selectedLabelIds'])
        : null);
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
    $jobId = is_string($job['id'] ?? null) ? trim((string) $job['id']) : '';
    $storedDocumentDataValues = $jobId !== '' ? document_metadata_actual_data_values($jobId) : null;
    if (is_array($storedDocumentDataValues)) {
        $approved = auto_archiving_result_with_document_actual_data_values($approved, $storedDocumentDataValues);
    } elseif (array_key_exists('selectedExtractionFieldValues', $job)) {
        $approved = auto_archiving_result_with_document_actual_data_values(
            $approved,
            document_actual_data_values_from_selected($job, normalize_stored_job_extraction_field_values($job['selectedExtractionFieldValues']))
        );
    }
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
            'messagePrefix' => 'Etikett tillagd:',
            'labelName' => $labelName,
            'metaText' => '',
            'detail' => '',
        ];
    }

    if (!$draftHasLabel && $activeHasLabel) {
        return [
            'type' => $approvedHasLabel ? 'risk' : 'improvement',
            'field' => 'labels',
            'message' => 'Etikett borttagen: ' . $labelName,
            'messagePrefix' => 'Etikett borttagen:',
            'labelName' => $labelName,
            'metaText' => $approvedHasLabel ? 'tidigare godkänd' : '',
            'detail' => '',
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

function classify_archiving_rule_change(array $approved, array $historicalResult, array $currentResult, array $displayMaps = []): array
{
    $historicalDiff = archiving_review_result_diff($approved, $historicalResult);
    $currentDiff = archiving_review_result_diff($approved, $currentResult);
    $currentVsHistoricalDiff = archiving_review_result_diff($historicalResult, $currentResult);
    $changeItems = archiving_review_change_items($approved, $historicalResult, $currentResult, $displayMaps);

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
        'type' => (($currentVsHistoricalDiff['changed'] ?? false) === true) ? $type : 'unchanged',
        'historicalDiff' => $historicalDiff,
        'currentDiff' => $currentDiff,
        'currentVsHistoricalDiff' => $currentVsHistoricalDiff,
        'changeItems' => $changeItems,
    ];
}

function archived_job_review_payload(array $config, string $jobId, ?array $job = null, ?array $activeRules = null): array
{
    $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
    $loadedJob = is_array($job) ? $job : load_json_file($jobDir . '/job.json');
    if (!is_array($loadedJob)) {
        throw new RuntimeException('Jobbet kunde inte läsas');
    }

    $resolvedActiveRules = $activeRules ?? load_active_archiving_rules();
    $activeVersion = active_archiving_rules_version();
    $snapshot = job_archiving_snapshot($loadedJob);
    $historicalResult = archived_job_auto_result_at_approval($jobId, $loadedJob);
    $currentResult = archived_job_active_result($config, $jobId, $loadedJob, $resolvedActiveRules, $activeVersion);
    $displayMaps = archiving_review_display_maps($resolvedActiveRules, $resolvedActiveRules);
    $archivedApproved = is_array($snapshot['userApproved'] ?? null)
        ? normalize_auto_archiving_result($snapshot['userApproved'])
        : current_approved_archiving_for_job($loadedJob);
    $currentApproved = current_approved_archiving_for_job($loadedJob);
    $classification = classify_archiving_rule_change(
        $archivedApproved,
        $historicalResult,
        $currentResult,
        $displayMaps
    );

    return [
        'jobId' => $jobId,
        'originalFilename' => is_string($loadedJob['originalFilename'] ?? null) ? (string) $loadedJob['originalFilename'] : $jobId,
        'archivedVersion' => job_archived_version($loadedJob),
        'dismissedAnalysisVersion' => job_dismissed_analysis_version($loadedJob),
        'archivedValue' => $archivedApproved,
        'currentApprovedValue' => $currentApproved,
        'historicalAutoResult' => $historicalResult,
        'currentAutoResult' => $currentResult,
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
        'isActionable' => (($classification['currentVsHistoricalDiff']['changed'] ?? false) === true),
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
        'archivedJobs' => 0,
        'testedJobs' => 0,
        'affected' => 0,
        'dismissed' => 0,
        'unchanged' => 0,
        'improvements' => 0,
        'risks' => 0,
        'info' => 0,
    ];
}

function empty_archiving_update_session(): array
{
    return [
        'status' => 'idle',
        'ignoreDismissed' => false,
        'activeVersion' => 0,
        'activeRulesHash' => '',
        'jobIdsHash' => '',
        'jobIds' => [],
        'pendingJobIds' => [],
        'nextIndex' => 0,
        'analyzedCount' => 0,
        'totalCount' => 0,
        'affectedJobIds' => [],
        'summary' => empty_archiving_review_summary(),
        'processedJobs' => [],
        'changedSections' => [],
        'templateChanges' => [],
        'reason' => '',
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
    $updateIn = is_array($decoded['updateSession'] ?? null)
        ? $decoded['updateSession']
        : (is_array($decoded['draftSession'] ?? null) ? $decoded['draftSession'] : []);

    $update = array_merge(empty_archiving_update_session(), $updateIn);
    $update['status'] = normalize_archiving_review_session_status($update['status'] ?? null);
    $update['ignoreDismissed'] = ($update['ignoreDismissed'] ?? false) === true;
    $update['activeVersion'] = max(0, (int) ($update['activeVersion'] ?? 0));
    $update['activeRulesHash'] = is_string($update['activeRulesHash'] ?? null) ? trim((string) $update['activeRulesHash']) : '';
    $update['jobIdsHash'] = is_string($update['jobIdsHash'] ?? null) ? trim((string) $update['jobIdsHash']) : '';
    $update['jobIds'] = array_values(array_filter(
        array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($update['jobIds'] ?? null) ? $update['jobIds'] : []),
        static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
    ));
    $update['pendingJobIds'] = array_values(array_unique(array_filter(
        array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($update['pendingJobIds'] ?? null) ? $update['pendingJobIds'] : []),
        static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
    )));
    $update['nextIndex'] = max(0, (int) ($update['nextIndex'] ?? 0));
    $update['analyzedCount'] = max(0, (int) ($update['analyzedCount'] ?? 0));
    $update['totalCount'] = max(0, (int) ($update['totalCount'] ?? 0));
    $update['affectedJobIds'] = array_values(array_unique(array_filter(
        array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($update['affectedJobIds'] ?? null) ? $update['affectedJobIds'] : []),
        static fn (string $jobId): bool => $jobId !== '' && is_valid_job_id($jobId)
    )));
    $update['summary'] = array_merge(empty_archiving_review_summary(), is_array($update['summary'] ?? null) ? $update['summary'] : []);
    $update['processedJobs'] = is_array($update['processedJobs'] ?? null) ? $update['processedJobs'] : [];
    $update['changedSections'] = array_values(array_filter(
        array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($update['changedSections'] ?? null) ? $update['changedSections'] : []),
        static fn (string $value): bool => $value !== ''
    ));
    $update['templateChanges'] = array_values(array_filter(
        is_array($update['templateChanges'] ?? null) ? $update['templateChanges'] : [],
        static fn ($value): bool => is_array($value)
    ));
    $update['reason'] = is_string($update['reason'] ?? null) ? trim((string) $update['reason']) : '';
    $update['startedAt'] = is_string($update['startedAt'] ?? null) ? $update['startedAt'] : null;
    $update['updatedAt'] = is_string($update['updatedAt'] ?? null) ? $update['updatedAt'] : null;
    $lastStateEventHash = is_string($decoded['lastStateEventHash'] ?? null) ? trim((string) $decoded['lastStateEventHash']) : '';

    return [
        'updateSession' => $update,
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

function archiving_review_summary_from_processed_jobs(array $processedJobs, bool $ignoreDismissed = false): array
{
    $summary = empty_archiving_review_summary();
    foreach ($processedJobs as $item) {
        if (!is_array($item)) {
            continue;
        }
        $summary['archivedJobs'] = ((int) ($summary['archivedJobs'] ?? 0)) + 1;
        $type = is_string($item['classification']['type'] ?? null) ? (string) $item['classification']['type'] : 'unchanged';
        update_archiving_review_summary($summary, $type);
        if ($type !== 'unchanged') {
            $dismissedForVersion = ($item['dismissedForVersion'] ?? false) === true;
            if ($dismissedForVersion) {
                $summary['dismissed'] = ((int) ($summary['dismissed'] ?? 0)) + 1;
            }
            if (!$dismissedForVersion || $ignoreDismissed) {
                $summary['affected'] = ((int) ($summary['affected'] ?? 0)) + 1;
            }
        }
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
    $session['summary'] = archiving_review_summary_from_processed_jobs($processedJobs, false);
    $session['status'] = 'running';
    $session['ignoreDismissed'] = false;
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
    $session['summary'] = archiving_review_summary_from_processed_jobs($processedJobs, false);
    $session['totalCount'] = count(is_array($session['jobIds'] ?? null) ? $session['jobIds'] : []);
    $session['analyzedCount'] = count($processedJobs);
    $session['nextIndex'] = min(max(0, (int) ($session['nextIndex'] ?? 0)), $session['totalCount']);
    $session['ignoreDismissed'] = false;
    $session['updatedAt'] = now_iso();
}

function archived_job_auto_result_at_approval(string $jobId, array $job): array
{
    $snapshot = job_archiving_snapshot($job);
    if (is_array($snapshot['autoDetectedAtApproval'] ?? null)) {
        return normalize_auto_archiving_result($snapshot['autoDetectedAtApproval']);
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

function initialize_archiving_update_session(
    array $config,
    int $activeVersion,
    array $activeRules,
    array $metadata = []
): array {
    $jobIds = archived_job_ids($config);
    $now = now_iso();

    return [
        'status' => count($jobIds) === 0 ? 'complete' : 'running',
        'ignoreDismissed' => false,
        'activeVersion' => $activeVersion,
        'activeRulesHash' => archiving_rules_review_relevant_hash($activeRules),
        'jobIdsHash' => archiving_rules_review_job_ids_hash($jobIds),
        'jobIds' => $jobIds,
        'pendingJobIds' => [],
        'nextIndex' => 0,
        'analyzedCount' => 0,
        'totalCount' => count($jobIds),
        'affectedJobIds' => [],
        'summary' => empty_archiving_review_summary(),
        'processedJobs' => [],
        'changedSections' => array_values(array_filter(
            array_map(static fn ($value): string => is_string($value) ? trim($value) : '', is_array($metadata['changedSections'] ?? null) ? $metadata['changedSections'] : []),
            static fn (string $value): bool => $value !== ''
        )),
        'templateChanges' => array_values(array_filter(
            is_array($metadata['templateChanges'] ?? null) ? $metadata['templateChanges'] : [],
            static fn ($value): bool => is_array($value)
        )),
        'reason' => is_string($metadata['reason'] ?? null) ? trim((string) $metadata['reason']) : '',
        'startedAt' => $now,
        'updatedAt' => $now,
    ];
}

function archiving_update_session_is_current(array $session, int $activeVersion, array $activeRules, array $jobIds): bool
{
    if ((int) ($session['activeVersion'] ?? 0) !== $activeVersion) {
        return false;
    }
    if ((string) ($session['activeRulesHash'] ?? '') !== archiving_rules_review_relevant_hash($activeRules)) {
        return false;
    }
    return (string) ($session['jobIdsHash'] ?? '') === archiving_rules_review_job_ids_hash($jobIds);
}

function archiving_update_review_response_from_session(int $activeVersion, array $session): array
{
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
    $summary = archiving_review_summary_from_processed_jobs($processedJobs, false);

    return [
        'activeArchivingRulesVersion' => $activeVersion,
        'changedSections' => array_values(array_filter(
            array_map(static fn ($value): string => is_string($value) ? trim((string) $value) : '', is_array($session['changedSections'] ?? null) ? $session['changedSections'] : []),
            static fn (string $value): bool => $value !== ''
        )),
        'templateChanges' => array_values(array_filter(
            is_array($session['templateChanges'] ?? null) ? $session['templateChanges'] : [],
            static fn ($value): bool => is_array($value)
        )),
        'summary' => $summary,
        'jobs' => sort_archiving_review_items($changedItems),
        'session' => [
            'status' => is_string($session['status'] ?? null) ? (string) $session['status'] : 'idle',
            'ignoreDismissed' => false,
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
        'reason' => is_string($session['reason'] ?? null) ? (string) $session['reason'] : '',
    ];
}

function current_archiving_update_session(array $config): array
{
    $state = load_archiving_rules_review_state();
    $session = is_array($state['updateSession'] ?? null) ? $state['updateSession'] : empty_archiving_update_session();
    $activeRules = load_active_archiving_rules();
    $activeVersion = active_archiving_rules_version();
    $jobIds = archived_job_ids($config);
    if (!archiving_update_session_is_current($session, $activeVersion, $activeRules, $jobIds)) {
        return empty_archiving_update_session();
    }
    return $session;
}

function set_archiving_update_session_ignore_dismissed(array $config, bool $ignoreDismissed): array
{
    return with_archiving_rules_review_lock(static function () use ($config): array {
        $state = load_archiving_rules_review_state();
        $session = is_array($state['updateSession'] ?? null) ? $state['updateSession'] : empty_archiving_update_session();
        $activeRules = load_active_archiving_rules();
        $activeVersion = active_archiving_rules_version();
        $jobIds = archived_job_ids($config);

        if (!archiving_update_session_is_current($session, $activeVersion, $activeRules, $jobIds)) {
            $session = initialize_archiving_update_session($config, $activeVersion, $activeRules, [
                'reason' => is_string($session['reason'] ?? null) ? $session['reason'] : '',
                'changedSections' => is_array($session['changedSections'] ?? null) ? $session['changedSections'] : [],
                'templateChanges' => is_array($session['templateChanges'] ?? null) ? $session['templateChanges'] : [],
            ]);
        } else {
            $session['ignoreDismissed'] = false;
            $session['status'] = 'running';
            $session['updatedAt'] = now_iso();
            $session['pendingJobIds'] = array_values(array_unique(array_merge(
                is_array($session['pendingJobIds'] ?? null) ? $session['pendingJobIds'] : [],
                is_array($session['jobIds'] ?? null) ? $session['jobIds'] : []
            )));
            $session['processedJobs'] = [];
            $session['affectedJobIds'] = [];
            $session['analyzedCount'] = 0;
            $session['nextIndex'] = 0;
            $session['summary'] = empty_archiving_review_summary();
        }

        $state['updateSession'] = $session;
        save_archiving_rules_review_state($state);
        return $session;
    });
}

function restart_archiving_update_session(
    array $config,
    array $previousRules,
    array $activeRules,
    int $activeVersion,
    array $metadata = []
): array {
    return with_archiving_rules_review_lock(static function () use ($config, $activeRules, $activeVersion, $metadata): array {
        $state = load_archiving_rules_review_state();
        $state['updateSession'] = initialize_archiving_update_session($config, $activeVersion, $activeRules, $metadata);
        save_archiving_rules_review_state($state);
        return is_array($state['updateSession'] ?? null) ? $state['updateSession'] : empty_archiving_update_session();
    });
}

function collect_archiving_update_review(array $config, int $chunkSize = 20): array
{
    return with_archiving_rules_review_lock(static function () use ($config, $chunkSize): array {
        $state = load_archiving_rules_review_state();
        $activeRules = load_active_archiving_rules();
        $activeVersion = active_archiving_rules_version();
        $jobIds = archived_job_ids($config);

        $session = is_array($state['updateSession'] ?? null) ? $state['updateSession'] : empty_archiving_update_session();
        if (!archiving_update_session_is_current($session, $activeVersion, $activeRules, $jobIds)) {
            $session = initialize_archiving_update_session($config, $activeVersion, $activeRules, [
                'reason' => is_string($session['reason'] ?? null) ? $session['reason'] : '',
                'changedSections' => is_array($session['changedSections'] ?? null) ? $session['changedSections'] : [],
                'templateChanges' => is_array($session['templateChanges'] ?? null) ? $session['templateChanges'] : [],
            ]);
        }
        archiving_review_session_sync_job_ids($session, $jobIds);
        $ignoreDismissed = false;
        $session['ignoreDismissed'] = false;

        $processedJobs = is_array($session['processedJobs'] ?? null) ? $session['processedJobs'] : [];
        $affectedJobIds = is_array($session['affectedJobIds'] ?? null) ? $session['affectedJobIds'] : [];
        $pendingJobIds = array_values(array_filter(
            is_array($session['pendingJobIds'] ?? null) ? $session['pendingJobIds'] : [],
            static fn ($value): bool => is_string($value) && $value !== ''
        ));
        $nextIndex = max(0, (int) ($session['nextIndex'] ?? 0));
        $totalCount = count($jobIds);
        $processed = 0;
        $displayMaps = archiving_review_display_maps($activeRules, $activeRules);

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
                $affectedJobIds = array_values(array_diff($affectedJobIds, [$jobId]));
                continue;
            }

            $approved = current_approved_archiving_for_job($job);
            $historicalResult = archived_job_auto_result_at_approval($jobId, $job);
            $currentResult = archived_job_active_result($config, $jobId, $job, $activeRules, $activeVersion);
            $item = archiving_update_review_item($jobId, $job, $approved, $historicalResult, $currentResult, $displayMaps, $activeVersion);
            $processedJobs[$jobId] = $item;
            $classificationType = is_string($item['classification']['type'] ?? null) ? (string) $item['classification']['type'] : 'unchanged';
            if ($classificationType === 'unchanged' || (($item['dismissedForVersion'] ?? false) === true)) {
                $affectedJobIds = array_values(array_diff($affectedJobIds, [$jobId]));
            } elseif (!in_array($jobId, $affectedJobIds, true)) {
                $affectedJobIds[] = $jobId;
            }
        }

        $session['processedJobs'] = $processedJobs;
        $session['affectedJobIds'] = array_values(array_unique($affectedJobIds));
        $session['pendingJobIds'] = array_values(array_unique($pendingJobIds));
        $session['nextIndex'] = $nextIndex;
        $session['analyzedCount'] = count($processedJobs);
        $session['totalCount'] = $totalCount;
        $session['summary'] = archiving_review_summary_from_processed_jobs($processedJobs, false);
        $session['updatedAt'] = now_iso();
        $session['status'] = ($nextIndex >= $totalCount && $session['pendingJobIds'] === []) ? 'complete' : 'running';
        $state['updateSession'] = $session;
        save_archiving_rules_review_state($state);

        $payload = archiving_update_review_response_from_session($activeVersion, $session);
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
            'changedSections' => is_array($payload['changedSections'] ?? null) ? $payload['changedSections'] : [],
            'templateChanges' => is_array($payload['templateChanges'] ?? null) ? $payload['templateChanges'] : [],
            'reason' => is_string($payload['reason'] ?? null) ? $payload['reason'] : '',
        ];
    });
}

function advance_archiving_update_session(array $config, int $chunkSize = 10): array
{
    $session = current_archiving_update_session($config);
    if (($session['status'] ?? 'idle') === 'idle') {
        return $session;
    }
    if (($session['status'] ?? 'idle') === 'complete' && empty($session['pendingJobIds'])) {
        return $session;
    }
    collect_archiving_update_review($config, $chunkSize);
    return current_archiving_update_session($config);
}

function invalidate_archiving_update_job(array $config, string $jobId, ?bool $isArchived = null): void
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
        $session = is_array($state['updateSession'] ?? null) ? $state['updateSession'] : empty_archiving_update_session();
        $activeRules = load_active_archiving_rules();
        $activeVersion = active_archiving_rules_version();
        $jobIds = archived_job_ids($config);
        if (!archiving_update_session_is_current($session, $activeVersion, $activeRules, $jobIds)) {
            return;
        }

        if ($resolvedArchived) {
            archiving_review_session_enqueue_job($session, $jobId);
            $session['affectedJobIds'] = array_values(array_filter(
                is_array($session['affectedJobIds'] ?? null) ? $session['affectedJobIds'] : [],
                static fn ($value): bool => is_string($value) && $value !== $jobId
            ));
        } else {
            archiving_review_session_remove_job($session, $jobId);
        }
        $state['updateSession'] = $session;
        save_archiving_rules_review_state($state);
    });
}

function invalidate_archiving_review_job(array $config, string $jobId, ?bool $isArchived = null): void
{
    invalidate_archiving_update_job($config, $jobId, $isArchived);
}

function sync_archiving_update_session_after_job_removal(array $config, string $jobId): void
{
    if (!is_valid_job_id($jobId)) {
        return;
    }

    with_archiving_rules_review_lock(static function () use ($config, $jobId): void {
        $state = load_archiving_rules_review_state();
        $session = is_array($state['updateSession'] ?? null) ? $state['updateSession'] : empty_archiving_update_session();
        $jobIds = archived_job_ids($config);
        archiving_review_session_sync_job_ids($session, $jobIds);
        archiving_review_session_remove_job($session, $jobId);
        $state['updateSession'] = $session;
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

function archiving_update_review_item(
    string $jobId,
    array $job,
    array $approved,
    array $historicalResult,
    array $currentResult,
    array $displayMaps,
    int $activeVersion
): array {
    $dismissedAnalysisVersion = job_dismissed_analysis_version($job);
    return [
        'jobId' => $jobId,
        'originalFilename' => is_string($job['originalFilename'] ?? null) ? (string) $job['originalFilename'] : $jobId,
        'archivedVersion' => job_archived_version($job),
        'dismissedAnalysisVersion' => $dismissedAnalysisVersion,
        'dismissedForVersion' => $dismissedAnalysisVersion > 0 && $dismissedAnalysisVersion === $activeVersion,
        'archivedApproved' => normalize_auto_archiving_result($approved),
        'historicalAutoResult' => normalize_auto_archiving_result($historicalResult),
        'currentAutoResult' => normalize_auto_archiving_result($currentResult),
        'classification' => classify_archiving_rule_change($approved, $historicalResult, $currentResult, $displayMaps),
    ];
}

function update_archiving_review_summary(array &$summary, string $type): void
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

function advance_archiving_review_sessions_background(array $config, int $chunkSize = 5, int $fallbackChunkSize = 5): void
{
    advance_archiving_update_session($config, max($chunkSize, $fallbackChunkSize));
    maybe_queue_archiving_rules_update_event($config);
}

function sync_unarchived_job_document_metadata_for_rule_change(
    array $config,
    string $jobId,
    array $job,
    array $previousRules,
    array $nextRules
): void
{
    if (!is_valid_job_id($jobId) || ($job['archived'] ?? false) === true || ($job['status'] ?? '') === 'processing') {
        return;
    }

    try {
        $currentLabelIds = job_document_label_ids_for_metadata_sync($jobId, $job);
        $currentDataValues = job_document_data_values_for_metadata_sync($jobId, $job);
        $previousAnalysis = calculate_auto_archiving_result_for_job($config, $jobId, $previousRules, $job);
        $previousAutoResult = normalize_auto_archiving_result(
            is_array($previousAnalysis['autoArchivingResult'] ?? null) ? $previousAnalysis['autoArchivingResult'] : []
        );
        $analysis = calculate_auto_archiving_result_for_job($config, $jobId, $nextRules, $job);
        $nextAutoResult = normalize_auto_archiving_result(
            is_array($analysis['autoArchivingResult'] ?? null) ? $analysis['autoArchivingResult'] : []
        );
        sync_document_metadata_from_auto_transition(
            $jobId,
            $previousAutoResult,
            $nextAutoResult,
            $currentLabelIds,
            $currentDataValues
        );
    } catch (Throwable $e) {
        // The queued reprocess remains the fallback if direct metadata refresh fails.
    }
}

function reprocess_unarchived_jobs_for_analysis_change(
    array $config,
    string $mode = 'post-ocr',
    bool $forceOcr = false,
    ?array $previousRules = null,
    ?array $nextRules = null
): array
{
    $normalizedMode = trim($mode);
    if ($normalizedMode !== 'full' && $normalizedMode !== 'post-ocr') {
        $normalizedMode = 'post-ocr';
    }

    $jobsDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR);
    $entries = scandir($jobsDir);
    if ($entries === false) {
        return [
            'reprocessedJobIds' => [],
            'reprocessedCount' => 0,
            'mode' => $normalizedMode,
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
        if (is_array($previousRules) && is_array($nextRules)) {
            sync_unarchived_job_document_metadata_for_rule_change($config, $entry, $job, $previousRules, $nextRules);
        }
        reprocess_job_by_id($config, $entry, $normalizedMode, $forceOcr);
        $jobIds[] = $entry;
    }

    return [
        'reprocessedJobIds' => $jobIds,
        'reprocessedCount' => count($jobIds),
        'mode' => $normalizedMode,
    ];
}

function reprocess_job_ids_for_analysis_change(
    array $config,
    array $jobIds,
    string $mode = 'post-ocr',
    bool $forceOcr = false
): array {
    $normalizedMode = trim($mode);
    if ($normalizedMode !== 'full' && $normalizedMode !== 'post-ocr') {
        $normalizedMode = 'post-ocr';
    }

    $jobsDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR);
    $seen = [];
    $processedJobIds = [];
    $skippedJobIds = [];

    foreach ($jobIds as $jobId) {
        if (!is_string($jobId)) {
            continue;
        }
        $normalizedJobId = trim($jobId);
        if ($normalizedJobId === '' || !is_valid_job_id($normalizedJobId) || isset($seen[$normalizedJobId])) {
            continue;
        }
        $seen[$normalizedJobId] = true;

        $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $normalizedJobId;
        $job = is_dir($jobDir) ? load_json_file($jobDir . '/job.json') : null;
        if (!is_array($job) || ($job['archived'] ?? false) === true || ($job['status'] ?? '') === 'processing') {
            $skippedJobIds[] = $normalizedJobId;
            continue;
        }

        if ($normalizedMode === 'full') {
            if (!is_file($jobDir . '/source.pdf')) {
                $skippedJobIds[] = $normalizedJobId;
                continue;
            }
        } elseif (job_review_pdf_path($config, $normalizedJobId, $job) === null) {
            $skippedJobIds[] = $normalizedJobId;
            continue;
        }

        reprocess_job_by_id($config, $normalizedJobId, $normalizedMode, $forceOcr);
        $processedJobIds[] = $normalizedJobId;
    }

    return [
        'reprocessedJobIds' => $processedJobIds,
        'reprocessedCount' => count($processedJobIds),
        'skippedJobIds' => $skippedJobIds,
        'skippedCount' => count($skippedJobIds),
        'mode' => $normalizedMode,
    ];
}

function reprocess_unarchived_jobs_for_active_archiving_rules(
    array $config,
    ?array $previousRules = null,
    ?array $nextRules = null
): array
{
    return reprocess_unarchived_jobs_for_analysis_change($config, 'post-ocr', false, $previousRules, $nextRules);
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
    $dismissOnly = false;

    if ($action === 'use-new') {
        $nextApproved = $proposed;
    } elseif ($action === 'manual') {
        $nextApproved = resolved_active_review_value($payload, $proposed, $config);
    } elseif ($action === 'dismiss' || $action === 'keep') {
        $dismissOnly = true;
    } else {
        throw new RuntimeException('Ogiltig granskningsåtgärd');
    }

    $nextPath = is_string($job['archivedPdfPath'] ?? null) ? trim((string) $job['archivedPdfPath']) : '';
    $currentArchivedPdfPath = $nextPath;
    if (!$dismissOnly) {
        if ($nextPath === '' || !is_file($nextPath)) {
            throw new RuntimeException('Arkiverad PDF saknas');
        }

        $outputBaseDirectory = trim((string) ($config['outputBaseDirectory'] ?? ''));
        if ($outputBaseDirectory === '' || !is_dir($outputBaseDirectory)) {
            throw new RuntimeException('Bas-sökväg för utdata är inte konfigurerad');
        }
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
        if ($targetPath !== $currentArchivedPdfPath) {
            if (is_file($targetPath)) {
                throw new RuntimeException('Det finns redan en fil med det filnamnet i mål-mappen');
            }
            if (!rename($currentArchivedPdfPath, $targetPath)) {
                throw new RuntimeException('Kunde inte uppdatera arkiverad fil');
            }
            $nextPath = $targetPath;
        }
    }

    if ($dismissOnly) {
        $job['dismissedAnalysisVersion'] = $activeVersion;
    } else {
        $normalizedApproved = normalize_auto_archiving_result($nextApproved);
        $job['approvedArchiving'] = $normalizedApproved;
        $job['selectedClientDirName'] = is_string($nextApproved['clientId'] ?? null) ? trim((string) $nextApproved['clientId']) : null;
        $job['selectedSenderId'] = isset($nextApproved['senderId']) ? (int) $nextApproved['senderId'] : null;
        $job['selectedFolderId'] = is_string($nextApproved['folderId'] ?? null) ? trim((string) $nextApproved['folderId']) : null;
        $job['selectedLabelIds'] = normalize_stored_job_label_ids($nextApproved['labels'] ?? null);
        $job['filename'] = is_string($nextApproved['filename'] ?? null) ? trim((string) $nextApproved['filename']) : null;
        $job['archivedPdfPath'] = $nextPath;
        set_job_archiving_snapshot($job, $activeVersion, $proposed, $normalizedApproved);
        unset($job['dismissedAnalysisVersion']);
    }
    $job['updatedAt'] = now_iso();
    unset(
        $job['needsRuleReview'],
        $job['ruleReviewTargetRulesVersion'],
        $job['lastResolvedArchivingRulesVersion'],
        $job['ruleReviewProposedValue'],
        $job['ruleReviewDiff']
    );
    write_json_file($jobPath, $job);
    set_archiving_update_session_ignore_dismissed($config, false);
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
            'clientMatches' => [],
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
        // We need OCR output objects for analysis, so this job path always reruns OCR.
        // Existing embedded text is still available later via the review PDF and extracted text pipeline.
        $ocrUsedExistingText = false;
        $ocrProcessedPdf = run_ocrmypdf(
            $sourcePdfPath,
            $reviewPdfPath,
            $ocrmypdfSidecarPath,
            $jobDir,
            false,
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
        write_merged_object_debug_files_from_rapidocr($jobDir, $ocrPdfTextSubstitutions);
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

    $analysisPdfPath = is_file($reviewPdfPath) ? $reviewPdfPath : $sourcePdfPath;
    $ocrText = load_job_analysis_text($jobDir, $analysisPdfPath);

    $activeRulesSource = load_active_archiving_rules();
    $allActiveFields = array_values(array_merge(
        is_array($activeRulesSource['predefinedFields'] ?? null) ? $activeRulesSource['predefinedFields'] : [],
        is_array($activeRulesSource['systemFields'] ?? null) ? $activeRulesSource['systemFields'] : [],
        is_array($activeRulesSource['fields'] ?? null) ? $activeRulesSource['fields'] : []
    ));
    $senders = load_senders();
    $activeRules = [
        'archiveFolders' => is_array($activeRulesSource['archiveFolders'] ?? null) ? $activeRulesSource['archiveFolders'] : [],
        'valuePatterns' => is_array($activeRulesSource['valuePatterns'] ?? null) ? $activeRulesSource['valuePatterns'] : [],
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
        'zoneMatches' => is_array($analysisPayload['zoneMatches'] ?? null) ? $analysisPayload['zoneMatches'] : [],
        'clientMatches' => $analysisPayload['clientMatches'],
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
        'zoneMatches' => is_array($analysisPayload['zoneMatches'] ?? null) ? $analysisPayload['zoneMatches'] : [],
        'clientMatches' => $analysisPayload['clientMatches'],
        'preselectedClient' => $analysisPayload['preselectedClient'],
        'preselectedSender' => $analysisPayload['preselectedSender'],
        'senderMatches' => $analysisPayload['senderMatches'],
        'autoArchivingResult' => $analysisPayload['autoArchivingResult'],
    ];

    write_json_file($jobDir . '/extracted.json', $extractedData);

    $analyzedAt = now_iso();
    $jobId = basename($jobDir);
    if (is_string($jobId) && $jobId !== '') {
        $previousAutoResultForMetadataSync = is_array($jobContext['metadataSyncPreviousAutoArchivingResult'] ?? null)
            ? normalize_auto_archiving_result($jobContext['metadataSyncPreviousAutoArchivingResult'])
            : null;
        $currentLabelIdsForMetadataSync = is_array($jobContext['metadataSyncPreviousDocumentLabelIds'] ?? null)
            ? normalize_stored_job_label_ids($jobContext['metadataSyncPreviousDocumentLabelIds'])
            : null;
        $currentDataValuesForMetadataSync = is_array($jobContext['metadataSyncPreviousDocumentDataValues'] ?? null)
            ? normalize_document_actual_data_values($jobContext['metadataSyncPreviousDocumentDataValues'])
            : null;
        sync_job_analysis_snapshot(
            $jobId,
            $analysisPayload['autoArchivingResult'],
            $analyzedAt,
            $previousAutoResultForMetadataSync,
            $currentLabelIdsForMetadataSync,
            $currentDataValuesForMetadataSync
        );
    }

    return [
        'extractedData' => $extractedData,
        'analysis' => [
            'preselectedClient' => $analysisPayload['preselectedClient'],
            'preselectedSender' => $analysisPayload['preselectedSender'],
            'clientMatches' => $analysisPayload['clientMatches'],
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

    $reprocessMode = is_string($jobData['reprocessMode'] ?? null)
        ? trim((string) $jobData['reprocessMode'])
        : 'full';
    if ($reprocessMode !== 'post-ocr') {
        $reprocessMode = 'full';
    }

    try {
        if ($reprocessMode !== 'post-ocr' && !is_file($sourcePdfPath)) {
            throw new RuntimeException('Missing source.pdf');
        }
        if ($reprocessMode === 'post-ocr' && !is_file($jobDir . '/review.pdf')) {
            throw new RuntimeException('Missing review.pdf');
        }

        $fallbackTxtPath = is_string($jobData['fallbackTxtPath'] ?? null)
            ? (string) $jobData['fallbackTxtPath']
            : null;

        $replacementMap = replacement_map(
            is_array($matchingPayload['replacements'] ?? null) ? $matchingPayload['replacements'] : []
        );
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

        if (!is_dir($jobDir) || !is_file($jobJsonPath)) {
            return;
        }

        $jobData['status'] = 'ready';
        $jobData['updatedAt'] = now_iso();
        unset($jobData['analysisOutdated'], $jobData['analysisAutoReprocessQueued']);
        unset(
            $jobData['error'],
            $jobData['reprocessMode'],
            $jobData['forceOcr'],
            $jobData['metadataSyncPreviousAutoArchivingResult'],
            $jobData['metadataSyncPreviousDocumentLabelIds'],
            $jobData['metadataSyncPreviousDocumentDataValues']
        );
        write_json_file($jobJsonPath, $jobData);
        queue_job_upsert_event($config, $jobId);
        ocr_debug_handle_job_processing_finished($config, $jobId, true);
    } catch (Throwable $e) {
        if (!is_dir($jobDir) || !is_file($jobJsonPath)) {
            return;
        }
        $jobData['status'] = 'failed';
        $jobData['updatedAt'] = now_iso();
        $jobData['error'] = $e->getMessage();
        unset($jobData['analysisOutdated'], $jobData['analysisAutoReprocessQueued']);
        unset(
            $jobData['reprocessMode'],
            $jobData['forceOcr'],
            $jobData['metadataSyncPreviousAutoArchivingResult'],
            $jobData['metadataSyncPreviousDocumentLabelIds'],
            $jobData['metadataSyncPreviousDocumentDataValues']
        );
        write_json_file($jobJsonPath, $jobData);
        queue_job_upsert_event($config, $jobId);
        ocr_debug_handle_job_processing_finished($config, $jobId, false, $e->getMessage());
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

function trigger_reanalyze_all_documents_worker(): void
{
    $scriptPath = PROJECT_ROOT . 'scripts/reanalyze-all-documents.php';
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

function cached_sender_match_names_for_analysis(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $repository = sender_repository_instance();
    if ($repository === null) {
        $cache = [];
        return $cache;
    }

    try {
        $cache = $repository->listMatchNamesForAnalysis();
    } catch (Throwable $e) {
        $cache = [];
    }

    return $cache;
}

function normalized_sender_summary_search_text(string $value): string
{
    return \Docflow\Senders\MatchNameNormalizer::normalize($value);
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

function sender_summary_extracted_with_actual_data_values(array $extracted, ?array $actualValues): array
{
    if (!is_array($actualValues)) {
        return $extracted;
    }

    $next = $extracted;
    $next['extractionFields'] = auto_archiving_fields_from_document_actual_data_values($actualValues);
    return $next;
}

function build_job_sender_summary(?array $extracted, string $jobDir, ?int $matchedSenderId, ?int $selectedSenderId): ?array
{
    if (!is_array($extracted)) {
        return null;
    }

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
    $matchedMatchNamesBySenderId = [];

    if ($documentText !== '') {
        foreach (cached_sender_match_names_for_analysis() as $matchNameRow) {
            if (!is_array($matchNameRow)) {
                continue;
            }
            $senderId = isset($matchNameRow['senderId']) ? (int) $matchNameRow['senderId'] : 0;
            $normalizedName = is_string($matchNameRow['normalizedName'] ?? null)
                ? trim((string) $matchNameRow['normalizedName'])
                : '';
            if ($senderId < 1 || $normalizedName === '' || !str_contains($documentText, $normalizedName)) {
                continue;
            }
            $senderIds[$senderId] = true;
            $matchedMatchNamesBySenderId[$senderId][$normalizedName] = true;
        }
    }

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
            $lookupName = is_string($observedRow['organizationName'] ?? null) ? trim((string) $observedRow['organizationName']) : '';
            $lookupStatus = is_string($observedRow['lookupStatus'] ?? null) ? trim((string) $observedRow['lookupStatus']) : '';
            $normalizedValue = \Docflow\Senders\IdentifierNormalizer::normalizeOrgNumber($organizationNumber)
                ?? preg_replace('/\D+/', '', $organizationNumber)
                ?? $organizationNumber;
            $observations[] = [
                'key' => $observationKey,
                'type' => 'organization_number',
                'kind' => 'organization',
                'identifierId' => isset($observedRow['id']) ? (int) $observedRow['id'] : null,
                'itemLabel' => 'ORG.NR',
                'itemValue' => $normalizedValue,
                'normalizedNumber' => $normalizedValue,
                'lookupName' => $lookupName,
                'status' => $lookupName !== '' || $lookupStatus === 'resolved'
                    ? 'resolved'
                    : ($lookupStatus === 'failed' ? 'failed' : 'pending'),
                'lookupErrorCode' => is_string($observedRow['lookupErrorCode'] ?? null) ? trim((string) $observedRow['lookupErrorCode']) : '',
                'lookupErrorMessage' => is_string($observedRow['lookupErrorMessage'] ?? null) ? trim((string) $observedRow['lookupErrorMessage']) : '',
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
            $lookupName = is_string($observedRow['payeeName'] ?? null) ? trim((string) $observedRow['payeeName']) : '';
            $normalizedValue = \Docflow\Senders\IdentifierNormalizer::normalizeBankgiro($bankgiro) ?? preg_replace('/\D+/', '', $bankgiro) ?? $bankgiro;
            $observations[] = [
                'key' => $observationKey,
                'type' => 'bankgiro',
                'kind' => 'payment',
                'paymentType' => 'bankgiro',
                'identifierId' => isset($observedRow['id']) ? (int) $observedRow['id'] : null,
                'itemLabel' => 'BANKGIRO',
                'itemValue' => $bankgiro,
                'normalizedNumber' => $normalizedValue,
                'lookupName' => $lookupName,
                'status' => $lookupName !== '' || $lookupStatus === 'resolved'
                    ? 'resolved'
                    : (in_array($lookupStatus, ['failed', 'not_found'], true) ? 'failed' : 'pending'),
                'lookupErrorCode' => is_string($observedRow['lookupErrorCode'] ?? null) ? trim((string) $observedRow['lookupErrorCode']) : '',
                'lookupErrorMessage' => is_string($observedRow['lookupErrorMessage'] ?? null) ? trim((string) $observedRow['lookupErrorMessage']) : '',
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
            $lookupName = is_string($observedRow['payeeName'] ?? null) ? trim((string) $observedRow['payeeName']) : '';
            $normalizedValue = \Docflow\Senders\IdentifierNormalizer::normalizePlusgiro($plusgiro) ?? preg_replace('/\D+/', '', $plusgiro) ?? $plusgiro;
            $observations[] = [
                'key' => $observationKey,
                'type' => 'plusgiro',
                'kind' => 'payment',
                'paymentType' => 'plusgiro',
                'identifierId' => isset($observedRow['id']) ? (int) $observedRow['id'] : null,
                'itemLabel' => 'PLUSGIRO',
                'itemValue' => $plusgiro,
                'normalizedNumber' => $normalizedValue,
                'lookupName' => $lookupName,
                'status' => $lookupName !== '' || $lookupStatus === 'resolved'
                    ? 'resolved'
                    : (in_array($lookupStatus, ['failed', 'not_found'], true) ? 'failed' : 'pending'),
                'lookupErrorCode' => is_string($observedRow['lookupErrorCode'] ?? null) ? trim((string) $observedRow['lookupErrorCode']) : '',
                'lookupErrorMessage' => is_string($observedRow['lookupErrorMessage'] ?? null) ? trim((string) $observedRow['lookupErrorMessage']) : '',
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

        $name = is_string($senderRow['displayName'] ?? null) && trim((string) $senderRow['displayName']) !== ''
            ? trim((string) $senderRow['displayName'])
            : (is_string($senderRow['name'] ?? null) ? trim((string) $senderRow['name']) : '');
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
                'type' => 'lookup_name',
            ];
        }

        $matchedAlias = null;
        $matchNameRows = is_array($senderRow['matchNames'] ?? null) ? $senderRow['matchNames'] : [];
        foreach ($matchNameRows as $matchNameRow) {
            if (!is_array($matchNameRow)) {
                continue;
            }
            $matchName = is_string($matchNameRow['name'] ?? null) ? trim((string) $matchNameRow['name']) : '';
            $normalizedMatchName = is_string($matchNameRow['normalizedName'] ?? null)
                ? trim((string) $matchNameRow['normalizedName'])
                : normalized_sender_summary_search_text($matchName);
            if ($matchName === '' || $normalizedMatchName === '' || isset($nameComponentMap['match:' . $normalizedMatchName])) {
                continue;
            }
            $found = isset($matchedMatchNamesBySenderId[$senderId][$normalizedMatchName]);
            $nameComponentMap['match:' . $normalizedMatchName] = true;
            $nameComponents[] = [
                'label' => 'Matchningsnamn',
                'value' => $matchName,
                'found' => $found,
                'type' => 'match_name',
            ];
            if ($found && $matchedAlias === null) {
                $matchedAlias = $matchName;
            }
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
                        'type' => 'lookup_name',
                    ];
                }
            }
        }

        $matchKinds = [];
        foreach ($nameComponents as $nameComponent) {
            if (($nameComponent['found'] ?? false) === true) {
                $matchKinds[] = ($nameComponent['type'] ?? '') === 'match_name'
                    ? 'match_name'
                    : 'name';
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
        foreach ($nameComponents as $nameComponent) {
            if (
                ($nameComponent['found'] ?? false) === true
                && ($nameComponent['type'] ?? '') === 'match_name'
            ) {
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
            'matchedAlias' => $matchedAlias,
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
                foreach (is_array($row['nameComponents'] ?? null) ? $row['nameComponents'] : [] as $nameComponent) {
                    if (
                        is_array($nameComponent)
                        && (($nameComponent['found'] ?? false) === true)
                        && (($nameComponent['type'] ?? '') === 'match_name')
                    ) {
                        $count++;
                    }
                }
                return $count;
            };

            $countAllFoundMarks = static function (array $row) use ($countStrongMatches): int {
                $count = $countStrongMatches($row);
                foreach (is_array($row['nameComponents'] ?? null) ? $row['nameComponents'] : [] as $nameComponent) {
                    if (
                        is_array($nameComponent)
                        && (($nameComponent['found'] ?? false) === true)
                        && (($nameComponent['type'] ?? '') !== 'match_name')
                    ) {
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
        'id' => isset($row['id']) ? (int) $row['id'] : null,
        'organizationNumber' => is_string($row['organization_number'] ?? null) ? trim((string) $row['organization_number']) : $normalized,
        'organizationName' => is_string($row['organization_name'] ?? null) ? trim((string) $row['organization_name']) : '',
        'senderId' => isset($row['sender_id']) ? (int) $row['sender_id'] : null,
        'source' => is_string($row['source'] ?? null) ? trim((string) $row['source']) : '',
        'lookupStatus' => is_string($row['lookup_status'] ?? null) ? trim((string) $row['lookup_status']) : '',
        'lookupErrorCode' => is_string($row['lookup_error_code'] ?? null) ? trim((string) $row['lookup_error_code']) : '',
        'lookupErrorMessage' => is_string($row['lookup_error_message'] ?? null) ? trim((string) $row['lookup_error_message']) : '',
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
        'id' => isset($row['id']) ? (int) $row['id'] : null,
        'type' => is_string($row['type'] ?? null) ? trim((string) $row['type']) : $normalizedType,
        'number' => is_string($row['number'] ?? null) ? trim((string) $row['number']) : $normalizedNumber,
        'payeeName' => is_string($row['payee_name'] ?? null) ? trim((string) $row['payee_name']) : '',
        'payeeLookupStatus' => is_string($row['payee_lookup_status'] ?? null) ? trim((string) $row['payee_lookup_status']) : '',
        'lookupErrorCode' => is_string($row['lookup_error_code'] ?? null) ? trim((string) $row['lookup_error_code']) : '',
        'lookupErrorMessage' => is_string($row['lookup_error_message'] ?? null) ? trim((string) $row['lookup_error_message']) : '',
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
    $unknownObservations = is_array($senderSummary) && is_array($senderSummary['unknownObservations'] ?? null)
        ? array_values(array_filter(
            $senderSummary['unknownObservations'],
            static fn (mixed $row): bool => is_array($row)
        ))
        : [];
    if ($unknownObservations !== []) {
        return [
            'matchedSenderId' => null,
            'preselectedSender' => null,
            'senderMatches' => $matches,
        ];
    }
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
    $isArchived = ($job['archived'] ?? false) === true;
    $baseAutoResult = job_auto_archiving_result($job);
    $storedDocumentLabelIds = document_metadata_label_ids($id);
    $selectedLabelIds = is_array($storedDocumentLabelIds)
        ? $storedDocumentLabelIds
        : (array_key_exists('selectedLabelIds', $job)
        ? normalize_stored_job_label_ids($job['selectedLabelIds'])
        : null);
    $storedDocumentDataValues = document_metadata_actual_data_values($id);
    $selectedExtractionFieldValues = is_array($storedDocumentDataValues)
        ? selected_extraction_field_values_from_document_actual($storedDocumentDataValues, $baseAutoResult)
        : (array_key_exists('selectedExtractionFieldValues', $job)
        ? normalize_stored_job_extraction_field_values($job['selectedExtractionFieldValues'])
        : null);
    $filename = is_string($job['filename'] ?? null)
        ? trim((string) $job['filename'])
        : null;
    $storedAutoResult = $isArchived ? [] : $baseAutoResult;
    $liveAutoResult = is_array($analysis['autoArchivingResult'] ?? null)
        ? $analysis['autoArchivingResult']
        : [];

    if (!$isArchived && $storedAutoResult !== [] && $liveAutoResult !== []) {
        $storedClientId = normalize_auto_archiving_result_scalar_value($storedAutoResult['clientId'] ?? null);
        $liveClientId = normalize_auto_archiving_result_scalar_value($liveAutoResult['clientId'] ?? null);
        if ($selectedClientDirName !== null && $selectedClientDirName === $storedClientId) {
            $selectedClientDirName = $liveClientId !== '' ? $liveClientId : null;
        }

        $storedSenderId = normalize_auto_archiving_result_sender_value($storedAutoResult['senderId'] ?? null);
        $liveSenderId = normalize_auto_archiving_result_sender_value($liveAutoResult['senderId'] ?? null);
        if ($selectedSenderId !== null && $selectedSenderId === $storedSenderId) {
            $selectedSenderId = $liveSenderId;
        }

        $storedFolderId = normalize_auto_archiving_result_scalar_value($storedAutoResult['folderId'] ?? null);
        $liveFolderId = normalize_auto_archiving_result_scalar_value($liveAutoResult['folderId'] ?? null);
        if ($selectedFolderId !== null && $selectedFolderId === $storedFolderId) {
            $selectedFolderId = $liveFolderId !== '' ? $liveFolderId : null;
        }

        $storedLabelIds = normalize_stored_job_label_ids($storedAutoResult['labels'] ?? null);
        $liveLabelIds = normalize_stored_job_label_ids($liveAutoResult['labels'] ?? null);
        sort($storedLabelIds, SORT_NATURAL);
        sort($liveLabelIds, SORT_NATURAL);
        if (is_array($selectedLabelIds)) {
            $currentLabelIds = normalize_stored_job_label_ids($selectedLabelIds);
            sort($currentLabelIds, SORT_NATURAL);
            if ($currentLabelIds === $storedLabelIds) {
                $selectedLabelIds = $liveLabelIds !== [] ? $liveLabelIds : null;
            }
        }

        $storedFilename = normalize_auto_archiving_result_scalar_value($storedAutoResult['filename'] ?? null);
        $liveFilename = normalize_auto_archiving_result_scalar_value($liveAutoResult['filename'] ?? null);
        if ($filename !== null && $filename === $storedFilename) {
            $filename = $liveFilename !== '' ? $liveFilename : null;
        }
    }
    $archivedAt = is_string($job['archivedAt'] ?? null) ? trim((string) $job['archivedAt']) : null;
    $archivedPdfPath = is_string($job['archivedPdfPath'] ?? null) ? trim((string) $job['archivedPdfPath']) : null;
    $archivedVersion = job_archived_version($job);
    $dismissedAnalysisVersion = job_dismissed_analysis_version($job);
    $extracted = load_json_file($jobDir . '/extracted.json');
    $senderSummaryActualValues = is_array($storedDocumentDataValues)
        ? $storedDocumentDataValues
        : (is_array($selectedExtractionFieldValues)
            ? document_actual_data_values_from_selected($job, $selectedExtractionFieldValues)
            : null);
    $senderSummaryExtracted = is_array($extracted)
        ? sender_summary_extracted_with_actual_data_values($extracted, $senderSummaryActualValues)
        : $extracted;
    $senderSummary = build_job_sender_summary($senderSummaryExtracted, $jobDir, null, $selectedSenderId);
    if (!$isArchived && $selectedSenderId === null && is_array($senderSummary)) {
        $senderSelection = single_preselected_sender_from_summary($senderSummary);
        $senderSelectionId = isset($senderSelection['matchedSenderId']) ? (int) $senderSelection['matchedSenderId'] : 0;
        $storedSenderId = normalize_auto_archiving_result_sender_value($storedAutoResult['senderId'] ?? null);
        $liveSenderId = normalize_auto_archiving_result_sender_value($liveAutoResult['senderId'] ?? null);
        if ($senderSelectionId > 0 && $senderSelectionId === $storedSenderId) {
            $selectedSenderId = $liveSenderId;
            $senderSummary = build_job_sender_summary($senderSummaryExtracted, $jobDir, null, $selectedSenderId);
        }
    }
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
                'selectedExtractionFieldValues' => $selectedExtractionFieldValues,
                'filename' => $filename,
                'senderSummary' => $senderSummary,
                'analysisOutdated' => $analysisOutdated,
                'analysisAutoReprocessQueued' => $analysisAutoReprocessQueued,
                'archived' => $isArchived,
                'archivedAt' => $archivedAt,
                'archivedPdfPath' => $archivedPdfPath,
                'archivedVersion' => $archivedVersion,
                'dismissedAnalysisVersion' => $dismissedAnalysisVersion,
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
                'selectedExtractionFieldValues' => $selectedExtractionFieldValues,
                'filename' => $filename,
                'senderSummary' => $senderSummary,
                'analysisOutdated' => $analysisOutdated,
                'analysisAutoReprocessQueued' => $analysisAutoReprocessQueued,
                'archived' => $isArchived,
                'archivedAt' => $archivedAt,
                'archivedPdfPath' => $archivedPdfPath,
                'archivedVersion' => $archivedVersion,
                'dismissedAnalysisVersion' => $dismissedAnalysisVersion,
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

    $senderSummary = build_job_sender_summary($senderSummaryExtracted, $jobDir, $matchedSenderId, $selectedSenderId);

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
        'selectedExtractionFieldValues' => $selectedExtractionFieldValues,
        'filename' => $filename,
        'senderSummary' => $senderSummary,
        'analysisOutdated' => $analysisOutdated,
        'analysisAutoReprocessQueued' => $analysisAutoReprocessQueued,
        'archived' => $isArchived,
        'archivedAt' => $archivedAt,
        'archivedPdfPath' => $archivedPdfPath,
        'archivedVersion' => $archivedVersion,
        'dismissedAnalysisVersion' => $dismissedAnalysisVersion,
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

function archived_job_uses_saved_label_id(array $job, string $labelId): bool
{
    $normalizedLabelId = is_string($labelId) ? trim($labelId) : '';
    if ($normalizedLabelId === '' || (($job['archived'] ?? false) !== true)) {
        return false;
    }
    $approved = current_approved_archiving_for_job($job);
    return in_array($normalizedLabelId, normalize_stored_job_label_ids($approved['labels'] ?? null), true);
}

function count_archived_documents_using_label_ids(array $config, array $labelIds): array
{
    $normalizedLabelIds = array_values(array_unique(array_filter(array_map(
        static fn ($value): string => is_string($value) ? trim($value) : '',
        $labelIds
    ), static fn (string $value): bool => $value !== '')));
    $counts = [];
    foreach ($normalizedLabelIds as $labelId) {
        $counts[$labelId] = 0;
    }
    if ($normalizedLabelIds === []) {
        return $counts;
    }

    $jobsDir = rtrim((string) ($config['jobsDirectory'] ?? ''), DIRECTORY_SEPARATOR);
    if ($jobsDir === '' || !is_dir($jobsDir)) {
        return $counts;
    }

    $entries = scandir($jobsDir);
    if ($entries === false) {
        return $counts;
    }

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
        foreach ($normalizedLabelIds as $labelId) {
            if (archived_job_uses_saved_label_id($job, $labelId)) {
                $counts[$labelId] = ((int) ($counts[$labelId] ?? 0)) + 1;
            }
        }
    }

    return $counts;
}

function archived_job_uses_saved_field_key(array $job, string $fieldKey): bool
{
    $normalizedFieldKey = is_string($fieldKey) ? trim($fieldKey) : '';
    if ($normalizedFieldKey === '' || (($job['archived'] ?? false) !== true)) {
        return false;
    }

    $approved = current_approved_archiving_for_job($job);
    $approvedFields = is_array($approved['fields'] ?? null) ? $approved['fields'] : [];
    return normalize_auto_archiving_field_value_list($approvedFields[$normalizedFieldKey] ?? null) !== [];
}

function count_archived_documents_using_field_keys(array $config, array $fieldKeys): array
{
    $normalizedFieldKeys = array_values(array_unique(array_filter(array_map(
        static fn ($value): string => is_string($value) ? trim($value) : '',
        $fieldKeys
    ), static fn (string $value): bool => $value !== '')));
    $counts = [];
    foreach ($normalizedFieldKeys as $fieldKey) {
        $counts[$fieldKey] = 0;
    }
    if ($normalizedFieldKeys === []) {
        return $counts;
    }

    $jobsDir = rtrim((string) ($config['jobsDirectory'] ?? ''), DIRECTORY_SEPARATOR);
    if ($jobsDir === '' || !is_dir($jobsDir)) {
        return $counts;
    }

    $entries = scandir($jobsDir);
    if ($entries === false) {
        return $counts;
    }

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
        foreach ($normalizedFieldKeys as $fieldKey) {
            if (archived_job_uses_saved_field_key($job, $fieldKey)) {
                $counts[$fieldKey] = ((int) ($counts[$fieldKey] ?? 0)) + 1;
            }
        }
    }

    return $counts;
}

function cleanup_removed_labels_from_unarchived_job(array &$job, array $removedLabelIds): bool
{
    if (($job['archived'] ?? false) === true || $removedLabelIds === []) {
        return false;
    }

    $changed = false;
    $removeLabels = static function (mixed $value) use ($removedLabelIds, &$changed): array {
        $normalized = normalize_stored_job_label_ids($value);
        $filtered = array_values(array_filter($normalized, static fn (string $labelId): bool => !in_array($labelId, $removedLabelIds, true)));
        if ($filtered !== $normalized) {
            $changed = true;
        }
        return $filtered;
    };

    if (array_key_exists('selectedLabelIds', $job)) {
        $selected = $removeLabels($job['selectedLabelIds']);
        if ($selected === []) {
            unset($job['selectedLabelIds']);
        } else {
            $job['selectedLabelIds'] = $selected;
        }
    }

    if (array_key_exists('labels', $job)) {
        $job['labels'] = $removeLabels($job['labels']);
    }
    if (is_array($job['autoArchivingResult'] ?? null)) {
        $autoLabels = $removeLabels($job['autoArchivingResult']['labels'] ?? null);
        $job['autoArchivingResult']['labels'] = $autoLabels;
    }

    if (is_array($job['analysis'] ?? null)) {
        if (array_key_exists('labels', $job['analysis'])) {
            $job['analysis']['labels'] = $removeLabels($job['analysis']['labels']);
        }
        if (is_array($job['analysis']['autoArchivingResult'] ?? null)) {
            $autoLabels = $removeLabels($job['analysis']['autoArchivingResult']['labels'] ?? null);
            $job['analysis']['autoArchivingResult']['labels'] = $autoLabels;
        }
    }

    return $changed;
}

function cleanup_removed_fields_from_unarchived_job(array &$job, array $removedFieldKeys): bool
{
    if (($job['archived'] ?? false) === true || $removedFieldKeys === []) {
        return false;
    }

    $changed = false;
    $removeFieldKeys = static function (mixed &$value) use ($removedFieldKeys, &$changed): void {
        if (!is_array($value)) {
            return;
        }
        foreach ($removedFieldKeys as $fieldKey) {
            if (array_key_exists($fieldKey, $value)) {
                unset($value[$fieldKey]);
                $changed = true;
            }
        }
    };

    if (array_key_exists('extractionFields', $job)) {
        $extractionFields = is_array($job['extractionFields']) ? $job['extractionFields'] : [];
        $removeFieldKeys($extractionFields);
        $job['extractionFields'] = $extractionFields === [] ? new stdClass() : $extractionFields;
    }
    if (array_key_exists('extractionFieldMeta', $job)) {
        $extractionFieldMeta = is_array($job['extractionFieldMeta']) ? $job['extractionFieldMeta'] : [];
        $removeFieldKeys($extractionFieldMeta);
        $job['extractionFieldMeta'] = $extractionFieldMeta === [] ? new stdClass() : $extractionFieldMeta;
    }
    if (is_array($job['autoArchivingResult'] ?? null) && array_key_exists('fields', $job['autoArchivingResult'])) {
        $autoFields = is_array($job['autoArchivingResult']['fields']) ? $job['autoArchivingResult']['fields'] : [];
        $removeFieldKeys($autoFields);
        $job['autoArchivingResult']['fields'] = $autoFields;
    }

    if (is_array($job['analysis'] ?? null)) {
        if (array_key_exists('extractionFields', $job['analysis'])) {
            $analysisFields = is_array($job['analysis']['extractionFields']) ? $job['analysis']['extractionFields'] : [];
            $removeFieldKeys($analysisFields);
            $job['analysis']['extractionFields'] = $analysisFields === [] ? new stdClass() : $analysisFields;
        }
        if (array_key_exists('extractionFieldMeta', $job['analysis'])) {
            $analysisFieldMeta = is_array($job['analysis']['extractionFieldMeta']) ? $job['analysis']['extractionFieldMeta'] : [];
            $removeFieldKeys($analysisFieldMeta);
            $job['analysis']['extractionFieldMeta'] = $analysisFieldMeta === [] ? new stdClass() : $analysisFieldMeta;
        }
        if (is_array($job['analysis']['autoArchivingResult'] ?? null) && array_key_exists('fields', $job['analysis']['autoArchivingResult'])) {
            $autoFields = is_array($job['analysis']['autoArchivingResult']['fields']) ? $job['analysis']['autoArchivingResult']['fields'] : [];
            $removeFieldKeys($autoFields);
            $job['analysis']['autoArchivingResult']['fields'] = $autoFields;
        }
    }

    return $changed;
}

function cleanup_unarchived_jobs_after_label_removal(array $config, array $removedLabelIds): array
{
    $normalizedLabelIds = array_values(array_unique(array_filter(array_map(
        static fn ($value): string => is_string($value) ? trim($value) : '',
        $removedLabelIds
    ), static fn (string $value): bool => $value !== '')));
    if ($normalizedLabelIds === []) {
        return ['cleanedJobIds' => [], 'cleanedCount' => 0];
    }

    $jobsDir = rtrim((string) ($config['jobsDirectory'] ?? ''), DIRECTORY_SEPARATOR);
    if ($jobsDir === '' || !is_dir($jobsDir)) {
        return ['cleanedJobIds' => [], 'cleanedCount' => 0];
    }

    $cleanedJobIds = [];
    foreach (scandir($jobsDir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($jobDir)) {
            continue;
        }
        $jobPath = $jobDir . '/job.json';
        $job = load_json_file($jobPath);
        if (!is_array($job) || (($job['archived'] ?? false) === true)) {
            continue;
        }
        $jobChanged = cleanup_removed_labels_from_unarchived_job($job, $normalizedLabelIds);
        $extractedPath = $jobDir . '/extracted.json';
        $extracted = load_json_file($extractedPath);
        $extractedChanged = false;
        if (is_array($extracted)) {
            $extractedChanged = cleanup_removed_labels_from_unarchived_job($extracted, $normalizedLabelIds);
        }
        if (!$jobChanged && !$extractedChanged) {
            continue;
        }
        $job['updatedAt'] = now_iso();
        write_json_file($jobPath, $job);
        if ($extractedChanged) {
            write_json_file($extractedPath, $extracted);
        }
        queue_job_upsert_event($config, $entry);
        $cleanedJobIds[] = $entry;
    }

    return [
        'cleanedJobIds' => $cleanedJobIds,
        'cleanedCount' => count($cleanedJobIds),
    ];
}

function cleanup_unarchived_jobs_after_field_removal(array $config, array $removedFieldKeys): array
{
    $normalizedFieldKeys = array_values(array_unique(array_filter(array_map(
        static fn ($value): string => is_string($value) ? trim($value) : '',
        $removedFieldKeys
    ), static fn (string $value): bool => $value !== '')));
    if ($normalizedFieldKeys === []) {
        return ['cleanedJobIds' => [], 'cleanedCount' => 0];
    }

    $jobsDir = rtrim((string) ($config['jobsDirectory'] ?? ''), DIRECTORY_SEPARATOR);
    if ($jobsDir === '' || !is_dir($jobsDir)) {
        return ['cleanedJobIds' => [], 'cleanedCount' => 0];
    }

    $cleanedJobIds = [];
    foreach (scandir($jobsDir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $entry;
        if (!is_dir($jobDir)) {
            continue;
        }
        $jobPath = $jobDir . '/job.json';
        $job = load_json_file($jobPath);
        if (!is_array($job) || (($job['archived'] ?? false) === true)) {
            continue;
        }
        $jobChanged = cleanup_removed_fields_from_unarchived_job($job, $normalizedFieldKeys);
        $extractedPath = $jobDir . '/extracted.json';
        $extracted = load_json_file($extractedPath);
        $extractedChanged = false;
        if (is_array($extracted)) {
            $extractedChanged = cleanup_removed_fields_from_unarchived_job($extracted, $normalizedFieldKeys);
        }
        if (!$jobChanged && !$extractedChanged) {
            continue;
        }
        $job['updatedAt'] = now_iso();
        write_json_file($jobPath, $job);
        if ($extractedChanged) {
            write_json_file($extractedPath, $extracted);
        }
        queue_job_upsert_event($config, $entry);
        $cleanedJobIds[] = $entry;
    }

    return [
        'cleanedJobIds' => $cleanedJobIds,
        'cleanedCount' => count($cleanedJobIds),
    ];
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
    $latestEventId = latest_job_event_id();
    if ($latestEventId > 0 && $afterEventId >= $latestEventId) {
        return [];
    }

    $path = job_events_log_path();
    if (!is_file($path)) {
        return [];
    }
    if ($latestEventId > 0 && $afterEventId > 0 && ($latestEventId - $afterEventId) <= $limit) {
        return read_recent_job_events_since($path, $afterEventId, $limit);
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

function read_recent_job_events_since(string $path, int $afterEventId, int $limit): array
{
    $fileSize = @filesize($path);
    if (!is_int($fileSize) || $fileSize <= 0) {
        return [];
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return [];
    }

    $events = [];
    $buffer = '';
    $position = $fileSize;
    $chunkSize = 65536;

    try {
        while ($position > 0 && count($events) < $limit) {
            $readSize = min($chunkSize, $position);
            $position -= $readSize;
            if (fseek($handle, $position) !== 0) {
                break;
            }
            $chunk = fread($handle, $readSize);
            if (!is_string($chunk) || $chunk === '') {
                break;
            }

            $buffer = $chunk . $buffer;
            $lines = explode("\n", $buffer);
            if ($position > 0) {
                $buffer = array_shift($lines);
            } else {
                $buffer = '';
            }

            for ($index = count($lines) - 1; $index >= 0; $index--) {
                $line = trim($lines[$index]);
                if ($line === '') {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (!is_array($decoded)) {
                    continue;
                }
                $eventId = isset($decoded['id']) ? (int) $decoded['id'] : 0;
                if ($eventId <= $afterEventId) {
                    return array_reverse($events);
                }
                $events[] = $decoded;
                if (count($events) >= $limit) {
                    break 2;
                }
            }
        }

        $line = trim($buffer);
        if ($line !== '' && count($events) < $limit) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $eventId = isset($decoded['id']) ? (int) $decoded['id'] : 0;
                if ($eventId > $afterEventId) {
                    $events[] = $decoded;
                }
            }
        }
    } finally {
        fclose($handle);
    }

    return array_reverse($events);
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

function normalized_realpath(string $path): ?string
{
    $resolved = realpath($path);
    return is_string($resolved) && $resolved !== '' ? $resolved : null;
}

function path_is_within_directory(string $path, string $directory): bool
{
    $normalizedPath = str_replace('\\', '/', $path);
    $normalizedDirectory = rtrim(str_replace('\\', '/', $directory), '/');
    if ($normalizedDirectory === '') {
        return false;
    }

    return $normalizedPath === $normalizedDirectory
        || str_starts_with($normalizedPath, $normalizedDirectory . '/');
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
    $resetJobIds = [];
    $skippedArchivedJobFolders = 0;
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
        if (is_array($job) && ($job['archived'] ?? false) === true) {
            $skippedArchivedJobFolders++;
            continue;
        }
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
        $resetJobIds[] = $entry;
        queue_job_remove_event($entry);
    }

    if ($restoredSources > 0 || $removedJobFolders > 0) {
        ensure_job_dispatcher_running($config);
    }

    return [
        'restoredSources' => $restoredSources,
        'removedJobFolders' => $removedJobFolders,
        'resetJobIds' => $resetJobIds,
        'skippedArchivedJobFolders' => $skippedArchivedJobFolders,
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

function delete_job_by_id(array $config, string $jobId, bool $deleteArchivedFile = false): array
{
    if (!is_valid_job_id($jobId)) {
        throw new RuntimeException('Invalid job id');
    }

    $jobsDir = rtrim((string) ($config['jobsDirectory'] ?? ''), DIRECTORY_SEPARATOR);
    if ($jobsDir === '') {
        throw new RuntimeException('Jobs directory is not configured');
    }
    ensure_directory($jobsDir);

    $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $jobId;
    $jobDirectoryExisted = is_dir($jobDir);
    $job = load_json_file($jobDir . '/job.json');
    $isArchived = is_array($job) && (($job['archived'] ?? false) === true);
    $archivedPdfPath = is_array($job) && is_string($job['archivedPdfPath'] ?? null)
        ? trim((string) $job['archivedPdfPath'])
        : '';

    if ($deleteArchivedFile && $isArchived && $archivedPdfPath !== '' && is_file($archivedPdfPath)) {
        $outputBaseDirectory = trim((string) ($config['outputBaseDirectory'] ?? ''));
        $resolvedOutputBaseDirectory = $outputBaseDirectory !== '' ? normalized_realpath($outputBaseDirectory) : null;
        if ($resolvedOutputBaseDirectory === null || !is_dir($resolvedOutputBaseDirectory)) {
            throw new RuntimeException('Arkivets bassökväg kunde inte verifieras.');
        }

        $resolvedArchivedPdfPath = normalized_realpath($archivedPdfPath);
        if ($resolvedArchivedPdfPath === null || !is_file($resolvedArchivedPdfPath)) {
            throw new RuntimeException('Den arkiverade filen kunde inte verifieras.');
        }
        if (!path_is_within_directory($resolvedArchivedPdfPath, $resolvedOutputBaseDirectory)) {
            throw new RuntimeException('Den arkiverade filen ligger utanför tillåtet arkiv.');
        }
        if (!unlink($resolvedArchivedPdfPath)) {
            throw new RuntimeException('Kunde inte ta bort den arkiverade filen.');
        }
    }

    if (is_dir($jobDir) && !delete_directory_recursive($jobDir)) {
        throw new RuntimeException('Kunde inte ta bort jobbets mapp.');
    }

    delete_job_repository_entry($jobId);
    sync_archiving_update_session_after_job_removal($config, $jobId);
    queue_job_remove_event($jobId);

    return [
        'jobId' => $jobId,
        'deletedArchivedFile' => $deleteArchivedFile && $isArchived && $archivedPdfPath !== '',
        'alreadyDeleted' => !$jobDirectoryExisted,
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

    $storedDocflowOcrVersion = job_docflow_ocr_version($job);
    $requiresFreshOcr = $storedDocflowOcrVersion === null || $storedDocflowOcrVersion < docflow_ocr_version();
    if ($normalizedMode === 'post-ocr' && $requiresFreshOcr) {
        $normalizedMode = 'full';
        $forceOcr = true;
    }

    if ($normalizedMode === 'post-ocr' && !is_file($jobDir . '/review.pdf')) {
        if (!is_file($sourcePath)) {
            throw new RuntimeException('Missing review.pdf');
        }
        $normalizedMode = 'full';
        $forceOcr = true;
    }

    if ($normalizedMode === 'full' && !is_file($sourcePath)) {
        throw new RuntimeException('Missing source.pdf');
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

    $job['metadataSyncPreviousAutoArchivingResult'] = job_auto_archiving_result($job);
    $currentLabelIdsForMetadataSync = job_document_label_ids_for_metadata_sync($jobId, $job);
    if ($currentLabelIdsForMetadataSync !== null) {
        $job['metadataSyncPreviousDocumentLabelIds'] = $currentLabelIdsForMetadataSync;
    } else {
        unset($job['metadataSyncPreviousDocumentLabelIds']);
    }
    $currentDataValuesForMetadataSync = job_document_data_values_for_metadata_sync($jobId, $job);
    if ($currentDataValuesForMetadataSync !== null) {
        $job['metadataSyncPreviousDocumentDataValues'] = $currentDataValuesForMetadataSync;
    } else {
        unset($job['metadataSyncPreviousDocumentDataValues']);
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
        $job['dismissedAnalysisVersion'],
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
