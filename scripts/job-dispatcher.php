#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

set_time_limit(0);
ignore_user_abort(true);

$running = true;

if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, static function () use (&$running): void {
        $running = false;
    });
    pcntl_signal(SIGINT, static function () use (&$running): void {
        $running = false;
    });
}

try {
    $config = load_config();
    $jobsDir = $config['jobsDirectory'];
    ensure_directory($jobsDir);

    $lockPath = $jobsDir . DIRECTORY_SEPARATOR . '.dispatcher.lock';
    $lockHandle = fopen($lockPath, 'c+');
    if ($lockHandle === false) {
        exit(0);
    }

    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        fclose($lockHandle);
        exit(0);
    }

    $pidPath = $jobsDir . DIRECTORY_SEPARATOR . '.dispatcher.pid';
    file_put_contents($pidPath, (string) getmypid());

    register_shutdown_function(static function () use ($lockHandle, $pidPath): void {
        @flock($lockHandle, LOCK_UN);
        @fclose($lockHandle);
        @unlink($pidPath);
    });

    while ($running) {
        try {
            $config = load_config();
            $clients = load_clients();

            claim_and_process_inbox($config, $clients);
            trigger_processing_worker();
            advance_archiving_review_sessions_background($config, 5, 5);
        } catch (Throwable $e) {
            // Keep the dispatcher alive; the next loop may recover.
        }

        usleep(1500000);
    }
} catch (Throwable $e) {
    exit(0);
}
