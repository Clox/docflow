<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_title_zone_overlap(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$candidate = [
    'value' => 'Dokumentets rubrik',
    'raw' => 'Dokumentets rubrik',
    'line' => 'Dokumentets rubrik',
    'lineIndex' => 0,
    'start' => 0,
    'end' => strlen('Dokumentets rubrik'),
    'bbox' => ['x0' => 100.0, 'y0' => 100.0, 'x1' => 300.0, 'y1' => 200.0],
    'valueBBoxIndexes' => [1, 2],
    'pageNumber' => 1,
];
$zones = [
    [
        'type' => 'zone',
        'zoneName' => 'Vanlig zon',
        'pageNumber' => 1,
        'boundingRect' => ['x0' => 100.0, 'y0' => 100.0, 'x1' => 200.0, 'y1' => 200.0],
    ],
    [
        'type' => 'systemzone',
        'isSystemZone' => true,
        'zoneName' => 'Svag systemzon',
        'confidence' => 0.4,
        'pageNumber' => 1,
        'boundingRect' => ['x0' => 100.0, 'y0' => 100.0, 'x1' => 300.0, 'y1' => 200.0],
    ],
    [
        'type' => 'systemzone',
        'isSystemZone' => true,
        'zoneName' => 'Avsändarblock',
        'confidence' => 0.9,
        'pageNumber' => 1,
        'boundingRect' => ['x0' => 100.0, 'y0' => 100.0, 'x1' => 260.0, 'y1' => 200.0],
    ],
    [
        'type' => 'zone',
        'zoneName' => 'Zon på annan sida',
        'pageNumber' => 2,
        'boundingRect' => ['x0' => 100.0, 'y0' => 100.0, 'x1' => 300.0, 'y1' => 200.0],
    ],
];

$best = title_candidate_zone_overlap($candidate, $zones);
assert_title_zone_overlap(is_array($best), 'An overlapping zone should produce overlap analysis.');
assert_title_zone_overlap(($best['zoneName'] ?? null) === 'Avsändarblock', 'The zone with the strongest effective overlap should win.');
assert_title_zone_overlap(abs((float) ($best['candidateOverlap'] ?? 0.0) - 0.8) < 0.000001, 'Raw overlap should be intersection divided by candidate area.');
assert_title_zone_overlap(abs((float) ($best['effectiveOverlap'] ?? 0.0) - 0.72) < 0.000001, 'System-zone overlap should be weighted by confidence.');
assert_title_zone_overlap(abs((float) ($best['zoneConfidence'] ?? 0.0) - 0.9) < 0.000001, 'Winning system-zone confidence should be retained for debug.');

$ordinary = title_candidate_zone_overlap($candidate, [$zones[0]]);
assert_title_zone_overlap(abs((float) ($ordinary['effectiveOverlap'] ?? 0.0) - 0.5) < 0.000001, 'Ordinary zones should be treated as fully confident.');
assert_title_zone_overlap(array_key_exists('zoneConfidence', $ordinary) && $ordinary['zoneConfidence'] === null, 'Ordinary zones should not expose a synthetic confidence value.');

$zeroCurve = [['x' => 0.0, 'y' => 0.0], ['x' => 1.0, 'y' => 0.0]];
$geometry = [[
    'text' => 'Dokumentets rubrik',
    'pageNumber' => 1,
    'pageWidth' => 600.0,
    'pageHeight' => 800.0,
    'segments' => [[
        'start' => 0,
        'end' => strlen('Dokumentets rubrik'),
        'wordIndex' => 0,
        'bbox' => $candidate['bbox'],
    ]],
]];
$scored = score_title_candidate($candidate, ['Dokumentets rubrik'], $geometry, [
    'signals' => [
        'vertical_position' => ['curve' => $zeroCurve],
        'horizontal_position' => ['curve' => $zeroCurve],
        'text_size' => ['curve' => $zeroCurve],
        'uppercase_ratio' => ['curve' => $zeroCurve],
        'brevity' => ['curve' => $zeroCurve],
        'text_density' => ['curve' => $zeroCurve],
        'short_line_before_long_line' => ['curve' => $zeroCurve],
        'body_text_before_candidate' => ['curve' => $zeroCurve],
        'zone_overlap' => [
            'curve' => [
                ['x' => 0.0, 'y' => 0.0],
                ['x' => 0.72, 'y' => -70.0],
                ['x' => 1.0, 'y' => -100.0],
            ],
        ],
    ],
], [], [], [], $zones);
$signal = array_values(array_filter(
    $scored['signals'] ?? [],
    static fn(mixed $row): bool => is_array($row) && ($row['code'] ?? null) === 'zone_overlap'
))[0] ?? null;
assert_title_zone_overlap(is_array($signal), 'Title scoring should emit Överlapp med zon.');
assert_title_zone_overlap(abs((float) ($signal['score'] ?? 0.0) - (-70.0)) < 0.0001, 'The configurable curve should receive effective overlap.');
assert_title_zone_overlap(str_contains((string) ($signal['detail'] ?? ''), 'zone_name:Avsändarblock'), 'Signal text should identify the winning zone.');
assert_title_zone_overlap(str_contains((string) ($signal['detail'] ?? ''), 'candidate_overlap:0.8'), 'Signal debug should contain raw overlap.');
assert_title_zone_overlap(str_contains((string) ($signal['detail'] ?? ''), 'effective_overlap:0.72'), 'Signal debug should contain effective overlap.');
assert_title_zone_overlap(is_array($signal['debug'] ?? null), 'Structured signal debug should be retained.');

$matches = title_result_matches(['candidates' => [$scored]], $geometry);
assert_title_zone_overlap(is_array($matches[0]['zoneOverlap'] ?? null), 'Zone-overlap debug should be preserved in extraction-field metadata.');
$snapshotCandidate = debug_export_accepted_candidate($matches[0], 0);
assert_title_zone_overlap(is_array($snapshotCandidate['zoneOverlap'] ?? null), 'Snapshots should include zone-overlap analysis.');
assert_title_zone_overlap(is_array($snapshotCandidate['signals'][0]['debug'] ?? null), 'Snapshots should include structured signal debug.');

fwrite(STDOUT, "title zone overlap signal tests passed\n");
