<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_title_character_size(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assert_title_character_size_close(float $actual, float $expected, string $message): void
{
    assert_title_character_size(abs($actual - $expected) < 0.0001, $message . " Expected {$expected}, got {$actual}.");
}

function title_character_size_geometry(string $text, array $segments, int $pageNumber = 1): array
{
    return [
        'text' => $text,
        'segments' => $segments,
        'pageNumber' => $pageNumber,
        'pageWidth' => 600.0,
        'pageHeight' => 800.0,
    ];
}

assert_title_character_size_close(
    title_character_weight_sum("e\u{0301}"),
    title_character_weight_sum('é'),
    'Text must be normalized to NFC before character weights are summed.'
);
assert_title_character_size_close(
    title_character_weight_sum('🙂'),
    1.0,
    'Characters missing from the static width table must use the fallback weight 1.0.'
);
assert_title_character_size_close(
    title_character_weight_sum('Wi'),
    2.0,
    'The configured relative character weights must be used.'
);

$candidate = [
    'value' => 'WW mmmm',
    'raw' => 'WW mmmm',
    'line' => 'WW mmmm',
    'lineIndex' => 0,
    'start' => 0,
    'end' => 7,
    'bbox' => ['x0' => 0.0, 'y0' => 0.0, 'x1' => 260.0, 'y1' => 20.0],
    'valueBBoxIndexes' => [1, 2],
    'pageNumber' => 1,
];
$geometries = [
    title_character_size_geometry('WW mmmm', [
        [
            'text' => 'WW',
            'start' => 0,
            'end' => 2,
            'wordIndex' => 0,
            'bbox' => ['x0' => 0.0, 'y0' => 0.0, 'x1' => 66.0, 'y1' => 20.0],
        ],
        [
            'text' => 'mmmm',
            'start' => 3,
            'end' => 7,
            'wordIndex' => 1,
            'bbox' => ['x0' => 200.0, 'y0' => 0.0, 'x1' => 260.0, 'y1' => 20.0],
        ],
    ]),
];
foreach ([1, 2, 3] as $index) {
    $geometries[] = title_character_size_geometry('mmmm', [[
        'text' => 'mmmm',
        'start' => 0,
        'end' => 4,
        'wordIndex' => $index + 1,
        'bbox' => ['x0' => 10.0, 'y0' => 40.0 * $index, 'x1' => 70.0, 'y1' => (40.0 * $index) + 20.0],
    ]]);
}
$geometries[] = title_character_size_geometry('WW', [[
    'text' => 'WW',
    'start' => 0,
    'end' => 2,
    'wordIndex' => 20,
    'bbox' => ['x0' => 0.0, 'y0' => 0.0, 'x1' => 330.0, 'y1' => 20.0],
]], 2);

$width = title_candidate_character_width_ratio($candidate, $geometries, 1, $candidate['bbox']);
assert_title_character_size(is_array($width), 'A valid candidate must receive a character-compensated width ratio.');
assert_title_character_size_close(
    (float) ($width['pageMedian'] ?? 0.0),
    10.0,
    'The character-width reference must be the median of relevant bboxes on the current page only.'
);
$expectedWidthRatio = ((2.0 * 3.3) + (1.0 * 6.0)) / 9.3;
assert_title_character_size_close(
    (float) ($width['ratio'] ?? 0.0),
    $expectedWidthRatio,
    'Candidate bbox ratios must be combined using their summed character weights.'
);
assert_title_character_size(
    count($width['bboxes'] ?? []) === 2,
    'Candidate width must be based on its individual OCR bboxes.'
);
assert_title_character_size_close(
    (float) ($width['bboxes'][0]['normalizedCharacterWidth'] ?? 0.0),
    20.0,
    'Each candidate bbox must expose its own character-compensated width.'
);
assert_title_character_size_close(
    (float) ($width['bboxes'][1]['normalizedCharacterWidth'] ?? 0.0),
    10.0,
    'Whitespace between separate candidate bboxes must not affect character width.'
);

$disabledSignals = [];
foreach (array_keys(default_title_heuristics()['signals']) as $signalCode) {
    if ($signalCode !== 'text_size') {
        $disabledSignals[$signalCode] = ['enabled' => false];
    }
}
$disabledSignals['text_size'] = [
    'curve' => [
        ['x' => 0.0, 'y' => 0.0],
        ['x' => 2.0, 'y' => 200.0],
    ],
];
$scored = score_title_candidate(
    $candidate,
    ['WW mmmm', 'mmmm', 'mmmm', 'mmmm'],
    $geometries,
    ['signals' => $disabledSignals]
);
$expectedVisualSize = sqrt($expectedWidthRatio);
assert_title_character_size_close(
    (float) ($scored['relativeTextHeight'] ?? 0.0),
    1.0,
    'The existing relative text-height measure must remain available.'
);
assert_title_character_size_close(
    (float) ($scored['relativeCharacterWidth'] ?? 0.0),
    $expectedWidthRatio,
    'The scored candidate must expose relative character width.'
);
assert_title_character_size_close(
    (float) ($scored['visualTextSizeRatio'] ?? 0.0),
    $expectedVisualSize,
    'Visual text size must be the geometric mean of relative height and relative character width.'
);
assert_title_character_size_close(
    (float) ($scored['score'] ?? 0.0),
    $expectedVisualSize * 100.0,
    'The existing Text size score curve must receive the combined visual size ratio.'
);
$textSizeSignal = array_values(array_filter(
    $scored['signals'] ?? [],
    static fn(mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'text_size'
))[0] ?? null;
assert_title_character_size(is_array($textSizeSignal), 'Scoring must still emit one Text size signal.');
assert_title_character_size(
    str_contains((string) ($textSizeSignal['detail'] ?? ''), 'relative_height:1')
        && str_contains((string) ($textSizeSignal['detail'] ?? ''), 'relative_character_width:')
        && str_contains((string) ($textSizeSignal['detail'] ?? ''), 'normal_character_width:10'),
    'Text size signal detail must include height, character width, and page reference values.'
);
assert_title_character_size(
    count($textSizeSignal['debug']['candidate_bboxes'] ?? []) === 2,
    'Structured Text size debug must include every contributing candidate bbox.'
);

$matches = title_result_matches(['candidates' => [$scored]], $geometries);
$snapshot = debug_export_accepted_candidate($matches[0], 0);
assert_title_character_size_close(
    (float) ($snapshot['relativeTextHeight'] ?? 0.0),
    1.0,
    'Snapshots must include relative text height.'
);
assert_title_character_size_close(
    (float) ($snapshot['relativeCharacterWidth'] ?? 0.0),
    $expectedWidthRatio,
    'Snapshots must include relative character width.'
);
assert_title_character_size_close(
    (float) ($snapshot['visualTextSizeRatio'] ?? 0.0),
    $expectedVisualSize,
    'Snapshots must include combined visual text size.'
);
assert_title_character_size(
    count($snapshot['textSizeBboxes'] ?? []) === 2,
    'Snapshots must include per-bbox Text size debug data.'
);

fwrite(STDOUT, "title character-compensated text size tests passed\n");
