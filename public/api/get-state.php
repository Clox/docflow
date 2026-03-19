<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $includeClients = array_key_exists('includeClients', $_GET);
    $includeSenders = array_key_exists('includeSenders', $_GET);
    $includeCategories = array_key_exists('includeCategories', $_GET);
    $hasAfterEventId = array_key_exists('afterEventId', $_GET);
    $afterEventId = filter_var($_GET['afterEventId'] ?? null, FILTER_VALIDATE_INT);
    if ($afterEventId === false || $afterEventId === null || $afterEventId < 0) {
        $afterEventId = 0;
    }

    if (!$includeClients && !$includeSenders && !$includeCategories && $hasAfterEventId) {
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
    $categories = $includeCategories ? load_categories() : [];

    $response = [
        'processingJobs' => $jobsPayload['processingJobs'],
        'readyJobs' => $jobsPayload['readyJobs'],
        'archivedJobs' => $jobsPayload['archivedJobs'],
        'failedJobs' => $jobsPayload['failedJobs'],
        'lastEventId' => latest_job_event_id(),
        'stateUpdateTransport' => (string) $config['stateUpdateTransport'],
    ];
    if ($includeClients) {
        $response['clients'] = $clients;
    }
    if ($includeSenders) {
        $response['senders'] = $senders;
    }
    if ($includeCategories) {
        $response['categories'] = $categories;
    }

    json_response($response);
} catch (Throwable $e) {
    json_response([
        'processingJobs' => [],
        'readyJobs' => [],
        'archivedJobs' => [],
        'failedJobs' => [],
        'lastEventId' => 0,
        'stateUpdateTransport' => 'polling',
        'error' => $e->getMessage(),
    ], 500);
}
