<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    json_response([
        'replacements' => load_matching_settings(),
    ]);
} catch (Throwable $e) {
    json_response([
        'replacements' => [],
    ], 500);
}
