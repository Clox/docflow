<?php
declare(strict_types=1);

namespace Docflow\Banking;

use Docflow\Clients\PersonalIdentityNumberNormalizer;
use PDO;
use RuntimeException;

final class BankAccountRepository
{
    public const DEFAULT_WORKSPACE_KEY = 'default';

    private PDO $pdo;
    private string $workspaceKey;

    public function __construct(PDO $pdo, string $workspaceKey = self::DEFAULT_WORKSPACE_KEY)
    {
        $this->pdo = $pdo;
        $normalizedWorkspaceKey = trim($workspaceKey);
        if ($normalizedWorkspaceKey === '') {
            throw new RuntimeException('Workspace key is required.');
        }
        $this->workspaceKey = $normalizedWorkspaceKey;
    }

    public function listAll(): array
    {
        $this->recalculateAutomaticLinks();
        $statement = $this->pdo->prepare($this->accountSelectSql() . '
            WHERE accounts.workspace_key = :workspace_key
            ORDER BY accounts.is_closed ASC, accounts.is_active DESC, accounts.bank_key ASC,
                accounts.account_name ASC, accounts.id ASC');
        $statement->execute([':workspace_key' => $this->workspaceKey]);
        $rows = $statement->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function findById(int $accountId): ?array
    {
        if ($accountId < 1) {
            return null;
        }
        $this->recalculateAutomaticLinks();
        $statement = $this->pdo->prepare($this->accountSelectSql() . '
            WHERE accounts.workspace_key = :workspace_key AND accounts.id = :account_id
            LIMIT 1');
        $statement->execute([
            ':workspace_key' => $this->workspaceKey,
            ':account_id' => $accountId,
        ]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    public function listAuditEvents(int $accountId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, workspace_key, bank_account_id, previous_principal_id, new_principal_id,
                linkage_method, event_type, actor_user_id, occurred_at, details_json
             FROM bank_account_link_events
             WHERE workspace_key = :workspace_key AND bank_account_id = :account_id
             ORDER BY occurred_at ASC, id ASC'
        );
        $statement->execute([
            ':workspace_key' => $this->workspaceKey,
            ':account_id' => $accountId,
        ]);
        $rows = $statement->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function listPrincipals(): array
    {
        $rows = $this->pdo->query(
            'SELECT id, first_name, last_name, folder_name, personal_identity_number,
                normalized_personal_identity_number, preferred_first_name_index
             FROM clients
             ORDER BY sort_order ASC, id ASC'
        )->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function principalSuggestionForAccount(array $account): ?array
    {
        $holderPin = is_string($account['account_holder_personal_identity_number'] ?? null)
            ? trim((string) $account['account_holder_personal_identity_number'])
            : '';
        $holderName = is_string($account['account_holder_name'] ?? null)
            ? trim((string) $account['account_holder_name'])
            : '';
        if ($holderPin !== '' || $holderName === '') {
            return null;
        }

        $best = null;
        $bestScore = 0.0;
        $ambiguous = false;
        foreach ($this->listPrincipals() as $principal) {
            if (!is_array($principal)) {
                continue;
            }
            $score = $this->nameSuggestionScore($holderName, $principal);
            if ($score < 0.6) {
                continue;
            }
            if ($score > $bestScore + 0.000001) {
                $bestScore = $score;
                $best = $principal;
                $ambiguous = false;
            } elseif (abs($score - $bestScore) < 0.000001) {
                $ambiguous = true;
            }
        }
        if (!is_array($best) || $ambiguous) {
            return null;
        }

        return [
            'suggestedPrincipalId' => (int) $best['id'],
            'suggestionScore' => round($bestScore, 3),
            'suggestionReason' => $bestScore >= 0.999
                ? 'Exakt namnmatchning efter normalisering'
                : 'Stark matchning på förnamn och efternamn',
        ];
    }

    public function upsertConnection(array $connection): array
    {
        $bankKey = $this->requiredText($connection['bankKey'] ?? null, 'Bank key is required.');
        $externalCustomerId = $this->nullableText($connection['externalCustomerId'] ?? null);
        $timestamp = $this->timestamp();
        $existingId = null;
        if ($externalCustomerId !== null) {
            $lookup = $this->pdo->prepare(
                'SELECT id FROM bank_connections
                 WHERE workspace_key = :workspace_key AND bank_key = :bank_key
                   AND external_customer_id = :external_customer_id
                 LIMIT 1'
            );
            $lookup->execute([
                ':workspace_key' => $this->workspaceKey,
                ':bank_key' => $bankKey,
                ':external_customer_id' => $externalCustomerId,
            ]);
            $resolved = $lookup->fetchColumn();
            $existingId = is_numeric($resolved) ? (int) $resolved : null;
        }

        $customerPin = $this->nullableText($connection['customerPersonalIdentityNumber'] ?? null);
        if ($existingId !== null) {
            $statement = $this->pdo->prepare(
                'UPDATE bank_connections SET
                    customer_name = :customer_name,
                    customer_personal_identity_number = :customer_pin,
                    normalized_customer_personal_identity_number = :normalized_customer_pin,
                    connection_label = :connection_label,
                    last_synced_at = :last_synced_at,
                    updated_at = :updated_at
                 WHERE id = :id AND workspace_key = :workspace_key'
            );
            $statement->execute([
                ':customer_name' => $this->nullableText($connection['customerName'] ?? null),
                ':customer_pin' => $customerPin,
                ':normalized_customer_pin' => PersonalIdentityNumberNormalizer::normalize($customerPin),
                ':connection_label' => $this->nullableText($connection['connectionLabel'] ?? null),
                ':last_synced_at' => $this->nullableText($connection['lastSyncedAt'] ?? null),
                ':updated_at' => $timestamp,
                ':id' => $existingId,
                ':workspace_key' => $this->workspaceKey,
            ]);
            return $this->findConnectionById($existingId) ?? [];
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO bank_connections (
                workspace_key, bank_key, external_customer_id, customer_name,
                customer_personal_identity_number, normalized_customer_personal_identity_number,
                connection_label, last_synced_at, created_at, updated_at
             ) VALUES (
                :workspace_key, :bank_key, :external_customer_id, :customer_name,
                :customer_pin, :normalized_customer_pin,
                :connection_label, :last_synced_at, :created_at, :updated_at
             )'
        );
        $statement->execute([
            ':workspace_key' => $this->workspaceKey,
            ':bank_key' => $bankKey,
            ':external_customer_id' => $externalCustomerId,
            ':customer_name' => $this->nullableText($connection['customerName'] ?? null),
            ':customer_pin' => $customerPin,
            ':normalized_customer_pin' => PersonalIdentityNumberNormalizer::normalize($customerPin),
            ':connection_label' => $this->nullableText($connection['connectionLabel'] ?? null),
            ':last_synced_at' => $this->nullableText($connection['lastSyncedAt'] ?? null),
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);
        return $this->findConnectionById((int) $this->pdo->lastInsertId()) ?? [];
    }

    public function upsertExternalAccount(array $account): array
    {
        $bankKey = $this->requiredText($account['bankKey'] ?? null, 'Bank key is required.');
        $connectionId = $this->positiveIntOrNull($account['bankConnectionId'] ?? null);
        if ($connectionId !== null && $this->findConnectionById($connectionId) === null) {
            throw new RuntimeException('Bank connection is unavailable in this workspace.');
        }
        $externalAccountId = $this->nullableText($account['externalAccountId'] ?? null);
        $fallbackKey = $this->nullableText($account['identityFallbackKey'] ?? null);
        if ($externalAccountId === null && $fallbackKey === null) {
            throw new RuntimeException('An external account id or scoped fallback identity is required.');
        }

        $existingId = $this->findAccountIdentityId($bankKey, $connectionId, $externalAccountId, $fallbackKey);
        $timestamp = $this->timestamp();
        $holderPin = $this->nullableText($account['accountHolderPersonalIdentityNumber'] ?? null);
        $params = [
            ':workspace_key' => $this->workspaceKey,
            ':bank_key' => $bankKey,
            ':bank_connection_id' => $connectionId,
            ':external_account_id' => $externalAccountId,
            ':identity_fallback_key' => $fallbackKey,
            ':account_number' => $this->nullableText($account['accountNumber'] ?? null),
            ':clearing_number' => $this->nullableText($account['clearingNumber'] ?? null),
            ':iban' => $this->nullableText($account['iban'] ?? null),
            ':bic' => $this->nullableText($account['bic'] ?? null),
            ':account_name' => $this->nullableText($account['accountName'] ?? null),
            ':account_type' => $this->nullableText($account['accountType'] ?? null),
            ':currency' => $this->nullableText($account['currency'] ?? null),
            ':account_holder_name' => $this->nullableText($account['accountHolderName'] ?? null),
            ':holder_pin' => $holderPin,
            ':normalized_holder_pin' => PersonalIdentityNumberNormalizer::normalize($holderPin),
            ':can_view_balance' => $this->nullableBool($account['canViewBalance'] ?? null),
            ':can_view_transactions' => $this->nullableBool($account['canViewTransactions'] ?? null),
            ':can_register_payments' => $this->nullableBool($account['canRegisterPayments'] ?? null),
            ':can_register_transfers' => $this->nullableBool($account['canRegisterTransfers'] ?? null),
            ':can_view_pending_payments' => $this->nullableBool($account['canViewPendingPayments'] ?? null),
            ':capabilities_json' => $this->encodeJsonOrNull($account['capabilities'] ?? null),
            ':last_seen_at' => $this->nullableText($account['lastSeenAt'] ?? null) ?? $timestamp,
            ':last_synced_at' => $this->nullableText($account['lastSyncedAt'] ?? null),
            ':external_created_at' => $this->nullableText($account['externalCreatedAt'] ?? null),
            ':external_updated_at' => $this->nullableText($account['externalUpdatedAt'] ?? null),
            ':is_active' => ($account['isActive'] ?? true) === false ? 0 : 1,
            ':is_closed' => ($account['isClosed'] ?? false) === true ? 1 : 0,
            ':updated_at' => $timestamp,
        ];

        if ($existingId === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO bank_accounts (
                    workspace_key, bank_key, bank_connection_id, external_account_id, identity_fallback_key,
                    account_number, clearing_number, iban, bic, account_name, account_type, currency,
                    account_holder_name, account_holder_personal_identity_number,
                    normalized_account_holder_personal_identity_number,
                    can_view_balance, can_view_transactions, can_register_payments, can_register_transfers,
                    can_view_pending_payments, capabilities_json, first_seen_at, last_seen_at, last_synced_at,
                    external_created_at, external_updated_at, is_active, is_closed, created_at, updated_at
                 ) VALUES (
                    :workspace_key, :bank_key, :bank_connection_id, :external_account_id, :identity_fallback_key,
                    :account_number, :clearing_number, :iban, :bic, :account_name, :account_type, :currency,
                    :account_holder_name, :holder_pin, :normalized_holder_pin,
                    :can_view_balance, :can_view_transactions, :can_register_payments, :can_register_transfers,
                    :can_view_pending_payments, :capabilities_json, :first_seen_at, :last_seen_at, :last_synced_at,
                    :external_created_at, :external_updated_at, :is_active, :is_closed, :created_at, :updated_at
                 )'
            );
            $params[':first_seen_at'] = $this->nullableText($account['firstSeenAt'] ?? null) ?? $timestamp;
            $params[':created_at'] = $timestamp;
            $statement->execute($params);
            $existingId = (int) $this->pdo->lastInsertId();
        } else {
            $statement = $this->pdo->prepare(
                'UPDATE bank_accounts SET
                    bank_key = :bank_key, bank_connection_id = :bank_connection_id,
                    external_account_id = :external_account_id, identity_fallback_key = :identity_fallback_key,
                    account_number = :account_number, clearing_number = :clearing_number, iban = :iban, bic = :bic,
                    account_name = :account_name, account_type = :account_type, currency = :currency,
                    account_holder_name = :account_holder_name,
                    account_holder_personal_identity_number = :holder_pin,
                    normalized_account_holder_personal_identity_number = :normalized_holder_pin,
                    can_view_balance = :can_view_balance, can_view_transactions = :can_view_transactions,
                    can_register_payments = :can_register_payments, can_register_transfers = :can_register_transfers,
                    can_view_pending_payments = :can_view_pending_payments, capabilities_json = :capabilities_json,
                    last_seen_at = :last_seen_at, last_synced_at = :last_synced_at,
                    external_created_at = :external_created_at, external_updated_at = :external_updated_at,
                    is_active = :is_active, is_closed = :is_closed, updated_at = :updated_at
                 WHERE id = :id AND workspace_key = :workspace_key'
            );
            $params[':id'] = $existingId;
            $statement->execute($params);
        }

        $this->recalculateAutomaticLinks();
        return $this->findById($existingId) ?? [];
    }

    public function linkManually(
        int $accountId,
        int $principalId,
        bool $suggestionConfirmed = false,
        ?string $actorUserId = null
    ): array {
        $account = $this->findRawById($accountId);
        if ($account === null) {
            throw new RuntimeException('Bankkontot finns inte i den aktuella klienten.');
        }
        $holderPin = is_string($account['account_holder_personal_identity_number'] ?? null)
            ? trim((string) $account['account_holder_personal_identity_number'])
            : '';
        if ($holderPin !== '') {
            throw new RuntimeException('Konton med bankrapporterat personnummer kan inte kopplas manuellt.');
        }
        if ($this->findPrincipalById($principalId) === null) {
            throw new RuntimeException('Huvudmannen finns inte.');
        }

        $previousPrincipalId = $this->positiveIntOrNull($account['principal_id'] ?? null);
        $method = $suggestionConfirmed ? 'name_suggestion_confirmed' : 'manual';
        $timestamp = $this->timestamp();
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'UPDATE bank_accounts SET principal_id = :principal_id, linkage_method = :linkage_method,
                    linkage_status = :linkage_status, linked_at = :linked_at,
                    linked_by_user_id = :linked_by_user_id, updated_at = :updated_at
                 WHERE id = :id AND workspace_key = :workspace_key'
            );
            $statement->execute([
                ':principal_id' => $principalId,
                ':linkage_method' => $method,
                ':linkage_status' => 'linked',
                ':linked_at' => $timestamp,
                ':linked_by_user_id' => $this->nullableText($actorUserId),
                ':updated_at' => $timestamp,
                ':id' => $accountId,
                ':workspace_key' => $this->workspaceKey,
            ]);
            $this->insertAuditEvent(
                $accountId,
                $previousPrincipalId,
                $principalId,
                $method,
                $previousPrincipalId === null ? 'linked' : 'changed',
                $actorUserId,
                ['source' => $suggestionConfirmed ? 'name_suggestion' : 'manual']
            );
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
        return $this->findById($accountId) ?? [];
    }

    public function unlinkManual(int $accountId, ?string $actorUserId = null): array
    {
        $account = $this->findRawById($accountId);
        if ($account === null) {
            throw new RuntimeException('Bankkontot finns inte i den aktuella klienten.');
        }
        $method = is_string($account['linkage_method'] ?? null) ? (string) $account['linkage_method'] : '';
        if (!in_array($method, ['manual', 'name_suggestion_confirmed'], true)) {
            throw new RuntimeException('Endast manuella kontokopplingar kan tas bort här.');
        }
        $previousPrincipalId = $this->positiveIntOrNull($account['principal_id'] ?? null);
        $timestamp = $this->timestamp();
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'UPDATE bank_accounts SET principal_id = NULL, linkage_method = NULL,
                    linkage_status = :linkage_status, linked_at = NULL,
                    linked_by_user_id = NULL, updated_at = :updated_at
                 WHERE id = :id AND workspace_key = :workspace_key'
            );
            $statement->execute([
                ':linkage_status' => 'unlinked',
                ':updated_at' => $timestamp,
                ':id' => $accountId,
                ':workspace_key' => $this->workspaceKey,
            ]);
            $this->insertAuditEvent(
                $accountId,
                $previousPrincipalId,
                null,
                $method,
                'unlinked',
                $actorUserId,
                ['source' => 'manual']
            );
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
        return $this->findById($accountId) ?? [];
    }

    public function recalculateAutomaticLinks(): void
    {
        $statement = $this->pdo->prepare(
            'SELECT id, principal_id, linkage_method, linkage_status,
                account_holder_personal_identity_number,
                normalized_account_holder_personal_identity_number
             FROM bank_accounts
             WHERE workspace_key = :workspace_key'
        );
        $statement->execute([':workspace_key' => $this->workspaceKey]);
        $accounts = $statement->fetchAll();
        if (!is_array($accounts) || $accounts === []) {
            return;
        }

        $principalRows = $this->pdo->query(
            'SELECT id, normalized_personal_identity_number
             FROM clients
             WHERE normalized_personal_identity_number IS NOT NULL
               AND trim(normalized_personal_identity_number) <> \'\''
        )->fetchAll();
        $principalByPin = [];
        foreach (is_array($principalRows) ? $principalRows : [] as $principal) {
            if (!is_array($principal)) {
                continue;
            }
            $pin = (string) ($principal['normalized_personal_identity_number'] ?? '');
            if ($pin !== '') {
                $principalByPin[$pin] = (int) $principal['id'];
            }
        }

        foreach ($accounts as $account) {
            if (!is_array($account)) {
                continue;
            }
            $accountId = (int) ($account['id'] ?? 0);
            $previousPrincipalId = $this->positiveIntOrNull($account['principal_id'] ?? null);
            $previousMethod = is_string($account['linkage_method'] ?? null) ? (string) $account['linkage_method'] : '';
            $rawPin = is_string($account['account_holder_personal_identity_number'] ?? null)
                ? trim((string) $account['account_holder_personal_identity_number'])
                : '';
            $normalizedPin = PersonalIdentityNumberNormalizer::normalize($rawPin);

            if ($rawPin === '') {
                if ($previousMethod === 'personal_identity_number_auto') {
                    $this->updateAutomaticLink($accountId, $previousPrincipalId, null, null, 'unlinked');
                } elseif ($previousPrincipalId === null && in_array($previousMethod, ['manual', 'name_suggestion_confirmed'], true)) {
                    $this->updateAutomaticLink($accountId, null, null, null, 'unlinked', false);
                }
                continue;
            }

            $targetPrincipalId = $normalizedPin !== null ? ($principalByPin[$normalizedPin] ?? null) : null;
            if ($targetPrincipalId !== null) {
                if ($previousPrincipalId !== $targetPrincipalId || $previousMethod !== 'personal_identity_number_auto') {
                    $this->updateAutomaticLink(
                        $accountId,
                        $previousPrincipalId,
                        $targetPrincipalId,
                        'personal_identity_number_auto',
                        'linked'
                    );
                }
                continue;
            }

            if ($previousPrincipalId !== null || $previousMethod !== '' || ($account['linkage_status'] ?? '') !== 'unlinked') {
                $this->updateAutomaticLink($accountId, $previousPrincipalId, null, null, 'unlinked');
            }
        }
    }

    private function updateAutomaticLink(
        int $accountId,
        ?int $previousPrincipalId,
        ?int $newPrincipalId,
        ?string $method,
        string $status,
        bool $audit = true
    ): void {
        $timestamp = $this->timestamp();
        $statement = $this->pdo->prepare(
            'UPDATE bank_accounts SET principal_id = :principal_id, linkage_method = :linkage_method,
                linkage_status = :linkage_status, linked_at = :linked_at,
                linked_by_user_id = NULL, updated_at = :updated_at
             WHERE id = :id AND workspace_key = :workspace_key'
        );
        $statement->execute([
            ':principal_id' => $newPrincipalId,
            ':linkage_method' => $method,
            ':linkage_status' => $status,
            ':linked_at' => $newPrincipalId !== null ? $timestamp : null,
            ':updated_at' => $timestamp,
            ':id' => $accountId,
            ':workspace_key' => $this->workspaceKey,
        ]);
        if ($audit) {
            $this->insertAuditEvent(
                $accountId,
                $previousPrincipalId,
                $newPrincipalId,
                $method ?? ($previousPrincipalId !== null ? 'personal_identity_number_auto' : null),
                $newPrincipalId !== null ? 'auto_linked' : 'auto_unlinked',
                null,
                ['source' => 'personal_identity_number']
            );
        }
    }

    private function insertAuditEvent(
        int $accountId,
        ?int $previousPrincipalId,
        ?int $newPrincipalId,
        ?string $method,
        string $eventType,
        ?string $actorUserId,
        array $details
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO bank_account_link_events (
                workspace_key, bank_account_id, previous_principal_id, new_principal_id,
                linkage_method, event_type, actor_user_id, occurred_at, details_json
             ) VALUES (
                :workspace_key, :bank_account_id, :previous_principal_id, :new_principal_id,
                :linkage_method, :event_type, :actor_user_id, :occurred_at, :details_json
             )'
        );
        $statement->execute([
            ':workspace_key' => $this->workspaceKey,
            ':bank_account_id' => $accountId,
            ':previous_principal_id' => $previousPrincipalId,
            ':new_principal_id' => $newPrincipalId,
            ':linkage_method' => $method,
            ':event_type' => $eventType,
            ':actor_user_id' => $this->nullableText($actorUserId),
            ':occurred_at' => $this->timestamp(),
            ':details_json' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function findRawById(int $accountId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM bank_accounts
             WHERE id = :account_id AND workspace_key = :workspace_key
             LIMIT 1'
        );
        $statement->execute([
            ':account_id' => $accountId,
            ':workspace_key' => $this->workspaceKey,
        ]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    private function findPrincipalById(int $principalId): ?array
    {
        if ($principalId < 1) {
            return null;
        }
        $statement = $this->pdo->prepare(
            'SELECT id, first_name, last_name, folder_name FROM clients WHERE id = :id LIMIT 1'
        );
        $statement->execute([':id' => $principalId]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    private function findConnectionById(int $connectionId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM bank_connections
             WHERE id = :id AND workspace_key = :workspace_key
             LIMIT 1'
        );
        $statement->execute([
            ':id' => $connectionId,
            ':workspace_key' => $this->workspaceKey,
        ]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    private function findAccountIdentityId(
        string $bankKey,
        ?int $connectionId,
        ?string $externalAccountId,
        ?string $fallbackKey
    ): ?int {
        if ($externalAccountId !== null) {
            if ($connectionId !== null) {
                $statement = $this->pdo->prepare(
                    'SELECT id FROM bank_accounts
                     WHERE workspace_key = :workspace_key AND bank_connection_id = :connection_id
                       AND external_account_id = :external_account_id
                     LIMIT 1'
                );
                $statement->execute([
                    ':workspace_key' => $this->workspaceKey,
                    ':connection_id' => $connectionId,
                    ':external_account_id' => $externalAccountId,
                ]);
            } else {
                $statement = $this->pdo->prepare(
                    'SELECT id FROM bank_accounts
                     WHERE workspace_key = :workspace_key AND bank_connection_id IS NULL
                       AND bank_key = :bank_key AND external_account_id = :external_account_id
                     LIMIT 1'
                );
                $statement->execute([
                    ':workspace_key' => $this->workspaceKey,
                    ':bank_key' => $bankKey,
                    ':external_account_id' => $externalAccountId,
                ]);
            }
        } else {
            if ($connectionId !== null) {
                $statement = $this->pdo->prepare(
                    'SELECT id FROM bank_accounts
                     WHERE workspace_key = :workspace_key AND bank_connection_id = :connection_id
                       AND identity_fallback_key = :fallback_key
                       AND (external_account_id IS NULL OR trim(external_account_id) = \'\')
                     LIMIT 1'
                );
                $statement->execute([
                    ':workspace_key' => $this->workspaceKey,
                    ':connection_id' => $connectionId,
                    ':fallback_key' => $fallbackKey,
                ]);
            } else {
                $statement = $this->pdo->prepare(
                    'SELECT id FROM bank_accounts
                     WHERE workspace_key = :workspace_key AND bank_connection_id IS NULL
                       AND bank_key = :bank_key AND identity_fallback_key = :fallback_key
                       AND (external_account_id IS NULL OR trim(external_account_id) = \'\')
                     LIMIT 1'
                );
                $statement->execute([
                    ':workspace_key' => $this->workspaceKey,
                    ':bank_key' => $bankKey,
                    ':fallback_key' => $fallbackKey,
                ]);
            }
        }
        $resolved = $statement->fetchColumn();
        return is_numeric($resolved) ? (int) $resolved : null;
    }

    private function nameSuggestionScore(string $holderName, array $principal): float
    {
        $principalName = trim(
            (string) ($principal['first_name'] ?? '') . ' ' . (string) ($principal['last_name'] ?? '')
        );
        $holderVariants = $this->nameVariants($holderName);
        $principalVariants = $this->nameVariants($principalName);
        $best = 0.0;
        foreach ($holderVariants as $holderVariant) {
            foreach ($principalVariants as $principalVariant) {
                if ($holderVariant === $principalVariant) {
                    $best = max($best, 1.0);
                    continue;
                }
                $holderTokens = explode(' ', $holderVariant);
                $principalTokens = explode(' ', $principalVariant);
                if (
                    $holderTokens[0] === $principalTokens[0]
                    && $holderTokens[count($holderTokens) - 1] === $principalTokens[count($principalTokens) - 1]
                ) {
                    $shorter = count($holderTokens) <= count($principalTokens) ? $holderTokens : $principalTokens;
                    $longer = count($holderTokens) <= count($principalTokens) ? $principalTokens : $holderTokens;
                    if (count(array_diff($shorter, $longer)) === 0) {
                        $best = max($best, 0.92);
                        continue;
                    }
                }
                $intersection = count(array_intersect(array_unique($holderTokens), array_unique($principalTokens)));
                $union = count(array_unique(array_merge($holderTokens, $principalTokens)));
                if ($union > 0) {
                    $best = max($best, $intersection / $union);
                }
            }
        }
        return $best;
    }

    private function nameVariants(string $value): array
    {
        $variants = [$value];
        if (str_contains($value, ',')) {
            [$left, $right] = array_pad(explode(',', $value, 2), 2, '');
            $variants[] = trim($right . ' ' . $left);
        }
        $normalized = [];
        foreach ($variants as $variant) {
            $lower = function_exists('mb_strtolower')
                ? mb_strtolower($variant, 'UTF-8')
                : strtolower($variant);
            $tokens = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $lower);
            $tokens = is_string($tokens) ? trim(preg_replace('/\s+/u', ' ', $tokens) ?? '') : '';
            if ($tokens !== '') {
                $normalized[$tokens] = true;
            }
        }
        return array_keys($normalized);
    }

    private function accountSelectSql(): string
    {
        return 'SELECT accounts.*,
                principals.first_name AS principal_first_name,
                principals.last_name AS principal_last_name,
                principals.folder_name AS principal_folder_name,
                connections.connection_label AS bank_connection_label
            FROM bank_accounts accounts
            LEFT JOIN clients principals ON principals.id = accounts.principal_id
            LEFT JOIN bank_connections connections
                ON connections.id = accounts.bank_connection_id
               AND connections.workspace_key = accounts.workspace_key';
    }

    private function requiredText(mixed $value, string $message): string
    {
        $resolved = $this->nullableText($value);
        if ($resolved === null) {
            throw new RuntimeException($message);
        }
        return $resolved;
    }

    private function nullableText(mixed $value): ?string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return null;
        }
        $resolved = trim((string) $value);
        return $resolved !== '' ? $resolved : null;
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }
        $resolved = (int) $value;
        return $resolved > 0 ? $resolved : null;
    }

    private function nullableBool(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return $value === true || $value === 1 || $value === '1' ? 1 : 0;
    }

    private function encodeJsonOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($encoded) ? $encoded : null;
    }

    private function timestamp(): string
    {
        return date(DATE_ATOM);
    }
}
