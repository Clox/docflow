#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/experiment-ocr-merge.php <job-id>\n");
    exit(1);
}

$jobId = trim((string) $argv[1]);
if (!is_valid_job_id($jobId)) {
    fwrite(STDERR, "Invalid job id\n");
    exit(1);
}

try {
    $config = load_config();
    $jobDir = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jobId;
    $payload = run_job_merge_experiment($jobDir);

    $summary = [
        'jobId' => $jobId,
        'pageCount' => $payload['pageCount'] ?? 0,
        'mergeJson' => $jobDir . '/merge-experiment.json',
        'mergeText' => $jobDir . '/merge-experiment.txt',
    ];
    fwrite(STDOUT, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
