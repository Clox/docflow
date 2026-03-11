<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
    exit;
}

function sanitize_positive_int(mixed $value, int $fallback = 1): int
{
    if (is_int($value)) {
        return $value < 1 ? 1 : $value;
    }

    if (is_float($value)) {
        $intValue = (int) floor($value);
        return $intValue < 1 ? 1 : $intValue;
    }

    if (is_string($value) && trim($value) !== '') {
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        if ($parsed !== false) {
            return $parsed < 1 ? 1 : (int) $parsed;
        }
    }

    return $fallback;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    json_response(['error' => 'Invalid request body'], 400);
    exit;
}

$payload = json_decode($raw, true);
if (!is_array($payload) || !array_key_exists('categories', $payload) || !is_array($payload['categories'])) {
    json_response(['error' => 'Invalid JSON payload'], 400);
    exit;
}

$normalized = [];
foreach ($payload['categories'] as $category) {
    if (!is_array($category)) {
        continue;
    }

    $rulesInput = $category['rules'] ?? [];
    $rules = [];
    if (is_array($rulesInput)) {
        foreach ($rulesInput as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $rules[] = [
                'text' => is_string($rule['text'] ?? null) ? (string) $rule['text'] : '',
                'score' => sanitize_positive_int($rule['score'] ?? 1, 1),
            ];
        }
    }

    if (count($rules) === 0) {
        $rules[] = ['text' => '', 'score' => 1];
    }

    $normalized[] = [
        'name' => is_string($category['name'] ?? null) ? (string) $category['name'] : '',
        'path' => is_string($category['path'] ?? null) ? (string) $category['path'] : '',
        'minScore' => sanitize_positive_int($category['minScore'] ?? 1, 1),
        'rules' => $rules,
    ];
}

try {
    write_json_file(DATA_DIR . '/categories.json', $normalized);
    json_response(['ok' => true]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
