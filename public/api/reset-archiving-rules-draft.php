<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

try {
    $state = reset_draft_archiving_rules_to_active();
    json_response([
        'ok' => true,
        'activeArchivingRulesVersion' => (int) ($state['activeArchivingRulesVersion'] ?? 1),
        'hasUnpublishedChanges' => false,
    ]);
} catch (Throwable $e) {
    json_response([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}
