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

        $statement = $this->pdo->prepare('SELECT * FROM senders WHERE org_number = :org_number LIMIT 1');
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
        string $slug,
        ?string $orgNumber = null,
        ?string $domain = null,
        ?string $kind = null,
        ?string $notes = null,
        float $confidence = 1.0
    ): int {
        $name = trim($name);
        $slug = trim($slug);
        if ($name === '' || $slug === '') {
            throw new RuntimeException('Sender name and slug are required.');
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
                slug,
                org_number,
                domain,
                kind,
                notes,
                confidence,
                created_at,
                updated_at
            ) VALUES (
                :name,
                :slug,
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
            ':slug' => $slug,
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
            WHERE p.type = :type AND p.number = :number
            LIMIT 1'
        );

        $statement->execute([
            ':type' => $normalizedType,
            ':number' => $normalizedNumber,
        ]);

        $sender = $statement->fetch();
        return is_array($sender) ? $sender : null;
    }
}
