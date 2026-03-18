<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $includeClients = array_key_exists('includeClients', $_GET);
    $includeSenders = array_key_exists('includeSenders', $_GET);
    $includeCategories = array_key_exists('includeCategories', $_GET);

    $jobsPayload = build_jobs_state_payload($config);
    $encodedJobsPayload = encode_jobs_state_payload($jobsPayload);
    $jobsSig = jobs_state_signature($encodedJobsPayload);

    $requestedJobsSig = is_string($_GET['jobsSig'] ?? null) ? trim((string) $_GET['jobsSig']) : '';
    if (
        !$includeClients
        && !$includeSenders
        && !$includeCategories
        && $requestedJobsSig !== ''
        && hash_equals($jobsSig, $requestedJobsSig)
    ) {
        http_response_code(204);
        exit;
    }

    $clients = $includeClients ? load_clients() : [];
    $senders = $includeSenders ? load_senders() : [];
    $categories = $includeCategories ? load_categories() : [];

    $response = [
        'processingJobs' => $jobsPayload['processingJobs'],
        'readyJobs' => $jobsPayload['readyJobs'],
        'archivedJobs' => $jobsPayload['archivedJobs'],
        'failedJobs' => $jobsPayload['failedJobs'],
        'jobsSig' => $jobsSig,
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
        'jobsSig' => '',
        'stateUpdateTransport' => 'polling',
        'error' => $e->getMessage(),
    ], 500);
}
