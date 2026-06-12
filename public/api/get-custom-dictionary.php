<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    json_response([
        'text' => read_docflow_custom_dictionary_text(),
        'path' => docflow_custom_dictionary_path(),
    ]);
} catch (Throwable $e) {
    json_response([
        'error' => $e->getMessage(),
    ], 500);
}
