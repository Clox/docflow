<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_title_multiline_candidates(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function title_multiline_test_geometry(string $text, int $wordIndex, array $bbox, int $pageNumber = 1): array
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

function title_multiline_candidate_bbox_overlap(array $left, array $right): bool
{
    $indexes = [];
    foreach (is_array($left['valueBBoxIndexes'] ?? null) ? $left['valueBBoxIndexes'] : [] as $index) {
        if (is_int($index) && $index > 0) {
            $indexes[$index] = true;
        }
    }
    foreach (is_array($right['valueBBoxIndexes'] ?? null) ? $right['valueBBoxIndexes'] : [] as $index) {
        if (is_int($index) && isset($indexes[$index])) {
            return true;
        }
    }
    return false;
}

$blockSettings = normalize_multiline_text_block_settings([
    'maxLines' => 3,
    'maxLineDistanceLineHeights' => 2.0,
    'maxTextSizeRatio' => 1.5,
    'minXOverlapRatio' => 0.3,
    'maxHorizontalOffsetLineHeights' => 3.0,
]);

$lines = [
    'Ansökan om ekonomiskt bistånd',
    'för perioden maj 2026',
    'Referensnummer',
];
$geometries = [
    title_multiline_test_geometry(
        'Ansökan om ekonomiskt bistånd',
        0,
        ['x0' => 80.0, 'y0' => 80.0, 'x1' => 300.0, 'y1' => 100.0]
    ),
    title_multiline_test_geometry(
        'för perioden maj 2026',
        1,
        ['x0' => 82.0, 'y0' => 104.0, 'x1' => 255.0, 'y1' => 124.0]
    ),
    title_multiline_test_geometry(
        'Referensnummer',
        2,
        ['x0' => 420.0, 'y0' => 420.0, 'x1' => 520.0, 'y1' => 436.0]
    ),
];

$result = extract_title_field_result($lines, $geometries, [], [], [], $blockSettings);
$candidates = is_array($result['candidates'] ?? null) ? $result['candidates'] : [];
$singleLineCandidates = array_values(array_filter(
    $candidates,
    static fn(array $candidate): bool => ($candidate['blockType'] ?? null) === 'single_line'
));
$multiLineCandidates = array_values(array_filter(
    $candidates,
    static fn(array $candidate): bool => ($candidate['blockType'] ?? null) === 'multiline'
));
$joinedCandidate = array_values(array_filter(
    $multiLineCandidates,
    static fn(array $candidate): bool => ($candidate['value'] ?? null) === 'Ansökan om ekonomiskt bistånd för perioden maj 2026'
))[0] ?? null;

assert_title_multiline_candidates(
    array_values(array_map(
        static fn(array $candidate): string => (string) ($candidate['value'] ?? ''),
        $singleLineCandidates
    )) === ['Referensnummer'],
    'Title must keep unrelated single-line candidates but consume the single lines inside an accepted multiline block.'
);
assert_title_multiline_candidates(is_array($joinedCandidate), 'Title must create multiline candidates with space-joined text.');
assert_title_multiline_candidates(($joinedCandidate['lineCount'] ?? null) === 2, 'The multiline title candidate must expose its line count.');
assert_title_multiline_candidates(($joinedCandidate['lineIndexes'] ?? null) === [0, 1], 'The multiline title candidate must expose included lines.');
assert_title_multiline_candidates(($joinedCandidate['valueBBoxIndexes'] ?? null) === [1, 2], 'The multiline title candidate must expose included bbox indexes.');
assert_title_multiline_candidates(
    array_map(
        static fn(array $part): string => (string) ($part['text'] ?? ''),
        is_array($joinedCandidate['blockParts'] ?? null) ? $joinedCandidate['blockParts'] : []
    ) === ['Ansökan om ekonomiskt bistånd', 'för perioden maj 2026'],
    'The multiline title candidate must expose how the text was built.'
);
assert_title_multiline_candidates(
    count(array_filter(
        $joinedCandidate['signals'] ?? [],
        static fn(mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'text_block'
    )) === 1,
    'The multiline title candidate must expose text block debug details as a signal.'
);

$matches = title_result_matches($result, $geometries);
$joinedMatch = array_values(array_filter(
    $matches,
    static fn(array $match): bool => ($match['value'] ?? null) === 'Ansökan om ekonomiskt bistånd för perioden maj 2026'
))[0] ?? null;
assert_title_multiline_candidates(is_array($joinedMatch), 'The multiline title candidate must be visible in match metadata.');
assert_title_multiline_candidates(($joinedMatch['blockType'] ?? null) === 'multiline', 'Match metadata must include candidate type.');
assert_title_multiline_candidates(($joinedMatch['lineCount'] ?? null) === 2, 'Match metadata must include line count.');
assert_title_multiline_candidates(($joinedMatch['lineIndexes'] ?? null) === [0, 1], 'Match metadata must include included lines.');
assert_title_multiline_candidates(($joinedMatch['valueBBoxIndexes'] ?? null) === [1, 2], 'Match metadata must include included bboxes.');
assert_title_multiline_candidates(
    array_map(
        static fn(array $part): string => (string) ($part['text'] ?? ''),
        is_array($joinedMatch['blockParts'] ?? null) ? $joinedMatch['blockParts'] : []
    ) === ['Ansökan om ekonomiskt bistånd', 'för perioden maj 2026'],
    'Match metadata must include how the multiline title text was built.'
);

$strictSettings = normalize_multiline_text_block_settings([
    ...$blockSettings,
    'maxLineDistanceLineHeights' => 0.05,
]);
$strictResult = extract_title_field_result($lines, $geometries, [], [], [], $strictSettings);
$strictCandidates = is_array($strictResult['candidates'] ?? null) ? $strictResult['candidates'] : [];
assert_title_multiline_candidates(
    count(array_filter(
        $strictCandidates,
        static fn(array $candidate): bool => ($candidate['blockType'] ?? null) === 'multiline'
    )) === 0,
    'Title multiline candidates must use the shared multiline block settings.'
);

$senderLines = ['Munkfors', 'kommun'];
$senderGeometries = [
    title_multiline_test_geometry('Munkfors', 0, ['x0' => 40.0, 'y0' => 60.0, 'x1' => 112.0, 'y1' => 72.0]),
    title_multiline_test_geometry('kommun', 1, ['x0' => 42.0, 'y0' => 76.0, 'x1' => 104.0, 'y1' => 88.0]),
];
$senderMatches = [[
    'value' => 'Munkfors kommun',
    'matchText' => 'Munkfors kommun',
    'matchedName' => 'Munkfors kommun',
]];
$senderResult = extract_title_field_result($senderLines, $senderGeometries, [], [], $senderMatches, $blockSettings);
$senderCandidates = is_array($senderResult['candidates'] ?? null) ? $senderResult['candidates'] : [];
$senderMultilineCandidate = array_values(array_filter(
    $senderCandidates,
    static fn(array $candidate): bool => ($candidate['blockType'] ?? null) === 'multiline'
        && ($candidate['value'] ?? null) === 'Munkfors kommun'
))[0] ?? null;
assert_title_multiline_candidates(is_array($senderMultilineCandidate), 'The sender-name example must produce a multiline title candidate.');
assert_title_multiline_candidates(
    count($senderCandidates) === 1,
    'Munkfors + kommun must only produce the joined title candidate when the multiline block is accepted.'
);
assert_title_multiline_candidates(
    count(array_filter(
        $senderMultilineCandidate['signals'] ?? [],
        static fn(mixed $signal): bool => is_array($signal)
            && ($signal['code'] ?? null) === 'sender_name'
            && (float) ($signal['score'] ?? 0.0) === -40.0
    )) === 1,
    'The title sender-name penalty must work for multiline candidates.'
);

$overlappingLines = ['Tomas Lars-Åke Bäck', 'Kyrkogatan 8 Lgh 1001', '684 30 MUNKFORS'];
$overlappingGeometries = [
    title_multiline_test_geometry('Tomas Lars-Åke Bäck', 0, ['x0' => 40.0, 'y0' => 60.0, 'x1' => 210.0, 'y1' => 80.0]),
    title_multiline_test_geometry('Kyrkogatan 8 Lgh 1001', 1, ['x0' => 42.0, 'y0' => 84.0, 'x1' => 236.0, 'y1' => 104.0]),
    title_multiline_test_geometry('684 30 MUNKFORS', 2, ['x0' => 44.0, 'y0' => 108.0, 'x1' => 198.0, 'y1' => 128.0]),
];
$overlappingResult = extract_title_field_result($overlappingLines, $overlappingGeometries, [], [], [], $blockSettings);
$overlappingCandidates = is_array($overlappingResult['candidates'] ?? null) ? $overlappingResult['candidates'] : [];
$overlappingMultilineCandidates = array_values(array_filter(
    $overlappingCandidates,
    static fn(array $candidate): bool => ($candidate['blockType'] ?? null) === 'multiline'
));
foreach ($overlappingMultilineCandidates as $leftIndex => $leftCandidate) {
    foreach ($overlappingMultilineCandidates as $rightIndex => $rightCandidate) {
        if ($rightIndex <= $leftIndex) {
            continue;
        }
        assert_title_multiline_candidates(
            !title_multiline_candidate_bbox_overlap($leftCandidate, $rightCandidate),
            'Overlapping multiline title blocks must not both be kept as title candidates.'
        );
    }
}
assert_title_multiline_candidates(
    count($overlappingMultilineCandidates) === 1,
    'A chain of overlapping multiline title blocks should keep only one non-overlapping candidate.'
);

echo "title_multiline_candidates_test: ok\n";
