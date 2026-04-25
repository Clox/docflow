<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

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
if (!is_array($payload) || !isset($payload['text']) || !is_string($payload['text'])) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$text = $payload['text'];
$decoded = json_decode($text, true);
if (!is_array($decoded)) {
    json_response(['error' => 'Clients payload must be a JSON array'], 400);
    exit;
}

try {
    $repository = client_repository_instance();
    if ($repository === null) {
        throw new RuntimeException('Client repository is unavailable.');
    }

    $stored = $repository->replaceAll($decoded);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
    exit;
}

$clients = [];
foreach ($stored as $row) {
    if (!is_array($row)) {
        continue;
    }
    $clients[] = [
        'firstName' => is_string($row['first_name'] ?? null) ? (string) $row['first_name'] : '',
        'lastName' => is_string($row['last_name'] ?? null) ? (string) $row['last_name'] : '',
        'folderName' => is_string($row['folder_name'] ?? null) ? (string) $row['folder_name'] : '',
        'personalIdentityNumber' => is_string($row['personal_identity_number'] ?? null)
            ? (string) $row['personal_identity_number']
            : '',
        'preferredFirstNameIndex' => isset($row['preferred_first_name_index']) && is_numeric($row['preferred_first_name_index'])
            ? (int) $row['preferred_first_name_index']
            : null,
    ];
}

json_response([
    'ok' => true,
    'clients' => $clients,
]);
