<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $payload = build_configuration_export_payload();
    $text = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($text)) {
        throw new RuntimeException('Could not encode configuration export.');
    }

    json_response([
        'ok' => true,
        'filename' => configuration_export_filename(is_string($payload['exportedAt'] ?? null) ? (string) $payload['exportedAt'] : null),
        'text' => $text,
    ]);
} catch (Throwable $e) {
    json_response([
        'error' => $e->getMessage(),
    ], 500);
}
