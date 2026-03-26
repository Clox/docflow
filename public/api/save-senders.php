<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

use Docflow\Database\Connection;
use Docflow\Senders\IdentifierNormalizer;
use Docflow\Senders\SenderRepository;

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

$normalized = [];
$seenOrgNumbers = [];
$seenPaymentNumbers = [];
$claimedMergedSourceIds = [];
$submittedSenderIds = [];

foreach ($payload['senders'] as $row) {
    if (!is_array($row)) {
        continue;
    }

    $id = isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : null;
    if ($id !== null && $id < 1) {
        $id = null;
    }

    $name = is_string($row['name'] ?? null) ? trim((string) $row['name']) : '';
    $orgNumberRaw = is_string($row['orgNumber'] ?? null) ? trim((string) $row['orgNumber']) : '';
    $domainRaw = is_string($row['domain'] ?? null) ? trim((string) $row['domain']) : '';
    $kindRaw = is_string($row['kind'] ?? null) ? trim((string) $row['kind']) : '';
    $notesRaw = is_string($row['notes'] ?? null) ? trim((string) $row['notes']) : '';
    $paymentRows = array_values(array_filter(
        is_array($row['paymentNumbers'] ?? null) ? $row['paymentNumbers'] : [],
        static fn (mixed $payment): bool => is_array($payment)
    ));

    $mergedSourceSenderIds = [];
    foreach (is_array($row['mergedSourceSenderIds'] ?? null) ? $row['mergedSourceSenderIds'] : [] as $value) {
        $sourceId = is_numeric($value) ? (int) $value : 0;
        if ($sourceId < 1 || ($id !== null && $sourceId === $id)) {
            continue;
        }
        if (isset($claimedMergedSourceIds[$sourceId])) {
            json_response(['error' => 'Samma avsändare kan inte slås samman till flera mål samtidigt'], 400);
            exit;
        }
        $claimedMergedSourceIds[$sourceId] = true;
        $mergedSourceSenderIds[] = $sourceId;
    }

    $isEffectivelyEmpty = $name === ''
        && $orgNumberRaw === ''
        && $domainRaw === ''
        && $kindRaw === ''
        && $notesRaw === ''
        && count($paymentRows) === 0;
    if ($isEffectivelyEmpty) {
        continue;
    }

    if ($name === '') {
        json_response(['error' => 'Varje avsändare måste ha ett namn'], 400);
        exit;
    }

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

    if ($id !== null) {
        $submittedSenderIds[$id] = true;
    }

    $normalized[] = [
        'id' => $id,
        'name' => $name,
        'orgNumber' => $orgNumber,
        'domain' => $domainRaw !== '' ? strtolower($domainRaw) : null,
        'kind' => $kindRaw !== '' ? $kindRaw : null,
        'notes' => $notesRaw !== '' ? $notesRaw : null,
        'paymentNumbers' => $paymentNumbers,
        'mergedSourceSenderIds' => $mergedSourceSenderIds,
    ];
}

foreach ($normalized as $row) {
    foreach ($row['mergedSourceSenderIds'] as $sourceId) {
        if (isset($submittedSenderIds[$sourceId])) {
            json_response(['error' => 'En avsändare kan inte både vara aktiv och sammanslagen i samma sparning'], 400);
            exit;
        }
    }
}

