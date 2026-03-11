#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

try {
    $config = load_config();
    run_processing_worker($config);
} catch (Throwable $e) {
    // Worker exits quietly; failures are reflected in job.json when possible.
}
