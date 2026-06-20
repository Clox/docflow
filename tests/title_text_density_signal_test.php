<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_title_text_density(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function title_text_density_candidate(): array
{
    return [
        'value' => 'UTTAGSTILLSTÅND',
        'raw' => 'UTTAGSTILLSTÅND',
        'line' => 'UTTAGSTILLSTÅND',
        'lineIndex' => 0,
        'start' => 0,
        'end' => strlen('UTTAGSTILLSTÅND'),
        'bbox' => ['x0' => 20.0, 'y0' => 20.0, 'x1' => 180.0, 'y1' => 40.0],
        'valueBBoxIndexes' => [1],
        'pageNumber' => 1,
    ];
}

function title_text_density_geometry(array $extraSegments): array
{
    return [
        [
            'text' => 'UTTAGSTILLSTÅND',
            'pageNumber' => 1,
            'pageWidth' => 600.0,
            'pageHeight' => 800.0,
            'segments' => [[
                'start' => 0,
                'end' => strlen('UTTAGSTILLSTÅND'),
                'wordIndex' => 0,
                'bbox' => ['x0' => 20.0, 'y0' => 20.0, 'x1' => 180.0, 'y1' => 40.0],
            ]],
        ],
        [
            'text' => 'Närliggande text',
            'pageNumber' => 1,
            'pageWidth' => 600.0,
            'pageHeight' => 800.0,
            'segments' => $extraSegments,
        ],
    ];
}

$candidate = title_text_density_candidate();
$emptyDensity = title_candidate_text_density($candidate, title_text_density_geometry([]), 3.0);
assert_title_text_density(
    abs((float) ($emptyDensity['ratio'] ?? -1.0)) < 0.0001,
    'The candidate bbox must not count toward its own text density.'
);

$nearDensity = title_candidate_text_density(
    $candidate,
    title_text_density_geometry([[
        'start' => 0,
        'end' => 4,
        'wordIndex' => 1,
        'bbox' => ['x0' => 182.0, 'y0' => 20.0, 'x1' => 242.0, 'y1' => 40.0],
    ]]),
    3.0
);
$farDensity = title_candidate_text_density(
    $candidate,
    title_text_density_geometry([[
        'start' => 0,
        'end' => 4,
        'wordIndex' => 1,
        'bbox' => ['x0' => 260.0, 'y0' => 20.0, 'x1' => 320.0, 'y1' => 40.0],
    ]]),
    3.0
);
assert_title_text_density(
    (float) ($nearDensity['ratio'] ?? 0.0) > (float) ($farDensity['ratio'] ?? 0.0),
    'Linear edge-distance falloff should give a nearby bbox more weight than a distant bbox.'
);
assert_title_text_density(
    abs((float) ($farDensity['ratio'] ?? -1.0)) < 0.0001,
    'Bboxes at or beyond the configured maximum distance must not contribute.'
);

$smallHeaderSegments = [];
$headerLabels = ['Bankens ex', 'Sida 1 av 1', 'Akt', 'Personnr', 'Diarienr'];
foreach ($headerLabels as $index => $label) {
    $smallHeaderSegments[] = [
        'start' => $index * 12,
        'end' => ($index * 12) + strlen($label),
        'wordIndex' => $index + 1,
        'bbox' => [
            'x0' => 205.0,
            'y0' => 16.0 + ($index * 11.0),
            'x1' => 205.0 + max(24.0, strlen($label) * 5.0),
            'y1' => 24.0 + ($index * 11.0),
        ],
    ];
}
$separateHeaderDensity = title_candidate_text_density(
    $candidate,
    title_text_density_geometry($smallHeaderSegments),
    3.0
);
assert_title_text_density(
    (float) ($separateHeaderDensity['ratio'] ?? 1.0) < 0.20,
    'A nearby block of substantially smaller header text should not automatically produce high density.'
);

$denseSegments = [];
for ($index = 0; $index < 24; $index++) {
    $column = $index % 4;
    $row = intdiv($index, 4);
    $denseSegments[] = [
        'start' => $index * 5,
        'end' => ($index * 5) + 4,
        'wordIndex' => $index + 1,
        'bbox' => [
            'x0' => 0.0 + ($column * 4.0),
            'y0' => 14.0 + ($row * 5.0),
            'x1' => 16.0 + ($column * 4.0),
            'y1' => 28.0 + ($row * 5.0),
        ],
    ];
}
$denseTextDensity = title_candidate_text_density(
    $candidate,
    title_text_density_geometry($denseSegments),
    3.0
);
assert_title_text_density(
    (float) ($denseTextDensity['ratio'] ?? 0.0) > 0.40,
    'Many nearby text bboxes should produce a high, comparable density value.'
);

$belowDensity = title_candidate_text_density(
    $candidate,
    title_text_density_geometry([[
        'start' => 0,
        'end' => 4,
        'wordIndex' => 1,
        'bbox' => ['x0' => 20.0, 'y0' => 43.0, 'x1' => 180.0, 'y1' => 63.0],
    ]]),
    3.0
);
assert_title_text_density(
    abs((float) ($belowDensity['ratio'] ?? -1.0)) < 0.0001,
    'Text below a title candidate must not contribute to text density.'
);
assert_title_text_density(
    (int) ($belowDensity['ignoredBelowBboxes'] ?? 0) === 1,
    'Text density debug data should count bboxes ignored below the candidate.'
);

$extremeDensity = title_candidate_text_density(
    $candidate,
    title_text_density_geometry([[
        'start' => 0,
        'end' => 4,
        'wordIndex' => 1,
        'bbox' => ['x0' => 181.0, 'y0' => 20.0, 'x1' => 581.0, 'y1' => 40.0],
    ]]),
    3.0
);
assert_title_text_density(
    (float) ($extremeDensity['ratio'] ?? 1.0) <= 0.1201,
    'A single extreme bbox must be capped to a small share of the reference area.'
);

$normalized = normalize_title_heuristics([
    'signals' => [
        'text_density' => [
            'horizontal_max_distance_line_heights' => 4.5,
            'vertical_max_distance_line_heights' => 2.5,
            'left_weight' => 0.8,
            'right_weight' => 0.9,
            'above_weight' => 0.2,
        ],
    ],
]);
assert_title_text_density(
    (float) ($normalized['signals']['text_density']['horizontal_max_distance_line_heights'] ?? 0.0) === 4.5
        && (float) ($normalized['signals']['text_density']['vertical_max_distance_line_heights'] ?? 0.0) === 2.5,
    'The Title text density horizontal and vertical maximum distance settings should be preserved.'
);
assert_title_text_density(
    (float) ($normalized['signals']['text_density']['left_weight'] ?? 0.0) === 0.8
        && (float) ($normalized['signals']['text_density']['right_weight'] ?? 0.0) === 0.9
        && (float) ($normalized['signals']['text_density']['above_weight'] ?? 0.0) === 0.2,
    'The Title text density direction weights should be preserved.'
);

$scored = score_title_candidate(
    $candidate,
    ['UTTAGSTILLSTÅND', 'Bankens ex Sida 1 av 1 Akt Personnr Diarienr'],
    title_text_density_geometry($smallHeaderSegments),
    [
        'signals' => [
            'text_density' => [
                'horizontal_max_distance_line_heights' => 3.0,
                'vertical_max_distance_line_heights' => 2.0,
                'curve' => [
                    ['x' => 0.0, 'y' => 1.0],
                    ['x' => 1.0, 'y' => -100.0],
                ],
            ],
        ],
    ]
);
$densitySignal = array_values(array_filter(
    $scored['signals'] ?? [],
    static fn (mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'text_density'
))[0] ?? null;
assert_title_text_density(is_array($densitySignal), 'Title scoring should emit the text density signal.');
$detail = (string) ($densitySignal['detail'] ?? '');
assert_title_text_density(str_contains($detail, 'density:'), 'Debug detail should contain the visual density.');
assert_title_text_density(
    str_contains($detail, 'horizontal_distance:3')
        && str_contains($detail, 'vertical_distance:2')
        && str_contains($detail, 'ignored_below:'),
    'Debug detail should contain configured directional distances and ignored-below count.'
);
assert_title_text_density(!str_contains($detail, 'words:'), 'Debug detail must no longer be word-count based.');

fwrite(STDOUT, "title text density signal tests passed\n");