try {
    $pdo = Connection::make();
    $repository = new SenderRepository($pdo);
    $pdo->beginTransaction();

    $existingSenderRows = $pdo->query(
        'SELECT id
        FROM senders
        WHERE merged_into_sender_id IS NULL'
    )->fetchAll(PDO::FETCH_COLUMN);
    $existingSenderIds = [];
    foreach ($existingSenderRows as $value) {
        $senderId = (int) $value;
        if ($senderId > 0) {
            $existingSenderIds[$senderId] = true;
        }
    }

    foreach ($claimedMergedSourceIds as $sourceId => $_true) {
        if (!isset($existingSenderIds[$sourceId])) {
            throw new RuntimeException('En avsändare som skulle slås samman finns inte längre eller är redan sammanslagen.');
        }
    }

    $existingPaymentRows = $pdo->query(
        'SELECT p.id, p.sender_id
        FROM sender_payment_numbers p
        INNER JOIN senders s ON s.id = p.sender_id
        WHERE s.merged_into_sender_id IS NULL'
    )->fetchAll(PDO::FETCH_ASSOC);
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

    $submittedPaymentIds = [];
    foreach ($normalized as $row) {
        foreach ($row['paymentNumbers'] as $paymentRow) {
            if ($paymentRow['id'] !== null) {
                $submittedPaymentIds[(int) $paymentRow['id']] = true;
            }
        }
    }

    $deleteRemovedPayments = $pdo->prepare('DELETE FROM sender_payment_numbers WHERE id = :id');
    $deleteRemovedSender = $pdo->prepare('DELETE FROM senders WHERE id = :id');
    $clearSourceSenderOrgNumber = $pdo->prepare(
        'UPDATE senders
        SET org_number = NULL, updated_at = :updated_at
        WHERE id = :id AND merged_into_sender_id IS NULL'
    );
    $markSourceSenderMerged = $pdo->prepare(
        'UPDATE senders
        SET merged_into_sender_id = :target_sender_id, updated_at = :updated_at
        WHERE id = :id AND merged_into_sender_id IS NULL'
    );

    $insertSender = $pdo->prepare(
        'INSERT INTO senders (
            name,
            org_number,
            domain,
            kind,
            notes,
            confidence,
            created_at,
            updated_at,
            merged_into_sender_id
        ) VALUES (
            :name,
            :org_number,
            :domain,
            :kind,
            :notes,
            1,
            :created_at,
            :updated_at,
            NULL
        )'
    );

    $updateSender = $pdo->prepare(
        'UPDATE senders
        SET
            name = :name,
            org_number = :org_number,
            domain = :domain,
            kind = :kind,
            notes = :notes,
            merged_into_sender_id = NULL,
            updated_at = :updated_at
        WHERE id = :id AND merged_into_sender_id IS NULL'
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

    foreach ($existingPaymentIds as $paymentId => $_senderId) {
        if (!isset($submittedPaymentIds[$paymentId])) {
            $deleteRemovedPayments->execute([':id' => $paymentId]);
        }
    }

    foreach ($existingSenderIds as $senderId => $_unused) {
        if (!isset($submittedSenderIds[$senderId]) && !isset($claimedMergedSourceIds[$senderId])) {
            $deleteRemovedSender->execute([':id' => $senderId]);
        }
    }

    foreach ($normalized as $row) {
        $timestamp = date(DATE_ATOM);
        $senderId = $row['id'];

        foreach ($row['mergedSourceSenderIds'] as $sourceSenderId) {
            $clearSourceSenderOrgNumber->execute([
                ':id' => $sourceSenderId,
                ':updated_at' => $timestamp,
            ]);
        }

        if ($senderId !== null && isset($existingSenderIds[$senderId])) {
            $updateSender->execute([
                ':id' => $senderId,
                ':name' => $row['name'],
                ':org_number' => $row['orgNumber'],
                ':domain' => $row['domain'],
                ':kind' => $row['kind'],
                ':notes' => $row['notes'],
                ':updated_at' => $timestamp,
            ]);
            if ($updateSender->rowCount() < 1) {
                throw new RuntimeException('Avsändaren som skulle uppdateras finns inte längre.');
            }
        } elseif ($senderId !== null) {
            throw new RuntimeException('Avsändaren som skulle uppdateras finns inte längre.');
        } else {
            $insertSender->execute([
                ':name' => $row['name'],
                ':org_number' => $row['orgNumber'],
                ':domain' => $row['domain'],
                ':kind' => $row['kind'],
                ':notes' => $row['notes'],
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ]);
            $senderId = (int) $pdo->lastInsertId();
        }

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
        }

        foreach ($row['mergedSourceSenderIds'] as $sourceSenderId) {
            $markSourceSenderMerged->execute([
                ':id' => $sourceSenderId,
                ':target_sender_id' => $senderId,
                ':updated_at' => date(DATE_ATOM),
            ]);
        }
    }

    $pdo->commit();

    json_response([
        'ok' => true,
        'senders' => $repository->listEditorRows(),
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => $e->getMessage()], 500);
}
