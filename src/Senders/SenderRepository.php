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

        $row = $this->canonicalOrganizationNumberRow($normalized);
        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'organization_number' => $normalized,
            'organization_name' => $row['organization_name'] ?? null,
            'sender_id' => $row['sender_id'] ?? null,
            'source' => $row['source'] ?? null,
            'lookup_status' => $row['lookup_status'] ?? null,
            'lookup_error_code' => $row['lookup_error_code'] ?? null,
            'lookup_error_message' => $row['lookup_error_message'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
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

        $row = $this->canonicalPaymentNumberRow($normalizedType, $normalizedNumber);
        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'sender_id' => $row['sender_id'] ?? null,
            'type' => $normalizedType,
            'number' => $normalizedNumber,
            'original_number' => $row['original_number'] ?? null,
            'payee_name' => $row['payee_name'] ?? null,
            'payee_lookup_status' => $row['payee_lookup_status'] ?? null,
            'lookup_error_code' => $row['lookup_error_code'] ?? null,
            'lookup_error_message' => $row['lookup_error_message'] ?? null,
            'source' => $row['source'] ?? null,
            'confidence' => $row['confidence'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
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
            INNER JOIN sender_organization_numbers o ON o.id = a.sender_organization_number_id
            INNER JOIN senders s ON s.id = o.sender_id
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
        return $this->listEditorRows();
    }

    public function listEditorRows(): array
    {
        $this->canonicalizeSenderIdentifierRows();

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
                'displayName' => '',
                'units' => [],
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

        $unitRows = $this->pdo->query(
            'SELECT
                u.id AS unit_id,
                u.sender_id,
                u.name AS unit_name,
                u.normalized_name,
                u.sort_order
            FROM sender_units u
            INNER JOIN senders s ON s.id = u.sender_id
            ORDER BY s.name ASC, s.id ASC, u.sort_order ASC, u.name COLLATE NOCASE ASC, u.id ASC'
        )->fetchAll();

        if (is_array($unitRows)) {
            foreach ($unitRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
                $unitId = isset($row['unit_id']) ? (int) $row['unit_id'] : 0;
                $name = is_string($row['unit_name'] ?? null) ? trim((string) $row['unit_name']) : '';
                if ($senderId < 1 || $unitId < 1 || $name === '' || !isset($sendersById[$senderId])) {
                    continue;
                }
                $sendersById[$senderId]['units'][] = [
                    'id' => $unitId,
                    'name' => $name,
                    'normalizedName' => is_string($row['normalized_name'] ?? null)
                        ? trim((string) $row['normalized_name'])
                        : NameNormalizer::normalize($name),
                    'sortOrder' => isset($row['sort_order']) ? (int) $row['sort_order'] : 0,
                ];
            }
        }

        foreach ($sendersById as $senderId => $senderRow) {
            $sendersById[$senderId]['displayName'] = $this->senderDisplayName($senderRow);
        }

        $rows = array_values($sendersById);
        usort(
            $rows,
            static function (array $left, array $right): int {
                $leftName = is_string($left['displayName'] ?? null) ? trim((string) $left['displayName']) : '';
                $rightName = is_string($right['displayName'] ?? null) ? trim((string) $right['displayName']) : '';
                $compare = strcasecmp($leftName, $rightName);
                if ($compare !== 0) {
                    return $compare;
                }
                return ((int) ($left['id'] ?? 0)) <=> ((int) ($right['id'] ?? 0));
            }
        );

        return $rows;
    }

    public function listNameEntriesForAnalysis(): array
    {
        $senderRows = $this->pdo->query(
            'SELECT
                id AS sender_id,
                name
            FROM senders
            WHERE trim(name) <> \'\'
            ORDER BY id ASC'
        )->fetchAll();

        $items = [];
        if (is_array($senderRows)) {
            foreach ($senderRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
                $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
                $normalizedName = $name !== '' ? NameNormalizer::normalize($name) : '';
                if ($senderId < 1 || $name === '' || $normalizedName === '') {
                    continue;
                }
                $items[] = [
                    'senderId' => $senderId,
                    'senderUnitId' => null,
                    'name' => $name,
                    'normalizedName' => $normalizedName,
                    'sortOrder' => 0,
                    'type' => 'sender_name',
                ];
            }
        }

        $unitRows = $this->pdo->query(
            'SELECT
                u.id AS sender_unit_id,
                u.sender_id,
                u.name,
                u.normalized_name,
                u.sort_order
            FROM sender_units u
            INNER JOIN senders s ON s.id = u.sender_id
            WHERE trim(u.name) <> \'\'
            ORDER BY u.sender_id ASC, u.sort_order ASC, u.id ASC'
        )->fetchAll();

        if (is_array($unitRows)) {
            foreach ($unitRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
                $unitId = isset($row['sender_unit_id']) ? (int) $row['sender_unit_id'] : 0;
                $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
                $normalizedName = is_string($row['normalized_name'] ?? null)
                    ? trim((string) $row['normalized_name'])
                    : '';
                if ($normalizedName === '' && $name !== '') {
                    $normalizedName = NameNormalizer::normalize($name);
                }
                if ($senderId < 1 || $unitId < 1 || $name === '' || $normalizedName === '') {
                    continue;
                }
                $items[] = [
                    'senderId' => $senderId,
                    'senderUnitId' => $unitId,
                    'name' => $name,
                    'normalizedName' => $normalizedName,
                    'sortOrder' => isset($row['sort_order']) ? (int) $row['sort_order'] : 0,
                    'type' => 'sender_unit',
                ];
            }
        }

        return $items;
    }

    public function listUnlinkedIdentifierRows(): array
    {
        $this->canonicalizeSenderIdentifierRows();

        $items = [];

        $organizationRows = $this->pdo->query(
            'SELECT
                id,
                organization_number,
                organization_name,
                source,
                lookup_status,
                lookup_error_code,
                lookup_error_message,
                created_at,
                updated_at
            FROM sender_organization_numbers
            WHERE sender_id IS NULL
            ORDER BY updated_at DESC, organization_number ASC, id ASC'
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
                    'paymentType' => '',
                    'number' => $this->formatOrganizationNumberForDisplay($normalizedNumber),
                    'normalizedNumber' => $normalizedNumber,
                    'name' => is_string($row['organization_name'] ?? null) ? trim((string) $row['organization_name']) : '',
                    'lookupStatus' => is_string($row['lookup_status'] ?? null) ? trim((string) $row['lookup_status']) : 'pending',
                    'lookupErrorCode' => is_string($row['lookup_error_code'] ?? null) ? trim((string) $row['lookup_error_code']) : '',
                    'lookupErrorMessage' => is_string($row['lookup_error_message'] ?? null) ? trim((string) $row['lookup_error_message']) : '',
                    'source' => is_string($row['source'] ?? null) ? trim((string) $row['source']) : '',
                    'updatedAt' => is_string($row['updated_at'] ?? null) ? trim((string) $row['updated_at']) : '',
                ];
            }
        }

        $paymentRows = $this->pdo->query(
            'SELECT
                id,
                type,
                number,
                payee_name,
                payee_lookup_status,
                lookup_error_code,
                lookup_error_message,
                source,
                created_at,
                updated_at
            FROM sender_payment_numbers
            WHERE sender_id IS NULL
            ORDER BY updated_at DESC, type ASC, number ASC, id ASC'
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
                if ($type !== 'plusgiro') {
                    $type = 'bankgiro';
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
                    'lookupStatus' => is_string($row['payee_lookup_status'] ?? null) ? trim((string) $row['payee_lookup_status']) : '',
                    'lookupErrorCode' => is_string($row['lookup_error_code'] ?? null) ? trim((string) $row['lookup_error_code']) : '',
                    'lookupErrorMessage' => is_string($row['lookup_error_message'] ?? null) ? trim((string) $row['lookup_error_message']) : '',
                    'source' => is_string($row['source'] ?? null) ? trim((string) $row['source']) : '',
                    'updatedAt' => is_string($row['updated_at'] ?? null) ? trim((string) $row['updated_at']) : '',
                ];
            }
        }

        usort(
            $items,
            static function (array $left, array $right): int {
                $leftUpdated = is_string($left['updatedAt'] ?? null) ? trim((string) $left['updatedAt']) : '';
                $rightUpdated = is_string($right['updatedAt'] ?? null) ? trim((string) $right['updatedAt']) : '';
                if ($leftUpdated !== $rightUpdated) {
                    return strcmp($rightUpdated, $leftUpdated);
                }
                return strcmp(
                    (string) ($left['key'] ?? ''),
                    (string) ($right['key'] ?? '')
                );
            }
        );

        return $items;
    }

    public function canonicalizeSenderIdentifierRows(): void
    {
        $organizationRows = $this->pdo->query(
            'SELECT organization_number
            FROM sender_organization_numbers
            WHERE trim(organization_number) <> \'\'
            GROUP BY organization_number
            HAVING COUNT(*) > 1'
        );
        if ($organizationRows !== false) {
            foreach ($organizationRows->fetchAll() ?: [] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $number = is_string($row['organization_number'] ?? null)
                    ? trim((string) $row['organization_number'])
                    : '';
                if ($number !== '') {
                    $this->canonicalOrganizationNumberRow($number);
                }
            }
        }

        $paymentRows = $this->pdo->query(
            'SELECT type, number
            FROM sender_payment_numbers
            WHERE trim(type) <> \'\'
              AND trim(number) <> \'\'
            GROUP BY type, number
            HAVING COUNT(*) > 1'
        );
        if ($paymentRows !== false) {
            foreach ($paymentRows->fetchAll() ?: [] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $type = is_string($row['type'] ?? null) ? trim((string) $row['type']) : '';
                $number = is_string($row['number'] ?? null) ? trim((string) $row['number']) : '';
                if ($type !== '' && $number !== '') {
                    $this->canonicalPaymentNumberRow($type, $number);
                }
            }
        }
    }

    private function canonicalOrganizationNumberRow(string $organizationNumber): ?array
    {
        $normalized = IdentifierNormalizer::normalizeOrgNumber($organizationNumber);
        if ($normalized === null) {
            return null;
        }

        $select = $this->pdo->prepare(
            'SELECT *
            FROM sender_organization_numbers
            WHERE organization_number = :organization_number
            ORDER BY
                CASE WHEN sender_id IS NULL THEN 1 ELSE 0 END ASC,
                updated_at DESC,
                id ASC'
        );
        $select->execute([':organization_number' => $normalized]);
        $rows = $select->fetchAll();
        if (!is_array($rows) || $rows === []) {
            return null;
        }

        $canonical = is_array($rows[0]) ? $rows[0] : null;
        if (!is_array($canonical)) {
            return null;
        }
        $canonicalId = isset($canonical['id']) ? (int) $canonical['id'] : 0;
        if ($canonicalId < 1 || count($rows) === 1) {
            return $canonicalId > 0 ? $canonical : null;
        }

        $merged = $canonical;
        $duplicateIds = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowId = isset($row['id']) ? (int) $row['id'] : 0;
            if ($rowId < 1 || $rowId === $canonicalId) {
                continue;
            }
            $duplicateIds[] = $rowId;
            foreach ([
                'organization_name',
                'source',
                'source_job_id',
                'source_bbox_indexes',
                'lookup_status',
                'lookup_error_code',
                'lookup_error_message',
            ] as $column) {
                $current = is_string($merged[$column] ?? null) ? trim((string) $merged[$column]) : '';
                $candidate = is_string($row[$column] ?? null) ? trim((string) $row[$column]) : '';
                if ($current === '' && $candidate !== '') {
                    $merged[$column] = $candidate;
                }
            }
            $currentCreated = is_string($merged['created_at'] ?? null) ? trim((string) $merged['created_at']) : '';
            $candidateCreated = is_string($row['created_at'] ?? null) ? trim((string) $row['created_at']) : '';
            if ($candidateCreated !== '' && ($currentCreated === '' || strcmp($candidateCreated, $currentCreated) < 0)) {
                $merged['created_at'] = $candidateCreated;
            }
            $currentUpdated = is_string($merged['updated_at'] ?? null) ? trim((string) $merged['updated_at']) : '';
            $candidateUpdated = is_string($row['updated_at'] ?? null) ? trim((string) $row['updated_at']) : '';
            if ($candidateUpdated !== '' && ($currentUpdated === '' || strcmp($candidateUpdated, $currentUpdated) > 0)) {
                $merged['updated_at'] = $candidateUpdated;
            }
        }

        $timestamp = date(DATE_ATOM);
        $update = $this->pdo->prepare(
            'UPDATE sender_organization_numbers
            SET organization_name = :organization_name,
                source = :source,
                source_job_id = :source_job_id,
                source_bbox_indexes = :source_bbox_indexes,
                lookup_status = :lookup_status,
                lookup_error_code = :lookup_error_code,
                lookup_error_message = :lookup_error_message,
                created_at = :created_at,
                updated_at = :updated_at
            WHERE id = :id'
        );
        $update->execute([
            ':id' => $canonicalId,
            ':organization_name' => $this->nullableTrimmedString($merged['organization_name'] ?? null),
            ':source' => $this->nullableTrimmedString($merged['source'] ?? null),
            ':source_job_id' => $this->nullableTrimmedString($merged['source_job_id'] ?? null),
            ':source_bbox_indexes' => $this->nullableTrimmedString($merged['source_bbox_indexes'] ?? null),
            ':lookup_status' => $this->nullableTrimmedString($merged['lookup_status'] ?? null),
            ':lookup_error_code' => $this->nullableTrimmedString($merged['lookup_error_code'] ?? null),
            ':lookup_error_message' => $this->nullableTrimmedString($merged['lookup_error_message'] ?? null),
            ':created_at' => $this->nullableTrimmedString($merged['created_at'] ?? null) ?? $timestamp,
            ':updated_at' => $this->nullableTrimmedString($merged['updated_at'] ?? null) ?? $timestamp,
        ]);

        if ($duplicateIds !== []) {
            $placeholders = implode(', ', array_fill(0, count($duplicateIds), '?'));
            $copyAlternativeNames = $this->pdo->prepare(
                'INSERT OR IGNORE INTO sender_alternative_names (
                    sender_organization_number_id,
                    name,
                    normalized_name,
                    source,
                    created_at,
                    updated_at
                )
                SELECT
                    ?,
                    name,
                    normalized_name,
                    source,
                    created_at,
                    updated_at
                FROM sender_alternative_names
                WHERE sender_organization_number_id IN (' . $placeholders . ')'
            );
            $copyAlternativeNames->execute([$canonicalId, ...$duplicateIds]);
            $delete = $this->pdo->prepare(
                'DELETE FROM sender_organization_numbers
                WHERE id IN (' . $placeholders . ')'
            );
            $delete->execute($duplicateIds);
        }

        $selectCanonical = $this->pdo->prepare(
            'SELECT *
            FROM sender_organization_numbers
            WHERE id = :id
            LIMIT 1'
        );
        $selectCanonical->execute([':id' => $canonicalId]);
        $row = $selectCanonical->fetch();

        return is_array($row) ? $row : null;
    }

    private function canonicalPaymentNumberRow(string $type, string $number): ?array
    {
        $normalizedType = trim(strtolower($type)) === 'plusgiro' ? 'plusgiro' : 'bankgiro';
        $normalizedNumber = $normalizedType === 'plusgiro'
            ? IdentifierNormalizer::normalizePlusgiro($number)
            : IdentifierNormalizer::normalizeBankgiro($number);
        if ($normalizedNumber === null) {
            return null;
        }

        $select = $this->pdo->prepare(
            'SELECT *
            FROM sender_payment_numbers
            WHERE type = :type
              AND number = :number
            ORDER BY
                CASE WHEN sender_id IS NULL THEN 1 ELSE 0 END ASC,
                updated_at DESC,
                id ASC'
        );
        $select->execute([
            ':type' => $normalizedType,
            ':number' => $normalizedNumber,
        ]);
        $rows = $select->fetchAll();
        if (!is_array($rows) || $rows === []) {
            return null;
        }

        $canonical = is_array($rows[0]) ? $rows[0] : null;
        if (!is_array($canonical)) {
            return null;
        }
        $canonicalId = isset($canonical['id']) ? (int) $canonical['id'] : 0;
        if ($canonicalId < 1 || count($rows) === 1) {
            return $canonicalId > 0 ? $canonical : null;
        }

        $merged = $canonical;
        $duplicateIds = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowId = isset($row['id']) ? (int) $row['id'] : 0;
            if ($rowId < 1 || $rowId === $canonicalId) {
                continue;
            }
            $duplicateIds[] = $rowId;
            foreach ([
                'original_number',
                'source',
                'source_job_id',
                'source_bbox_indexes',
                'payee_name',
                'payee_lookup_status',
                'lookup_error_code',
                'lookup_error_message',
            ] as $column) {
                $current = is_string($merged[$column] ?? null) ? trim((string) $merged[$column]) : '';
                $candidate = is_string($row[$column] ?? null) ? trim((string) $row[$column]) : '';
                if ($current === '' && $candidate !== '') {
                    $merged[$column] = $candidate;
                }
            }
            $merged['requires_ocr'] = ((int) ($merged['requires_ocr'] ?? 0) === 1 || (int) ($row['requires_ocr'] ?? 0) === 1) ? 1 : 0;
            $merged['confidence'] = max((float) ($merged['confidence'] ?? 0), (float) ($row['confidence'] ?? 0));
            $currentCreated = is_string($merged['created_at'] ?? null) ? trim((string) $merged['created_at']) : '';
            $candidateCreated = is_string($row['created_at'] ?? null) ? trim((string) $row['created_at']) : '';
            if ($candidateCreated !== '' && ($currentCreated === '' || strcmp($candidateCreated, $currentCreated) < 0)) {
                $merged['created_at'] = $candidateCreated;
            }
            $currentUpdated = is_string($merged['updated_at'] ?? null) ? trim((string) $merged['updated_at']) : '';
            $candidateUpdated = is_string($row['updated_at'] ?? null) ? trim((string) $row['updated_at']) : '';
            if ($candidateUpdated !== '' && ($currentUpdated === '' || strcmp($candidateUpdated, $currentUpdated) > 0)) {
                $merged['updated_at'] = $candidateUpdated;
            }
        }

        $timestamp = date(DATE_ATOM);
        $update = $this->pdo->prepare(
            'UPDATE sender_payment_numbers
            SET original_number = :original_number,
                requires_ocr = :requires_ocr,
                source = :source,
                source_job_id = :source_job_id,
                source_bbox_indexes = :source_bbox_indexes,
                confidence = :confidence,
                payee_name = :payee_name,
                payee_lookup_status = :payee_lookup_status,
                lookup_error_code = :lookup_error_code,
                lookup_error_message = :lookup_error_message,
                created_at = :created_at,
                updated_at = :updated_at
            WHERE id = :id'
        );
        $update->execute([
            ':id' => $canonicalId,
            ':original_number' => $this->nullableTrimmedString($merged['original_number'] ?? null),
            ':requires_ocr' => (int) ($merged['requires_ocr'] ?? 0) === 1 ? 1 : 0,
            ':source' => $this->nullableTrimmedString($merged['source'] ?? null),
            ':source_job_id' => $this->nullableTrimmedString($merged['source_job_id'] ?? null),
            ':source_bbox_indexes' => $this->nullableTrimmedString($merged['source_bbox_indexes'] ?? null),
            ':confidence' => isset($merged['confidence']) ? (float) $merged['confidence'] : 1.0,
            ':payee_name' => $this->nullableTrimmedString($merged['payee_name'] ?? null),
            ':payee_lookup_status' => $this->nullableTrimmedString($merged['payee_lookup_status'] ?? null),
            ':lookup_error_code' => $this->nullableTrimmedString($merged['lookup_error_code'] ?? null),
            ':lookup_error_message' => $this->nullableTrimmedString($merged['lookup_error_message'] ?? null),
            ':created_at' => $this->nullableTrimmedString($merged['created_at'] ?? null) ?? $timestamp,
            ':updated_at' => $this->nullableTrimmedString($merged['updated_at'] ?? null) ?? $timestamp,
        ]);

        if ($duplicateIds !== []) {
            $placeholders = implode(', ', array_fill(0, count($duplicateIds), '?'));
            $delete = $this->pdo->prepare(
                'DELETE FROM sender_payment_numbers
                WHERE id IN (' . $placeholders . ')'
            );
            $delete->execute($duplicateIds);
        }

        $selectCanonical = $this->pdo->prepare(
            'SELECT *
            FROM sender_payment_numbers
            WHERE id = :id
            LIMIT 1'
        );
        $selectCanonical->execute([':id' => $canonicalId]);
        $row = $selectCanonical->fetch();

        return is_array($row) ? $row : null;
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);
        return $trimmed !== '' ? $trimmed : null;
    }

    private function senderDisplayName(array $senderRow): string
    {
        $manualName = is_string($senderRow['name'] ?? null) ? trim((string) $senderRow['name']) : '';
        return $manualName !== '' ? $manualName : '(Namnlös)';
    }

    public function replaceAll(array $senders): array
    {
        $normalizedSenders = [];
        $seenOrganizationNumbers = [];
        $seenPaymentNumbers = [];

        foreach (array_values($senders) as $senderIndex => $senderRow) {
            if (!is_array($senderRow)) {
                continue;
            }

            $id = isset($senderRow['id']) && is_numeric($senderRow['id']) ? (int) $senderRow['id'] : null;
            if ($id !== null && $id < 1) {
                $id = null;
            }

            $name = is_string($senderRow['name'] ?? null) ? trim((string) $senderRow['name']) : '';
            $domain = is_string($senderRow['domain'] ?? null) ? trim((string) $senderRow['domain']) : '';
            $kind = is_string($senderRow['kind'] ?? null) ? trim((string) $senderRow['kind']) : '';
            $notes = is_string($senderRow['notes'] ?? null) ? trim((string) $senderRow['notes']) : '';
            $organizationRows = array_values(array_filter(
                is_array($senderRow['organizationNumbers'] ?? null) ? $senderRow['organizationNumbers'] : [],
                static fn (mixed $organization): bool => is_array($organization)
            ));
            $paymentRows = array_values(array_filter(
                is_array($senderRow['paymentNumbers'] ?? null) ? $senderRow['paymentNumbers'] : [],
                static fn (mixed $payment): bool => is_array($payment)
            ));
            $unitRows = array_values(array_filter(
                is_array($senderRow['units'] ?? null) ? $senderRow['units'] : [],
                static fn (mixed $unit): bool => is_array($unit) || is_string($unit)
            ));
            $isEffectivelyEmpty = $name === ''
                && $domain === ''
                && $kind === ''
                && $notes === ''
                && $unitRows === []
                && $organizationRows === []
                && $paymentRows === [];
            if ($isEffectivelyEmpty) {
                continue;
            }

            $normalizedOrganizations = [];
            foreach ($organizationRows as $organizationRow) {
                $organizationId = isset($organizationRow['id']) && is_numeric($organizationRow['id'])
                    ? (int) $organizationRow['id']
                    : null;
                if ($organizationId !== null && $organizationId < 1) {
                    $organizationId = null;
                }

                $organizationNumberRaw = is_string($organizationRow['organizationNumber'] ?? null)
                    ? trim((string) $organizationRow['organizationNumber'])
                    : '';
                if ($organizationNumberRaw === '') {
                    continue;
                }

                $organizationNumber = IdentifierNormalizer::normalizeOrgNumber($organizationNumberRaw);
                if ($organizationNumber === null) {
                    throw new RuntimeException('Invalid organization number in sender import.');
                }

                if (isset($seenOrganizationNumbers[$organizationNumber])) {
                    throw new RuntimeException('Organization numbers must be unique.');
                }
                $seenOrganizationNumbers[$organizationNumber] = true;

                $organizationName = is_string($organizationRow['organizationName'] ?? null)
                    ? trim((string) $organizationRow['organizationName'])
                    : '';

                $normalizedOrganizations[] = [
                    'id' => $organizationId,
                    'organizationNumber' => $organizationNumber,
                    'organizationName' => $organizationName,
                ];
            }

            $normalizedPayments = [];
            foreach ($paymentRows as $paymentRow) {
                $paymentId = isset($paymentRow['id']) && is_numeric($paymentRow['id'])
                    ? (int) $paymentRow['id']
                    : null;
                if ($paymentId !== null && $paymentId < 1) {
                    $paymentId = null;
                }

                $type = is_string($paymentRow['type'] ?? null) ? trim(strtolower((string) $paymentRow['type'])) : '';
                if ($type !== 'bankgiro' && $type !== 'plusgiro') {
                    throw new RuntimeException('Payment number type must be bankgiro or plusgiro.');
                }

                $numberRaw = is_string($paymentRow['number'] ?? null) ? trim((string) $paymentRow['number']) : '';
                if ($numberRaw === '') {
                    continue;
                }

                $number = $type === 'plusgiro'
                    ? IdentifierNormalizer::normalizePlusgiro($numberRaw)
                    : IdentifierNormalizer::normalizeBankgiro($numberRaw);
                if ($number === null) {
                    throw new RuntimeException('Invalid payment number in sender import.');
                }

                $paymentKey = $type . ':' . $number;
                if (isset($seenPaymentNumbers[$paymentKey])) {
                    throw new RuntimeException('Payment numbers must be unique.');
                }
                $seenPaymentNumbers[$paymentKey] = true;

                $normalizedPayments[] = [
                    'id' => $paymentId,
                    'type' => $type,
                    'number' => $number,
                ];
            }

            $normalizedUnits = [];
            $seenUnits = [];
            foreach ($unitRows as $unitIndex => $unitRow) {
                $unitName = is_string($unitRow)
                    ? trim($unitRow)
                    : (is_string($unitRow['name'] ?? null) ? trim((string) $unitRow['name']) : '');
                if ($unitName === '') {
                    continue;
                }
                $normalizedUnitName = NameNormalizer::normalize($unitName);
                if ($normalizedUnitName === '' || isset($seenUnits[$normalizedUnitName])) {
                    continue;
                }
                $seenUnits[$normalizedUnitName] = true;
                $normalizedUnits[] = [
                    'name' => $unitName,
                    'normalizedName' => $normalizedUnitName,
                    'sortOrder' => is_array($unitRow) && isset($unitRow['sortOrder']) && is_numeric($unitRow['sortOrder'])
                        ? (int) $unitRow['sortOrder']
                        : $unitIndex,
                ];
            }

            $normalizedSenders[] = [
                'id' => $id,
                'name' => $name !== '' ? $name : '(Namnlös)',
                'domain' => $domain !== '' ? strtolower($domain) : null,
                'kind' => $kind !== '' ? $kind : null,
                'notes' => $notes !== '' ? $notes : null,
                'units' => $normalizedUnits,
                'organizationNumbers' => $normalizedOrganizations,
                'paymentNumbers' => $normalizedPayments,
            ];
        }

        $timestamp = date(DATE_ATOM);
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $this->pdo->exec('DELETE FROM sender_alternative_names');
            $this->pdo->exec('DELETE FROM sender_units');
            $this->pdo->exec('DELETE FROM sender_payment_numbers');
            $this->pdo->exec('DELETE FROM sender_organization_numbers');
            $this->pdo->exec('DELETE FROM senders');

            $senderInsert = $this->pdo->prepare(
                'INSERT INTO senders (
                    id,
                    name,
                    domain,
                    kind,
                    notes,
                    confidence,
                    matching_updated_at,
                    created_at,
                    updated_at
                ) VALUES (
                    :id,
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
            $organizationInsert = $this->pdo->prepare(
                'INSERT INTO sender_organization_numbers (
                    id,
                    organization_number,
                    organization_name,
                    sender_id,
                    source,
                    created_at,
                    updated_at
                ) VALUES (
                    :id,
                    :organization_number,
                    :organization_name,
                    :sender_id,
                    :source,
                    :created_at,
                    :updated_at
                )'
            );
            $paymentInsert = $this->pdo->prepare(
                'INSERT INTO sender_payment_numbers (
                    id,
                    sender_id,
                    type,
                    number,
                    original_number,
                    requires_ocr,
                    source,
                    confidence,
                    created_at,
                    updated_at,
                    payee_name,
                    payee_lookup_status
                ) VALUES (
                    :id,
                    :sender_id,
                    :type,
                    :number,
                    :original_number,
                    :requires_ocr,
                    :source,
                    :confidence,
                    :created_at,
                    :updated_at,
                    :payee_name,
                    :payee_lookup_status
                )'
            );
            $unitInsert = $this->pdo->prepare(
                'INSERT INTO sender_units (
                    sender_id,
                    name,
                    normalized_name,
                    sort_order,
                    created_at,
                    updated_at
                ) VALUES (
                    :sender_id,
                    :name,
                    :normalized_name,
                    :sort_order,
                    :created_at,
                    :updated_at
                )'
            );
            $resolvedSenderIds = [];
            foreach ($normalizedSenders as $senderIndex => $sender) {
                $senderInsert->execute([
                    ':id' => $sender['id'],
                    ':name' => $sender['name'],
                    ':domain' => $sender['domain'],
                    ':kind' => $sender['kind'],
                    ':notes' => $sender['notes'],
                    ':confidence' => 1.0,
                    ':matching_updated_at' => $timestamp,
                    ':created_at' => $timestamp,
                    ':updated_at' => $timestamp,
                ]);
                $resolvedSenderIds[$senderIndex] = $sender['id'] !== null
                    ? (int) $sender['id']
                    : (int) $this->pdo->lastInsertId();
            }

            foreach ($normalizedSenders as $senderIndex => $sender) {
                $senderId = isset($resolvedSenderIds[$senderIndex]) ? (int) $resolvedSenderIds[$senderIndex] : 0;
                if ($senderId < 1) {
                    continue;
                }

                foreach ($sender['units'] as $unitRow) {
                    $unitInsert->execute([
                        ':sender_id' => $senderId,
                        ':name' => $unitRow['name'],
                        ':normalized_name' => $unitRow['normalizedName'],
                        ':sort_order' => $unitRow['sortOrder'],
                        ':created_at' => $timestamp,
                        ':updated_at' => $timestamp,
                    ]);
                }

                foreach ($sender['organizationNumbers'] as $organizationRow) {
                    $organizationInsert->execute([
                        ':id' => $organizationRow['id'],
                        ':organization_number' => $organizationRow['organizationNumber'],
                        ':organization_name' => $organizationRow['organizationName'] !== '' ? $organizationRow['organizationName'] : null,
                        ':sender_id' => $senderId,
                        ':source' => null,
                        ':created_at' => $timestamp,
                        ':updated_at' => $timestamp,
                    ]);
                }

                foreach ($sender['paymentNumbers'] as $paymentRow) {
                    $paymentInsert->execute([
                        ':id' => $paymentRow['id'],
                        ':sender_id' => $senderId,
                        ':type' => $paymentRow['type'],
                        ':number' => $paymentRow['number'],
                        ':original_number' => null,
                        ':requires_ocr' => 0,
                        ':source' => null,
                        ':confidence' => 1.0,
                        ':created_at' => $timestamp,
                        ':updated_at' => $timestamp,
                        ':payee_name' => null,
                        ':payee_lookup_status' => null,
                    ]);
                }

            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return $this->listEditorRows();
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
                p.payee_lookup_status,
                p.lookup_error_code,
                p.lookup_error_message
            FROM sender_payment_numbers p
            LEFT JOIN senders s ON s.id = p.sender_id
            WHERE (p.payee_name IS NULL OR trim(p.payee_name) = \'\')
              AND (p.payee_lookup_status IS NULL OR trim(p.payee_lookup_status) = \'\' OR p.payee_lookup_status = \'pending\')
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
                'lookupErrorCode' => is_string($row['lookup_error_code'] ?? null) ? trim((string) $row['lookup_error_code']) : '',
                'lookupErrorMessage' => is_string($row['lookup_error_message'] ?? null) ? trim((string) $row['lookup_error_message']) : '',
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
              AND (p.payee_lookup_status IS NULL OR trim(p.payee_lookup_status) = \'\' OR p.payee_lookup_status = \'pending\')'
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
                o.source,
                o.lookup_status,
                o.lookup_error_code,
                o.lookup_error_message
            FROM sender_organization_numbers o
            LEFT JOIN senders s ON s.id = o.sender_id
            WHERE (o.organization_name IS NULL OR trim(o.organization_name) = \'\')
              AND (o.lookup_status IS NULL OR trim(o.lookup_status) = \'\' OR o.lookup_status = \'pending\')
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
                'lookupStatus' => is_string($row['lookup_status'] ?? null) ? trim((string) $row['lookup_status']) : '',
                'lookupErrorCode' => is_string($row['lookup_error_code'] ?? null) ? trim((string) $row['lookup_error_code']) : '',
                'lookupErrorMessage' => is_string($row['lookup_error_message'] ?? null) ? trim((string) $row['lookup_error_message']) : '',
            ];
        }

        return $items;
    }

    public function countOrganizationNumbersMissingName(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*)
            FROM sender_organization_numbers o
            WHERE (o.organization_name IS NULL OR trim(o.organization_name) = \'\')
              AND (o.lookup_status IS NULL OR trim(o.lookup_status) = \'\' OR o.lookup_status = \'pending\')'
        );
        if ($statement === false) {
            return 0;
        }

        return max(0, (int) $statement->fetchColumn());
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

            $resolvedAlternativeNames = [];
            if ($normalizedOrganizationName !== null) {
                $resolvedAlternativeNames = $this->upsertOrganizationAlternativeNames(
                    $organizationId,
                    $normalizedOrganizationName,
                    $alternativeNames,
                    'allabolag'
                );
            }

            $timestamp = date(DATE_ATOM);
            $update = $this->pdo->prepare(
                'UPDATE sender_organization_numbers
                SET organization_name = :organization_name,
                    lookup_status = :lookup_status,
                    lookup_error_code = NULL,
                    lookup_error_message = NULL,
                    updated_at = :updated_at
                WHERE id = :id'
            );
            $update->execute([
                ':id' => $organizationId,
                ':organization_name' => $normalizedOrganizationName,
                ':lookup_status' => $normalizedOrganizationName !== null ? 'resolved' : 'pending',
                ':updated_at' => $timestamp,
            ]);

            $senderId = isset($row['sender_id']) && (int) $row['sender_id'] > 0
                ? (int) $row['sender_id']
                : null;
            if ($senderId !== null) {
                $this->touchSenderMatchingUpdatedAt($senderId, $timestamp);
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return [
                'organizationId' => $organizationId,
                'organizationNumber' => is_string($row['organization_number'] ?? null) ? trim((string) $row['organization_number']) : '',
                'organizationName' => $normalizedOrganizationName,
                'lookupStatus' => $normalizedOrganizationName !== null ? 'resolved' : 'pending',
                'alternativeNames' => $resolvedAlternativeNames,
                'senderId' => $senderId,
                'linkChanged' => false,
            ];
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function failOrganizationNameLookup(int $organizationId, string $errorCode, string $errorMessage): array
    {
        if ($organizationId < 1) {
            throw new RuntimeException('Organization id is required.');
        }

        $select = $this->pdo->prepare(
            'SELECT id, organization_number, sender_id
            FROM sender_organization_numbers
            WHERE id = :id
            LIMIT 1'
        );
        $select->execute([':id' => $organizationId]);
        $row = $select->fetch();
        if (!is_array($row)) {
            throw new RuntimeException('Organization number not found.');
        }

        $number = is_string($row['organization_number'] ?? null) ? trim((string) $row['organization_number']) : '';
        $normalizedCode = trim($errorCode) !== '' ? trim($errorCode) : 'ORG_LOOKUP_FAILED';
        $normalizedMessage = trim($errorMessage);
        if ($normalizedMessage === '') {
            $normalizedMessage = 'Org.nr ' . $this->formatOrganizationNumberForDisplay($number) . ' kunde inte slås upp hos Allabolag.';
        }

        $timestamp = date(DATE_ATOM);
        $update = $this->pdo->prepare(
            'UPDATE sender_organization_numbers
            SET lookup_status = \'failed\',
                lookup_error_code = :error_code,
                lookup_error_message = :error_message,
                updated_at = :updated_at
            WHERE id = :id'
        );
        $update->execute([
            ':id' => $organizationId,
            ':error_code' => $normalizedCode,
            ':error_message' => $normalizedMessage,
            ':updated_at' => $timestamp,
        ]);

        return [
            'organizationId' => $organizationId,
            'organizationNumber' => $number,
            'lookupStatus' => 'failed',
            'lookupErrorCode' => $normalizedCode,
            'lookupErrorMessage' => $normalizedMessage,
            'senderId' => isset($row['sender_id']) && (int) $row['sender_id'] > 0 ? (int) $row['sender_id'] : null,
        ];
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
                lookup_status = :lookup_status,
                lookup_error_code = NULL,
                lookup_error_message = NULL,
                updated_at = :updated_at
            WHERE id = :id'
        );
        $statement->execute([
            ':id' => $organizationId,
            ':organization_name' => $normalizedOrganizationName,
            ':lookup_status' => $normalizedOrganizationName !== null ? 'resolved' : 'pending',
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
        if ($normalizedLookupStatus === 'not_found') {
            $normalizedLookupStatus = 'failed';
        }
        if ($normalizedLookupStatus !== null && !in_array($normalizedLookupStatus, ['pending', 'resolved', 'failed'], true)) {
            throw new RuntimeException('Unsupported payee lookup status.');
        }
        if ($normalizedPayeeName !== null) {
            $normalizedLookupStatus = 'resolved';
        } elseif ($normalizedLookupStatus === null) {
            $normalizedLookupStatus = 'pending';
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

            $timestamp = date(DATE_ATOM);
            $update = $this->pdo->prepare(
                'UPDATE sender_payment_numbers
                SET payee_name = :payee_name,
                    payee_lookup_status = :payee_lookup_status,
                    lookup_error_code = NULL,
                    lookup_error_message = NULL,
                    updated_at = :updated_at
                WHERE id = :id'
            );
            $update->execute([
                ':id' => $paymentId,
                ':payee_name' => $normalizedPayeeName,
                ':payee_lookup_status' => $normalizedLookupStatus,
                ':updated_at' => $timestamp,
            ]);

            $senderId = isset($row['sender_id']) && (int) $row['sender_id'] > 0
                ? (int) $row['sender_id']
                : null;
            if ($senderId !== null) {
                $this->touchSenderMatchingUpdatedAt($senderId, $timestamp);
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
                'senderId' => $senderId,
                'linkChanged' => false,
            ];
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function failPaymentPayeeLookup(int $paymentId, string $errorCode, string $errorMessage): array
    {
        if ($paymentId < 1) {
            throw new RuntimeException('Payment id is required.');
        }

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

        $type = is_string($row['type'] ?? null) && trim(strtolower((string) $row['type'])) === 'plusgiro'
            ? 'plusgiro'
            : 'bankgiro';
        $number = is_string($row['number'] ?? null) ? trim((string) $row['number']) : '';
        $normalizedCode = trim($errorCode) !== '' ? trim($errorCode) : 'PAYEE_LOOKUP_FAILED';
        $normalizedMessage = trim($errorMessage);
        if ($normalizedMessage === '') {
            $normalizedMessage = ($type === 'plusgiro' ? 'Plusgiro ' : 'Bankgiro ')
                . $this->formatPaymentNumberForDisplay($type, $number)
                . ' kunde inte slås upp.';
        }

        $timestamp = date(DATE_ATOM);
        $update = $this->pdo->prepare(
            'UPDATE sender_payment_numbers
            SET payee_lookup_status = \'failed\',
                lookup_error_code = :error_code,
                lookup_error_message = :error_message,
                updated_at = :updated_at
            WHERE id = :id'
        );
        $update->execute([
            ':id' => $paymentId,
            ':error_code' => $normalizedCode,
            ':error_message' => $normalizedMessage,
            ':updated_at' => $timestamp,
        ]);

        return [
            'paymentId' => $paymentId,
            'type' => $type,
            'number' => $number,
            'lookupStatus' => 'failed',
            'lookupErrorCode' => $normalizedCode,
            'lookupErrorMessage' => $normalizedMessage,
            'senderId' => isset($row['sender_id']) && (int) $row['sender_id'] > 0 ? (int) $row['sender_id'] : null,
        ];
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
        if ($normalizedLookupStatus === 'not_found') {
            $normalizedLookupStatus = 'failed';
        }
        if ($normalizedLookupStatus !== null && !in_array($normalizedLookupStatus, ['pending', 'resolved', 'failed'], true)) {
            throw new RuntimeException('Unsupported payee lookup status.');
        }
        if ($normalizedPayeeName !== null) {
            $normalizedLookupStatus = 'resolved';
        } elseif ($normalizedLookupStatus === null) {
            $normalizedLookupStatus = 'pending';
        }

        $statement = $this->pdo->prepare(
            'UPDATE sender_payment_numbers
            SET payee_name = :payee_name,
                payee_lookup_status = :payee_lookup_status,
                lookup_error_code = NULL,
                lookup_error_message = NULL,
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
        ?string $name,
        ?string $domain = null,
        ?string $kind = null,
        ?string $notes = null,
        float $confidence = 1.0
    ): int {
        $normalizedName = is_string($name) && trim($name) !== '' ? trim($name) : '(Namnlös)';

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
            ':name' => $normalizedName,
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

    public function updateSenderBasic(int $id, ?string $name): void
    {
        $normalizedName = is_string($name) && trim($name) !== '' ? trim($name) : null;
        if ($id < 1) {
            throw new RuntimeException('Sender id is required.');
        }

        $statement = $this->pdo->prepare(
            'UPDATE senders
            SET name = :name, updated_at = :updated_at
            WHERE id = :id'
        );
        $statement->execute([
            ':id' => $id,
            ':name' => $normalizedName,
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
        $existing = $this->canonicalPaymentNumberRow($normalizedType, $normalizedNumber);
        if (is_array($existing)) {
            $existingId = isset($existing['id']) ? (int) $existing['id'] : 0;
            $existingSenderId = isset($existing['sender_id']) ? (int) $existing['sender_id'] : 0;
            if ($existingId < 1) {
                throw new RuntimeException('Payment number row is invalid.');
            }
            if ($existingSenderId > 0 && $existingSenderId !== $senderId) {
                throw new RuntimeException('Payment number is already linked to another sender.');
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
                SET sender_id = :sender_id,
                    original_number = :original_number,
                    requires_ocr = :requires_ocr,
                    source = :source,
                    confidence = :confidence,
                    updated_at = :updated_at
                WHERE id = :id'
            );
            $update->execute([
                ':id' => $existingId,
                ':sender_id' => $senderId,
                ':original_number' => $currentOriginalNumber !== '' ? $currentOriginalNumber : $originalNumber,
                ':requires_ocr' => $requiresOcr ? 1 : (int) ($existing['requires_ocr'] ?? 0),
                ':source' => $currentSource !== '' ? $currentSource : $source,
                ':confidence' => max($currentConfidence, $confidence),
                ':updated_at' => $timestamp,
            ]);
            return;
        }

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
        ?string $source = 'document_auto',
        ?string $sourceJobId = null,
        array $sourceBboxIndexes = []
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
        $normalizedSourceJobId = is_string($sourceJobId) ? trim($sourceJobId) : '';
        if ($normalizedSourceJobId === '') {
            $normalizedSourceJobId = null;
        }
        $normalizedSourceBboxIndexes = $this->normalizeSourceBboxIndexes($sourceBboxIndexes);

        $existing = $this->canonicalOrganizationNumberRow($normalizedNumber);
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
                source_job_id,
                source_bbox_indexes,
                created_at,
                updated_at
            ) VALUES (
                :organization_number,
                :organization_name,
                NULL,
                :source,
                :source_job_id,
                :source_bbox_indexes,
                :created_at,
                :updated_at
            )'
        );
        $insert->execute([
            ':organization_number' => $normalizedNumber,
            ':organization_name' => $normalizedName,
            ':source' => $normalizedSource,
            ':source_job_id' => $normalizedSourceJobId,
            ':source_bbox_indexes' => $normalizedSourceJobId !== null && $normalizedSourceBboxIndexes !== []
                ? json_encode($normalizedSourceBboxIndexes, JSON_THROW_ON_ERROR)
                : null,
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
        float $confidence = 1.0,
        ?string $sourceJobId = null,
        array $sourceBboxIndexes = []
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
        $normalizedSourceJobId = is_string($sourceJobId) ? trim($sourceJobId) : '';
        if ($normalizedSourceJobId === '') {
            $normalizedSourceJobId = null;
        }
        $normalizedSourceBboxIndexes = $this->normalizeSourceBboxIndexes($sourceBboxIndexes);

        $existing = $this->canonicalPaymentNumberRow($normalizedType, $normalizedNumber);
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
                source_job_id,
                source_bbox_indexes,
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
                :source_job_id,
                :source_bbox_indexes,
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
            ':source_job_id' => $normalizedSourceJobId,
            ':source_bbox_indexes' => $normalizedSourceJobId !== null && $normalizedSourceBboxIndexes !== []
                ? json_encode($normalizedSourceBboxIndexes, JSON_THROW_ON_ERROR)
                : null,
            ':confidence' => $confidence,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function normalizeSourceBboxIndexes(array $indexes): array
    {
        $normalized = [];
        foreach ($indexes as $index) {
            if (is_int($index) && $index > 0) {
                $normalized[$index] = true;
            }
        }

        $values = array_keys($normalized);
        sort($values, SORT_NUMERIC);

        return $values;
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
                'SELECT id, organization_number
                FROM sender_organization_numbers
                WHERE sender_id = ?'
            );
            $paymentStatement = $this->pdo->prepare(
                'SELECT id, type, number
                FROM sender_payment_numbers
                WHERE sender_id = ?'
            );
            $moveOrganization = $this->pdo->prepare(
                'UPDATE sender_organization_numbers
                SET sender_id = :sender_id,
                    updated_at = :updated_at
                WHERE id = :id'
            );
            $movePayment = $this->pdo->prepare(
                'UPDATE sender_payment_numbers
                SET sender_id = :sender_id,
                    updated_at = :updated_at
                WHERE id = :id'
            );
            $copyUnits = $this->pdo->prepare(
                'INSERT OR IGNORE INTO sender_units (
                    sender_id,
                    name,
                    normalized_name,
                    sort_order,
                    created_at,
                    updated_at
                )
                SELECT
                    :sender_id,
                    name,
                    normalized_name,
                    sort_order,
                    created_at,
                    :updated_at
                FROM sender_units
                WHERE sender_id = :source_sender_id'
            );
            $deleteSourceUnits = $this->pdo->prepare(
                'DELETE FROM sender_units
                WHERE sender_id = :source_sender_id'
            );
            $deleteSender = $this->pdo->prepare(
                'DELETE FROM senders
                WHERE id = :id'
            );

            $timestamp = date(DATE_ATOM);
            $movedOrganizationNumbers = [];
            $movedPaymentNumbers = [];

            foreach ($normalizedSourceIds as $sourceSenderId) {
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
                            $canonical = $this->canonicalOrganizationNumberRow($organizationNumber);
                            $canonicalId = is_array($canonical) && isset($canonical['id']) ? (int) $canonical['id'] : 0;
                            if ($canonicalId > 0) {
                                $moveOrganization->execute([
                                    ':id' => $canonicalId,
                                    ':sender_id' => $targetSenderId,
                                    ':updated_at' => $timestamp,
                                ]);
                            }
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
                            $canonical = $this->canonicalPaymentNumberRow($paymentType, $paymentNumber);
                            $canonicalId = is_array($canonical) && isset($canonical['id']) ? (int) $canonical['id'] : 0;
                            if ($canonicalId > 0) {
                                $movePayment->execute([
                                    ':id' => $canonicalId,
                                    ':sender_id' => $targetSenderId,
                                    ':updated_at' => $timestamp,
                                ]);
                            }
                        }
                    }
                }

                $copyUnits->execute([
                    ':sender_id' => $targetSenderId,
                    ':updated_at' => $timestamp,
                    ':source_sender_id' => $sourceSenderId,
                ]);
                $deleteSourceUnits->execute([
                    ':source_sender_id' => $sourceSenderId,
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

    public function resolveDocumentSenderLinks(array $orgNumbers, array $bankgiros, array $plusgiros): array
    {
        $components = $this->loadDocumentSenderComponents($orgNumbers, $bankgiros, $plusgiros);
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
            'components' => $this->loadDocumentSenderComponents($orgNumbers, $bankgiros, $plusgiros),
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

    private function loadDocumentSenderComponents(array $orgNumbers, array $bankgiros, array $plusgiros): array
    {
        $components = [];
        $seenComponentKeys = [];

        foreach ($orgNumbers as $orgNumber) {
            if (!is_string($orgNumber)) {
                continue;
            }
            $normalizedOrganizationNumber = IdentifierNormalizer::normalizeOrgNumber($orgNumber);
            if ($normalizedOrganizationNumber === null) {
                continue;
            }
            $componentKey = 'organization_number:' . $normalizedOrganizationNumber;
            if (isset($seenComponentKeys[$componentKey])) {
                continue;
            }
            $seenComponentKeys[$componentKey] = true;
            $row = $this->findObservedOrganizationNumberRow($normalizedOrganizationNumber);
            $components[] = [
                'type' => 'organization_number',
                'senderId' => isset($row['sender_id']) ? (int) $row['sender_id'] : 0,
                'number' => $normalizedOrganizationNumber,
                'observedName' => is_string($row['organization_name'] ?? null) ? trim((string) $row['organization_name']) : '',
            ];
        }

        foreach ($bankgiros as $bankgiro) {
            if (!is_string($bankgiro)) {
                continue;
            }
            $normalizedBankgiro = IdentifierNormalizer::normalizeBankgiro($bankgiro);
            if ($normalizedBankgiro === null) {
                continue;
            }
            $componentKey = 'bankgiro:' . $normalizedBankgiro;
            if (isset($seenComponentKeys[$componentKey])) {
                continue;
            }
            $seenComponentKeys[$componentKey] = true;
            $row = $this->findObservedPaymentNumberRow('bankgiro', $normalizedBankgiro);
            $components[] = [
                'type' => 'bankgiro',
                'senderId' => isset($row['sender_id']) ? (int) $row['sender_id'] : 0,
                'number' => $normalizedBankgiro,
                'observedName' => is_string($row['payee_name'] ?? null) ? trim((string) $row['payee_name']) : '',
            ];
        }

        foreach ($plusgiros as $plusgiro) {
            if (!is_string($plusgiro)) {
                continue;
            }
            $normalizedPlusgiro = IdentifierNormalizer::normalizePlusgiro($plusgiro);
            if ($normalizedPlusgiro === null) {
                continue;
            }
            $componentKey = 'plusgiro:' . $normalizedPlusgiro;
            if (isset($seenComponentKeys[$componentKey])) {
                continue;
            }
            $seenComponentKeys[$componentKey] = true;
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
                    'organizationNames' => [],
                    'paymentNames' => [],
                    'organizationCount' => 0,
                    'paymentCount' => 0,
                ];
            }
        }

        if ($bundles === []) {
            return [];
        }

        $alternativeStatement = $this->pdo->prepare(
            'SELECT o.sender_id, a.name
            FROM sender_alternative_names a
            INNER JOIN sender_organization_numbers o ON o.id = a.sender_organization_number_id
            WHERE o.sender_id IN (' . $placeholders . ')
            ORDER BY a.name ASC, a.id ASC'
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

        $organizationNameStatement = $this->pdo->prepare(
            'SELECT sender_id, organization_name
            FROM sender_organization_numbers
            WHERE sender_id IN (' . $placeholders . ')
              AND organization_name IS NOT NULL
              AND trim(organization_name) <> \'\'
            ORDER BY organization_name ASC, id ASC'
        );
        $organizationNameStatement->execute($normalizedSenderIds);
        $organizationNameRows = $organizationNameStatement->fetchAll();
        if (is_array($organizationNameRows)) {
            foreach ($organizationNameRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
                if ($senderId < 1 || !isset($bundles[$senderId])) {
                    continue;
                }
                $name = is_string($row['organization_name'] ?? null) ? trim((string) $row['organization_name']) : '';
                if ($name === '') {
                    continue;
                }
                $bundles[$senderId]['organizationNames'][] = $name;
            }
        }

        $paymentNameStatement = $this->pdo->prepare(
            'SELECT sender_id, payee_name
            FROM sender_payment_numbers
            WHERE sender_id IN (' . $placeholders . ')
              AND payee_name IS NOT NULL
              AND trim(payee_name) <> \'\'
            ORDER BY payee_name ASC, id ASC'
        );
        $paymentNameStatement->execute($normalizedSenderIds);
        $paymentNameRows = $paymentNameStatement->fetchAll();
        if (is_array($paymentNameRows)) {
            foreach ($paymentNameRows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $senderId = isset($row['sender_id']) ? (int) $row['sender_id'] : 0;
                if ($senderId < 1 || !isset($bundles[$senderId])) {
                    continue;
                }
                $name = is_string($row['payee_name'] ?? null) ? trim((string) $row['payee_name']) : '';
                if ($name === '') {
                    continue;
                }
                $bundles[$senderId]['paymentNames'][] = $name;
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
        $leftKnownNames = $this->normalizedAlternativeNameMap(array_merge(
            [(string) ($leftSender['name'] ?? '')],
            is_array($leftSender['organizationNames'] ?? null) ? $leftSender['organizationNames'] : [],
            is_array($leftSender['alternativeNames'] ?? null) ? $leftSender['alternativeNames'] : [],
            is_array($leftSender['paymentNames'] ?? null) ? $leftSender['paymentNames'] : []
        ));
        $rightKnownNames = $this->normalizedAlternativeNameMap(array_merge(
            [(string) ($rightSender['name'] ?? '')],
            is_array($rightSender['organizationNames'] ?? null) ? $rightSender['organizationNames'] : [],
            is_array($rightSender['alternativeNames'] ?? null) ? $rightSender['alternativeNames'] : [],
            is_array($rightSender['paymentNames'] ?? null) ? $rightSender['paymentNames'] : []
        ));

        if ($leftPrimaryName !== null && $rightPrimaryName !== null && $leftPrimaryName === $rightPrimaryName) {
            return true;
        }
        if ($leftObservedName !== null && isset($rightKnownNames[$leftObservedName])) {
            return true;
        }
        if ($rightObservedName !== null && isset($leftKnownNames[$rightObservedName])) {
            return true;
        }

        foreach (array_keys($leftKnownNames) as $leftKnownName) {
            if (isset($rightKnownNames[$leftKnownName])) {
                return true;
            }
        }

        if ($rightPrimaryName !== null && isset($leftKnownNames[$rightPrimaryName])) {
            return true;
        }
        if ($leftPrimaryName !== null && isset($rightKnownNames[$leftPrimaryName])) {
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

    private function upsertOrganizationAlternativeNames(
        int $organizationId,
        ?string $organizationName,
        array $names,
        ?string $source = null
    ): array
    {
        if ($organizationId < 1) {
            return [];
        }

        $primaryName = IdentifierNormalizer::normalizeName((string) ($organizationName ?? ''));
        $normalizedSource = is_string($source) && trim($source) !== '' ? trim($source) : null;
        $existingNames = [];
        $select = $this->pdo->prepare(
            'SELECT normalized_name
            FROM sender_alternative_names
            WHERE sender_organization_number_id = :organization_id'
        );
        $select->execute([':organization_id' => $organizationId]);
        $rows = $select->fetchAll();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $normalized = is_string($row['normalized_name'] ?? null) ? trim((string) $row['normalized_name']) : '';
                if ($normalized === '') {
                    continue;
                }
                $existingNames[$normalized] = true;
            }
        }

        $insert = $this->pdo->prepare(
            'INSERT OR IGNORE INTO sender_alternative_names (
                sender_organization_number_id,
                name,
                normalized_name,
                source,
                created_at,
                updated_at
            ) VALUES (
                :organization_id,
                :name,
                :normalized_name,
                :source,
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
                ':organization_id' => $organizationId,
                ':name' => $trimmed,
                ':normalized_name' => $normalized,
                ':source' => $normalizedSource,
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ]);
            $saved[] = $trimmed;
        }

        return $saved;
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
