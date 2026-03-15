<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

use Docflow\Database\Connection;

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

try {
    $pdo = Connection::make();
    $senders = load_sender_editor_rows($pdo);

    json_response([
        'senders' => $senders,
    ]);
} catch (Throwable $e) {
    json_response([
        'senders' => [],
        'error' => $e->getMessage(),
    ], 500);
}
