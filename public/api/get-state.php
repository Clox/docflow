<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();
    $clients = load_clients();
    $categories = load_categories();

    claim_and_process_inbox($config, $clients);
    trigger_processing_worker();
    $jobsState = read_jobs_state($config);

    json_response([
        'processingJobs' => $jobsState['processingJobs'],
        'readyJobs' => $jobsState['readyJobs'],
        'failedJobs' => $jobsState['failedJobs'],
        'clients' => $clients,
        'categories' => $categories,
    ]);
} catch (Throwable $e) {
    json_response([
        'processingJobs' => [],
        'readyJobs' => [],
        'failedJobs' => [],
        'clients' => [],
        'categories' => [],
        'error' => $e->getMessage(),
    ], 500);
}
