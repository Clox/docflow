<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

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

if (($payload['format'] ?? null) !== 'docflow_config' || (int) ($payload['version'] ?? 0) !== 1) {
    json_response(['error' => 'Unsupported configuration format'], 400);
    exit;
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
    json_response(['error' => 'Configuration payload is missing required sections'], 400);
    exit;
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
            $paymentNumbers[] = [
                'id' => isset($payment['id']) && is_numeric($payment['id']) ? (int) $payment['id'] : null,
                'type' => is_string($payment['type'] ?? null) ? trim(strtolower((string) $payment['type'])) : '',
                'number' => is_string($payment['number'] ?? null) ? trim((string) $payment['number']) : '',
            ];
        }

        $alternativeNames = [];
        foreach (is_array($row['alternativeNames'] ?? null) ? $row['alternativeNames'] : [] as $alternativeName) {
            $name = is_string($alternativeName) ? trim($alternativeName) : '';
            if ($name !== '') {
                $alternativeNames[] = $name;
            }
        }

        $senders[] = [
            'id' => isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : null,
            'name' => is_string($row['name'] ?? null) ? trim((string) $row['name']) : '',
            'domain' => is_string($row['domain'] ?? null) ? strtolower(trim((string) $row['domain'])) : '',
            'kind' => is_string($row['kind'] ?? null) ? trim((string) $row['kind']) : '',
            'notes' => is_string($row['notes'] ?? null) ? trim((string) $row['notes']) : '',
            'organizationNumbers' => $organizationNumbers,
            'paymentNumbers' => $paymentNumbers,
            'alternativeNames' => $alternativeNames,
        ];
    }

    return $senders;
}

try {
    $backupPayload = build_configuration_export_payload();
    $backupPath = write_configuration_backup($backupPayload);

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
        'labels' => is_array($payload['labels'] ?? null) ? $payload['labels'] : [],
        'systemLabels' => is_array($payload['systemLabels'] ?? null) ? $payload['systemLabels'] : [],
        'fields' => is_array($payload['dataFields']['fields'] ?? null) ? $payload['dataFields']['fields'] : [],
        'predefinedFields' => is_array($payload['dataFields']['predefinedFields'] ?? null) ? $payload['dataFields']['predefinedFields'] : [],
        'systemFields' => is_array($payload['dataFields']['systemFields'] ?? null) ? $payload['dataFields']['systemFields'] : [],
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

    json_response([
        'ok' => true,
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
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 400);
}
