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
if (!is_array($payload)) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$identifiers = array_values(array_filter(
    is_array($payload['identifiers'] ?? null) ? $payload['identifiers'] : [],
    static fn (mixed $row): bool => is_array($row)
));
if ($identifiers === []) {
    json_response(['error' => 'Välj minst en okopplad uppgift.'], 400);
    exit;
}

$createSender = ($payload['createSender'] ?? false) === true;
$forceIncompleteLookup = ($payload['forceIncompleteLookup'] ?? false) === true;
$senderId = isset($payload['senderId']) && is_numeric($payload['senderId'])
    ? (int) $payload['senderId']
    : 0;
if (!$createSender && $senderId < 1) {
    json_response(['error' => 'Välj en avsändare att koppla till.'], 400);
    exit;
}

try {
    $pdo = Connection::make();
    $repository = new SenderRepository($pdo);
    $pdo->beginTransaction();
    $repository->canonicalizeSenderIdentifierRows();

    $selectOrganizationById = $pdo->prepare(
        'SELECT id, sender_id, organization_name
        FROM sender_organization_numbers
        WHERE id = :id
        LIMIT 1'
    );
    $selectOrganizationByNumber = $pdo->prepare(
        'SELECT id, sender_id, organization_name
        FROM sender_organization_numbers
        WHERE organization_number = :organization_number
        ORDER BY
            CASE WHEN sender_id IS NULL THEN 0 ELSE 1 END ASC,
            updated_at DESC,
            id ASC
        LIMIT 1'
    );
    $selectPaymentById = $pdo->prepare(
        'SELECT id, sender_id, payee_name
        FROM sender_payment_numbers
        WHERE id = :id
        LIMIT 1'
    );
    $selectPaymentByNumber = $pdo->prepare(
        'SELECT id, sender_id, payee_name
        FROM sender_payment_numbers
        WHERE type = :type
          AND number = :number
        ORDER BY
            CASE WHEN sender_id IS NULL THEN 0 ELSE 1 END ASC,
            updated_at DESC,
            id ASC
        LIMIT 1'
    );

    $resolveOrganization = static function (array $identifier) use (
        $selectOrganizationById,
        $selectOrganizationByNumber
    ): ?array {
        $identifierId = isset($identifier['identifierId']) && is_numeric($identifier['identifierId'])
            ? (int) $identifier['identifierId']
            : (isset($identifier['id']) && is_numeric($identifier['id']) ? (int) $identifier['id'] : 0);
        if ($identifierId > 0) {
            $selectOrganizationById->execute([':id' => $identifierId]);
            $row = $selectOrganizationById->fetch(PDO::FETCH_ASSOC);
            if (is_array($row)) {
                return $row;
            }
        }

        $number = is_string($identifier['normalizedNumber'] ?? null)
            ? (string) $identifier['normalizedNumber']
            : (is_string($identifier['itemValue'] ?? null) ? (string) $identifier['itemValue'] : '');
        $normalizedNumber = IdentifierNormalizer::normalizeOrgNumber($number);
        if ($normalizedNumber === null) {
            return null;
        }
        $selectOrganizationByNumber->execute([':organization_number' => $normalizedNumber]);
        $row = $selectOrganizationByNumber->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    };
    $resolvePayment = static function (array $identifier) use (
        $selectPaymentById,
        $selectPaymentByNumber
    ): ?array {
        $type = is_string($identifier['paymentType'] ?? null)
            && trim(strtolower((string) $identifier['paymentType'])) === 'plusgiro'
            ? 'plusgiro'
            : 'bankgiro';
        $identifierId = isset($identifier['identifierId']) && is_numeric($identifier['identifierId'])
            ? (int) $identifier['identifierId']
            : (isset($identifier['id']) && is_numeric($identifier['id']) ? (int) $identifier['id'] : 0);
        if ($identifierId > 0) {
            $selectPaymentById->execute([':id' => $identifierId]);
            $row = $selectPaymentById->fetch(PDO::FETCH_ASSOC);
            if (is_array($row)) {
                return ['type' => $type, 'row' => $row];
            }
        }

        $number = is_string($identifier['normalizedNumber'] ?? null)
            ? (string) $identifier['normalizedNumber']
            : (is_string($identifier['itemValue'] ?? null) ? (string) $identifier['itemValue'] : '');
        $normalizedNumber = $type === 'plusgiro'
            ? IdentifierNormalizer::normalizePlusgiro($number)
            : IdentifierNormalizer::normalizeBankgiro($number);
        if ($normalizedNumber === null) {
            return null;
        }
        $selectPaymentByNumber->execute([
            ':type' => $type,
            ':number' => $normalizedNumber,
        ]);
        $row = $selectPaymentByNumber->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? ['type' => $type, 'row' => $row] : null;
    };

    if ($createSender) {
        $organizationNames = [];
        $paymentNames = [];
        foreach ($identifiers as $identifier) {
            $kind = is_string($identifier['kind'] ?? null) ? trim(strtolower((string) $identifier['kind'])) : '';

            if ($kind === 'organization' || $kind === 'organization_number') {
                $organizationRow = $resolveOrganization($identifier);
                $name = is_array($organizationRow) && is_string($organizationRow['organization_name'] ?? null)
                    ? trim((string) $organizationRow['organization_name'])
                    : '';
                if ($name !== '') {
                    $organizationNames[] = $name;
                }
                continue;
            }

            if ($kind === 'payment') {
                $resolvedPayment = $resolvePayment($identifier);
                $paymentRow = is_array($resolvedPayment) && is_array($resolvedPayment['row'] ?? null)
                    ? $resolvedPayment['row']
                    : null;
                $name = is_array($paymentRow) && is_string($paymentRow['payee_name'] ?? null)
                    ? trim((string) $paymentRow['payee_name'])
                    : '';
                if ($name !== '') {
                    $paymentNames[] = $name;
                }
            }
        }

        $senderId = $repository->createSender($organizationNames[0] ?? $paymentNames[0] ?? '(Namnlös)');
    } elseif ($repository->findById($senderId) === null) {
        throw new RuntimeException('Avsändaren finns inte längre.');
    }

    $linkOrganization = $pdo->prepare(
        'UPDATE sender_organization_numbers
        SET sender_id = :sender_id,
            updated_at = :updated_at
        WHERE id = :id'
    );
    $linkPayment = $pdo->prepare(
        'UPDATE sender_payment_numbers
        SET sender_id = :sender_id,
            updated_at = :updated_at
        WHERE id = :id'
    );

    $linkedCount = 0;
    foreach ($identifiers as $identifier) {
        $kind = is_string($identifier['kind'] ?? null) ? trim(strtolower((string) $identifier['kind'])) : '';
        $timestamp = date(DATE_ATOM);

        if ($kind === 'organization' || $kind === 'organization_number') {
            $organizationRow = $resolveOrganization($identifier);
            $organizationId = is_array($organizationRow) ? (int) ($organizationRow['id'] ?? 0) : 0;
            if ($organizationId < 1) {
                continue;
            }
            if (isset($organizationRow['sender_id']) && (int) $organizationRow['sender_id'] > 0) {
                throw new RuntimeException('En av uppgifterna är redan kopplad till en avsändare.');
            }
            $organizationName = is_string($organizationRow['organization_name'] ?? null)
                ? trim((string) $organizationRow['organization_name'])
                : '';
            if ($organizationName === '' && !$forceIncompleteLookup) {
                throw new RuntimeException('Väntar på uppslag av uppgifter.');
            }
            $linkOrganization->execute([
                ':id' => $organizationId,
                ':sender_id' => $senderId,
                ':updated_at' => $timestamp,
            ]);
            $linkedCount++;
            continue;
        }

        if ($kind === 'payment') {
            $resolvedPayment = $resolvePayment($identifier);
            $paymentRow = is_array($resolvedPayment) && is_array($resolvedPayment['row'] ?? null)
                ? $resolvedPayment['row']
                : null;
            $paymentId = is_array($paymentRow) ? (int) ($paymentRow['id'] ?? 0) : 0;
            if ($paymentId < 1) {
                continue;
            }
            if (isset($paymentRow['sender_id']) && (int) $paymentRow['sender_id'] > 0) {
                throw new RuntimeException('En av uppgifterna är redan kopplad till en avsändare.');
            }
            $payeeName = is_string($paymentRow['payee_name'] ?? null) ? trim((string) $paymentRow['payee_name']) : '';
            if ($payeeName === '' && !$forceIncompleteLookup) {
                throw new RuntimeException('Väntar på uppslag av uppgifter.');
            }
            $linkPayment->execute([
                ':id' => $paymentId,
                ':sender_id' => $senderId,
                ':updated_at' => $timestamp,
            ]);
            $linkedCount++;
        }
    }

    if ($linkedCount < 1) {
        throw new RuntimeException('Inga valda uppgifter kunde kopplas.');
    }

    $pdo->commit();

    json_response([
        'ok' => true,
        'senderId' => $senderId,
        'senders' => $repository->listEditorRows(),
        'unlinkedIdentifiers' => $repository->listUnlinkedIdentifierRows(),
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => $e->getMessage()], 500);
}
