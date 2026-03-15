<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

use Docflow\Database\Connection;
use Docflow\Senders\IdentifierNormalizer;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    json_response(['error' => 'Invalid request body'], 400);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload) || !array_key_exists('senders', $payload) || !is_array($payload['senders'])) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

function format_payment_number_for_display(string $type, string $number): string
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

/**
 * @return array<int, array<string, mixed>>
 */
function load_sender_editor_rows(PDO $pdo): array
{
    $statement = $pdo->query(
        'SELECT
            s.id AS sender_id,
            s.name AS sender_name,
            s.slug AS sender_slug,
            s.org_number AS sender_org_number,
            s.domain AS sender_domain,
            s.kind AS sender_kind,
            s.notes AS sender_notes,
            p.id AS payment_id,
            p.type AS payment_type,
            p.number AS payment_number
        FROM senders s
        LEFT JOIN sender_payment_numbers p ON p.sender_id = s.id
        ORDER BY s.name ASC, s.slug ASC, p.type ASC, p.number ASC, p.id ASC'
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
                'slug' => is_string($row['sender_slug'] ?? null) ? trim((string) $row['sender_slug']) : '',
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

        $sendersById[$senderId]['paymentNumbers'][] = [
            'id' => $paymentId,
            'type' => is_string($row['payment_type'] ?? null) ? trim(strtolower((string) $row['payment_type'])) : 'bankgiro',
            'number' => format_payment_number_for_display(
                is_string($row['payment_type'] ?? null) ? trim(strtolower((string) $row['payment_type'])) : 'bankgiro',
                is_string($row['payment_number'] ?? null) ? trim((string) $row['payment_number']) : ''
            ),
        ];
    }

    return array_values($sendersById);
}

$normalized = [];
$seenSlugs = [];
$seenOrgNumbers = [];
$seenPaymentNumbers = [];

