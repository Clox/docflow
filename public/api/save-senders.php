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
$seenOrganizationNumbers = [];
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
    $domainRaw = is_string($row['domain'] ?? null) ? trim((string) $row['domain']) : '';
    $kindRaw = is_string($row['kind'] ?? null) ? trim((string) $row['kind']) : '';
    $notesRaw = is_string($row['notes'] ?? null) ? trim((string) $row['notes']) : '';
    $organizationRows = array_values(array_filter(
        is_array($row['organizationNumbers'] ?? null) ? $row['organizationNumbers'] : [],
        static fn (mixed $organization): bool => is_array($organization)
    ));
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
        && $domainRaw === ''
        && $kindRaw === ''
        && $notesRaw === ''
        && count($organizationRows) === 0
        && count($paymentRows) === 0;
    if ($isEffectivelyEmpty) {
        continue;
    }

    if ($name === '') {
        json_response(['error' => 'Varje avsändare måste ha ett namn'], 400);
        exit;
    }

    $organizationNumbers = [];
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
        $organizationNameRaw = is_string($organizationRow['organizationName'] ?? null)
            ? trim((string) $organizationRow['organizationName'])
            : '';

        if ($organizationNumberRaw === '' && $organizationNameRaw === '') {
            continue;
        }
        if ($organizationNumberRaw === '') {
            json_response(['error' => 'Organisationsnummer kräver ett nummer'], 400);
            exit;
        }

        $organizationNumber = IdentifierNormalizer::normalizeOrgNumber($organizationNumberRaw);
        if ($organizationNumber === null) {
            json_response(['error' => 'Ogiltigt organisationsnummer för avsändare: ' . $name], 400);
            exit;
        }

        if (isset($seenOrganizationNumbers[$organizationNumber])) {
            json_response(['error' => 'Organisationsnummer måste vara unikt'], 400);
            exit;
        }
        $seenOrganizationNumbers[$organizationNumber] = true;

        $organizationNumbers[] = [
            'id' => $organizationId,
            'organizationNumber' => $organizationNumber,
            'organizationName' => $organizationNameRaw !== '' ? $organizationNameRaw : null,
        ];
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
        'domain' => $domainRaw !== '' ? strtolower($domainRaw) : null,
        'kind' => $kindRaw !== '' ? $kindRaw : null,
        'notes' => $notesRaw !== '' ? $notesRaw : null,
        'organizationNumbers' => $organizationNumbers,
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

    $existingSenderRows = $pdo->query('SELECT id FROM senders')->fetchAll(PDO::FETCH_COLUMN);
    $existingSenderIds = [];
    foreach ($existingSenderRows as $value) {
        $senderId = (int) $value;
        if ($senderId > 0) {
            $existingSenderIds[$senderId] = true;
        }
    }

    foreach ($claimedMergedSourceIds as $sourceId => $_true) {
        if (!isset($existingSenderIds[$sourceId])) {
            throw new RuntimeException('En avsändare som skulle slås samman finns inte längre.');
        }
    }

    $existingPaymentRows = $pdo->query(
        'SELECT id, sender_id, type, number
        FROM sender_payment_numbers'
    )->fetchAll(PDO::FETCH_ASSOC);
    $existingPaymentRowsById = [];
    $existingPaymentsByKey = [];
    foreach ($existingPaymentRows as $paymentRow) {
        if (!is_array($paymentRow)) {
            continue;
        }
        $paymentId = isset($paymentRow['id']) ? (int) $paymentRow['id'] : 0;
        if ($paymentId < 1) {
            continue;
        }
        $rowData = [
            'senderId' => isset($paymentRow['sender_id']) ? (int) $paymentRow['sender_id'] : 0,
            'type' => is_string($paymentRow['type'] ?? null) ? trim(strtolower((string) $paymentRow['type'])) : 'bankgiro',
            'number' => is_string($paymentRow['number'] ?? null) ? trim((string) $paymentRow['number']) : '',
        ];
        $existingPaymentRowsById[$paymentId] = $rowData;
        $paymentKey = $rowData['type'] . ':' . $rowData['number'];
        $existingPaymentsByKey[$paymentKey] = [
            'id' => $paymentId,
            ...$rowData,
        ];
    }

    $existingOrganizationRows = $pdo->query(
        'SELECT id, organization_number, organization_name, sender_id
        FROM sender_organization_numbers'
    )->fetchAll(PDO::FETCH_ASSOC);
    $existingOrganizationRowsById = [];
    $existingOrganizationsByNumber = [];
    foreach ($existingOrganizationRows as $organizationRow) {
        if (!is_array($organizationRow)) {
            continue;
        }
        $organizationId = isset($organizationRow['id']) ? (int) $organizationRow['id'] : 0;
        if ($organizationId < 1) {
            continue;
        }
        $organizationNumber = is_string($organizationRow['organization_number'] ?? null)
            ? trim((string) $organizationRow['organization_number'])
            : '';
        if ($organizationNumber === '') {
            continue;
        }
        $rowData = [
            'senderId' => isset($organizationRow['sender_id']) ? (int) $organizationRow['sender_id'] : 0,
            'organizationNumber' => $organizationNumber,
            'organizationName' => is_string($organizationRow['organization_name'] ?? null)
                ? trim((string) $organizationRow['organization_name'])
                : '',
        ];
        $existingOrganizationRowsById[$organizationId] = $rowData;
        $existingOrganizationsByNumber[$organizationNumber] = [
            'id' => $organizationId,
            ...$rowData,
        ];
    }

    $submittedPaymentIds = [];
    $submittedOrganizationIds = [];
    foreach ($normalized as $row) {
        foreach ($row['paymentNumbers'] as $paymentRow) {
            if ($paymentRow['id'] !== null) {
                $submittedPaymentIds[(int) $paymentRow['id']] = true;
            }
        }
        foreach ($row['organizationNumbers'] as $organizationRow) {
            if ($organizationRow['id'] !== null) {
                $submittedOrganizationIds[(int) $organizationRow['id']] = true;
            }
        }
    }

    $detachPayment = $pdo->prepare(
        'UPDATE sender_payment_numbers
        SET sender_id = NULL, updated_at = :updated_at
        WHERE id = :id'
    );
    $detachOrganization = $pdo->prepare(
        'UPDATE sender_organization_numbers
        SET sender_id = NULL, updated_at = :updated_at
        WHERE id = :id'
    );
    $deleteRemovedSender = $pdo->prepare('DELETE FROM senders WHERE id = :id');
    $insertSender = $pdo->prepare(
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
            1,
            :matching_updated_at,
            :created_at,
            :updated_at
        )'
    );
    $updateSender = $pdo->prepare(
        'UPDATE senders
        SET
            name = :name,
            domain = :domain,
            kind = :kind,
            notes = :notes,
            updated_at = :updated_at
        WHERE id = :id'
    );
    $touchSenderMatching = $pdo->prepare(
        'UPDATE senders
        SET matching_updated_at = :matching_updated_at,
            updated_at = :updated_at
        WHERE id = :id'
    );
    $insertOrganization = $pdo->prepare(
        'INSERT INTO sender_organization_numbers (
            organization_number,
            organization_name,
            sender_id,
            created_at,
            updated_at
        ) VALUES (
            :organization_number,
            :organization_name,
            :sender_id,
            :created_at,
            :updated_at
        )'
    );
    $updateOrganization = $pdo->prepare(
        'UPDATE sender_organization_numbers
        SET
            organization_number = :organization_number,
            organization_name = :organization_name,
            sender_id = :sender_id,
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
            payee_name = CASE
                WHEN type <> :type OR number <> :number THEN NULL
                ELSE payee_name
            END,
            payee_lookup_status = CASE
                WHEN type <> :type OR number <> :number THEN NULL
                ELSE payee_lookup_status
            END,
            updated_at = :updated_at
        WHERE id = :id'
    );

    $shouldTouchExistingSender = static function (int $senderId) use ($submittedSenderIds, $claimedMergedSourceIds): bool {
        return $senderId > 0
            && isset($submittedSenderIds[$senderId])
            && !isset($claimedMergedSourceIds[$senderId]);
    };

    $touchSender = static function (int $senderId, ?string $timestamp = null) use ($touchSenderMatching): void {
        if ($senderId < 1) {
            return;
        }
        $touchTimestamp = is_string($timestamp) && trim($timestamp) !== '' ? $timestamp : date(DATE_ATOM);
        $touchSenderMatching->execute([
            ':id' => $senderId,
            ':matching_updated_at' => $touchTimestamp,
            ':updated_at' => $touchTimestamp,
        ]);
    };

    foreach ($existingPaymentRowsById as $paymentId => $paymentRow) {
        if (isset($submittedPaymentIds[$paymentId])) {
            continue;
        }
        $senderId = (int) ($paymentRow['senderId'] ?? 0);
        if ($senderId < 1) {
            continue;
        }
        if ($shouldTouchExistingSender($senderId)) {
            $touchSender($senderId);
        }
        $detachPayment->execute([
            ':id' => $paymentId,
            ':updated_at' => date(DATE_ATOM),
        ]);
        $existingPaymentRowsById[$paymentId]['senderId'] = 0;
        $paymentKey = (string) ($paymentRow['type'] ?? '') . ':' . (string) ($paymentRow['number'] ?? '');
        if (isset($existingPaymentsByKey[$paymentKey])) {
            $existingPaymentsByKey[$paymentKey]['senderId'] = 0;
        }
    }

    foreach ($existingOrganizationRowsById as $organizationId => $organizationRow) {
        if (isset($submittedOrganizationIds[$organizationId])) {
            continue;
        }
        $senderId = (int) ($organizationRow['senderId'] ?? 0);
        if ($senderId > 0) {
            if ($shouldTouchExistingSender($senderId)) {
                $touchSender($senderId);
            }
            $detachOrganization->execute([
                ':id' => $organizationId,
                ':updated_at' => date(DATE_ATOM),
            ]);
            $existingOrganizationRowsById[$organizationId]['senderId'] = 0;
            $existingOrganizationsByNumber[$organizationRow['organizationNumber']]['senderId'] = 0;
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

        if ($senderId !== null && isset($existingSenderIds[$senderId])) {
            $updateSender->execute([
                ':id' => $senderId,
                ':name' => $row['name'],
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
                ':domain' => $row['domain'],
                ':kind' => $row['kind'],
                ':notes' => $row['notes'],
                ':matching_updated_at' => $timestamp,
                ':created_at' => $timestamp,
                ':updated_at' => $timestamp,
            ]);
            $senderId = (int) $pdo->lastInsertId();
        }

        foreach ($row['organizationNumbers'] as $organizationRow) {
            $organizationId = $organizationRow['id'];
            $organizationNumber = $organizationRow['organizationNumber'];
            $organizationName = $organizationRow['organizationName'];
            $organizationTimestamp = date(DATE_ATOM);
            $previousSenderId = 0;
            $previousNumber = '';

            if ($organizationId !== null && isset($existingOrganizationRowsById[$organizationId])) {
                $previousSenderId = (int) ($existingOrganizationRowsById[$organizationId]['senderId'] ?? 0);
                $previousNumber = (string) ($existingOrganizationRowsById[$organizationId]['organizationNumber'] ?? '');
                $updateOrganization->execute([
                    ':id' => $organizationId,
                    ':organization_number' => $organizationNumber,
                    ':organization_name' => $organizationName,
                    ':sender_id' => $senderId,
                    ':updated_at' => $organizationTimestamp,
                ]);
            } elseif ($organizationId !== null) {
                throw new RuntimeException('Organisationsnumret som skulle uppdateras finns inte längre.');
            } else {
                $existingByNumber = $existingOrganizationsByNumber[$organizationNumber] ?? null;
                if (is_array($existingByNumber)) {
                    $existingId = (int) ($existingByNumber['id'] ?? 0);
                    $existingSenderId = (int) ($existingByNumber['senderId'] ?? 0);
                    $canClaimExistingRow = $existingId > 0 && ($existingSenderId < 1 || !isset($submittedSenderIds[$existingSenderId]) || isset($claimedMergedSourceIds[$existingSenderId]));
                    if (!$canClaimExistingRow) {
                        throw new RuntimeException('Organisationsnummer måste vara unikt');
                    }
                    $organizationId = $existingId;
                    $previousSenderId = $existingSenderId;
                    $previousNumber = (string) ($existingByNumber['organizationNumber'] ?? '');
                    $updateOrganization->execute([
                        ':id' => $organizationId,
                        ':organization_number' => $organizationNumber,
                        ':organization_name' => $organizationName,
                        ':sender_id' => $senderId,
                        ':updated_at' => $organizationTimestamp,
                    ]);
                } else {
                    $insertOrganization->execute([
                        ':organization_number' => $organizationNumber,
                        ':organization_name' => $organizationName,
                        ':sender_id' => $senderId,
                        ':created_at' => $organizationTimestamp,
                        ':updated_at' => $organizationTimestamp,
                    ]);
                    $organizationId = (int) $pdo->lastInsertId();
                }
            }

            if ($organizationId !== null) {
                $existingOrganizationRowsById[$organizationId] = [
                    'senderId' => $senderId,
                    'organizationNumber' => $organizationNumber,
                    'organizationName' => $organizationName ?? '',
                ];
                $existingOrganizationsByNumber[$organizationNumber] = [
                    'id' => $organizationId,
                    'senderId' => $senderId,
                    'organizationNumber' => $organizationNumber,
                    'organizationName' => $organizationName ?? '',
                ];
            }

            $matchChanged = $previousSenderId !== $senderId || ($previousNumber !== '' && $previousNumber !== $organizationNumber);
            if ($organizationId !== null && ($previousSenderId < 1 && $previousNumber === '')) {
                $matchChanged = true;
            }
            if ($matchChanged) {
                $touchSender($senderId, $organizationTimestamp);
                if ($previousSenderId > 0 && $previousSenderId !== $senderId && $shouldTouchExistingSender($previousSenderId)) {
                    $touchSender($previousSenderId, $organizationTimestamp);
                }
            }
        }

        foreach ($row['paymentNumbers'] as $paymentRow) {
            $paymentId = $paymentRow['id'];
            $paymentTimestamp = date(DATE_ATOM);
            $previousSenderId = 0;
            $previousType = '';
            $previousNumber = '';

            if ($paymentId !== null && isset($existingPaymentRowsById[$paymentId])) {
                $previousSenderId = (int) ($existingPaymentRowsById[$paymentId]['senderId'] ?? 0);
                $previousType = (string) ($existingPaymentRowsById[$paymentId]['type'] ?? '');
                $previousNumber = (string) ($existingPaymentRowsById[$paymentId]['number'] ?? '');
                $updatePayment->execute([
                    ':id' => $paymentId,
                    ':sender_id' => $senderId,
                    ':type' => $paymentRow['type'],
                    ':number' => $paymentRow['number'],
                    ':source' => 'docflow_editor',
                    ':updated_at' => $paymentTimestamp,
                ]);
            } elseif ($paymentId !== null) {
                throw new RuntimeException('Betalnumret som skulle uppdateras finns inte längre.');
            } else {
                $paymentKey = $paymentRow['type'] . ':' . $paymentRow['number'];
                $existingByKey = $existingPaymentsByKey[$paymentKey] ?? null;
                if (is_array($existingByKey)) {
                    $existingId = (int) ($existingByKey['id'] ?? 0);
                    $existingSenderId = (int) ($existingByKey['senderId'] ?? 0);
                    $canClaimExistingRow = $existingId > 0 && ($existingSenderId < 1 || !isset($submittedSenderIds[$existingSenderId]) || isset($claimedMergedSourceIds[$existingSenderId]));
                    if (!$canClaimExistingRow) {
                        throw new RuntimeException('Betalnummer måste vara unikt');
                    }
                    $paymentId = $existingId;
                    $previousSenderId = $existingSenderId;
                    $previousType = (string) ($existingByKey['type'] ?? '');
                    $previousNumber = (string) ($existingByKey['number'] ?? '');
                    $updatePayment->execute([
                        ':id' => $paymentId,
                        ':sender_id' => $senderId,
                        ':type' => $paymentRow['type'],
                        ':number' => $paymentRow['number'],
                        ':source' => 'docflow_editor',
                        ':updated_at' => $paymentTimestamp,
                    ]);
                } else {
                    $insertPayment->execute([
                        ':sender_id' => $senderId,
                        ':type' => $paymentRow['type'],
                        ':number' => $paymentRow['number'],
                        ':original_number' => null,
                        ':source' => 'docflow_editor',
                        ':created_at' => $paymentTimestamp,
                        ':updated_at' => $paymentTimestamp,
                    ]);
                    $paymentId = (int) $pdo->lastInsertId();
                }
            }

            if ($paymentId !== null) {
                if ($previousType !== '' && $previousNumber !== '') {
                    unset($existingPaymentsByKey[$previousType . ':' . $previousNumber]);
                }
                $existingPaymentRowsById[$paymentId] = [
                    'senderId' => $senderId,
                    'type' => $paymentRow['type'],
                    'number' => $paymentRow['number'],
                ];
                $existingPaymentsByKey[$paymentRow['type'] . ':' . $paymentRow['number']] = [
                    'id' => $paymentId,
                    'senderId' => $senderId,
                    'type' => $paymentRow['type'],
                    'number' => $paymentRow['number'],
                ];
            }

            $matchChanged = $previousSenderId !== $senderId
                || ($previousType !== '' && $previousType !== $paymentRow['type'])
                || ($previousNumber !== '' && $previousNumber !== $paymentRow['number']);
            if ($paymentId !== null && ($previousSenderId < 1 && $previousType === '' && $previousNumber === '')) {
                $matchChanged = true;
            }
            if ($matchChanged) {
                $touchSender($senderId, $paymentTimestamp);
                if ($previousSenderId > 0 && $previousSenderId !== $senderId && $shouldTouchExistingSender($previousSenderId)) {
                    $touchSender($previousSenderId, $paymentTimestamp);
                }
            }
        }

        foreach ($row['mergedSourceSenderIds'] as $sourceSenderId) {
            if ($sourceSenderId < 1 || $sourceSenderId === $senderId) {
                continue;
            }
            $deleteRemovedSender->execute([':id' => $sourceSenderId]);
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
