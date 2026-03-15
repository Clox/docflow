<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $clients = load_clients();
    $senders = load_senders();
    $categories = load_categories();
    $jobsState = read_jobs_state($config);

    json_response([
        'processingJobs' => $jobsState['processingJobs'],
        'readyJobs' => $jobsState['readyJobs'],
        'failedJobs' => $jobsState['failedJobs'],
        'stateUpdateTransport' => (string) $config['stateUpdateTransport'],
        'clients' => $clients,
        'senders' => $senders,
        'categories' => $categories,
    ]);
} catch (Throwable $e) {
    json_response([
        'processingJobs' => [],
        'readyJobs' => [],
        'failedJobs' => [],
        'stateUpdateTransport' => 'polling',
        'clients' => [],
        'senders' => [],
        'categories' => [],
        'error' => $e->getMessage(),
    ], 500);
}
