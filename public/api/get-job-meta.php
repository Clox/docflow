<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();

    $id = $_GET['id'] ?? '';
    if (!is_string($id) || !is_valid_job_id($id)) {
        http_response_code(404);
        exit;
    }

    $jobPath = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . $id
        . DIRECTORY_SEPARATOR . 'job.json';

    if (!is_file($jobPath)) {
        http_response_code(404);
        exit;
    }

    $job = load_json_file($jobPath);
    if (!is_array($job)) {
        json_response(['job' => null], 500);
        exit;
    }

    $job['analysis'] = job_analysis_payload($job);

    if (is_array($job['analysis'] ?? null) && array_key_exists('senderLookup', $job['analysis'])) {
        unset($job['analysis']['senderLookup']);
    }

    json_response(['job' => $job]);
} catch (Throwable $e) {
    json_response(['job' => null], 500);
}
