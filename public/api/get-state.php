<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $clients = load_clients();

    claim_and_process_inbox($config, $clients);
    $jobsState = read_jobs_state($config);

    json_response([
        'processingJobs' => $jobsState['processingJobs'],
        'readyJobs' => $jobsState['readyJobs'],
        'failedJobs' => $jobsState['failedJobs'],
        'clients' => $clients,
    ]);
} catch (Throwable $e) {
    json_response([
        'processingJobs' => [],
        'readyJobs' => [],
        'failedJobs' => [],
        'clients' => [],
        'error' => $e->getMessage(),
    ], 500);
}
