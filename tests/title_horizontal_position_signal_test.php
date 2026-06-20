<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_title_horizontal_position(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function title_horizontal_signal_result(array $bbox, float $leftMarginX = 40.0): array
{
    $zeroCurve = [
        ['x' => 0.0, 'y' => 0.0],
        ['x' => 1.0, 'y' => 0.0],
    ];
    $heuristics = [
        'full_confidence_score' => 120.0,
        'signals' => [
            'vertical_position' => ['curve' => $zeroCurve],
            'horizontal_position' => [
                'curve' => [
                    ['x' => 0.0, 'y' => 30.0],
                    ['x' => 20.0, 'y' => 30.0],
                ],
            ],
            'text_size' => ['curve' => $zeroCurve],
            'uppercase_ratio' => ['curve' => $zeroCurve],
            'brevity' => ['curve' => $zeroCurve],
            'text_density' => ['curve' => $zeroCurve],
        ],
    ];

    return score_title_candidate(
        [
            'value' => 'Beslut om bistånd',
            'raw' => 'Beslut om bistånd',
            'line' => 'Beslut om bistånd',
            'lineIndex' => 0,
            'start' => 0,
            'end' => strlen('Beslut om bistånd'),
            'bbox' => $bbox,
            'valueBBoxIndexes' => [1, 2, 3],
        ],
        ['Beslut om bistånd'],
        [[
            'text' => 'Beslut om bistånd',
            'pageNumber' => 1,
            'pageWidth' => 1000.0,
            'pageHeight' => 1400.0,
            'segments' => [[
                'start' => 0,
                'end' => strlen('Beslut om bistånd'),
                'wordIndex' => 0,
                'bbox' => $bbox,
            ]],
        ]],
        $heuristics,
        [],
        [
            1 => [
                'leftMargin' => [
                    'available' => true,
                    'x' => $leftMarginX,
                ],
            ],
        ]
    );
}

$leftMode = title_horizontal_signal_result(['x0' => 40.0, 'y0' => 80.0, 'x1' => 280.0, 'y1' => 110.0]);
assert_title_horizontal_position(
    abs((float) ($leftMode['score'] ?? 0.0) - 30.0) < 0.0001,
    'Horizontal title position should contribute through one shared signal.'
);
assert_title_horizontal_position(
    ($leftMode['horizontalPositionMode'] ?? null) === 'left',
    'The shared horizontal signal should choose left margin mode when it is closest.'
);
assert_title_horizontal_position(
    count(array_filter(
        $leftMode['signals'] ?? [],
        static fn(mixed $signal): bool => is_array($signal)
            && ($signal['code'] ?? null) === 'horizontal_position'
    )) === 1,
    'The candidate signal list should contain one shared horizontal signal.'
);
assert_title_horizontal_position(
    count(array_filter(
        $leftMode['signals'] ?? [],
        static fn(mixed $signal): bool => is_array($signal)
            && in_array($signal['code'] ?? null, ['horizontal_position_centered', 'horizontal_position_left_aligned'], true)
    )) === 0,
    'Legacy separate horizontal title signals should not be shown in the candidate signal list.'
);
$leftSignal = array_values(array_filter(
    $leftMode['signals'] ?? [],
    static fn(mixed $signal): bool => is_array($signal)
        && ($signal['code'] ?? null) === 'horizontal_position'
))[0] ?? null;
assert_title_horizontal_position(
    is_array($leftSignal)
        && str_contains((string) ($leftSignal['detail'] ?? ''), 'mode:left')
        && str_contains((string) ($leftSignal['detail'] ?? ''), 'margin_x:40')
        && str_contains((string) ($leftSignal['detail'] ?? ''), 'candidate_x:40')
        && str_contains((string) ($leftSignal['detail'] ?? ''), 'distance:0'),
    'The shared horizontal title signal should describe left-margin mode and distance.'
);

$centerMode = title_horizontal_signal_result(['x0' => 380.0, 'y0' => 80.0, 'x1' => 620.0, 'y1' => 110.0]);
assert_title_horizontal_position(
    abs((float) ($centerMode['score'] ?? 0.0) - 30.0) < 0.0001,
    'The shared horizontal title signal should use the same curve for centered candidates.'
);
assert_title_horizontal_position(
    ($centerMode['horizontalPositionMode'] ?? null) === 'center',
    'The shared horizontal signal should choose center mode when it is closest.'
);
assert_title_horizontal_position(
    str_contains((string) (($centerMode['signals'][0]['detail'] ?? '')), 'mode:center'),
    'The shared horizontal title signal should describe center mode in debug details.'
);

$zeroCurve = [
    ['x' => 0.0, 'y' => 0.0],
    ['x' => 1.0, 'y' => 0.0],
];
$zeroConfidenceResult = extract_title_field_result(
    ['Neutral rubrik'],
    [[
        'text' => 'Neutral rubrik',
        'pageNumber' => 1,
        'pageWidth' => 1000.0,
        'pageHeight' => 1400.0,
        'segments' => [[
            'start' => 0,
            'end' => strlen('Neutral rubrik'),
            'wordIndex' => 1,
            'bbox' => ['x0' => 40.0, 'y0' => 80.0, 'x1' => 240.0, 'y1' => 110.0],
        ]],
    ]],
    [
        'signals' => [
            'vertical_position' => ['curve' => $zeroCurve],
            'horizontal_position' => ['curve' => $zeroCurve],
            'text_size' => ['curve' => $zeroCurve],
            'uppercase_ratio' => ['curve' => $zeroCurve],
            'brevity' => ['curve' => $zeroCurve],
            'text_density' => ['curve' => $zeroCurve],
        ],
    ],
    [],
    [],
    null,
    []
);
$zeroConfidenceMatches = title_result_matches(
    $zeroConfidenceResult,
    [[
        'text' => 'Neutral rubrik',
        'pageNumber' => 1,
        'pageWidth' => 1000.0,
        'pageHeight' => 1400.0,
        'segments' => [[
            'start' => 0,
            'end' => strlen('Neutral rubrik'),
            'wordIndex' => 1,
            'bbox' => ['x0' => 40.0, 'y0' => 80.0, 'x1' => 240.0, 'y1' => 110.0],
        ]],
    ]]
);
$zeroConfidenceMatch = array_values(array_filter(
    $zeroConfidenceMatches,
    static fn(array $match): bool => ($match['value'] ?? null) === 'Neutral rubrik'
))[0] ?? null;
assert_title_horizontal_position(
    is_array($zeroConfidenceMatch),
    'Zero-confidence title candidates must remain visible in all-candidates match metadata.'
);
assert_title_horizontal_position(
    (float) ($zeroConfidenceMatch['finalConfidence'] ?? -1.0) === 0.0,
    'The zero-confidence title candidate should keep 0% confidence.'
);

$pageOneCandidate = [
    'blockType' => 'multiline',
    'value' => 'Sida ett',
    'score' => 10.0,
    'lineIndex' => 0,
    'pageNumber' => 1,
    'valueBBoxIndexes' => [33],
];
$pageTwoCandidate = [
    'blockType' => 'multiline',
    'value' => 'Sida två',
    'score' => 20.0,
    'lineIndex' => 10,
    'pageNumber' => 2,
    'valueBBoxIndexes' => [33],
];
assert_title_horizontal_position(
    !title_candidates_have_overlapping_bbox_indexes($pageOneCandidate, $pageTwoCandidate),
    'Title bbox overlap detection must treat equal bbox numbers on different pages as separate boxes.'
);
assert_title_horizontal_position(
    count(title_select_non_overlapping_multiline_candidates([$pageOneCandidate, $pageTwoCandidate])) === 2,
    'Multiline title candidates on different pages must not suppress each other just because bbox indexes match.'
);

echo "title_horizontal_position_signal_test: ok\n";
