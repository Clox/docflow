<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_title_horizontal_position(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function title_horizontal_signal_result(float $centeredPoints, float $leftAlignedPoints): array
{
    $zeroCurve = [
        ['x' => 0.0, 'y' => 0.0],
        ['x' => 1.0, 'y' => 0.0],
    ];
    $heuristics = [
        'full_confidence_score' => 120.0,
        'signals' => [
            'vertical_position' => ['curve' => $zeroCurve],
            'horizontal_position_centered' => [
                'curve' => [
                    ['x' => 0.0, 'y' => $centeredPoints],
                    ['x' => 1.0, 'y' => $centeredPoints],
                ],
            ],
            'horizontal_position_left_aligned' => [
                'curve' => [
                    ['x' => 0.0, 'y' => $leftAlignedPoints],
                    ['x' => 4.0, 'y' => $leftAlignedPoints],
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
            'bbox' => ['x0' => 40.0, 'y0' => 80.0, 'x1' => 280.0, 'y1' => 110.0],
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
                'bbox' => ['x0' => 40.0, 'y0' => 80.0, 'x1' => 280.0, 'y1' => 110.0],
            ]],
        ]],
        $heuristics,
        [],
        [
            1 => [
                'leftMargin' => [
                    'available' => true,
                    'x' => 40.0,
                ],
            ],
        ]
    );
}

$leftWins = title_horizontal_signal_result(18.0, 27.0);
assert_title_horizontal_position(
    abs((float) ($leftWins['score'] ?? 0.0) - 27.0) < 0.0001,
    'Horizontal title signals must use only the highest score, not the sum.'
);
assert_title_horizontal_position(
    ($leftWins['horizontalPositionWinner'] ?? null) === 'left_aligned',
    'Left-aligned signal should be recorded as winner when it scores highest.'
);
assert_title_horizontal_position(
    count(array_filter(
        $leftWins['signals'] ?? [],
        static fn(mixed $signal): bool => is_array($signal)
            && ($signal['code'] ?? null) === 'horizontal_position_left_aligned'
            && ($signal['type'] ?? null) !== 'ignored'
    )) === 1,
    'The winning left-aligned signal should be used.'
);
assert_title_horizontal_position(
    count(array_filter(
        $leftWins['signals'] ?? [],
        static fn(mixed $signal): bool => is_array($signal)
            && ($signal['code'] ?? null) === 'horizontal_position_centered'
    )) === 0,
    'The losing centered signal should not be shown in the candidate signal list.'
);
$leftWinningSignal = array_values(array_filter(
    $leftWins['signals'] ?? [],
    static fn(mixed $signal): bool => is_array($signal)
        && ($signal['code'] ?? null) === 'horizontal_position_left_aligned'
))[0] ?? null;
assert_title_horizontal_position(
    is_array($leftWinningSignal)
        && str_contains((string) ($leftWinningSignal['detail'] ?? ''), 'margin_x:40')
        && str_contains((string) ($leftWinningSignal['detail'] ?? ''), 'candidate_x:40')
        && str_contains((string) ($leftWinningSignal['detail'] ?? ''), 'distance:0'),
    'The left-aligned title signal should describe distance from the computed left margin.'
);

$centerWins = title_horizontal_signal_result(29.0, 12.0);
assert_title_horizontal_position(
    abs((float) ($centerWins['score'] ?? 0.0) - 29.0) < 0.0001,
    'Centered and left-aligned title signals must not both contribute.'
);
assert_title_horizontal_position(
    ($centerWins['horizontalPositionWinner'] ?? null) === 'centered',
    'Centered signal should be recorded as winner when it scores highest.'
);
assert_title_horizontal_position(
    count(array_filter(
        $centerWins['signals'] ?? [],
        static fn(mixed $signal): bool => is_array($signal)
            && ($signal['code'] ?? null) === 'horizontal_position_left_aligned'
    )) === 0,
    'The losing left-aligned signal should not be shown in the candidate signal list.'
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
            'horizontal_position_centered' => ['curve' => $zeroCurve],
            'horizontal_position_left_aligned' => ['curve' => $zeroCurve],
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
