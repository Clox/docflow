<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}
$accountId = isset($payload['accountId']) && is_numeric($payload['accountId']) ? (int) $payload['accountId'] : 0;
if ($accountId < 1) {
    json_response(['error' => 'Konto måste anges.'], 400);
    exit;
}

try {
    $repository = bank_account_repository_instance(current_bank_workspace_key());
    if ($repository === null) {
        throw new RuntimeException('Kontoregistret är inte tillgängligt.');
    }
    $row = $repository->unlinkManual($accountId, 'local-user');
    json_response([
        'ok' => true,
        'account' => bank_account_api_payload($repository, $row),
    ]);
} catch (RuntimeException $e) {
    json_response(['error' => $e->getMessage()], 400);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
