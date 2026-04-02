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

        return array_values($sendersById);
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
}
