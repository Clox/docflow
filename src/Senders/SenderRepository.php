<?php
declare(strict_types=1);

namespace Docflow\Senders;

use PDO;
use RuntimeException;

final class SenderRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByOrgNumber(string $orgNumber): ?array
    {
        $normalized = IdentifierNormalizer::normalizeOrgNumber($orgNumber);
        if ($normalized === null) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                s.*,
                o.id AS organization_match_id,
                o.organization_number AS organization_match_number,
                o.organization_name AS organization_match_name
            FROM sender_organization_numbers o
            INNER JOIN senders s ON s.id = o.sender_id
            WHERE o.organization_number = :organization_number
            LIMIT 1'
        );
        $statement->execute([':organization_number' => $normalized]);
        $sender = $statement->fetch();

        return is_array($sender) ? $sender : null;
    }

    public function findByBankgiro(string $bankgiro): ?array
    {
        return $this->findByPaymentNumber('bankgiro', $bankgiro);
    }

    public function findByPlusgiro(string $plusgiro): ?array
    {
        return $this->findByPaymentNumber('plusgiro', $plusgiro);
    }

    public function findObservedOrganizationNumberRow(string $organizationNumber): ?array
    {
        $normalized = IdentifierNormalizer::normalizeOrgNumber($organizationNumber);
        if ($normalized === null) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                id,
                organization_number,
                organization_name,
                sender_id,
                source,
                created_at,
                updated_at
            FROM sender_organization_numbers
            WHERE organization_number = :organization_number
            LIMIT 1'
        );
        $statement->execute([':organization_number' => $normalized]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function findObservedPaymentNumberRow(string $type, string $number): ?array
    {
        $normalizedType = trim(strtolower($type));
        $normalizedNumber = $normalizedType === 'bankgiro'
            ? IdentifierNormalizer::normalizeBankgiro($number)
            : IdentifierNormalizer::normalizePlusgiro($number);

        if ($normalizedNumber === null) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                id,
                sender_id,
                type,
                number,
                original_number,
                payee_name,
                payee_lookup_status,
                source,
                confidence,
                created_at,
                updated_at
            FROM sender_payment_numbers
            WHERE type = :type
              AND number = :number
            LIMIT 1'
        );
        $statement->execute([
            ':type' => $normalizedType,
            ':number' => $normalizedNumber,
        ]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function findByNameCaseInsensitive(string $name): ?array
    {
        $normalized = trim($name);
        if ($normalized === '') {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT *
            FROM senders
            WHERE lower(trim(name)) = lower(:name)
            ORDER BY id ASC
            LIMIT 1'
        );
        $statement->execute([':name' => $normalized]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function findByAlternativeNameCaseInsensitive(string $name): ?array
    {
        $normalized = trim($name);
        if ($normalized === '') {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT s.*
            FROM sender_alternative_names a
            INNER JOIN senders s ON s.id = a.sender_id
            WHERE lower(trim(a.name)) = lower(:name)
            ORDER BY s.id ASC
            LIMIT 1'
        );
        $statement->execute([':name' => $normalized]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function listAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, domain, kind, notes, confidence, matching_updated_at, created_at, updated_at
            FROM senders
            ORDER BY name ASC, id ASC'
        );
        $rows = $statement->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function listEditorRows(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                id,
                name,
                domain,
                kind,
                notes,
                confidence,
                matching_updated_at,
                created_at,
                updated_at
            FROM senders
            ORDER BY name ASC, id ASC'
        );

        $rows = $statement->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $sendersById = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $senderId = isset($row['id']) ? (int) $row['id'] : 0;
            if ($senderId < 1) {
                continue;
            }

            $sendersById[$senderId] = [
                'id' => $senderId,
                'name' => is_string($row['name'] ?? null) ? trim((string) $row['name']) : '',
                'domain' => is_string($row['domain'] ?? null) ? trim((string) $row['domain']) : '',
                'kind' => is_string($row['kind'] ?? null) ? trim((string) $row['kind']) : '',
                'notes' => is_string($row['notes'] ?? null) ? (string) $row['notes'] : '',
                'alternativeNames' => [],
                'organizationNumbers' => [],
                'paymentNumbers' => [],
            ];
        }

        if ($sendersById === []) {
            return [];
        }

        $organizationRows = $this->pdo->query(
            'SELECT
                o.id AS organization_id,
                o.sender_id AS organization_sender_id,
                o.organization_number,
                o.organization_name
            FROM sender_organization_numbers o
            INNER JOIN senders s ON s.id = o.sender_id
            ORDER BY s.name ASC, s.id ASC, o.organization_number ASC, o.id ASC'
        )->fetchAll();

        if (is_array($organizationRows)) {
            foreach ($organizationRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['organization_sender_id']) ? (int) $row['organization_sender_id'] : 0;
                if ($senderId < 1 || !isset($sendersById[$senderId])) {
                    continue;
                }
                $organizationId = isset($row['organization_id']) ? (int) $row['organization_id'] : 0;
                if ($organizationId < 1) {
                    continue;
                }

                $sendersById[$senderId]['organizationNumbers'][] = [
                    'id' => $organizationId,
                    'organizationNumber' => $this->formatOrganizationNumberForDisplay(
                        is_string($row['organization_number'] ?? null) ? trim((string) $row['organization_number']) : ''
                    ),
                    'organizationName' => is_string($row['organization_name'] ?? null) ? trim((string) $row['organization_name']) : '',
                ];
            }
        }

        $paymentRows = $this->pdo->query(
            'SELECT
                p.id AS payment_id,
                p.sender_id,
                p.type AS payment_type,
                p.number AS payment_number,
                p.payee_name AS payment_payee_name,
                p.payee_lookup_status AS payment_payee_lookup_status
            FROM sender_payment_numbers p
            INNER JOIN senders s ON s.id = p.sender_id
            ORDER BY s.name ASC, s.id ASC, p.type ASC, p.number ASC, p.id ASC'
        )->fetchAll();

        if (is_array($paymentRows)) {
            foreach ($paymentRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
                if ($senderId < 1 || !isset($sendersById[$senderId])) {
                    continue;
                }

                $paymentId = isset($row['payment_id']) ? (int) $row['payment_id'] : 0;
                if ($paymentId < 1) {
                    continue;
                }

                $type = is_string($row['payment_type'] ?? null) ? trim(strtolower((string) $row['payment_type'])) : 'bankgiro';
                $sendersById[$senderId]['paymentNumbers'][] = [
                    'id' => $paymentId,
                    'type' => $type,
                    'number' => $this->formatPaymentNumberForDisplay(
                        $type,
                        is_string($row['payment_number'] ?? null) ? trim((string) $row['payment_number']) : ''
                    ),
                    'payeeName' => is_string($row['payment_payee_name'] ?? null) ? trim((string) $row['payment_payee_name']) : '',
                    'payeeLookupStatus' => is_string($row['payment_payee_lookup_status'] ?? null) ? trim((string) $row['payment_payee_lookup_status']) : '',
                ];
            }
        }

        $alternativeNameRows = $this->pdo->query(
            'SELECT
                a.sender_id,
                a.name
            FROM sender_alternative_names a
            INNER JOIN senders s ON s.id = a.sender_id
            ORDER BY s.name ASC, s.id ASC, a.name ASC, a.id ASC'
        )->fetchAll();

        if (is_array($alternativeNameRows)) {
            foreach ($alternativeNameRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
                if ($senderId < 1 || !isset($sendersById[$senderId])) {
                    continue;
                }
                $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
                if ($name === '') {
                    continue;
                }
                $sendersById[$senderId]['alternativeNames'][] = $name;
            }
        }

        return array_values($sendersById);
    }

    public function listUnlinkedIdentifierRows(): array
    {
        $items = [];

        $organizationRows = $this->pdo->query(
            'SELECT
                id,
                organization_number,
                organization_name
            FROM sender_organization_numbers
            WHERE sender_id IS NULL
            ORDER BY organization_number ASC, id ASC'
        )->fetchAll();

        if (is_array($organizationRows)) {
            foreach ($organizationRows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $organizationId = isset($row['id']) ? (int) $row['id'] : 0;
                $normalizedNumber = is_string($row['organization_number'] ?? null)
                    ? trim((string) $row['organization_number'])
                    : '';
                if ($organizationId < 1 || $normalizedNumber === '') {
                    continue;
                }

                $items[] = [
                    'key' => 'organization:' . $normalizedNumber,
                    'kind' => 'organization',
                    'id' => $organizationId,
                    'typeLabel' => 'ORG.NR',
                    'number' => $this->formatOrganizationNumberForDisplay($normalizedNumber),
                    'normalizedNumber' => $normalizedNumber,
                    'name' => is_string($row['organization_name'] ?? null) ? trim((string) $row['organization_name']) : '',
                ];
            }
        }

        $paymentRows = $this->pdo->query(
            'SELECT
                id,
                type,
                number,
                payee_name
            FROM sender_payment_numbers
            WHERE sender_id IS NULL
            ORDER BY type ASC, number ASC, id ASC'
        )->fetchAll();

        if (is_array($paymentRows)) {
            foreach ($paymentRows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $paymentId = isset($row['id']) ? (int) $row['id'] : 0;
                $type = is_string($row['type'] ?? null) ? trim(strtolower((string) $row['type'])) : 'bankgiro';
                $normalizedNumber = is_string($row['number'] ?? null) ? trim((string) $row['number']) : '';
                if ($paymentId < 1 || $normalizedNumber === '') {
                    continue;
                }

                $items[] = [
                    'key' => 'payment:' . $type . ':' . $normalizedNumber,
                    'kind' => 'payment',
                    'id' => $paymentId,
                    'typeLabel' => $type === 'plusgiro' ? 'PG' : 'BG',
                    'paymentType' => $type,
                    'number' => $this->formatPaymentNumberForDisplay($type, $normalizedNumber),
                    'normalizedNumber' => $normalizedNumber,
                    'name' => is_string($row['payee_name'] ?? null) ? trim((string) $row['payee_name']) : '',
                ];
            }
        }

        usort(
            $items,
            static function (array $left, array $right): int {
                $leftName = trim((string) ($left['name'] ?? ''));
                $rightName = trim((string) ($right['name'] ?? ''));
                $leftHasName = $leftName !== '';
                $rightHasName = $rightName !== '';

                if ($leftHasName !== $rightHasName) {
                    return $leftHasName ? -1 : 1;
                }

                if ($leftName !== '' || $rightName !== '') {
                    $nameCompare = strcmp(strtolower($leftName), strtolower($rightName));
                    if ($nameCompare !== 0) {
                        return $nameCompare;
                    }
                }

                $leftType = (string) ($left['typeLabel'] ?? '');
                $rightType = (string) ($right['typeLabel'] ?? '');
                if ($leftType !== $rightType) {
                    return strcmp($leftType, $rightType);
                }

                $leftNumber = (string) ($left['normalizedNumber'] ?? '');
                $rightNumber = (string) ($right['normalizedNumber'] ?? '');
                if ($leftNumber !== $rightNumber) {
                    return strcmp($leftNumber, $rightNumber);
                }

                return ((int) ($left['id'] ?? 0)) <=> ((int) ($right['id'] ?? 0));
            }
        );

        return $items;
    }

    public function findEditorRowById(int $senderId): ?array
    {
        if ($senderId < 1) {
            return null;
        }

        foreach ($this->listEditorRows() as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ((int) ($row['id'] ?? 0) === $senderId) {
                return $row;
            }
        }

        return null;
    }

    public function listPaymentNumbersMissingPayeeName(int $limit = 10): array
    {
        $normalizedLimit = max(1, min(100, $limit));
        $statement = $this->pdo->prepare(
            'SELECT
                p.id,
                p.sender_id,
                s.name AS sender_name,
                p.type,
                p.number,
                p.payee_name,
                p.payee_lookup_status
            FROM sender_payment_numbers p
            LEFT JOIN senders s ON s.id = p.sender_id
            WHERE (p.payee_name IS NULL OR trim(p.payee_name) = \'\')
              AND (p.payee_lookup_status IS NULL OR trim(p.payee_lookup_status) = \'\')
            ORDER BY
                CASE WHEN s.name IS NULL OR trim(s.name) = \'\' THEN 1 ELSE 0 END ASC,
                s.name ASC,
                p.type ASC,
                p.number ASC,
                p.id ASC
            LIMIT :limit'
        );
        $statement->bindValue(':limit', $normalizedLimit, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $paymentId = isset($row['id']) ? (int) $row['id'] : 0;
            $senderId = isset($row['sender_id']) && (int) $row['sender_id'] > 0
                ? (int) $row['sender_id']
                : null;
            if ($paymentId < 1) {
                continue;
            }

            $type = is_string($row['type'] ?? null) ? trim(strtolower((string) $row['type'])) : 'bankgiro';
            $normalizedNumber = is_string($row['number'] ?? null) ? trim((string) $row['number']) : '';

            $items[] = [
                'paymentId' => $paymentId,
                'senderId' => $senderId,
                'senderName' => is_string($row['sender_name'] ?? null) ? trim((string) $row['sender_name']) : '',
                'type' => $type,
                'number' => $this->formatPaymentNumberForDisplay($type, $normalizedNumber),
                'normalizedNumber' => $normalizedNumber,
                'payeeName' => is_string($row['payee_name'] ?? null) ? trim((string) $row['payee_name']) : '',
                'payeeLookupStatus' => is_string($row['payee_lookup_status'] ?? null) ? trim((string) $row['payee_lookup_status']) : '',
            ];
        }

        return $items;
    }

    public function countPaymentNumbersMissingPayeeName(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*)
            FROM sender_payment_numbers p
            WHERE (p.payee_name IS NULL OR trim(p.payee_name) = \'\')
              AND (p.payee_lookup_status IS NULL OR trim(p.payee_lookup_status) = \'\')'
        );
        if ($statement === false) {
            return 0;
        }

        return max(0, (int) $statement->fetchColumn());
    }

    public function listOrganizationNumbersMissingName(int $limit = 10): array
    {
        $normalizedLimit = max(1, min(100, $limit));
        $statement = $this->pdo->prepare(
            'SELECT
                o.id,
                o.sender_id,
                s.name AS sender_name,
                o.organization_number,
                o.organization_name,
                o.source
            FROM sender_organization_numbers o
            LEFT JOIN senders s ON s.id = o.sender_id
            WHERE o.organization_name IS NULL OR trim(o.organization_name) = \'\'
            ORDER BY
                CASE WHEN s.name IS NULL OR trim(s.name) = \'\' THEN 1 ELSE 0 END ASC,
                s.name ASC,
                o.organization_number ASC,
                o.id ASC
            LIMIT :limit'
        );
        $statement->bindValue(':limit', $normalizedLimit, PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $organizationId = isset($row['id']) ? (int) $row['id'] : 0;
            $senderId = isset($row['sender_id']) && (int) $row['sender_id'] > 0
                ? (int) $row['sender_id']
                : null;
            if ($organizationId < 1) {
                continue;
            }

            $normalizedNumber = is_string($row['organization_number'] ?? null)
                ? trim((string) $row['organization_number'])
                : '';
            if ($normalizedNumber === '') {
                continue;
            }

            $items[] = [
                'organizationId' => $organizationId,
                'senderId' => $senderId,
                'senderName' => is_string($row['sender_name'] ?? null) ? trim((string) $row['sender_name']) : '',
                'organizationNumber' => $this->formatOrganizationNumberForDisplay($normalizedNumber),
                'normalizedOrganizationNumber' => $normalizedNumber,
                'organizationName' => is_string($row['organization_name'] ?? null) ? trim((string) $row['organization_name']) : '',
                'source' => is_string($row['source'] ?? null) ? trim((string) $row['source']) : '',
            ];
        }

        return $items;
    }

    public function countOrganizationNumbersMissingName(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*)
            FROM sender_organization_numbers o
            WHERE o.organization_name IS NULL OR trim(o.organization_name) = \'\''
        );
        if ($statement === false) {
            return 0;
        }

        return max(0, (int) $statement->fetchColumn());
    }

    public function resolveUnlinkedNamedIdentifiers(): void
    {
        $organizationRows = $this->pdo->query(
            'SELECT id, organization_name
            FROM sender_organization_numbers
            WHERE sender_id IS NULL
              AND organization_name IS NOT NULL
              AND trim(organization_name) <> \'\'
            ORDER BY id ASC'
        )->fetchAll();
        if (is_array($organizationRows)) {
            foreach ($organizationRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $organizationId = isset($row['id']) ? (int) $row['id'] : 0;
                $organizationName = is_string($row['organization_name'] ?? null) ? trim((string) $row['organization_name']) : '';
                if ($organizationId < 1 || $organizationName === '') {
                    continue;
                }
                $this->resolveOrganizationName($organizationId, $organizationName);
            }
        }

        $paymentRows = $this->pdo->query(
            'SELECT id, payee_name
            FROM sender_payment_numbers
            WHERE sender_id IS NULL
              AND payee_name IS NOT NULL
              AND trim(payee_name) <> \'\'
            ORDER BY id ASC'
        )->fetchAll();
        if (is_array($paymentRows)) {
            foreach ($paymentRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $paymentId = isset($row['id']) ? (int) $row['id'] : 0;
                $payeeName = is_string($row['payee_name'] ?? null) ? trim((string) $row['payee_name']) : '';
                if ($paymentId < 1 || $payeeName === '') {
                    continue;
                }
                $this->resolvePaymentPayeeName($paymentId, $payeeName, null);
            }
        }
    }

    public function resolveOrganizationName(int $organizationId, ?string $organizationName, array $alternativeNames = []): array
    {
        if ($organizationId < 1) {
            throw new RuntimeException('Organization id is required.');
        }

        $normalizedOrganizationName = is_string($organizationName) ? trim($organizationName) : '';
        if ($normalizedOrganizationName === '') {
            $normalizedOrganizationName = null;
        }

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $select = $this->pdo->prepare(
                'SELECT id, organization_number, organization_name, sender_id
                FROM sender_organization_numbers
                WHERE id = :id
                LIMIT 1'
            );
            $select->execute([':id' => $organizationId]);
            $row = $select->fetch();
            if (!is_array($row)) {
                throw new RuntimeException('Organization number not found.');
            }

            $previousSenderId = isset($row['sender_id']) && (int) $row['sender_id'] > 0
                ? (int) $row['sender_id']
                : null;
            $resolvedSenderId = null;
            $resolvedAlternativeNames = [];
            if ($normalizedOrganizationName !== null) {
                $resolvedSenderId = $this->ensureSenderIdForCanonicalName($normalizedOrganizationName);
                $resolvedAlternativeNames = $this->upsertAlternativeNames($resolvedSenderId, $alternativeNames);
            }

            $timestamp = date(DATE_ATOM);
            $update = $this->pdo->prepare(
                'UPDATE sender_organization_numbers
                SET organization_name = :organization_name,
                    sender_id = :sender_id,
                    updated_at = :updated_at
                WHERE id = :id'
            );
            $update->execute([
                ':id' => $organizationId,
                ':organization_name' => $normalizedOrganizationName,
                ':sender_id' => $resolvedSenderId,
                ':updated_at' => $timestamp,
            ]);

            if ($resolvedSenderId !== null) {
                $this->touchSenderMatchingUpdatedAt($resolvedSenderId, $timestamp);
            }
            if ($previousSenderId !== null && $previousSenderId !== $resolvedSenderId) {
                $this->touchSenderMatchingUpdatedAt($previousSenderId, $timestamp);
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return [
                'organizationId' => $organizationId,
                'organizationNumber' => is_string($row['organization_number'] ?? null) ? trim((string) $row['organization_number']) : '',
                'organizationName' => $normalizedOrganizationName,
                'alternativeNames' => $resolvedAlternativeNames,
                'previousSenderId' => $previousSenderId,
                'senderId' => $resolvedSenderId,
                'linkChanged' => $previousSenderId !== $resolvedSenderId,
            ];
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateOrganizationName(int $organizationId, ?string $organizationName): void
    {
        if ($organizationId < 1) {
            throw new RuntimeException('Organization id is required.');
        }

        $normalizedOrganizationName = null;
        if (is_string($organizationName)) {
            $normalizedOrganizationName = trim($organizationName);
            if ($normalizedOrganizationName === '') {
                $normalizedOrganizationName = null;
            }
        }

        $statement = $this->pdo->prepare(
            'UPDATE sender_organization_numbers
            SET organization_name = :organization_name,
                updated_at = :updated_at
            WHERE id = :id'
        );
        $statement->execute([
            ':id' => $organizationId,
            ':organization_name' => $normalizedOrganizationName,
            ':updated_at' => date(DATE_ATOM),
        ]);

        if ($statement->rowCount() < 1) {
            $exists = $this->pdo->prepare('SELECT 1 FROM sender_organization_numbers WHERE id = :id LIMIT 1');
            $exists->execute([':id' => $organizationId]);
            if ($exists->fetchColumn() === false) {
                throw new RuntimeException('Organization number not found.');
            }
        }
    }

    public function resolvePaymentPayeeName(int $paymentId, ?string $payeeName, ?string $lookupStatus = null): array
    {
        if ($paymentId < 1) {
            throw new RuntimeException('Payment id is required.');
        }

        $normalizedPayeeName = is_string($payeeName) ? trim($payeeName) : '';
        if ($normalizedPayeeName === '') {
            $normalizedPayeeName = null;
        }

        $normalizedLookupStatus = is_string($lookupStatus) ? trim(strtolower($lookupStatus)) : '';
        if ($normalizedLookupStatus === '') {
            $normalizedLookupStatus = null;
        }
        if ($normalizedLookupStatus !== null && $normalizedLookupStatus !== 'not_found') {
            throw new RuntimeException('Unsupported payee lookup status.');
        }
        if ($normalizedPayeeName !== null) {
            $normalizedLookupStatus = null;
        }

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $select = $this->pdo->prepare(
                'SELECT id, type, number, sender_id
                FROM sender_payment_numbers
                WHERE id = :id
                LIMIT 1'
            );
            $select->execute([':id' => $paymentId]);
            $row = $select->fetch();
            if (!is_array($row)) {
                throw new RuntimeException('Payment number not found.');
            }

            $previousSenderId = isset($row['sender_id']) && (int) $row['sender_id'] > 0
                ? (int) $row['sender_id']
                : null;
            $resolvedSenderId = null;
            if ($normalizedPayeeName !== null) {
                $resolvedSenderId = $this->ensureSenderIdForCanonicalName($normalizedPayeeName);
            }

            $timestamp = date(DATE_ATOM);
            $update = $this->pdo->prepare(
                'UPDATE sender_payment_numbers
                SET payee_name = :payee_name,
                    payee_lookup_status = :payee_lookup_status,
                    sender_id = :sender_id,
                    updated_at = :updated_at
                WHERE id = :id'
            );
            $update->execute([
                ':id' => $paymentId,
                ':payee_name' => $normalizedPayeeName,
                ':payee_lookup_status' => $normalizedLookupStatus,
                ':sender_id' => $resolvedSenderId,
                ':updated_at' => $timestamp,
            ]);

            if ($resolvedSenderId !== null) {
                $this->touchSenderMatchingUpdatedAt($resolvedSenderId, $timestamp);
            }
            if ($previousSenderId !== null && $previousSenderId !== $resolvedSenderId) {
                $this->touchSenderMatchingUpdatedAt($previousSenderId, $timestamp);
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return [
                'paymentId' => $paymentId,
                'type' => is_string($row['type'] ?? null) ? trim((string) $row['type']) : '',
                'number' => is_string($row['number'] ?? null) ? trim((string) $row['number']) : '',
                'payeeName' => $normalizedPayeeName,
                'lookupStatus' => $normalizedLookupStatus,
                'previousSenderId' => $previousSenderId,
                'senderId' => $resolvedSenderId,
                'linkChanged' => $previousSenderId !== $resolvedSenderId,
            ];
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updatePaymentPayeeName(int $paymentId, ?string $payeeName, ?string $lookupStatus = null): void
    {
        if ($paymentId < 1) {
            throw new RuntimeException('Payment id is required.');
        }

        $normalizedPayeeName = null;
        if (is_string($payeeName)) {
            $normalizedPayeeName = trim($payeeName);
            if ($normalizedPayeeName === '') {
                $normalizedPayeeName = null;
            }
        }

        $normalizedLookupStatus = null;
        if (is_string($lookupStatus)) {
            $normalizedLookupStatus = trim(strtolower($lookupStatus));
            if ($normalizedLookupStatus === '') {
                $normalizedLookupStatus = null;
            }
        }
        if ($normalizedLookupStatus !== null && $normalizedLookupStatus !== 'not_found') {
            throw new RuntimeException('Unsupported payee lookup status.');
        }
        if ($normalizedPayeeName !== null) {
            $normalizedLookupStatus = null;
        }

        $statement = $this->pdo->prepare(
            'UPDATE sender_payment_numbers
            SET payee_name = :payee_name,
                payee_lookup_status = :payee_lookup_status,
                updated_at = :updated_at
            WHERE id = :id'
        );
        $statement->execute([
            ':id' => $paymentId,
            ':payee_name' => $normalizedPayeeName,
            ':payee_lookup_status' => $normalizedLookupStatus,
            ':updated_at' => date(DATE_ATOM),
        ]);

        if ($statement->rowCount() < 1) {
            $exists = $this->pdo->prepare('SELECT 1 FROM sender_payment_numbers WHERE id = :id LIMIT 1');
            $exists->execute([':id' => $paymentId]);
            if ($exists->fetchColumn() === false) {
                throw new RuntimeException('Payment number not found.');
            }
        }
    }

    public function findById(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $statement = $this->pdo->prepare('SELECT * FROM senders WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function resolveActiveSenderId(int $senderId): ?int
    {
        if ($senderId < 1) {
            return null;
        }
        return $this->findById($senderId) !== null ? $senderId : null;
    }

    public function findByDocumentIdentifiers(?string $orgNumber, ?string $bankgiro, ?string $plusgiro): ?array
    {
        if (is_string($orgNumber) && trim($orgNumber) !== '') {
            $sender = $this->findByOrgNumber($orgNumber);
            if ($sender !== null) {
                $sender['matchedBy'] = 'org_number';
                $sender['matchedValue'] = IdentifierNormalizer::normalizeOrgNumber($orgNumber);
                return $sender;
            }
        }

        if (is_string($bankgiro) && trim($bankgiro) !== '') {
            $sender = $this->findByBankgiro($bankgiro);
            if ($sender !== null) {
                $sender['matchedBy'] = 'bankgiro';
                $sender['matchedValue'] = IdentifierNormalizer::normalizeBankgiro($bankgiro);
                return $sender;
            }
        }

        if (is_string($plusgiro) && trim($plusgiro) !== '') {
            $sender = $this->findByPlusgiro($plusgiro);
            if ($sender !== null) {
                $sender['matchedBy'] = 'plusgiro';
                $sender['matchedValue'] = IdentifierNormalizer::normalizePlusgiro($plusgiro);
                return $sender;
            }
        }

        return null;
    }

    public function createSender(
        string $name,
        ?string $domain = null,
        ?string $kind = null,
        ?string $notes = null,
        float $confidence = 1.0
    ): int {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Sender name is required.');
        }

        $timestamp = date(DATE_ATOM);
        $statement = $this->pdo->prepare(
            'INSERT INTO senders (
                name,
                domain,
                kind,
                notes,
                confidence,
                matching_updated_at,
                created_at,
                updated_at
            ) VALUES (
                :name,
                :domain,
                :kind,
                :notes,
                :confidence,
                :matching_updated_at,
                :created_at,
                :updated_at
            )'
        );

        $statement->execute([
            ':name' => $name,
            ':domain' => $domain !== null ? trim($domain) : null,
            ':kind' => $kind !== null ? trim($kind) : null,
            ':notes' => $notes,
            ':confidence' => $confidence,
            ':matching_updated_at' => $timestamp,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateSenderBasic(int $id, string $name): void
    {
        $name = trim($name);
        if ($id < 1 || $name === '') {
            throw new RuntimeException('Sender id and name are required.');
        }

        $statement = $this->pdo->prepare(
            'UPDATE senders
            SET name = :name, updated_at = :updated_at
            WHERE id = :id'
        );
        $statement->execute([
            ':id' => $id,
            ':name' => $name,
            ':updated_at' => date(DATE_ATOM),
        ]);

        if ($statement->rowCount() < 1) {
            $exists = $this->pdo->prepare('SELECT 1 FROM senders WHERE id = :id LIMIT 1');
            $exists->execute([':id' => $id]);
            if ($exists->fetchColumn() === false) {
                throw new RuntimeException('Sender not found.');
            }
        }
    }

    public function deleteSenderById(int $id): void
    {
        if ($id < 1) {
            throw new RuntimeException('Sender id is required.');
        }

        $statement = $this->pdo->prepare('DELETE FROM senders WHERE id = :id');
        $statement->execute([':id' => $id]);
    }

    public function addPaymentNumber(
        int $senderId,
        string $type,
        string $number,
        ?string $originalNumber = null,
        bool $requiresOcr = false,
        ?string $source = null,
        float $confidence = 1.0
    ): void {
        $normalizedType = trim(strtolower($type));
        if ($normalizedType !== 'bankgiro' && $normalizedType !== 'plusgiro') {
            throw new RuntimeException('Payment number type must be bankgiro or plusgiro.');
        }

        $normalizedNumber = $normalizedType === 'bankgiro'
            ? IdentifierNormalizer::normalizeBankgiro($number)
            : IdentifierNormalizer::normalizePlusgiro($number);

        if ($normalizedNumber === null) {
            throw new RuntimeException('Invalid payment number.');
        }

        $timestamp = date(DATE_ATOM);
        $statement = $this->pdo->prepare(
            'INSERT INTO sender_payment_numbers (
                sender_id,
                type,
                number,
                original_number,
                requires_ocr,
                source,
                confidence,
                created_at,
                updated_at
            ) VALUES (
                :sender_id,
                :type,
                :number,
                :original_number,
                :requires_ocr,
                :source,
                :confidence,
                :created_at,
                :updated_at
            )'
        );

        $statement->execute([
            ':sender_id' => $senderId,
            ':type' => $normalizedType,
            ':number' => $normalizedNumber,
            ':original_number' => $originalNumber,
            ':requires_ocr' => $requiresOcr ? 1 : 0,
            ':source' => $source,
            ':confidence' => $confidence,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);
    }

    public function observeOrganizationNumber(
        string $organizationNumber,
        ?string $organizationName = null,
        ?string $source = 'document_auto'
    ): int
    {
        $normalizedNumber = IdentifierNormalizer::normalizeOrgNumber($organizationNumber);
        if ($normalizedNumber === null) {
            throw new RuntimeException('Invalid organization number.');
        }

        $normalizedName = is_string($organizationName) ? trim($organizationName) : '';
        if ($normalizedName === '') {
            $normalizedName = null;
        }
        $normalizedSource = is_string($source) ? trim($source) : '';
        if ($normalizedSource === '') {
            $normalizedSource = null;
        }

        $select = $this->pdo->prepare(
            'SELECT id, organization_name, source
            FROM sender_organization_numbers
            WHERE organization_number = :organization_number
            LIMIT 1'
        );
        $select->execute([':organization_number' => $normalizedNumber]);
        $existing = $select->fetch();
        $timestamp = date(DATE_ATOM);

        if (is_array($existing)) {
            $organizationId = isset($existing['id']) ? (int) $existing['id'] : 0;
            if ($organizationId < 1) {
                throw new RuntimeException('Observed organization row is invalid.');
            }

            $currentName = is_string($existing['organization_name'] ?? null)
                ? trim((string) $existing['organization_name'])
                : '';
            $currentSource = is_string($existing['source'] ?? null)
                ? trim((string) $existing['source'])
                : '';
            $resolvedName = $currentName !== '' ? $currentName : $normalizedName;

            $update = $this->pdo->prepare(
                'UPDATE sender_organization_numbers
                SET organization_name = :organization_name,
                    source = :source,
                    updated_at = :updated_at
                WHERE id = :id'
            );
            $update->execute([
                ':id' => $organizationId,
                ':organization_name' => $resolvedName !== '' ? $resolvedName : null,
                ':source' => $currentSource !== '' ? $currentSource : $normalizedSource,
                ':updated_at' => $timestamp,
            ]);

            return $organizationId;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO sender_organization_numbers (
                organization_number,
                organization_name,
                sender_id,
                source,
                created_at,
                updated_at
            ) VALUES (
                :organization_number,
                :organization_name,
                NULL,
                :source,
                :created_at,
                :updated_at
            )'
        );
        $insert->execute([
            ':organization_number' => $normalizedNumber,
            ':organization_name' => $normalizedName,
            ':source' => $normalizedSource,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function observePaymentNumber(
        string $type,
        string $number,
        ?string $originalNumber = null,
        ?string $source = 'document_auto',
        float $confidence = 1.0
    ): int {
        $normalizedType = trim(strtolower($type));
        if ($normalizedType !== 'bankgiro' && $normalizedType !== 'plusgiro') {
            throw new RuntimeException('Payment number type must be bankgiro or plusgiro.');
        }

        $normalizedNumber = $normalizedType === 'bankgiro'
            ? IdentifierNormalizer::normalizeBankgiro($number)
            : IdentifierNormalizer::normalizePlusgiro($number);
        if ($normalizedNumber === null) {
            throw new RuntimeException('Invalid payment number.');
        }

        $normalizedOriginalNumber = is_string($originalNumber) ? trim($originalNumber) : '';
        if ($normalizedOriginalNumber === '') {
            $normalizedOriginalNumber = null;
        }
        $normalizedSource = is_string($source) ? trim($source) : '';
        if ($normalizedSource === '') {
            $normalizedSource = null;
        }

        $select = $this->pdo->prepare(
            'SELECT id, original_number, source, confidence
            FROM sender_payment_numbers
            WHERE type = :type
              AND number = :number
            LIMIT 1'
        );
        $select->execute([
            ':type' => $normalizedType,
            ':number' => $normalizedNumber,
        ]);
        $existing = $select->fetch();
        $timestamp = date(DATE_ATOM);

        if (is_array($existing)) {
            $paymentId = isset($existing['id']) ? (int) $existing['id'] : 0;
            if ($paymentId < 1) {
                throw new RuntimeException('Observed payment row is invalid.');
            }

            $currentOriginalNumber = is_string($existing['original_number'] ?? null)
                ? trim((string) $existing['original_number'])
                : '';
            $currentSource = is_string($existing['source'] ?? null)
                ? trim((string) $existing['source'])
                : '';
            $currentConfidence = isset($existing['confidence']) ? (float) $existing['confidence'] : 1.0;

            $update = $this->pdo->prepare(
                'UPDATE sender_payment_numbers
                SET original_number = :original_number,
                    source = :source,
                    confidence = :confidence,
                    updated_at = :updated_at
                WHERE id = :id'
            );
            $update->execute([
                ':id' => $paymentId,
                ':original_number' => $currentOriginalNumber !== '' ? $currentOriginalNumber : $normalizedOriginalNumber,
                ':source' => $currentSource !== '' ? $currentSource : $normalizedSource,
                ':confidence' => max($currentConfidence, $confidence),
                ':updated_at' => $timestamp,
            ]);

            return $paymentId;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO sender_payment_numbers (
                sender_id,
                type,
                number,
                original_number,
                requires_ocr,
                source,
                confidence,
                created_at,
                updated_at
            ) VALUES (
                NULL,
                :type,
                :number,
                :original_number,
                0,
                :source,
                :confidence,
                :created_at,
                :updated_at
            )'
        );
        $insert->execute([
            ':type' => $normalizedType,
            ':number' => $normalizedNumber,
            ':original_number' => $normalizedOriginalNumber,
            ':source' => $normalizedSource,
            ':confidence' => $confidence,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function mergeSenders(int $targetSenderId, array $sourceSenderIds): array
    {
        if ($targetSenderId < 1) {
            throw new RuntimeException('Target sender id is required.');
        }

        $normalizedSourceIds = array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $value): int => is_numeric($value) ? (int) $value : 0,
                $sourceSenderIds
            ),
            static fn (int $senderId): bool => $senderId > 0 && $senderId !== $targetSenderId
        )));

        if ($normalizedSourceIds === []) {
            return [
                'targetSenderId' => $targetSenderId,
                'sourceSenderIds' => [],
                'movedOrganizationNumbers' => [],
                'movedPaymentNumbers' => [],
            ];
        }

        $targetSender = $this->findById($targetSenderId);
        if (!is_array($targetSender)) {
            throw new RuntimeException('Target sender not found.');
        }

        $placeholders = implode(', ', array_fill(0, count($normalizedSourceIds), '?'));
        $sourceStatement = $this->pdo->prepare(
            'SELECT id, name
            FROM senders
            WHERE id IN (' . $placeholders . ')'
        );
        $sourceStatement->execute($normalizedSourceIds);
        $sourceRows = $sourceStatement->fetchAll();

        $sourcesById = [];
        if (is_array($sourceRows)) {
            foreach ($sourceRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['id']) ? (int) $row['id'] : 0;
                if ($senderId < 1) {
                    continue;
                }
                $sourcesById[$senderId] = [
                    'id' => $senderId,
                    'name' => is_string($row['name'] ?? null) ? trim((string) $row['name']) : '',
                ];
            }
        }

        if (count($sourcesById) !== count($normalizedSourceIds)) {
            throw new RuntimeException('One or more source senders no longer exist.');
        }

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $organizationStatement = $this->pdo->prepare(
                'SELECT organization_number
                FROM sender_organization_numbers
                WHERE sender_id = ?'
            );
            $paymentStatement = $this->pdo->prepare(
                'SELECT type, number
                FROM sender_payment_numbers
                WHERE sender_id = ?'
            );
            $alternativeNameStatement = $this->pdo->prepare(
                'SELECT name
                FROM sender_alternative_names
                WHERE sender_id = ?'
            );
            $moveOrganizations = $this->pdo->prepare(
                'UPDATE sender_organization_numbers
                SET sender_id = :sender_id,
                    updated_at = :updated_at
                WHERE sender_id = :source_sender_id'
            );
            $movePayments = $this->pdo->prepare(
                'UPDATE sender_payment_numbers
                SET sender_id = :sender_id,
                    updated_at = :updated_at
                WHERE sender_id = :source_sender_id'
            );
            $deleteAlternativeNames = $this->pdo->prepare(
                'DELETE FROM sender_alternative_names
                WHERE sender_id = :sender_id'
            );
            $deleteSender = $this->pdo->prepare(
                'DELETE FROM senders
                WHERE id = :id'
            );

            $timestamp = date(DATE_ATOM);
            $movedOrganizationNumbers = [];
            $movedPaymentNumbers = [];

            foreach ($normalizedSourceIds as $sourceSenderId) {
                $source = $sourcesById[$sourceSenderId];

                $organizationStatement->execute([$sourceSenderId]);
                $organizationRows = $organizationStatement->fetchAll();
                if (is_array($organizationRows)) {
                    foreach ($organizationRows as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        $organizationNumber = is_string($row['organization_number'] ?? null)
                            ? trim((string) $row['organization_number'])
                            : '';
                        if ($organizationNumber !== '') {
                            $movedOrganizationNumbers[] = $organizationNumber;
                        }
                    }
                }

                $paymentStatement->execute([$sourceSenderId]);
                $paymentRows = $paymentStatement->fetchAll();
                if (is_array($paymentRows)) {
                    foreach ($paymentRows as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        $paymentType = is_string($row['type'] ?? null) ? trim((string) $row['type']) : '';
                        $paymentNumber = is_string($row['number'] ?? null) ? trim((string) $row['number']) : '';
                        if ($paymentType !== '' && $paymentNumber !== '') {
                            $movedPaymentNumbers[] = [
                                'type' => $paymentType,
                                'number' => $paymentNumber,
                            ];
                        }
                    }
                }

                $alternativeNameStatement->execute([$sourceSenderId]);
                $alternativeNameRows = $alternativeNameStatement->fetchAll();
                $alternativeNames = [];
                if ($source['name'] !== '') {
                    $alternativeNames[] = $source['name'];
                }
                if (is_array($alternativeNameRows)) {
                    foreach ($alternativeNameRows as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
                        if ($name !== '') {
                            $alternativeNames[] = $name;
                        }
                    }
                }
                $this->upsertAlternativeNames($targetSenderId, $alternativeNames);

                $moveOrganizations->execute([
                    ':sender_id' => $targetSenderId,
                    ':updated_at' => $timestamp,
                    ':source_sender_id' => $sourceSenderId,
                ]);
                $movePayments->execute([
                    ':sender_id' => $targetSenderId,
                    ':updated_at' => $timestamp,
                    ':source_sender_id' => $sourceSenderId,
                ]);
                $deleteAlternativeNames->execute([
                    ':sender_id' => $sourceSenderId,
                ]);
                $deleteSender->execute([
                    ':id' => $sourceSenderId,
                ]);
            }

            $this->touchSenderMatchingUpdatedAt($targetSenderId, $timestamp);

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return [
                'targetSenderId' => $targetSenderId,
                'sourceSenderIds' => array_values(array_keys($sourcesById)),
                'movedOrganizationNumbers' => array_values(array_unique($movedOrganizationNumbers)),
                'movedPaymentNumbers' => array_values(array_map(
                    static fn (array $item): array => [
                        'type' => (string) ($item['type'] ?? ''),
                        'number' => (string) ($item['number'] ?? ''),
                    ],
                    $movedPaymentNumbers
                )),
            ];
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function resolveDocumentSenderLinks(?string $orgNumber, ?string $bankgiro, ?string $plusgiro): array
    {
        $this->resolveUnlinkedNamedIdentifiers();

        $components = $this->loadDocumentSenderComponents($orgNumber, $bankgiro, $plusgiro);
        $senderIds = array_values(array_unique(array_filter(
            array_map(
                static fn (array $component): int => isset($component['senderId']) ? (int) $component['senderId'] : 0,
                $components
            ),
            static fn (int $senderId): bool => $senderId > 0
        )));

        if (count($senderIds) < 2) {
            return [
                'merged' => false,
                'merges' => [],
                'components' => $components,
            ];
        }

        $senderBundles = $this->loadSenderBundles($senderIds);
        $parents = [];
        foreach ($senderIds as $senderId) {
            $parents[$senderId] = $senderId;
        }

        $find = static function (int $senderId) use (&$parents, &$find): int {
            $parent = $parents[$senderId] ?? $senderId;
            if ($parent === $senderId) {
                return $senderId;
            }
            $parents[$senderId] = $find($parent);
            return $parents[$senderId];
        };
        $union = static function (int $left, int $right) use (&$parents, $find): void {
            $leftRoot = $find($left);
            $rightRoot = $find($right);
            if ($leftRoot === $rightRoot) {
                return;
            }
            $parents[$rightRoot] = $leftRoot;
        };

        $relationFound = false;
        $componentCount = count($components);
        for ($leftIndex = 0; $leftIndex < $componentCount; $leftIndex++) {
            $leftComponent = $components[$leftIndex];
            $leftSenderId = isset($leftComponent['senderId']) ? (int) $leftComponent['senderId'] : 0;
            if ($leftSenderId < 1) {
                continue;
            }

            for ($rightIndex = $leftIndex + 1; $rightIndex < $componentCount; $rightIndex++) {
                $rightComponent = $components[$rightIndex];
                $rightSenderId = isset($rightComponent['senderId']) ? (int) $rightComponent['senderId'] : 0;
                if ($rightSenderId < 1 || $rightSenderId === $leftSenderId) {
                    continue;
                }

                if ($this->documentComponentsProveSameSender($leftComponent, $rightComponent, $senderBundles)) {
                    $relationFound = true;
                    $union($leftSenderId, $rightSenderId);
                }
            }
        }

        if (!$relationFound) {
            return [
                'merged' => false,
                'merges' => [],
                'components' => $components,
            ];
        }

        $groups = [];
        foreach ($senderIds as $senderId) {
            $root = $find($senderId);
            if (!isset($groups[$root])) {
                $groups[$root] = [];
            }
            $groups[$root][] = $senderId;
        }

        $merges = [];
        foreach ($groups as $groupSenderIds) {
            $groupSenderIds = array_values(array_unique(array_filter(
                array_map(static fn (mixed $value): int => (int) $value, $groupSenderIds),
                static fn (int $value): bool => $value > 0
            )));
            if (count($groupSenderIds) < 2) {
                continue;
            }

            $targetSenderId = $this->selectPreferredMergeTargetSenderId($groupSenderIds, $senderBundles);
            $sourceIds = array_values(array_filter(
                $groupSenderIds,
                static fn (int $senderId): bool => $senderId !== $targetSenderId
            ));
            if ($sourceIds === []) {
                continue;
            }

            $merges[] = $this->mergeSenders($targetSenderId, $sourceIds);
        }

        return [
            'merged' => $merges !== [],
            'merges' => $merges,
            'components' => $this->loadDocumentSenderComponents($orgNumber, $bankgiro, $plusgiro),
        ];
    }

    private function findByPaymentNumber(string $type, string $number): ?array
    {
        $normalizedType = trim(strtolower($type));
        $normalizedNumber = $normalizedType === 'bankgiro'
            ? IdentifierNormalizer::normalizeBankgiro($number)
            : IdentifierNormalizer::normalizePlusgiro($number);

        if ($normalizedNumber === null) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                s.*, 
                p.type AS payment_match_type,
                p.number AS payment_match_number,
                p.original_number AS payment_match_original_number,
                p.requires_ocr AS payment_requires_ocr,
                p.source AS payment_source,
                p.confidence AS payment_confidence
            FROM sender_payment_numbers p
            INNER JOIN senders s ON s.id = p.sender_id
            WHERE p.type = :type
              AND p.number = :number
            LIMIT 1'
        );

        $statement->execute([
            ':type' => $normalizedType,
            ':number' => $normalizedNumber,
        ]);

        $sender = $statement->fetch();
        return is_array($sender) ? $sender : null;
    }

    private function loadDocumentSenderComponents(?string $orgNumber, ?string $bankgiro, ?string $plusgiro): array
    {
        $components = [];

        $normalizedOrganizationNumber = is_string($orgNumber) ? IdentifierNormalizer::normalizeOrgNumber($orgNumber) : null;
        if ($normalizedOrganizationNumber !== null) {
            $row = $this->findObservedOrganizationNumberRow($normalizedOrganizationNumber);
            $components[] = [
                'type' => 'organization_number',
                'senderId' => isset($row['sender_id']) ? (int) $row['sender_id'] : 0,
                'number' => $normalizedOrganizationNumber,
                'observedName' => is_string($row['organization_name'] ?? null) ? trim((string) $row['organization_name']) : '',
            ];
        }

        $normalizedBankgiro = is_string($bankgiro) ? IdentifierNormalizer::normalizeBankgiro($bankgiro) : null;
        if ($normalizedBankgiro !== null) {
            $row = $this->findObservedPaymentNumberRow('bankgiro', $normalizedBankgiro);
            $components[] = [
                'type' => 'bankgiro',
                'senderId' => isset($row['sender_id']) ? (int) $row['sender_id'] : 0,
                'number' => $normalizedBankgiro,
                'observedName' => is_string($row['payee_name'] ?? null) ? trim((string) $row['payee_name']) : '',
            ];
        }

        $normalizedPlusgiro = is_string($plusgiro) ? IdentifierNormalizer::normalizePlusgiro($plusgiro) : null;
        if ($normalizedPlusgiro !== null) {
            $row = $this->findObservedPaymentNumberRow('plusgiro', $normalizedPlusgiro);
            $components[] = [
                'type' => 'plusgiro',
                'senderId' => isset($row['sender_id']) ? (int) $row['sender_id'] : 0,
                'number' => $normalizedPlusgiro,
                'observedName' => is_string($row['payee_name'] ?? null) ? trim((string) $row['payee_name']) : '',
            ];
        }

        return $components;
    }

    private function loadSenderBundles(array $senderIds): array
    {
        $normalizedSenderIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $value): int => is_numeric($value) ? (int) $value : 0, $senderIds),
            static fn (int $senderId): bool => $senderId > 0
        )));
        if ($normalizedSenderIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($normalizedSenderIds), '?'));
        $statement = $this->pdo->prepare(
            'SELECT id, name
            FROM senders
            WHERE id IN (' . $placeholders . ')'
        );
        $statement->execute($normalizedSenderIds);
        $rows = $statement->fetchAll();

        $bundles = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['id']) ? (int) $row['id'] : 0;
                if ($senderId < 1) {
                    continue;
                }
                $bundles[$senderId] = [
                    'id' => $senderId,
                    'name' => is_string($row['name'] ?? null) ? trim((string) $row['name']) : '',
                    'alternativeNames' => [],
                    'organizationCount' => 0,
                    'paymentCount' => 0,
                ];
            }
        }

        if ($bundles === []) {
            return [];
        }

        $alternativeStatement = $this->pdo->prepare(
            'SELECT sender_id, name
            FROM sender_alternative_names
            WHERE sender_id IN (' . $placeholders . ')
            ORDER BY name ASC, id ASC'
        );
        $alternativeStatement->execute($normalizedSenderIds);
        $alternativeRows = $alternativeStatement->fetchAll();
        if (is_array($alternativeRows)) {
            foreach ($alternativeRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
                if ($senderId < 1 || !isset($bundles[$senderId])) {
                    continue;
                }
                $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
                if ($name === '') {
                    continue;
                }
                $bundles[$senderId]['alternativeNames'][] = $name;
            }
        }

        $organizationCountStatement = $this->pdo->prepare(
            'SELECT sender_id, COUNT(*) AS total
            FROM sender_organization_numbers
            WHERE sender_id IN (' . $placeholders . ')
            GROUP BY sender_id'
        );
        $organizationCountStatement->execute($normalizedSenderIds);
        $organizationCountRows = $organizationCountStatement->fetchAll();
        if (is_array($organizationCountRows)) {
            foreach ($organizationCountRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
                if ($senderId < 1 || !isset($bundles[$senderId])) {
                    continue;
                }
                $bundles[$senderId]['organizationCount'] = max(0, (int) ($row['total'] ?? 0));
            }
        }

        $paymentCountStatement = $this->pdo->prepare(
            'SELECT sender_id, COUNT(*) AS total
            FROM sender_payment_numbers
            WHERE sender_id IN (' . $placeholders . ')
            GROUP BY sender_id'
        );
        $paymentCountStatement->execute($normalizedSenderIds);
        $paymentCountRows = $paymentCountStatement->fetchAll();
        if (is_array($paymentCountRows)) {
            foreach ($paymentCountRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
                if ($senderId < 1 || !isset($bundles[$senderId])) {
                    continue;
                }
                $bundles[$senderId]['paymentCount'] = max(0, (int) ($row['total'] ?? 0));
            }
        }

        return $bundles;
    }

    private function documentComponentsProveSameSender(array $leftComponent, array $rightComponent, array $senderBundles): bool
    {
        $leftSenderId = isset($leftComponent['senderId']) ? (int) $leftComponent['senderId'] : 0;
        $rightSenderId = isset($rightComponent['senderId']) ? (int) $rightComponent['senderId'] : 0;
        if ($leftSenderId < 1 || $rightSenderId < 1 || $leftSenderId === $rightSenderId) {
            return false;
        }

        $leftSender = $senderBundles[$leftSenderId] ?? null;
        $rightSender = $senderBundles[$rightSenderId] ?? null;
        if (!is_array($leftSender) || !is_array($rightSender)) {
            return false;
        }

        $leftObservedName = IdentifierNormalizer::normalizeName((string) ($leftComponent['observedName'] ?? ''));
        $rightObservedName = IdentifierNormalizer::normalizeName((string) ($rightComponent['observedName'] ?? ''));
        $leftPrimaryName = IdentifierNormalizer::normalizeName((string) ($leftSender['name'] ?? ''));
        $rightPrimaryName = IdentifierNormalizer::normalizeName((string) ($rightSender['name'] ?? ''));

        if ($leftPrimaryName !== null && $rightPrimaryName !== null && $leftPrimaryName === $rightPrimaryName) {
            return true;
        }
        if ($leftObservedName !== null && $rightPrimaryName !== null && $leftObservedName === $rightPrimaryName) {
            return true;
        }
        if ($rightObservedName !== null && $leftPrimaryName !== null && $rightObservedName === $leftPrimaryName) {
            return true;
        }

        $leftAlternativeNames = $this->normalizedAlternativeNameMap($leftSender['alternativeNames'] ?? []);
        $rightAlternativeNames = $this->normalizedAlternativeNameMap($rightSender['alternativeNames'] ?? []);

        if ($leftObservedName !== null && isset($rightAlternativeNames[$leftObservedName])) {
            return true;
        }
        if ($rightObservedName !== null && isset($leftAlternativeNames[$rightObservedName])) {
            return true;
        }

        return false;
    }

    private function selectPreferredMergeTargetSenderId(array $senderIds, array $senderBundles): int
    {
        $normalizedSenderIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $value): int => is_numeric($value) ? (int) $value : 0, $senderIds),
            static fn (int $senderId): bool => $senderId > 0
        )));
        if ($normalizedSenderIds === []) {
            throw new RuntimeException('No sender ids available for merge target selection.');
        }

        usort(
            $normalizedSenderIds,
            static function (int $leftSenderId, int $rightSenderId) use ($senderBundles): int {
                $left = is_array($senderBundles[$leftSenderId] ?? null) ? $senderBundles[$leftSenderId] : [];
                $right = is_array($senderBundles[$rightSenderId] ?? null) ? $senderBundles[$rightSenderId] : [];

                $leftTotal = ((int) ($left['organizationCount'] ?? 0)) + ((int) ($left['paymentCount'] ?? 0));
                $rightTotal = ((int) ($right['organizationCount'] ?? 0)) + ((int) ($right['paymentCount'] ?? 0));
                if ($leftTotal !== $rightTotal) {
                    return $rightTotal <=> $leftTotal;
                }

                return $leftSenderId <=> $rightSenderId;
            }
        );

        return $normalizedSenderIds[0];
    }

    private function formatPaymentNumberForDisplay(string $type, string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);
        if (!is_string($digits) || $digits === '') {
            return '';
        }

        if ($type === 'bankgiro') {
            $length = strlen($digits);
            if ($length >= 5) {
                return substr($digits, 0, $length - 4) . '-' . substr($digits, -4);
            }
        }

        if ($type === 'plusgiro') {
            $length = strlen($digits);
            if ($length >= 2) {
                return substr($digits, 0, $length - 1) . '-' . substr($digits, -1);
            }
        }

        return $digits;
    }

    private function formatOrganizationNumberForDisplay(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);
        if (!is_string($digits) || $digits === '' || strlen($digits) !== 10) {
            return is_string($number) ? trim($number) : '';
        }

        return substr($digits, 0, 6) . '-' . substr($digits, 6);
    }

    private function normalizedAlternativeNameMap(array $names): array
    {
        $map = [];
        foreach ($names as $name) {
            $trimmed = is_string($name) ? trim($name) : '';
            if ($trimmed === '') {
                continue;
            }
            $normalized = IdentifierNormalizer::normalizeName($trimmed);
            if ($normalized === null || isset($map[$normalized])) {
                continue;
            }
            $map[$normalized] = $trimmed;
        }

        return $map;
    }

    private function upsertAlternativeNames(int $senderId, array $names): array
    {
        if ($senderId < 1) {
            return [];
        }

        $sender = $this->findById($senderId);
        if (!is_array($sender)) {
            return [];
        }

        $primaryName = IdentifierNormalizer::normalizeName((string) ($sender['name'] ?? ''));
        $existingNames = [];
        $select = $this->pdo->prepare(
            'SELECT name
            FROM sender_alternative_names
            WHERE sender_id = :sender_id'
        );
        $select->execute([':sender_id' => $senderId]);
        $rows = $select->fetchAll();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
                $normalized = $name !== '' ? IdentifierNormalizer::normalizeName($name) : null;
                if ($normalized === null) {
                    continue;
                }
                $existingNames[$normalized] = true;
            }
        }

        $insert = $this->pdo->prepare(
            'INSERT OR IGNORE INTO sender_alternative_names (
                sender_id,
                name,
                created_at,
                updated_at
            ) VALUES (
                :sender_id,
                :name,
                :created_at,
                :updated_at
            )'
        );

        $saved = [];
        $seen = [];
        foreach ($names as $name) {
            $trimmed = is_string($name) ? trim($name) : '';
            if ($trimmed === '') {
                continue;
            }
            $normalized = IdentifierNormalizer::normalizeName($trimmed);
            if ($normalized === null || $normalized === $primaryName || isset($existingNames[$normalized]) || isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;
            $timestamp = date(DATE_ATOM);
            $insert->execute([
                ':sender_id' => $senderId,
                ':name' => $trimmed,
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ]);
            $saved[] = $trimmed;
        }

        return $saved;
    }

    private function ensureSenderIdForCanonicalName(string $name): int
    {
        $normalized = trim($name);
        if ($normalized === '') {
            throw new RuntimeException('Sender name is required.');
        }

        $existing = $this->findByNameCaseInsensitive($normalized);
        if (is_array($existing) && (int) ($existing['id'] ?? 0) > 0) {
            return (int) $existing['id'];
        }

        return $this->createSender($normalized);
    }

    private function touchSenderMatchingUpdatedAt(int $senderId, string $timestamp): void
    {
        if ($senderId < 1) {
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE senders
            SET matching_updated_at = :matching_updated_at,
                updated_at = CASE
                    WHEN updated_at > :matching_updated_at THEN updated_at
                    ELSE :matching_updated_at
                END
            WHERE id = :id'
        );
        $statement->execute([
            ':id' => $senderId,
            ':matching_updated_at' => $timestamp,
        ]);
    }
}
