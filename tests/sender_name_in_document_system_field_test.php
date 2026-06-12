<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_sender_name_in_document(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function sender_name_test_geometry(string $text, int $wordIndex, array $bbox, int $pageNumber = 1): array
{
    return [
        'text' => $text,
        'segments' => [[
            'text' => $text,
            'start' => 0,
            'end' => strlen($text),
            'wordIndex' => $wordIndex,
            'bbox' => $bbox,
        ]],
        'pageNumber' => $pageNumber,
        'pageWidth' => 600.0,
        'pageHeight' => 800.0,
    ];
}

$definitions = system_extraction_field_definitions();
assert_sender_name_in_document(
    ($definitions['sender_name_in_document']['name'] ?? null) === 'Avsändarnamn i dokument',
    'The system field must use the exact requested display name.'
);
assert_sender_name_in_document(
    ($definitions['sender_name_in_document']['extractor'] ?? null) === 'sender_name_in_document',
    'The system field must have its own extractor.'
);

$settings = normalize_multiline_text_block_settings([
    'maxLines' => 3,
    'maxLineDistanceLineHeights' => 2.0,
    'maxTextSizeRatio' => 1.5,
    'minXOverlapRatio' => 0.3,
    'maxHorizontalOffsetLineHeights' => 3.0,
]);
$lines = [
    'Karlstads kommun',
    'Munkfors',
    'kommun',
];
$geometries = [
    sender_name_test_geometry('Karlstads kommun', 0, ['x0' => 40.0, 'y0' => 20.0, 'x1' => 160.0, 'y1' => 32.0]),
    sender_name_test_geometry('Munkfors', 1, ['x0' => 40.0, 'y0' => 60.0, 'x1' => 112.0, 'y1' => 72.0]),
    sender_name_test_geometry('kommun', 2, ['x0' => 42.0, 'y0' => 76.0, 'x1' => 104.0, 'y1' => 88.0]),
];
$entries = [
    [
        'senderId' => 10,
        'senderUnitId' => null,
        'name' => 'Karlstads kommun',
        'normalizedName' => 'karlstads kommun',
        'type' => 'sender_name',
    ],
    [
        'senderId' => 20,
        'senderUnitId' => 21,
        'name' => 'Munkfors kommun',
        'normalizedName' => 'munkfors kommun',
        'type' => 'sender_unit',
    ],
];

$result = extract_sender_name_in_document_field_result($lines, $geometries, $settings, $entries);
$matches = is_array($result['matches'] ?? null) ? $result['matches'] : [];
assert_sender_name_in_document(count($matches) === 2, 'Single-line and multiline exact matches should both be returned.');

$singleLine = null;
$multiLine = null;
foreach ($matches as $match) {
    if (($match['blockType'] ?? null) === 'single_line') {
        $singleLine = $match;
    }
    if (($match['blockType'] ?? null) === 'multiline') {
        $multiLine = $match;
    }
}
assert_sender_name_in_document(is_array($singleLine), 'The sender name should produce a single-line candidate.');
assert_sender_name_in_document(($singleLine['senderId'] ?? null) === 10, 'The sender id should be included in debug metadata.');
assert_sender_name_in_document(($singleLine['senderMatchType'] ?? null) === 'sender_name', 'Sender matches should identify their source type.');
assert_sender_name_in_document(($singleLine['matchType'] ?? null) === 'sender_name', 'The standard match type should identify a sender name.');
assert_sender_name_in_document(($singleLine['valueBBoxIndexes'] ?? null) === [1], 'The single-line match should expose its bbox index.');

assert_sender_name_in_document(is_array($multiLine), 'The sender unit should produce a multiline candidate.');
assert_sender_name_in_document(($multiLine['value'] ?? null) === 'Munkfors kommun', 'Multiline text should be joined with normalized whitespace.');
assert_sender_name_in_document(($multiLine['senderUnitId'] ?? null) === 21, 'The sender unit id should be included in debug metadata.');
assert_sender_name_in_document(($multiLine['senderMatchType'] ?? null) === 'sender_unit', 'Units should be treated as equivalent name sources.');
assert_sender_name_in_document(($multiLine['matchType'] ?? null) === 'sender_unit', 'The standard match type should identify a sender unit.');
assert_sender_name_in_document(($multiLine['lineCount'] ?? null) === 2, 'The multiline candidate should report its line count.');
assert_sender_name_in_document(($multiLine['valueBBoxIndexes'] ?? null) === [2, 3], 'The multiline match should expose every included bbox index.');
assert_sender_name_in_document(
    ($multiLine['valueBbox'] ?? null) === ['x0' => 40.0, 'y0' => 60.0, 'x1' => 112.0, 'y1' => 88.0],
    'The multiline bbox should be the union of its line bboxes.'
);

$separatedGeometries = [
    sender_name_test_geometry('Munkfors', 0, ['x0' => 40.0, 'y0' => 20.0, 'x1' => 112.0, 'y1' => 32.0]),
    sender_name_test_geometry('kommun', 1, ['x0' => 240.0, 'y0' => 36.0, 'x1' => 302.0, 'y1' => 48.0]),
];
$separatedResult = extract_sender_name_in_document_field_result(
    ['Munkfors', 'kommun'],
    $separatedGeometries,
    $settings,
    [$entries[1]]
);
assert_sender_name_in_document(
    ($separatedResult['matches'] ?? []) === [],
    'Lines without sufficient x overlap must not form a multiline block.'
);

echo "sender_name_in_document_system_field_test: ok\n";
