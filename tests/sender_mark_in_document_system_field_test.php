<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_sender_mark_in_document(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function sender_mark_words_test_geometry(array $words, int $firstWordIndex, array $bboxes, int $pageNumber = 1): array
{
    $line = implode(' ', $words);
    $segments = [];
    $offset = 0;
    foreach ($words as $index => $word) {
        $wordText = (string) $word;
        $start = $offset;
        $end = $start + strlen($wordText);
        $segments[] = [
            'text' => $wordText,
            'start' => $start,
            'end' => $end,
            'wordIndex' => $firstWordIndex + $index,
            'bbox' => $bboxes[$index],
        ];
        $offset = $end + 1;
    }

    return [
        'text' => $line,
        'segments' => $segments,
        'pageNumber' => $pageNumber,
        'pageWidth' => 3200.0,
        'pageHeight' => 4600.0,
    ];
}

$definitions = system_extraction_field_definitions();
assert_sender_mark_in_document(
    ($definitions['sender_mark_in_document']['name'] ?? null) === 'Avsändarmärke i dokument',
    'The sender mark system field must exist with the requested display name.'
);
assert_sender_mark_in_document(
    ($definitions['sender_mark_in_document']['extractor'] ?? null) === 'sender_mark_in_document',
    'The sender mark system field must have its own extractor.'
);

$settings = normalize_multiline_text_block_settings([
    'maxLines' => 3,
    'maxLineDistanceLineHeights' => 1.0,
    'maxTextSizeRatio' => 1.1,
    'minXOverlapRatio' => 0.3,
    'maxHorizontalOffsetLineHeights' => 3.0,
]);
$lines = [
    'Klarna',
    'Klarna Bank AB (publ), FE 50500',
];
$geometries = [
    sender_mark_words_test_geometry(
        ['Klarna'],
        0,
        [['x0' => 307.0, 'y0' => 138.0, 'x1' => 884.0, 'y1' => 303.0]]
    ),
    sender_mark_words_test_geometry(
        ['Klarna', 'Bank', 'AB', '(publ),', 'FE', '50500'],
        8,
        [
            ['x0' => 299.0, 'y0' => 321.0, 'x1' => 422.0, 'y1' => 369.0],
            ['x0' => 438.0, 'y0' => 321.0, 'x1' => 530.0, 'y1' => 369.0],
            ['x0' => 546.0, 'y0' => 321.0, 'x1' => 592.0, 'y1' => 369.0],
            ['x0' => 608.0, 'y0' => 321.0, 'x1' => 748.0, 'y1' => 369.0],
            ['x0' => 756.0, 'y0' => 321.0, 'x1' => 802.0, 'y1' => 369.0],
            ['x0' => 811.0, 'y0' => 321.0, 'x1' => 933.0, 'y1' => 369.0],
        ]
    ),
];
$entries = [[
    'senderId' => 30,
    'senderUnitId' => null,
    'name' => 'Klarna Bank AB',
    'normalizedName' => 'klarna bank ab',
    'type' => 'sender_name',
]];

$senderNameResult = extract_sender_name_in_document_field_result($lines, $geometries, $settings, $entries);
$senderNameMatches = is_array($senderNameResult['matches'] ?? null) ? $senderNameResult['matches'] : [];
assert_sender_mark_in_document(count($senderNameMatches) === 1, 'The sender-name field must first identify Klarna Bank AB.');
assert_sender_mark_in_document(($senderNameMatches[0]['value'] ?? null) === 'Klarna Bank AB', 'The sender-name match must stay separate from the mark.');
assert_sender_mark_in_document(($senderNameMatches[0]['valueBBoxIndexes'] ?? null) === [9, 10, 11], 'The sender-name match must use only the legal-name bboxes.');

$senderMarkResult = extract_sender_mark_in_document_field_result($lines, $geometries, $settings, $senderNameMatches);
$senderMarkMatches = is_array($senderMarkResult['matches'] ?? null) ? $senderMarkResult['matches'] : [];
$senderMark = $senderMarkMatches[0] ?? null;
assert_sender_mark_in_document(is_array($senderMark), 'The sender mark field must identify the prominent Klarna text.');
assert_sender_mark_in_document(($senderMark['value'] ?? null) === 'Klarna', 'The sender mark value must be the short brand text.');
assert_sender_mark_in_document(($senderMark['sourceSenderName'] ?? null) === 'Klarna Bank AB', 'The sender mark must expose its source sender-name match.');
assert_sender_mark_in_document(($senderMark['valueBBoxIndexes'] ?? null) === [1], 'The sender mark must expose its own bbox, not the sender-name bbox.');
assert_sender_mark_in_document(
    is_numeric($senderMark['relativeTextSizeToSenderName'] ?? null) && (float) $senderMark['relativeTextSizeToSenderName'] > 1.0,
    'The sender mark debug data must include relative text size against the sender name.'
);
assert_sender_mark_in_document(
    count(array_filter(
        $senderMark['signals'] ?? [],
        static fn(mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'distance_to_sender_name'
    )) === 1,
    'The sender mark must expose distance scoring debug.'
);

$titleResult = extract_title_field_result($lines, $geometries, [], [], $senderNameMatches, $senderMarkMatches, $settings);
$titleCandidates = is_array($titleResult['candidates'] ?? null) ? $titleResult['candidates'] : [];
$klarnaTitleCandidate = array_values(array_filter(
    $titleCandidates,
    static fn(array $candidate): bool => ($candidate['value'] ?? null) === 'Klarna'
))[0] ?? null;
assert_sender_mark_in_document(is_array($klarnaTitleCandidate), 'The title candidate for Klarna must remain visible for debugging.');
assert_sender_mark_in_document(
    count(array_filter(
        $klarnaTitleCandidate['signals'] ?? [],
        static fn(mixed $signal): bool => is_array($signal)
            && ($signal['code'] ?? null) === 'sender_name'
            && ($signal['type'] ?? null) === 'negative'
            && str_contains((string) ($signal['detail'] ?? ''), 'Klarna')
    )) === 1,
    'Title must receive its sender penalty from the separate sender mark match.'
);

echo "sender_mark_in_document_system_field_test: ok\n";
