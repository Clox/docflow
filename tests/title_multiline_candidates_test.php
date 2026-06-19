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

$result = extract_title_field_result($lines, $geometries, [], [], [], null, $blockSettings);
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
    )) === 0,
    'The multiline title candidate must not show text block metadata as a score signal.'
);
assert_title_multiline_candidates(
    is_array($joinedCandidate['blockJoinMetrics'] ?? null)
        && count($joinedCandidate['blockJoinMetrics']) === 1
        && abs((float) ($joinedCandidate['blockJoinMetrics'][0]['textSizeRatio'] ?? 0.0) - 1.0) < 0.0001,
    'The multiline title candidate must expose symmetric join metrics.'
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
$strictResult = extract_title_field_result($lines, $geometries, [], [], [], null, $strictSettings);
$strictCandidates = is_array($strictResult['candidates'] ?? null) ? $strictResult['candidates'] : [];
assert_title_multiline_candidates(
    count(array_filter(
        $strictCandidates,
        static fn(array $candidate): bool => ($candidate['blockType'] ?? null) === 'multiline'
    )) === 0,
    'Title multiline candidates must use the shared multiline block settings.'
);

$sizeRatioSettings = normalize_multiline_text_block_settings([
    ...$blockSettings,
    'maxTextSizeRatio' => 1.2,
]);
$largeFirst = title_multiline_test_geometry('Stor rubrik', 0, ['x0' => 80.0, 'y0' => 80.0, 'x1' => 260.0, 'y1' => 150.0]);
$smallSecond = title_multiline_test_geometry('mindre rad', 1, ['x0' => 82.0, 'y0' => 154.0, 'x1' => 230.0, 'y1' => 215.0]);
$smallFirst = title_multiline_test_geometry('mindre rad', 0, ['x0' => 82.0, 'y0' => 80.0, 'x1' => 230.0, 'y1' => 141.0]);
$largeSecond = title_multiline_test_geometry('Stor rubrik', 1, ['x0' => 80.0, 'y0' => 145.0, 'x1' => 260.0, 'y1' => 215.0]);
assert_title_multiline_candidates(
    count(array_filter(
        build_multiline_text_blocks(['Stor rubrik', 'mindre rad'], [$largeFirst, $smallSecond], $sizeRatioSettings),
        static fn(array $block): bool => ($block['blockType'] ?? null) === 'multiline'
    )) === 1,
    'Text size ratio 70/61 should be accepted when maxTextSizeRatio is high enough.'
);
assert_title_multiline_candidates(
    count(array_filter(
        build_multiline_text_blocks(['mindre rad', 'Stor rubrik'], [$smallFirst, $largeSecond], $sizeRatioSettings),
        static fn(array $block): bool => ($block['blockType'] ?? null) === 'multiline'
    )) === 1,
    'Text size ratio comparison must be symmetric when the smaller line comes first.'
);
assert_title_multiline_candidates(
    count(array_filter(
        build_multiline_text_blocks(['Stor rubrik', 'mindre rad'], [$largeFirst, $smallSecond], [
            ...$sizeRatioSettings,
            'maxTextSizeRatio' => 1.1,
        ]),
        static fn(array $block): bool => ($block['blockType'] ?? null) === 'multiline'
    )) === 0,
    'Text size ratio 70/61 should be rejected when maxTextSizeRatio is below the symmetric ratio.'
);

$multilineTextSizeCandidate = [
    'value' => 'Tomas Lars-Åke Bäck Kyrkogatan 8 Lgh 1001',
    'raw' => 'Tomas Lars-Åke Bäck Kyrkogatan 8 Lgh 1001',
    'line' => 'Tomas Lars-Åke Bäck Kyrkogatan 8 Lgh 1001',
    'lineIndex' => 0,
    'start' => 0,
    'end' => 42,
    'bbox' => ['x0' => 1851.0, 'y0' => 565.0, 'x1' => 2482.0, 'y1' => 710.0],
    'pageNumber' => 1,
    'blockType' => 'multiline',
    'lineCount' => 2,
    'lineIndexes' => [0, 1],
    'blockParts' => [
        [
            'text' => 'Tomas Lars-Åke Bäck',
            'lineIndex' => 0,
            'bbox' => ['x0' => 1851.0, 'y0' => 565.0, 'x1' => 2480.0, 'y1' => 628.0],
        ],
        [
            'text' => 'Kyrkogatan 8 Lgh 1001',
            'lineIndex' => 1,
            'bbox' => ['x0' => 1853.0, 'y0' => 647.0, 'x1' => 2482.0, 'y1' => 710.0],
        ],
    ],
];
$normalHeightGeometries = [
    title_multiline_test_geometry('Tomas Lars-Åke Bäck', 0, ['x0' => 1851.0, 'y0' => 565.0, 'x1' => 2480.0, 'y1' => 628.0]),
    title_multiline_test_geometry('Kyrkogatan 8 Lgh 1001', 1, ['x0' => 1853.0, 'y0' => 647.0, 'x1' => 2482.0, 'y1' => 710.0]),
    title_multiline_test_geometry('Normal text', 2, ['x0' => 100.0, 'y0' => 800.0, 'x1' => 500.0, 'y1' => 869.0]),
    title_multiline_test_geometry('Normal text igen', 3, ['x0' => 100.0, 'y0' => 900.0, 'x1' => 500.0, 'y1' => 969.0]),
    title_multiline_test_geometry('Normal text tre', 4, ['x0' => 100.0, 'y0' => 1000.0, 'x1' => 500.0, 'y1' => 1069.0]),
];
$textSizeScored = score_title_candidate($multilineTextSizeCandidate, [], $normalHeightGeometries, [
    'signals' => [
        'vertical_position' => ['enabled' => false],
        'horizontal_position_centered' => ['enabled' => false],
        'horizontal_position_left_aligned' => ['enabled' => false],
        'uppercase_ratio' => ['enabled' => false],
        'brevity' => ['enabled' => false],
        'text_density' => ['enabled' => false],
        'sender_name' => ['enabled' => false],
    ],
]);
assert_title_multiline_candidates(
    abs((float) ($textSizeScored['relativeTextSize'] ?? 0.0) - (63.0 / 69.0)) < 0.0001,
    'Title text size must use representative line height for multiline candidates, not the full block bbox height.'
);

$labelValueCandidate = [
    'value' => 'AVSER Rickard Bernt Peter Henriksen, 19920112-4212',
    'raw' => 'AVSER Rickard Bernt Peter Henriksen, 19920112-4212',
    'line' => 'AVSER Rickard Bernt Peter Henriksen, 19920112-4212',
    'lineIndex' => 0,
    'start' => 0,
    'end' => 52,
    'bbox' => ['x0' => 40.0, 'y0' => 60.0, 'x1' => 866.0, 'y1' => 108.0],
    'pageNumber' => 1,
    'blockType' => 'multiline',
    'lineCount' => 2,
    'lineIndexes' => [0, 1],
    'blockParts' => [
        [
            'text' => 'AVSER',
            'lineIndex' => 0,
            'bbox' => ['x0' => 40.0, 'y0' => 60.0, 'x1' => 175.0, 'y1' => 82.0],
        ],
        [
            'text' => 'Rickard Bernt Peter Henriksen, 19920112-4212',
            'lineIndex' => 1,
            'bbox' => ['x0' => 40.0, 'y0' => 86.0, 'x1' => 866.0, 'y1' => 108.0],
        ],
    ],
];
$legitimateShortLastLineCandidate = [
    'value' => 'Ansökan om ekonomiskt bistånd maj 2026',
    'raw' => 'Ansökan om ekonomiskt bistånd maj 2026',
    'line' => 'Ansökan om ekonomiskt bistånd maj 2026',
    'lineIndex' => 0,
    'start' => 0,
    'end' => 40,
    'bbox' => ['x0' => 40.0, 'y0' => 160.0, 'x1' => 540.0, 'y1' => 208.0],
    'pageNumber' => 1,
    'blockType' => 'multiline',
    'lineCount' => 2,
    'lineIndexes' => [0, 1],
    'blockParts' => [
        [
            'text' => 'Ansökan om ekonomiskt bistånd',
            'lineIndex' => 0,
            'bbox' => ['x0' => 40.0, 'y0' => 160.0, 'x1' => 540.0, 'y1' => 182.0],
        ],
        [
            'text' => 'maj 2026',
            'lineIndex' => 1,
            'bbox' => ['x0' => 40.0, 'y0' => 186.0, 'x1' => 220.0, 'y1' => 208.0],
        ],
    ],
];
$shortLineHeuristics = [
    'signals' => [
        'vertical_position' => ['enabled' => false],
        'horizontal_position_centered' => ['enabled' => false],
        'horizontal_position_left_aligned' => ['enabled' => false],
        'text_size' => ['enabled' => false],
        'uppercase_ratio' => ['enabled' => false],
        'brevity' => ['enabled' => false],
        'text_density' => ['enabled' => false],
        'sender_name' => ['enabled' => false],
        'short_line_before_long_line' => [
            'curve' => [
                ['x' => 0.0, 'y' => -55.0],
                ['x' => 0.25, 'y' => -45.0],
                ['x' => 0.50, 'y' => -20.0],
                ['x' => 0.75, 'y' => 0.0],
                ['x' => 1.0, 'y' => 0.0],
            ],
        ],
    ],
];
$labelValueScored = score_title_candidate(
    $labelValueCandidate,
    [],
    [
        title_multiline_test_geometry('AVSER', 0, ['x0' => 40.0, 'y0' => 60.0, 'x1' => 175.0, 'y1' => 82.0]),
        title_multiline_test_geometry('Rickard Bernt Peter Henriksen, 19920112-4212', 1, ['x0' => 40.0, 'y0' => 86.0, 'x1' => 866.0, 'y1' => 108.0]),
    ],
    $shortLineHeuristics
);
$labelValueShortLineSignals = array_values(array_filter(
    $labelValueScored['signals'] ?? [],
    static fn(mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'short_line_before_long_line'
));
assert_title_multiline_candidates(
    abs((float) ($labelValueScored['shortLineBeforeLongLineRatio'] ?? 0.0) - (135.0 / 826.0)) < 0.0001,
    'Short-line-before-long-line ratio must compare each earlier line to the widest following line.'
);
assert_title_multiline_candidates(
    count($labelValueShortLineSignals) === 1
        && (float) ($labelValueShortLineSignals[0]['score'] ?? 0.0) < 0.0
        && str_contains((string) ($labelValueShortLineSignals[0]['detail'] ?? ''), 'ratio:0.163'),
    'Label + value multiline title candidates must get a negative short-line-before-long-line signal with debug ratio.'
);
$legitimateShortLastLineScored = score_title_candidate(
    $legitimateShortLastLineCandidate,
    [],
    [
        title_multiline_test_geometry('Ansökan om ekonomiskt bistånd', 0, ['x0' => 40.0, 'y0' => 160.0, 'x1' => 540.0, 'y1' => 182.0]),
        title_multiline_test_geometry('maj 2026', 1, ['x0' => 40.0, 'y0' => 186.0, 'x1' => 220.0, 'y1' => 208.0]),
    ],
    $shortLineHeuristics
);
assert_title_multiline_candidates(
    abs((float) ($legitimateShortLastLineScored['shortLineBeforeLongLineRatio'] ?? 0.0) - (500.0 / 180.0)) < 0.0001,
    'A short last line must not be used as the penalized line.'
);
assert_title_multiline_candidates(
    count(array_filter(
        $legitimateShortLastLineScored['signals'] ?? [],
        static fn(mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'short_line_before_long_line'
    )) === 0,
    'A normal multiline title with a short last line must not get a short-line-before-long-line penalty.'
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
$senderResult = extract_title_field_result($senderLines, $senderGeometries, [], [], $senderMatches, null, $blockSettings);
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
            && (float) ($signal['score'] ?? 0.0) === -60.0
    )) === 1,
    'The title sender-name penalty must work for multiline candidates.'
);

$overlappingLines = ['Tomas Lars-Åke Bäck', 'Kyrkogatan 8 Lgh 1001', '684 30 MUNKFORS'];
$overlappingGeometries = [
    title_multiline_test_geometry('Tomas Lars-Åke Bäck', 0, ['x0' => 40.0, 'y0' => 60.0, 'x1' => 210.0, 'y1' => 80.0]),
    title_multiline_test_geometry('Kyrkogatan 8 Lgh 1001', 1, ['x0' => 42.0, 'y0' => 84.0, 'x1' => 236.0, 'y1' => 104.0]),
    title_multiline_test_geometry('684 30 MUNKFORS', 2, ['x0' => 44.0, 'y0' => 108.0, 'x1' => 198.0, 'y1' => 128.0]),
];
$overlappingResult = extract_title_field_result($overlappingLines, $overlappingGeometries, [], [], [], null, $blockSettings);
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

$verticalSideText = 'S:25431(1/1)A:19284K:15123';
$verticalBbox = ['x0' => 40.0, 'y0' => 60.0, 'x1' => 90.0, 'y1' => 548.0];
$verticalCandidate = [
    'value' => $verticalSideText,
    'raw' => $verticalSideText,
    'line' => $verticalSideText,
    'lineIndex' => 0,
    'start' => 0,
    'end' => strlen($verticalSideText),
    'bbox' => $verticalBbox,
    'pageNumber' => 1,
    'blockType' => 'single_line',
    'lineCount' => 1,
    'lineIndexes' => [0],
];
$verticalScored = score_title_candidate(
    $verticalCandidate,
    [$verticalSideText],
    [title_multiline_test_geometry($verticalSideText, 0, $verticalBbox)]
);
assert_title_multiline_candidates(
    ($verticalScored['excluded'] ?? false) === true
        && ($verticalScored['invalidReason'] ?? null) === 'Vertikal text'
        && abs((float) ($verticalScored['orientationRatio'] ?? 0.0) - (488.0 / 50.0)) < 0.0001,
    'Title candidates with a final bbox that is taller than wide must be rejected as vertical text with debug ratio.'
);

$verticalOnlyResult = extract_title_field_result(
    [$verticalSideText],
    [title_multiline_test_geometry($verticalSideText, 0, $verticalBbox)],
    [],
    [],
    [],
    null,
    $blockSettings
);
assert_title_multiline_candidates(
    ($verticalOnlyResult['value'] ?? null) === null,
    'Vertical side text must not be selected as the title result.'
);

echo "title_multiline_candidates_test: ok\n";