foreach ($payload['senders'] as $row) {
    if (!is_array($row)) {
        continue;
    }

    $id = isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : null;
    if ($id !== null && $id < 1) {
        $id = null;
    }

    $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
    $slug = is_string($row['slug'] ?? null) ? trim((string) $row['slug']) : '';
    $orgNumberRaw = is_string($row['orgNumber'] ?? null) ? trim((string) $row['orgNumber']) : '';
    $domainRaw = is_string($row['domain'] ?? null) ? trim((string) $row['domain']) : '';
    $kindRaw = is_string($row['kind'] ?? null) ? trim((string) $row['kind']) : '';
    $notesRaw = is_string($row['notes'] ?? null) ? trim((string) $row['notes']) : '';
    $paymentRows = array_values(array_filter(
        is_array($row['paymentNumbers'] ?? null) ? $row['paymentNumbers'] : [],
        static fn (mixed $payment): bool => is_array($payment)
    ));

    $isEffectivelyEmpty = $name === ''
        && $slug === ''
        && $orgNumberRaw === ''
        && $domainRaw === ''
        && $kindRaw === ''
        && $notesRaw === ''
        && count($paymentRows) === 0;
    if ($isEffectivelyEmpty) {
        continue;
    }

    if ($name === '' || $slug === '') {
        json_response(['error' => 'Varje avsändare måste ha både namn och slug'], 400);
        exit;
    }

    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $slug)) {
        json_response(['error' => 'Slug får bara innehålla bokstäver, siffror, bindestreck och understreck'], 400);
        exit;
    }

    $slugKey = strtolower($slug);
    if (isset($seenSlugs[$slugKey])) {
        json_response(['error' => 'Slug måste vara unik'], 400);
        exit;
    }
    $seenSlugs[$slugKey] = true;

    $orgNumber = null;
    if ($orgNumberRaw !== '') {
        $orgNumber = IdentifierNormalizer::normalizeOrgNumber($orgNumberRaw);
        if ($orgNumber === null) {
            json_response(['error' => 'Ogiltigt organisationsnummer för avsändare: ' . $name], 400);
            exit;
        }

        if (isset($seenOrgNumbers[$orgNumber])) {
            json_response(['error' => 'Organisationsnummer måste vara unikt'], 400);
            exit;
        }
        $seenOrgNumbers[$orgNumber] = true;
    }

    $paymentNumbers = [];
    foreach ($paymentRows as $paymentRow) {
        $paymentId = isset($paymentRow['id']) && is_numeric($paymentRow['id']) ? (int) $paymentRow['id'] : null;
        if ($paymentId !== null && $paymentId < 1) {
            $paymentId = null;
        }

        $type = is_string($paymentRow['type'] ?? null) ? trim(strtolower((string) $paymentRow['type'])) : 'bankgiro';
        if ($type !== 'bankgiro' && $type !== 'plusgiro') {
            json_response(['error' => 'Betalnummerstyp måste vara bankgiro eller plusgiro'], 400);
            exit;
        }

        $numberRaw = is_string($paymentRow['number'] ?? null) ? trim((string) $paymentRow['number']) : '';
        if ($numberRaw === '') {
            continue;
        }

        $normalizedNumber = $type === 'plusgiro'
            ? IdentifierNormalizer::normalizePlusgiro($numberRaw)
            : IdentifierNormalizer::normalizeBankgiro($numberRaw);
        if ($normalizedNumber === null) {
            json_response(['error' => 'Ogiltigt betalnummer för avsändare: ' . $name], 400);
            exit;
        }

        $paymentKey = $type . ':' . $normalizedNumber;
        if (isset($seenPaymentNumbers[$paymentKey])) {
            json_response(['error' => 'Betalnummer måste vara unikt'], 400);
            exit;
        }
        $seenPaymentNumbers[$paymentKey] = true;

        $paymentNumbers[] = [
            'id' => $paymentId,
            'type' => $type,
            'number' => $normalizedNumber,
        ];
    }

    $normalized[] = [
        'id' => $id,
        'name' => $name,
        'slug' => $slug,
        'orgNumber' => $orgNumber,
        'domain' => $domainRaw !== '' ? strtolower($domainRaw) : null,
        'kind' => $kindRaw !== '' ? $kindRaw : null,
        'notes' => $notesRaw !== '' ? $notesRaw : null,
        'paymentNumbers' => $paymentNumbers,
    ];
}

