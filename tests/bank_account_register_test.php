<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';

use Docflow\Banking\BankAccountRepository;
use Docflow\Clients\ClientRepository;
use Docflow\Database\Connection;
use Docflow\Database\MigrationRunner;

function assert_bank_register(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function principal_input(?int $id, string $firstName, string $lastName, ?string $pin): array
{
    return array_filter([
        'id' => $id,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'folderName' => trim($firstName . ' ' . $lastName),
        'personalIdentityNumber' => $pin ?? '',
        'preferredFirstNameIndex' => null,
    ], static fn (mixed $value, string $key): bool => $key !== 'id' || $value !== null, ARRAY_FILTER_USE_BOTH);
}

$databasePath = tempnam(sys_get_temp_dir(), 'docflow-bank-register-');
if (!is_string($databasePath)) {
    throw new RuntimeException('Could not create temporary database.');
}

try {
    $pdo = Connection::make($databasePath);
    (new MigrationRunner($pdo, __DIR__ . '/../database/migrations'))->migrate();
    $principals = new ClientRepository($pdo);
    $accounts = new BankAccountRepository($pdo, 'workspace-a');

    $savedPrincipals = $principals->replaceAll([
        principal_input(null, 'Anna', 'Andersson', '19800101-1234'),
        principal_input(null, 'Bertil', 'Berg', null),
        principal_input(null, 'Cecilia', 'Carlsson', null),
    ]);
    $annaId = (int) $savedPrincipals[0]['id'];
    $bertilId = (int) $savedPrincipals[1]['id'];
    $ceciliaId = (int) $savedPrincipals[2]['id'];
    assert_bank_register($annaId > 0 && $bertilId > 0 && $ceciliaId > 0, 'Principals must receive stable ids.');
    assert_bank_register($savedPrincipals[1]['personal_identity_number'] === null, 'Several principals must be allowed to omit PIN.');

    $withoutPin = $accounts->upsertExternalAccount([
        'bankKey' => 'handelsbanken',
        'externalAccountId' => 'account-without-pin',
        'accountNumber' => '1111 2222 3333',
        'accountName' => 'Allkonto',
        'accountHolderName' => 'Andersson, Anna',
        'canRegisterPayments' => true,
    ]);
    assert_bank_register((int) $withoutPin['id'] > 0, 'An account without PIN must be stored.');
    assert_bank_register($withoutPin['principal_id'] === null, 'A name suggestion must not create an automatic link.');
    $suggestion = $accounts->principalSuggestionForAccount($withoutPin);
    assert_bank_register(($suggestion['suggestedPrincipalId'] ?? null) === $annaId, 'Reversed comma names must produce a suggestion.');

    $withPin = $accounts->upsertExternalAccount([
        'bankKey' => 'swedbank',
        'externalAccountId' => 'account-with-pin',
        'accountNumber' => '1111 2222 3333',
        'accountName' => 'Privatkonto',
        'accountHolderName' => 'Anna Andersson',
        'accountHolderPersonalIdentityNumber' => '198001011234',
    ]);
    assert_bank_register((int) $withPin['id'] !== (int) $withoutPin['id'], 'Account number must not be the global technical identity.');
    assert_bank_register((int) $withPin['principal_id'] === $annaId, 'A normalized exact PIN match must link automatically.');
    assert_bank_register($withPin['linkage_method'] === 'personal_identity_number_auto', 'Automatic links must expose their linkage method.');

    $unmatched = $accounts->upsertExternalAccount([
        'bankKey' => 'nordea',
        'externalAccountId' => 'unmatched-pin',
        'accountHolderPersonalIdentityNumber' => '19991231-9999',
    ]);
    assert_bank_register($unmatched['principal_id'] === null, 'An unmatched bank-reported PIN must remain unlinked.');
    try {
        $accounts->linkManually((int) $unmatched['id'], $bertilId);
        throw new RuntimeException('Manual linking of a bank-reported PIN unexpectedly succeeded.');
    } catch (RuntimeException $e) {
        assert_bank_register(str_contains($e->getMessage(), 'personnummer'), 'Manual PIN rejection must explain the reason.');
    }

    $principals->replaceAll([
        principal_input($annaId, 'Anna', 'Andersson', '19700101-1111'),
        principal_input($bertilId, 'Bertil', 'Berg', '199912319999'),
        principal_input($ceciliaId, 'Cecilia', 'Carlsson', null),
    ]);
    $withPin = $accounts->findById((int) $withPin['id']);
    $unmatched = $accounts->findById((int) $unmatched['id']);
    assert_bank_register($withPin !== null && $withPin['principal_id'] === null, 'Changing a principal PIN must remove the old automatic link.');
    assert_bank_register($unmatched !== null && (int) $unmatched['principal_id'] === $bertilId, 'An existing account must auto-link when a principal later gets its PIN.');

    $principals->replaceAll([
        principal_input($annaId, 'Anna', 'Andersson', '198001011234'),
        principal_input($bertilId, 'Bertil', 'Berg', null),
        principal_input($ceciliaId, 'Cecilia', 'Carlsson', '19991231-9999'),
    ]);
    $unmatched = $accounts->findById((int) $unmatched['id']);
    assert_bank_register($unmatched !== null && (int) $unmatched['principal_id'] === $ceciliaId, 'The old PIN must be able to auto-link to another principal.');

    $manuallyLinked = $accounts->linkManually((int) $withoutPin['id'], $bertilId);
    assert_bank_register((int) $manuallyLinked['principal_id'] === $bertilId && $manuallyLinked['linkage_method'] === 'manual', 'An account without PIN must support manual linking.');
    $manuallyChanged = $accounts->linkManually((int) $withoutPin['id'], $ceciliaId, true, 'test-user');
    assert_bank_register((int) $manuallyChanged['principal_id'] === $ceciliaId && $manuallyChanged['linkage_method'] === 'name_suggestion_confirmed', 'A confirmed suggestion must use its own linkage method.');

    $principals->replaceAll([
        principal_input($annaId, 'Anna', 'Andersson', '198001011234'),
        principal_input($bertilId, 'Bertil', 'Berg', '20000101-1111'),
        principal_input($ceciliaId, 'Cecilia', 'Carlsson', null),
    ]);
    $manuallyChanged = $accounts->findById((int) $withoutPin['id']);
    assert_bank_register($manuallyChanged !== null && (int) $manuallyChanged['principal_id'] === $ceciliaId, 'A manual link must survive principal PIN changes.');
    $unlinked = $accounts->unlinkManual((int) $withoutPin['id'], 'test-user');
    assert_bank_register($unlinked['principal_id'] === null && $unlinked['linkage_method'] === null, 'A manual link must be removable.');
    $events = $accounts->listAuditEvents((int) $withoutPin['id']);
    assert_bank_register(count($events) === 3, 'Manual link, change and unlink must all be audited.');
    assert_bank_register(($events[1]['actor_user_id'] ?? null) === 'test-user', 'Audit events must retain the acting user.');

    try {
        $principals->replaceAll([
            principal_input($annaId, 'Anna', 'Andersson', '198001011234'),
            principal_input($bertilId, 'Bertil', 'Berg', '19800101-1234'),
            principal_input($ceciliaId, 'Cecilia', 'Carlsson', null),
        ]);
        throw new RuntimeException('Duplicate normalized principal PIN unexpectedly succeeded.');
    } catch (RuntimeException $e) {
        assert_bank_register(str_contains($e->getMessage(), 'Samma personnummer'), 'Duplicate normalized PINs must be rejected clearly.');
    }

    $otherWorkspace = new BankAccountRepository($pdo, 'workspace-b');
    assert_bank_register($otherWorkspace->findById((int) $withoutPin['id']) === null, 'Another workspace must not read the account.');
    try {
        $otherWorkspace->linkManually((int) $withoutPin['id'], $annaId);
        throw new RuntimeException('Cross-workspace link unexpectedly succeeded.');
    } catch (RuntimeException $e) {
        assert_bank_register(str_contains($e->getMessage(), 'aktuella klienten'), 'Cross-workspace linking must be rejected.');
    }

    $connection = $accounts->upsertConnection([
        'bankKey' => 'handelsbanken',
        'externalCustomerId' => 'customer-a',
        'connectionLabel' => 'Testanslutning',
    ]);
    $connectedAccount = $accounts->upsertExternalAccount([
        'bankKey' => 'handelsbanken',
        'bankConnectionId' => $connection['id'],
        'externalAccountId' => 'connected-account',
        'accountNumber' => '0000',
    ]);
    assert_bank_register((int) $connectedAccount['bank_connection_id'] === (int) $connection['id'], 'Accounts must support a scoped bank connection.');
    $secondConnection = $accounts->upsertConnection([
        'bankKey' => 'handelsbanken',
        'externalCustomerId' => 'customer-b',
    ]);
    $fallbackOne = $accounts->upsertExternalAccount([
        'bankKey' => 'handelsbanken',
        'bankConnectionId' => $connection['id'],
        'identityFallbackKey' => 'fallback-account',
        'accountNumber' => '1234',
    ]);
    $fallbackTwo = $accounts->upsertExternalAccount([
        'bankKey' => 'handelsbanken',
        'bankConnectionId' => $secondConnection['id'],
        'identityFallbackKey' => 'fallback-account',
        'accountNumber' => '1234',
    ]);
    assert_bank_register((int) $fallbackOne['id'] !== (int) $fallbackTwo['id'], 'Fallback identity must be scoped to each bank connection.');

    $frontend = file_get_contents(__DIR__ . '/../public/index.php') ?: '';
    assert_bank_register(str_contains($frontend, 'id="settings-template-accounts"'), 'The account register UI must exist.');
    assert_bank_register(!str_contains($frontend, 'id="accounts-add'), 'The account register must not expose an account creation button.');
    preg_match('/<template id="settings-template-accounts">([\s\S]*?)<\/template>/', $frontend, $accountTemplateMatch);
    $accountTemplate = is_string($accountTemplateMatch[1] ?? null) ? $accountTemplateMatch[1] : '';
    assert_bank_register($accountTemplate !== '' && !preg_match('/<input\b/', $accountTemplate), 'Bank fields must not be editable in the account register.');

    $seedScript = file_get_contents(__DIR__ . '/../scripts/seed-development-bank-accounts.php') ?: '';
    assert_bank_register(str_contains($seedScript, "DOCFLOW_ENV"), 'The dummy-account seeder must be environment-gated.');

    // Simulate legacy rows that are distinct as raw strings but collide after normalization.
    $legacyPdo = new PDO('sqlite::memory:');
    $legacyPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $legacyPdo->exec('CREATE TABLE clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT, first_name TEXT NOT NULL, last_name TEXT NOT NULL,
        folder_name TEXT NOT NULL, personal_identity_number TEXT NOT NULL UNIQUE,
        preferred_first_name_index INTEGER NULL, sort_order INTEGER NOT NULL,
        created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    )');
    $legacyPdo->exec("INSERT INTO clients VALUES
        (1, 'A', 'A', 'A', '19800101-1234', NULL, 0, 'now', 'now'),
        (2, 'B', 'B', 'B', '198001011234', NULL, 1, 'now', 'now')");
    $migrationSql = file_get_contents(__DIR__ . '/../database/migrations/048_create_bank_account_register.sql');
    assert_bank_register(is_string($migrationSql), 'The bank register migration must be readable.');
    $legacyPdo->exec($migrationSql);
    $legacyPins = $legacyPdo->query('SELECT normalized_personal_identity_number FROM clients ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    assert_bank_register($legacyPins === [null, null], 'Legacy normalized duplicate PINs must not crash migration or become auto-matchable.');

    fwrite(STDOUT, "bank account register tests passed\n");
} finally {
    @unlink($databasePath);
}
