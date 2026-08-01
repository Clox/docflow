<?php
declare(strict_types=1);

use Docflow\Banking\BankAccountRepository;
use Docflow\Database\Connection;
use Docflow\Database\MigrationRunner;

require_once __DIR__ . '/../src/autoload.php';

$environment = strtolower(trim((string) getenv('DOCFLOW_ENV')));
if (!in_array($environment, ['development', 'test'], true)) {
    fwrite(STDERR, "Dummy-konton skapas endast när DOCFLOW_ENV=development eller test.\n");
    exit(1);
}

$pdo = Connection::make();
(new MigrationRunner($pdo, __DIR__ . '/../database/migrations'))->migrate();
$repository = new BankAccountRepository($pdo);

$accounts = [
    [
        'bankKey' => 'handelsbanken',
        'externalAccountId' => 'docflow-development-dummy-1',
        'accountNumber' => 'DEV-1234',
        'accountName' => 'Allkonto',
        'accountType' => 'transaction',
        'currency' => 'SEK',
        'accountHolderName' => 'Testperson Ett',
        'canViewBalance' => true,
        'canViewTransactions' => true,
        'canRegisterPayments' => true,
        'isActive' => true,
    ],
    [
        'bankKey' => 'swedbank',
        'externalAccountId' => 'docflow-development-dummy-2',
        'accountNumber' => 'DEV-5678',
        'accountName' => 'Sparkonto',
        'accountType' => 'savings',
        'currency' => 'SEK',
        'accountHolderName' => 'Testperson Två',
        'canViewBalance' => true,
        'canViewTransactions' => true,
        'canRegisterPayments' => false,
        'isActive' => true,
    ],
];

foreach ($accounts as $account) {
    $stored = $repository->upsertExternalAccount($account);
    fwrite(STDOUT, sprintf(
        "Sparade dummy-konto %d (%s).\n",
        (int) ($stored['id'] ?? 0),
        (string) ($stored['account_name'] ?? '')
    ));
}
