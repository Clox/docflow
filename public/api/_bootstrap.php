<?php
declare(strict_types=1);

const PROJECT_ROOT = __DIR__ . '/../../';
const DATA_DIR = PROJECT_ROOT . 'data';

require_once PROJECT_ROOT . 'src/autoload.php';
require_once __DIR__ . '/_functions.php';

// For web requests, keep the background dispatcher alive so jobs continue processing even if
// the watcher endpoint (tick-jobs.php) is not being called.
if (PHP_SAPI !== 'cli') {
    try {
        $config = load_config();
        ensure_job_dispatcher_running($config);
    } catch (Throwable $e) {
        // Best-effort only; the endpoint itself may still succeed.
    }
}
