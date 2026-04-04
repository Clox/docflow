<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

try {
    $config = load_config();

    $id = $_GET['id'] ?? '';
    if (!is_string($id) || !is_valid_job_id($id)) {
        http_response_code(404);
        exit;
    }

    $extractedPath = rtrim($config['jobsDirectory'], DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . $id
        . DIRECTORY_SEPARATOR . 'extracted.json';

    if (!is_file($extractedPath)) {
        http_response_code(404);
        exit;
    }

    $extracted = load_json_file($extractedPath);
    if (!is_array($extracted)) {
        json_response(['categories' => []]);
        exit;
    }

    $categories = $extracted['categoryMatches'] ?? [];
    if (!is_array($categories)) {
        $categories = [];
    }

    $labels = $extracted['labelMatches'] ?? [];
    if (!is_array($labels)) {
        $labels = [];
    }

    $systemLabels = $extracted['systemLabelMatches'] ?? [];
    if (!is_array($systemLabels)) {
        $systemLabels = [];
    }
    $labels = array_values(array_merge($systemLabels, $labels));
    $fieldValues = $extracted['extractionFields'] ?? [];
    if (!is_array($fieldValues)) {
        $fieldValues = [];
    }
    $fieldMeta = $extracted['extractionFieldMeta'] ?? [];
    if (!is_array($fieldMeta)) {
        $fieldMeta = [];
    }

    $fields = [];
    $allFieldKeys = array_values(array_unique(array_merge(array_keys($fieldValues), array_keys($fieldMeta))));
    foreach ($allFieldKeys as $fieldKey) {
        if (!is_string($fieldKey) || trim($fieldKey) === '') {
            continue;
        }

        $resolvedKey = trim($fieldKey);
        $values = normalize_auto_archiving_field_value_list($fieldValues[$resolvedKey] ?? null);
        if ($values === []) {
            continue;
        }

        $legacyField = is_array($fieldValues[$resolvedKey] ?? null) ? $fieldValues[$resolvedKey] : [];
        $meta = is_array($fieldMeta[$resolvedKey] ?? null) ? $fieldMeta[$resolvedKey] : [];
        $matches = is_array($meta['matches'] ?? null) ? $meta['matches'] : [];
        $firstMatch = is_array($matches[0] ?? null) ? $matches[0] : [];

        $candidateRows = [];
        if ($matches !== []) {
            foreach ($matches as $match) {
                if (!is_array($match)) {
                    continue;
                }

                $candidateValue = normalize_auto_archiving_field_value_item($match['value'] ?? null);
                if ($candidateValue === null) {
                    continue;
                }

                $matchType = is_string($match['matchType'] ?? null) ? trim((string) $match['matchType']) : '';
                if ($matchType === '') {
                    $source = is_string($match['source'] ?? null) ? trim((string) $match['source']) : '';
                    if ($source === 'pattern') {
                        $matchType = 'pattern';
                    } elseif ($source === 'document_date_heuristic') {
                        $matchType = 'document_date_heuristic';
                    }
                }
                $confidence = isset($match['confidence']) ? (float) $match['confidence'] : null;
                if ($matchType === 'pattern') {
                    $confidence = 1.0;
                } elseif ($matchType === 'document_date_heuristic' && ($confidence === null || $confidence <= 0.0)) {
                    $confidence = null;
                }
                $score = is_numeric($match['score'] ?? null) ? (float) $match['score'] : null;
                if ($matchType === 'document_date_heuristic' && ($score === null || $score <= 0.0)) {
                    $score = null;
                }

                $candidateRows[] = [
                    'value' => $candidateValue,
                    'source' => is_string($match['source'] ?? null) ? (string) $match['source'] : '',
                    'searchTerm' => is_string($match['searchTerm'] ?? null) ? trim((string) $match['searchTerm']) : '',
                    'raw' => is_string($match['raw'] ?? null) ? (string) $match['raw'] : '',
                    'confidence' => $confidence,
                    'score' => $score,
                    'lineIndex' => is_int($match['lineIndex'] ?? null) ? (int) $match['lineIndex'] : PHP_INT_MAX,
                    'start' => is_int($match['start'] ?? null) ? (int) $match['start'] : PHP_INT_MAX,
                    'matchType' => $matchType !== '' ? $matchType : null,
                ];
            }
        }

        if ($candidateRows === []) {
            foreach ($values as $index => $candidateValue) {
                $fallbackConfidence = $index === 0
                    ? (isset($firstMatch['confidence']) ? (float) $firstMatch['confidence'] : (isset($legacyField['confidence']) ? (float) $legacyField['confidence'] : null))
                    : null;
                $fallbackMatchType = $index === 0 && is_string($firstMatch['matchType'] ?? null)
                    ? trim((string) $firstMatch['matchType'])
                    : '';
                if ($fallbackMatchType === '') {
                    $fallbackSource = $index === 0 && is_string($firstMatch['source'] ?? null)
                        ? trim((string) $firstMatch['source'])
                        : '';
                    if ($fallbackSource === 'pattern') {
                        $fallbackMatchType = 'pattern';
                    } elseif ($fallbackSource === 'document_date_heuristic') {
                        $fallbackMatchType = 'document_date_heuristic';
                    }
                }
                if ($fallbackMatchType === 'pattern') {
                    $fallbackConfidence = 1.0;
                } elseif ($fallbackMatchType === 'document_date_heuristic' && ($fallbackConfidence === null || $fallbackConfidence <= 0.0)) {
                    $fallbackConfidence = null;
                }
                $fallbackScore = $index === 0 && is_numeric($firstMatch['score'] ?? null)
                    ? (float) $firstMatch['score']
                    : null;
                if ($fallbackMatchType === 'document_date_heuristic' && ($fallbackScore === null || $fallbackScore <= 0.0)) {
                    $fallbackScore = null;
                }

                $candidateRows[] = [
                    'value' => $candidateValue,
                    'source' => $index === 0 && is_string($firstMatch['source'] ?? null)
                        ? (string) $firstMatch['source']
                        : ($index === 0 && is_string($legacyField['source'] ?? null) ? (string) $legacyField['source'] : ''),
                    'searchTerm' => $index === 0 && is_string($firstMatch['searchTerm'] ?? null)
                        ? trim((string) $firstMatch['searchTerm'])
                        : '',
                    'raw' => $index === 0 && is_string($firstMatch['raw'] ?? null)
                        ? (string) $firstMatch['raw']
                        : ($index === 0 && is_string($legacyField['raw'] ?? null) ? (string) $legacyField['raw'] : ''),
                    'confidence' => $fallbackConfidence,
                    'score' => $fallbackScore,
                    'lineIndex' => $index === 0 && is_int($firstMatch['lineIndex'] ?? null) ? (int) $firstMatch['lineIndex'] : PHP_INT_MAX,
                    'start' => $index === 0 && is_int($firstMatch['start'] ?? null) ? (int) $firstMatch['start'] : PHP_INT_MAX,
                    'matchType' => $fallbackMatchType !== '' ? $fallbackMatchType : null,
                ];
            }
        }

        $compareCandidateRows = static function (array $left, array $right): int {
            $leftConfidence = isset($left['confidence']) && is_numeric($left['confidence']) ? (float) $left['confidence'] : -1.0;
            $rightConfidence = isset($right['confidence']) && is_numeric($right['confidence']) ? (float) $right['confidence'] : -1.0;
            $confidenceCompare = $rightConfidence <=> $leftConfidence;
            if ($confidenceCompare !== 0) {
                return $confidenceCompare;
            }

            $lineCompare = ((int) ($left['lineIndex'] ?? PHP_INT_MAX)) <=> ((int) ($right['lineIndex'] ?? PHP_INT_MAX));
            if ($lineCompare !== 0) {
                return $lineCompare;
            }

            return ((int) ($left['start'] ?? PHP_INT_MAX)) <=> ((int) ($right['start'] ?? PHP_INT_MAX));
        };

        $dedupedRowsByValue = [];
        foreach ($candidateRows as $row) {
            $normalizedValueKey = json_encode($row['value'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($normalizedValueKey)) {
                $normalizedValueKey = is_scalar($row['value'] ?? null) ? (string) $row['value'] : '';
            }

            if (!isset($dedupedRowsByValue[$normalizedValueKey]) || $compareCandidateRows($row, $dedupedRowsByValue[$normalizedValueKey]) < 0) {
                $dedupedRowsByValue[$normalizedValueKey] = $row;
            }
        }

        $candidateRows = array_values($dedupedRowsByValue);
        usort($candidateRows, $compareCandidateRows);

        $fields[$resolvedKey] = [
            'key' => is_string($meta['key'] ?? null) && trim((string) $meta['key']) !== ''
                ? trim((string) $meta['key'])
                : (is_string($legacyField['key'] ?? null) && trim((string) $legacyField['key']) !== '' ? trim((string) $legacyField['key']) : $resolvedKey),
            'name' => is_string($meta['name'] ?? null) && trim((string) $meta['name']) !== ''
                ? trim((string) $meta['name'])
                : (is_string($legacyField['name'] ?? null) && trim((string) $legacyField['name']) !== '' ? trim((string) $legacyField['name']) : $resolvedKey),
            'value' => $values[0],
            'values' => $values,
            'matches' => $candidateRows,
            'source' => is_string($firstMatch['source'] ?? null)
                ? (string) $firstMatch['source']
                : (is_string($legacyField['source'] ?? null) ? (string) $legacyField['source'] : ''),
            'raw' => is_string($firstMatch['raw'] ?? null)
                ? (string) $firstMatch['raw']
                : (is_string($legacyField['raw'] ?? null) ? (string) $legacyField['raw'] : ''),
            'confidence' => isset($firstMatch['confidence'])
                ? (float) $firstMatch['confidence']
                : (isset($legacyField['confidence']) ? (float) $legacyField['confidence'] : null),
        ];
    }

    json_response([
        'categories' => $categories,
        'labels' => $labels,
        'fields' => $fields,
    ]);
} catch (Throwable $e) {
    json_response([
        'categories' => [],
        'labels' => [],
        'fields' => [],
    ], 500);
}
