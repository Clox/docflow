<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $payload = load_matching_settings_payload();
    json_response([
        'replacements' => is_array($payload['replacements'] ?? null) ? $payload['replacements'] : [],
        'invoiceFieldMinConfidence' => sanitize_invoice_field_min_confidence(
            $payload['invoiceFieldMinConfidence'] ?? null
        ),
    ]);
} catch (Throwable $e) {
    json_response([
        'replacements' => [],
        'invoiceFieldMinConfidence' => default_invoice_field_min_confidence(),
    ], 500);
}
