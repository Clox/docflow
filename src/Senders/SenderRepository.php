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
            'SELECT *
            FROM senders
            WHERE org_number = :org_number
              AND merged_into_sender_id IS NULL
            LIMIT 1'
        );
        $statement->execute([':org_number' => $normalized]);
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

    public function listAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, org_number, domain, kind, notes, confidence, created_at, updated_at
            FROM senders
            WHERE merged_into_sender_id IS NULL
            ORDER BY name ASC, id ASC'
        );
        $rows = $statement->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function listEditorRows(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                s.id AS sender_id,
                s.name AS sender_name,
                s.org_number AS sender_org_number,
                s.domain AS sender_domain,
                s.kind AS sender_kind,
                s.notes AS sender_notes,
                p.id AS payment_id,
                p.type AS payment_type,
                p.number AS payment_number,
                p.payee_name AS payment_payee_name,
                p.payee_lookup_status AS payment_payee_lookup_status
            FROM senders s
            LEFT JOIN sender_payment_numbers p ON p.sender_id = s.id
            WHERE s.merged_into_sender_id IS NULL
            ORDER BY s.name ASC, s.id ASC, p.type ASC, p.number ASC, p.id ASC'
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

            $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
            if ($senderId < 1) {
                continue;
            }

            if (!isset($sendersById[$senderId])) {
                $sendersById[$senderId] = [
                    'id' => $senderId,
                    'name' => is_string($row['sender_name'] ?? null) ? trim((string) $row['sender_name']) : '',
                    'orgNumber' => is_string($row['sender_org_number'] ?? null) ? trim((string) $row['sender_org_number']) : '',
                    'domain' => is_string($row['sender_domain'] ?? null) ? trim((string) $row['sender_domain']) : '',
                    'kind' => is_string($row['sender_kind'] ?? null) ? trim((string) $row['sender_kind']) : '',
                    'notes' => is_string($row['sender_notes'] ?? null) ? (string) $row['sender_notes'] : '',
                    'paymentNumbers' => [],
                ];
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
            INNER JOIN senders s ON s.id = p.sender_id
            WHERE s.merged_into_sender_id IS NULL
              AND (p.payee_name IS NULL OR trim(p.payee_name) = \'\')
              AND (p.payee_lookup_status IS NULL OR trim(p.payee_lookup_status) = \'\')
            ORDER BY s.name ASC, p.type ASC, p.number ASC, p.id ASC
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
            $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
            if ($paymentId < 1 || $senderId < 1) {
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
            INNER JOIN senders s ON s.id = p.sender_id
            WHERE s.merged_into_sender_id IS NULL
              AND (p.payee_name IS NULL OR trim(p.payee_name) = \'\')
              AND (p.payee_lookup_status IS NULL OR trim(p.payee_lookup_status) = \'\')'
        );
        if ($statement === false) {
            return 0;
        }

        return max(0, (int) $statement->fetchColumn());
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

        $currentId = $senderId;
        $visited = [];

        while ($currentId > 0) {
            if (isset($visited[$currentId])) {
                throw new RuntimeException('Detected sender merge cycle for sender id ' . $senderId);
            }
            $visited[$currentId] = true;

            $row = $this->findById($currentId);
            if (!is_array($row)) {
                return null;
            }

            $mergedIntoSenderId = isset($row['merged_into_sender_id']) ? (int) $row['merged_into_sender_id'] : 0;
            if ($mergedIntoSenderId < 1) {
                return $currentId;
            }

            $currentId = $mergedIntoSenderId;
        }

        return null;
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
        ?string $orgNumber = null,
        ?string $domain = null,
        ?string $kind = null,
        ?string $notes = null,
        float $confidence = 1.0
    ): int {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Sender name is required.');
        }

        $normalizedOrgNumber = null;
        if (is_string($orgNumber) && trim($orgNumber) !== '') {
            $normalizedOrgNumber = IdentifierNormalizer::normalizeOrgNumber($orgNumber);
            if ($normalizedOrgNumber === null) {
                throw new RuntimeException('Invalid org number.');
            }
        }

        $timestamp = date(DATE_ATOM);
        $statement = $this->pdo->prepare(
            'INSERT INTO senders (
                name,
                org_number,
                domain,
                kind,
                notes,
                confidence,
                created_at,
                updated_at
            ) VALUES (
                :name,
                :org_number,
                :domain,
                :kind,
                :notes,
                :confidence,
                :created_at,
                :updated_at
            )'
        );

        $statement->execute([
            ':name' => $name,
            ':org_number' => $normalizedOrgNumber,
            ':domain' => $domain !== null ? trim($domain) : null,
            ':kind' => $kind !== null ? trim($kind) : null,
            ':notes' => $notes,
            ':confidence' => $confidence,
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
              AND s.merged_into_sender_id IS NULL
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
}
