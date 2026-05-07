#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

try {
    $config = load_config();
    $jobsDir = $config['jobsDirectory'];
    ensure_directory($jobsDir);

    $lockPath = $jobsDir . DIRECTORY_SEPARATOR . '.reanalyze-all.lock';
    $lockHandle = fopen($lockPath, 'c+');
    if ($lockHandle === false) {
        exit(0);
    }

    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        fclose($lockHandle);
        exit(0);
    }

    register_shutdown_function(static function () use ($lockHandle): void {
        @flock($lockHandle, LOCK_UN);
        @fclose($lockHandle);
    });

    reanalyze_all_documents($config);
} catch (Throwable $e) {
    // Worker exits quietly; API callers receive errors from the foreground path.
}
