<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $repository = bank_account_repository_instance(current_bank_workspace_key());
    if ($repository === null) {
        throw new RuntimeException('Kontoregistret är inte tillgängligt.');
    }
    $accountId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if ($accountId !== false && $accountId !== null && $accountId > 0) {
        $row = $repository->findById((int) $accountId);
        if ($row === null) {
            json_response(['error' => 'Bankkontot finns inte i den aktuella klienten.'], 404);
            exit;
        }
        json_response([
            'account' => bank_account_api_payload($repository, $row, true),
            'auditEvents' => $repository->listAuditEvents((int) $accountId),
        ]);
        exit;
    }

    $accounts = array_map(
        static fn (array $row): array => bank_account_api_payload($repository, $row),
        $repository->listAll()
    );
    json_response([
        'accounts' => $accounts,
        'principals' => bank_account_principal_options($repository),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
