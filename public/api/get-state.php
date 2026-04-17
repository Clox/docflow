<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    ensure_job_dispatcher_running($config);
    $includeClients = array_key_exists('includeClients', $_GET);
    $includeSenders = array_key_exists('includeSenders', $_GET);
    $includeArchiveStructure = array_key_exists('includeArchiveStructure', $_GET);
    $hasAfterEventId = array_key_exists('afterEventId', $_GET);
    $afterEventId = filter_var($_GET['afterEventId'] ?? null, FILTER_VALIDATE_INT);
    if ($afterEventId === false || $afterEventId === null || $afterEventId < 0) {
        $afterEventId = 0;
    }

    if (!$includeClients && !$includeSenders && !$includeArchiveStructure && $hasAfterEventId) {
        $events = read_job_events_since($afterEventId);
        if (count($events) === 0) {
            http_response_code(204);
            exit;
        }

        $lastEventId = $afterEventId;
        foreach ($events as $event) {
            $eventId = isset($event['id']) ? (int) $event['id'] : 0;
            if ($eventId > $lastEventId) {
                $lastEventId = $eventId;
            }
        }

        json_response([
            'events' => $events,
            'lastEventId' => $lastEventId,
            'stateUpdateTransport' => (string) $config['stateUpdateTransport'],
        ]);
        exit;
    }

    $jobsPayload = build_jobs_state_payload($config);
    $clients = $includeClients ? load_clients() : [];
    $senders = $includeSenders ? load_senders() : [];
    $archiveFolders = $includeArchiveStructure ? load_archive_folders() : [];

    $response = [
        'processingJobs' => $jobsPayload['processingJobs'],
        'readyJobs' => $jobsPayload['readyJobs'],
        'archivedJobs' => $jobsPayload['archivedJobs'],
        'failedJobs' => $jobsPayload['failedJobs'],
        'senderPayeeLookupQueue' => build_sender_payee_lookup_queue_state_payload(1),
        'senderOrganizationLookupQueue' => build_sender_organization_lookup_queue_state_payload(1),
        'archivingRules' => build_archiving_rules_state_payload($config),
        'lastEventId' => latest_job_event_id(),
        'stateUpdateTransport' => (string) $config['stateUpdateTransport'],
    ];
    if ($includeClients) {
        $response['clients'] = $clients;
    }
    if ($includeSenders) {
        $response['senders'] = $senders;
    }
    if ($includeArchiveStructure) {
        $response['archiveFolders'] = $archiveFolders;
    }

    json_response($response);
} catch (Throwable $e) {
    json_response([
        'processingJobs' => [],
        'readyJobs' => [],
        'archivedJobs' => [],
        'failedJobs' => [],
        'senderPayeeLookupQueue' => [
            'remainingCount' => 0,
            'item' => null,
        ],
        'senderOrganizationLookupQueue' => [
            'remainingCount' => 0,
            'item' => null,
        ],
        'archivingRules' => [
            'activeVersion' => 1,
            'hasPendingArchivedUpdates' => false,
            'pendingArchivedUpdateCount' => 0,
            'updateReview' => [
                'activeArchivingRulesVersion' => 1,
                'changedSections' => [],
                'templateChanges' => [],
                'summary' => empty_archiving_review_summary(),
                'jobs' => [],
                'session' => [
                    'status' => 'idle',
                    'ignoreDismissed' => false,
                    'analyzedCount' => 0,
                    'totalCount' => 0,
                    'foundCount' => 0,
                    'remainingCount' => 0,
                ],
                'reason' => '',
                'signature' => '',
            ],
            'signature' => '',
        ],
        'lastEventId' => 0,
        'stateUpdateTransport' => 'polling',
        'error' => $e->getMessage(),
    ], 500);
}
