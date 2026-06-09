<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_primary_date_near_title(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assert_primary_date_near_title_close(float $actual, float $expected, string $message): void
{
    assert_primary_date_near_title(abs($actual - $expected) < 0.0001, $message . " Expected {$expected}, got {$actual}.");
}

$dateBbox = ['x0' => 0.0, 'y0' => 20.0, 'x1' => 100.0, 'y1' => 30.0];
$aboveTitleBbox = ['x0' => 0.0, 'y0' => 8.0, 'x1' => 100.0, 'y1' => 18.0];
$belowTitleBbox = ['x0' => 0.0, 'y0' => 32.0, 'x1' => 100.0, 'y1' => 42.0];

assert_primary_date_near_title_close(
    primary_date_bbox_edge_distance_line_heights($dateBbox, $aboveTitleBbox),
    0.2,
    'Distance above the date should use bbox edges and line heights.'
);
assert_primary_date_near_title_close(
    primary_date_bbox_edge_distance_line_heights($dateBbox, $belowTitleBbox),
    0.2,
    'Direction must not affect the normalized bbox distance.'
);
assert_primary_date_near_title_close(
    primary_date_bbox_edge_distance_line_heights(
        $dateBbox,
        ['x0' => 25.0, 'y0' => 25.0, 'x1' => 75.0, 'y1' => 35.0]
    ),
    0.0,
    'Overlapping bboxes should have zero distance.'
);

$bestMatch = primary_date_near_title_match(
    $dateBbox,
    1,
    [
        [
            'value' => 'Svag nära rubrik',
            'finalConfidence' => 0.50,
            'pageNumber' => 1,
            'valueBbox' => ['x0' => 0.0, 'y0' => 10.0, 'x1' => 100.0, 'y1' => 20.0],
        ],
        [
            'value' => 'UTTAGSTILLSTÅND',
            'finalConfidence' => 0.60,
            'pageNumber' => 1,
            'valueBbox' => $aboveTitleBbox,
        ],
        [
            'value' => 'Under tröskeln',
            'finalConfidence' => 0.49,
            'pageNumber' => 1,
            'valueBbox' => ['x0' => 0.0, 'y0' => 10.0, 'x1' => 100.0, 'y1' => 20.0],
        ],
        [
            'value' => 'Avvisad rubrik',
            'finalConfidence' => 1.0,
            'accepted' => false,
            'pageNumber' => 1,
            'valueBbox' => ['x0' => 0.0, 'y0' => 10.0, 'x1' => 100.0, 'y1' => 20.0],
        ],
        [
            'value' => 'Annan sida',
            'finalConfidence' => 1.0,
            'pageNumber' => 2,
            'valueBbox' => ['x0' => 0.0, 'y0' => 10.0, 'x1' => 100.0, 'y1' => 20.0],
        ],
    ],
    0.5,
    60.0,
    6.0
);

assert_primary_date_near_title(is_array($bestMatch), 'An accepted nearby title should produce a match.');
assert_primary_date_near_title(
    ($bestMatch['title'] ?? null) === 'UTTAGSTILLSTÅND',
    'The title candidate with the highest confidence-weighted proximity score should win.'
);
assert_primary_date_near_title_close(
    (float) ($bestMatch['score'] ?? 0.0),
    60.0 * (1.0 - (0.2 / 6.0)) * 0.60,
    'Signal score should be max points times proximity times title confidence.'
);

$farMatch = primary_date_near_title_match(
    $dateBbox,
    1,
    [[
        'value' => 'Avlägsen rubrik',
        'finalConfidence' => 1.0,
        'pageNumber' => 1,
        'valueBbox' => ['x0' => 0.0, 'y0' => 100.0, 'x1' => 100.0, 'y1' => 110.0],
    ]],
    0.5,
    60.0,
    6.0
);
assert_primary_date_near_title($farMatch === null, 'A title beyond the configured maximum distance should give no score.');

$scoredDate = score_primary_date_candidate(
    [
        'value' => '2026-05-20',
        'raw' => '2026-05-20',
        'line' => '2026-05-20',
        'lineIndex' => 0,
        'start' => 0,
    ],
    ['2026-05-20'],
    [[
        'pageNumber' => 1,
        'pageHeight' => 100.0,
        'segments' => [[
            'start' => 0,
            'end' => 10,
            'bbox' => $dateBbox,
        ]],
    ]],
    [
        'bonuses' => [
            'near_title' => [
                'max_points' => 60.0,
                'max_distance_line_heights' => 6.0,
            ],
        ],
    ],
    [],
    [],
    [[
        'value' => 'UTTAGSTILLSTÅND',
        'finalConfidence' => 0.60,
        'pageNumber' => 1,
        'valueBbox' => $aboveTitleBbox,
    ]],
    0.5
);
$nearTitleSignals = array_values(array_filter(
    $scoredDate['signals'] ?? [],
    static fn (mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'near_title'
));
assert_primary_date_near_title(count($nearTitleSignals) === 1, 'Primary date scoring should emit the near_title signal.');
assert_primary_date_near_title(
    str_contains((string) ($nearTitleSignals[0]['detail'] ?? ''), 'title:UTTAGSTILLSTÅND,confidence:0.6,distance:0.2'),
    'Signal detail should identify the title, confidence, and normalized distance.'
);

$orderedResults = extract_configured_text_field_results(
    ['UTTAGSTILLSTÅND', '2026-05-20'],
    [],
    [
        [
            'key' => 'primary_date',
            'name' => 'Huvuddatum',
            'valueType' => 'date',
            'extractor' => 'primary_date',
        ],
        [
            'key' => 'title',
            'name' => 'Rubrik',
            'valueType' => 'text',
            'extractor' => 'title',
        ],
    ],
    [],
    [
        [
            'pageNumber' => 1,
            'pageHeight' => 100.0,
            'segments' => [[
                'start' => 0,
                'end' => strlen('UTTAGSTILLSTÅND'),
                'bbox' => ['x0' => 0.0, 'y0' => 10.0, 'x1' => 100.0, 'y1' => 20.0],
            ]],
        ],
        [
            'pageNumber' => 1,
            'pageHeight' => 100.0,
            'segments' => [[
                'start' => 0,
                'end' => strlen('2026-05-20'),
                'bbox' => ['x0' => 0.0, 'y0' => 22.0, 'x1' => 100.0, 'y1' => 32.0],
            ]],
        ],
    ],
    0.5
);
$orderedDateSignals = $orderedResults['primary_date']['matches'][0]['signals'] ?? [];
assert_primary_date_near_title(
    count(array_filter(
        is_array($orderedDateSignals) ? $orderedDateSignals : [],
        static fn (mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'near_title'
    )) === 1,
    'Title candidates should be available to primary date even when the date field is configured first.'
);

fwrite(STDOUT, "primary date near title signal tests passed\n");
