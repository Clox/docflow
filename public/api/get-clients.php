<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $repository = client_repository_instance();
    if ($repository === null) {
        throw new RuntimeException('Client repository is unavailable.');
    }

    $rows = $repository->listAll();
    $clients = [];
    foreach ($rows as $row) {
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

    $text = json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($text)) {
        throw new RuntimeException('Could not encode clients payload.');
    }

    json_response(['text' => $text]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
