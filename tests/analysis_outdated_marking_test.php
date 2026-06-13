<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_analysis_outdated_marking(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function analysis_outdated_test_remove_dir(string $path): void
{
    if (!is_dir($path)) {
        return;
    }
    $entries = scandir($path);
    if ($entries === false) {
        return;
    }
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $child = $path . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($child)) {
            analysis_outdated_test_remove_dir($child);
            continue;
        }
        @unlink($child);
    }
    @rmdir($path);
}

function analysis_outdated_test_write_job(string $jobsDir, string $jobId, array $job): void
{
    $jobDir = $jobsDir . DIRECTORY_SEPARATOR . $jobId;
    ensure_directory($jobDir);
    write_json_file($jobDir . '/job.json', array_merge([
        'id' => $jobId,
        'status' => 'ready',
        'createdAt' => '2026-06-13T10:00:00+00:00',
        'updatedAt' => '2026-06-13T10:00:00+00:00',
        'originalFilename' => $jobId . '.pdf',
        'archived' => false,
    ], $job));
}

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'docflow-analysis-outdated-' . bin2hex(random_bytes(4));
$jobsDir = $root . DIRECTORY_SEPARATOR . 'jobs';
ensure_directory($jobsDir);

try {
    analysis_outdated_test_write_job($jobsDir, 'ready_review', [
        'status' => 'ready',
        'archived' => false,
    ]);
    analysis_outdated_test_write_job($jobsDir, 'ready_archived', [
        'status' => 'ready',
        'archived' => true,
    ]);
    analysis_outdated_test_write_job($jobsDir, 'processing_review', [
        'status' => 'processing',
        'archived' => false,
    ]);
    analysis_outdated_test_write_job($jobsDir, 'failed_review', [
        'status' => 'failed',
        'archived' => false,
    ]);

    $result = mark_ready_jobs_analysis_outdated_for_analysis_change([
        'jobsDirectory' => $jobsDir,
    ]);

    assert_analysis_outdated_marking(
        ($result['markedJobIds'] ?? null) === ['ready_review'],
        'Only ready, unarchived review jobs should be marked as analysis-outdated.'
    );
    assert_analysis_outdated_marking(
        ($result['markedCount'] ?? null) === 1,
        'The marked count should include only ready review jobs.'
    );

    $readyJob = load_json_file($jobsDir . '/ready_review/job.json');
    $archivedJob = load_json_file($jobsDir . '/ready_archived/job.json');
    $processingJob = load_json_file($jobsDir . '/processing_review/job.json');
    $failedJob = load_json_file($jobsDir . '/failed_review/job.json');

    assert_analysis_outdated_marking(($readyJob['analysisOutdated'] ?? false) === true, 'Ready review jobs must be flagged as outdated.');
    assert_analysis_outdated_marking(!array_key_exists('analysisOutdated', $archivedJob), 'Archived jobs must not be flagged as outdated.');
    assert_analysis_outdated_marking(!array_key_exists('analysisOutdated', $processingJob), 'Processing jobs must not be flagged as outdated.');
    assert_analysis_outdated_marking(!array_key_exists('analysisOutdated', $failedJob), 'Failed jobs must not be flagged as outdated.');
} finally {
    analysis_outdated_test_remove_dir($root);
}
