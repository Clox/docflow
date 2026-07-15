<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_title_body_text_before(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function body_text_geometry(string $text, int $page, float $y, int $firstWordIndex): array
{
    $segments = [];
    $offset = 0;
    foreach (preg_split('/\s+/u', $text) ?: [] as $wordOffset => $word) {
        $start = strpos($text, $word, $offset);
        $start = is_int($start) ? $start : $offset;
        $end = $start + strlen($word);
        $segments[] = [
            'start' => $start,
            'end' => $end,
            'wordIndex' => $firstWordIndex + $wordOffset,
            'bbox' => ['x0' => 40.0 + ($wordOffset * 55.0), 'y0' => $y, 'x1' => 90.0 + ($wordOffset * 55.0), 'y1' => $y + 20.0],
        ];
        $offset = $end;
    }
    return [
        'text' => $text,
        'pageNumber' => $page,
        'pageWidth' => 600.0,
        'pageHeight' => 800.0,
        'segments' => $segments,
    ];
}

$lines = [
    'Detta är lång löpande text från föregående sida',
    'Detta är brödtext ovanför rubriken',
    'Viktig rubrik',
    'Denna text ligger efter kandidaten och får inte räknas',
];
$geometries = [
    body_text_geometry($lines[0], 1, 200.0, 0),
    body_text_geometry($lines[1], 2, 60.0, 20),
    body_text_geometry($lines[2], 2, 140.0, 40),
    body_text_geometry($lines[3], 2, 220.0, 50),
];
$layout = [
    1 => ['leftMargin' => ['available' => true, 'x' => 40.0]],
    2 => ['leftMargin' => ['available' => true, 'x' => 40.0]],
];
$previousIndexes = system_zone_line_bbox_indexes($geometries[0]);
$structures = [[
    'type' => 'Avsändarblock',
    'pageNumber' => 1,
    'bboxIndexes' => $previousIndexes,
    'bbox' => system_zone_line_bbox($geometries[0]),
    'confidence' => 0.5,
]];
$analysis = title_body_text_line_analysis($lines, $geometries, $layout, $structures);

assert_title_body_text_before(count($analysis) === 4, 'Every OCR line should receive internal body-text analysis.');
foreach ($analysis as $row) {
    assert_title_body_text_before(
        (float) ($row['bodyTextLikelihood'] ?? -1.0) >= 0.0 && (float) ($row['bodyTextLikelihood'] ?? 2.0) <= 1.0,
        'bodyTextLikelihood must stay within 0..1.'
    );
}
assert_title_body_text_before(
    abs((float) $analysis[0]['effectiveBodyTextLikelihood'] - ((float) $analysis[0]['bodyTextLikelihood'] * 0.5)) < 0.0002,
    'Structure confidence should multiplicatively reduce effective body-text likelihood.'
);

$candidate = [
    'value' => $lines[2],
    'raw' => $lines[2],
    'line' => $lines[2],
    'lineIndex' => 2,
    'lineIndexes' => [2],
    'start' => 0,
    'end' => strlen($lines[2]),
    'bbox' => system_zone_line_bbox($geometries[2]),
    'valueBBoxIndexes' => system_zone_line_bbox_indexes($geometries[2]),
    'pageNumber' => 2,
];
$summary = title_body_text_before_candidate($candidate, $analysis, [
    'max_characters_per_line' => 20,
    'minimum_body_text_likelihood' => 0.35,
]);

assert_title_body_text_before((int) $summary['contributingLineCount'] === 2, 'Only the previous-page and above-candidate lines should contribute.');
assert_title_body_text_before((float) $summary['previousPagesWeightedTextAmount'] > 0.0, 'Text on previous pages should contribute.');
assert_title_body_text_before((float) $summary['currentPageWeightedTextAmount'] > 0.0, 'Text above the candidate on its page should contribute.');
assert_title_body_text_before((int) $summary['reducedLineCount'] === 1, 'The structure-reduced line should be counted in debug data.');
assert_title_body_text_before(($summary['reducedByStructureTypes']['Avsändarblock'] ?? 0) === 1, 'Debug data should identify the reducing structure type.');
foreach ($summary['basisLines'] as $basisLine) {
    assert_title_body_text_before((float) $basisLine['weightedContribution'] <= 20.0, 'Max characters per line should cap each weighted contribution.');
    assert_title_body_text_before((int) $basisLine['lineIndex'] !== 2 && (int) $basisLine['lineIndex'] !== 3, 'The candidate and following text must not be included.');
}

$zeroCurve = [['x' => 0.0, 'y' => 0.0], ['x' => 1.0, 'y' => 0.0]];
$scored = score_title_candidate($candidate, $lines, $geometries, [
    'signals' => [
        'vertical_position' => ['curve' => $zeroCurve],
        'horizontal_position' => ['curve' => $zeroCurve],
        'text_size' => ['curve' => $zeroCurve],
        'uppercase_ratio' => ['curve' => $zeroCurve],
        'brevity' => ['curve' => $zeroCurve],
        'text_density' => ['curve' => $zeroCurve],
        'short_line_before_long_line' => ['curve' => $zeroCurve],
        'body_text_before_candidate' => [
            'max_characters_per_line' => 20,
            'minimum_body_text_likelihood' => 0.35,
            'curve' => [['x' => 0.0, 'y' => 10.0], ['x' => 10.0, 'y' => -20.0]],
        ],
    ],
], [], $layout, $analysis);
$signal = array_values(array_filter(
    $scored['signals'] ?? [],
    static fn(mixed $row): bool => is_array($row) && ($row['code'] ?? null) === 'body_text_before_candidate'
))[0] ?? null;
assert_title_body_text_before(is_array($signal), 'Title scoring should emit Brödtext före kandidaten.');
assert_title_body_text_before((float) ($signal['score'] ?? 0.0) === -20.0, 'The configurable curve should saturate at its final point.');
assert_title_body_text_before(str_contains((string) ($signal['detail'] ?? ''), 'previous_pages:'), 'Signal debug should distinguish previous pages.');

$matches = title_result_matches(['candidates' => [$scored]], $geometries);
assert_title_body_text_before(is_array($matches[0]['bodyTextBeforeCandidate'] ?? null), 'The signal analysis should be preserved in extraction-field metadata.');
$snapshotCandidate = debug_export_accepted_candidate($matches[0], 0);
assert_title_body_text_before(is_array($snapshotCandidate['bodyTextBeforeCandidate'] ?? null), 'Snapshots should include weighted body-text analysis.');
assert_title_body_text_before(is_array($snapshotCandidate['signals'] ?? null), 'Snapshots should include the signal score and debug detail.');

fwrite(STDOUT, "title body text before candidate signal tests passed\n");