try {
    $pdo = Connection::make();
    $pdo->beginTransaction();

    $existingSenderRows = $pdo->query('SELECT id FROM senders')->fetchAll(PDO::FETCH_COLUMN);
    $existingSenderIds = [];
    foreach ($existingSenderRows as $value) {
        $senderId = (int) $value;
        if ($senderId > 0) {
            $existingSenderIds[$senderId] = true;
        }
    }

    $existingPaymentRows = $pdo->query('SELECT id, sender_id FROM sender_payment_numbers')->fetchAll(PDO::FETCH_ASSOC);
    $existingPaymentIds = [];
    foreach ($existingPaymentRows as $paymentRow) {
        if (!is_array($paymentRow)) {
            continue;
        }
        $paymentId = isset($paymentRow['id']) ? (int) $paymentRow['id'] : 0;
        $senderId = isset($paymentRow['sender_id']) ? (int) $paymentRow['sender_id'] : 0;
        if ($paymentId > 0 && $senderId > 0) {
            $existingPaymentIds[$paymentId] = $senderId;
        }
    }

    $submittedSenderIds = [];
    $submittedPaymentIds = [];

    $deleteRemovedPayments = $pdo->prepare('DELETE FROM sender_payment_numbers WHERE id = :id');
    $deleteRemovedSender = $pdo->prepare('DELETE FROM senders WHERE id = :id');

    $insertSender = $pdo->prepare(
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
            1,
            :created_at,
            :updated_at
        )'
    );

    $updateSender = $pdo->prepare(
        'UPDATE senders
        SET
            name = :name,
            slug = :slug,
            org_number = :org_number,
            domain = :domain,
            kind = :kind,
            notes = :notes,
            updated_at = :updated_at
        WHERE id = :id'
    );

    $insertPayment = $pdo->prepare(
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
            0,
            :source,
            1,
            :created_at,
            :updated_at
        )'
    );

    $updatePayment = $pdo->prepare(
        'UPDATE sender_payment_numbers
        SET
            sender_id = :sender_id,
            type = :type,
            number = :number,
            original_number = NULL,
            requires_ocr = 0,
            source = :source,
            confidence = 1,
            updated_at = :updated_at
        WHERE id = :id'
    );

    foreach ($normalized as $row) {
        if ($row['id'] !== null) {
            $submittedSenderIds[(int) $row['id']] = true;
        }
        foreach ($row['paymentNumbers'] as $paymentRow) {
            if ($paymentRow['id'] !== null) {
                $submittedPaymentIds[(int) $paymentRow['id']] = true;
            }
        }
    }

    foreach ($existingPaymentIds as $paymentId => $_senderId) {
        if (!isset($submittedPaymentIds[$paymentId])) {
            $deleteRemovedPayments->execute([':id' => $paymentId]);
        }
    }

    foreach ($existingSenderIds as $senderId => $_unused) {
        if (!isset($submittedSenderIds[$senderId])) {
            $deleteRemovedSender->execute([':id' => $senderId]);
        }
    }

    $submittedSenderIds = [];
    $submittedPaymentIds = [];

    foreach ($normalized as $row) {
        $timestamp = date(DATE_ATOM);
        $senderId = $row['id'];

        if ($senderId !== null && isset($existingSenderIds[$senderId])) {
            $updateSender->execute([
                ':id' => $senderId,
                ':name' => $row['name'],
                ':slug' => $row['slug'],
                ':org_number' => $row['orgNumber'],
                ':domain' => $row['domain'],
                ':kind' => $row['kind'],
                ':notes' => $row['notes'],
                ':updated_at' => $timestamp,
            ]);
        } elseif ($senderId !== null) {
            throw new RuntimeException('Avsändaren som skulle uppdateras finns inte längre.');
        } else {
            $insertSender->execute([
                ':name' => $row['name'],
                ':slug' => $row['slug'],
                ':org_number' => $row['orgNumber'],
                ':domain' => $row['domain'],
                ':kind' => $row['kind'],
                ':notes' => $row['notes'],
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ]);
            $senderId = (int) $pdo->lastInsertId();
        }

        $submittedSenderIds[$senderId] = true;

        foreach ($row['paymentNumbers'] as $paymentRow) {
            $paymentId = $paymentRow['id'];
            $paymentTimestamp = date(DATE_ATOM);

            if ($paymentId !== null && isset($existingPaymentIds[$paymentId])) {
                $updatePayment->execute([
                    ':id' => $paymentId,
                    ':sender_id' => $senderId,
                    ':type' => $paymentRow['type'],
                    ':number' => $paymentRow['number'],
                    ':source' => 'docflow_editor',
                    ':updated_at' => $paymentTimestamp,
                ]);
                $submittedPaymentIds[$paymentId] = true;
                continue;
            } elseif ($paymentId !== null) {
                throw new RuntimeException('Betalnumret som skulle uppdateras finns inte längre.');
            }

            $insertPayment->execute([
                ':sender_id' => $senderId,
                ':type' => $paymentRow['type'],
                ':number' => $paymentRow['number'],
                ':original_number' => null,
                ':source' => 'docflow_editor',
                ':created_at' => $paymentTimestamp,
                ':updated_at' => $paymentTimestamp,
            ]);
            $submittedPaymentIds[(int) $pdo->lastInsertId()] = true;
        }
    }

    $pdo->commit();

    $senders = load_sender_editor_rows($pdo);

    json_response([
        'ok' => true,
        'senders' => $senders,
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => $e->getMessage()], 500);
}
