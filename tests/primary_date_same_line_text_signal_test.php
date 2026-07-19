<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_primary_date_same_line(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assert_primary_date_same_line_close(float $actual, float $expected, string $message): void
{
    assert_primary_date_same_line(abs($actual - $expected) < 0.0001, $message . " Expected {$expected}, got {$actual}.");
}

function primary_date_same_line_geometry(array $parts, int $pageNumber = 1): array
{
    $text = '';
    $segments = [];
    foreach ($parts as $wordIndex => $part) {
        $partText = (string) ($part['text'] ?? '');
        if ($text !== '') {
            $text .= ' ';
        }
        $start = strlen($text);
        $text .= $partText;
        $segments[] = [
            'text' => $partText,
            'start' => $start,
            'end' => $start + strlen($partText),
            'wordIndex' => $wordIndex,
            'bbox' => [
                'x0' => (float) ($part['x0'] ?? 0.0),
                'y0' => 10.0,
                'x1' => (float) ($part['x1'] ?? 0.0),
                'y1' => 30.0,
            ],
        ];
    }
    return [
        'text' => $text,
        'segments' => $segments,
        'pageNumber' => $pageNumber,
        'pageWidth' => 800.0,
        'pageHeight' => 1000.0,
    ];
}

function primary_date_same_line_candidate(array $geometry, string $raw = '2026-05-17'): array
{
    $text = (string) ($geometry['text'] ?? '');
    $start = strpos($text, $raw);
    if (!is_int($start)) {
        throw new RuntimeException('Test date not found in OCR row.');
    }
    return [
        'value' => '2026-05-17',
        'raw' => $raw,
        'line' => $text,
        'lineIndex' => 0,
        'start' => $start,
        'end' => $start + strlen($raw),
    ];
}

$longGeometry = primary_date_same_line_geometry([
    ['text' => 'Beslut', 'x0' => 20, 'x1' => 80],
    ['text' => 'om', 'x0' => 86, 'x1' => 106],
    ['text' => 'ekonomiskt', 'x0' => 112, 'x1' => 202],
    ['text' => 'bistånd', 'x0' => 208, 'x1' => 268],
    ['text' => '–', 'x0' => 274, 'x1' => 284],
    ['text' => '2026-05-17', 'x0' => 290, 'x1' => 390],
]);
$longCandidate = primary_date_same_line_candidate($longGeometry);
$longSignal = primary_date_same_line_text_signal($longCandidate, [$longGeometry]);
assert_primary_date_same_line(is_array($longSignal), 'A candidate with OCR geometry must receive same-line analysis.');
assert_primary_date_same_line(
    ($longSignal['textBefore'] ?? null) === 'Beslut om ekonomiskt bistånd –'
        && ($longSignal['textAfter'] ?? null) === '',
    'Debug must expose OCR text before and after the date separately.'
);
assert_primary_date_same_line(
    (int) ($longSignal['unweightedBefore'] ?? 0) > 20
        && (int) ($longSignal['unweightedAfter'] ?? -1) === 0,
    'Only real non-whitespace text outside the date candidate should be counted.'
);
assert_primary_date_same_line(
    (float) ($longSignal['weightedCharacterCount'] ?? 0.0) > 10.0
        && (float) ($longSignal['weightedCharacterCount'] ?? 0.0) < (float) ($longSignal['unweightedCharacterCount'] ?? 0.0),
    'Long same-line text must contribute continuously with distance falloff.'
);

$nearGeometry = primary_date_same_line_geometry([
    ['text' => 'Text', 'x0' => 200, 'x1' => 240],
    ['text' => '2026-05-17', 'x0' => 244, 'x1' => 344],
]);
$farGeometry = primary_date_same_line_geometry([
    ['text' => 'Text', 'x0' => 20, 'x1' => 60],
    ['text' => '2026-05-17', 'x0' => 500, 'x1' => 600],
]);
$nearSignal = primary_date_same_line_text_signal(primary_date_same_line_candidate($nearGeometry), [$nearGeometry]);
$farSignal = primary_date_same_line_text_signal(primary_date_same_line_candidate($farGeometry), [$farGeometry]);
assert_primary_date_same_line(
    (float) ($nearSignal['weightedCharacterCount'] ?? 0.0) > (float) ($farSignal['weightedCharacterCount'] ?? 0.0),
    'Text close to the date must have greater weight than identical text far away on the same OCR row.'
);
assert_primary_date_same_line_close(
    (float) ($farSignal['weightedCharacterCount'] ?? -1.0),
    0.0,
    'Text beyond the monotonic falloff range should have no influence.'
);

$dateOnlyGeometry = primary_date_same_line_geometry([
    ['text' => '2026-05-17', 'x0' => 100, 'x1' => 200],
]);
$dateOnlySignal = primary_date_same_line_text_signal(
    primary_date_same_line_candidate($dateOnlyGeometry),
    [$dateOnlyGeometry]
);
assert_primary_date_same_line_close(
    (float) ($dateOnlySignal['signalValue'] ?? -1.0),
    0.0,
    'The candidate date bboxes must never count as same-line text.'
);

$multiBboxDateGeometry = primary_date_same_line_geometry([
    ['text' => 'maj', 'x0' => 100, 'x1' => 130],
    ['text' => '2026', 'x0' => 160, 'x1' => 205],
]);
$multiBboxDateCandidate = primary_date_same_line_candidate($multiBboxDateGeometry, 'maj 2026');
$multiBboxDateCandidate['value'] = '2026-05-01';
$multiBboxDateSignal = primary_date_same_line_text_signal(
    $multiBboxDateCandidate,
    [$multiBboxDateGeometry]
);
assert_primary_date_same_line_close(
    (float) ($multiBboxDateSignal['signalValue'] ?? -1.0),
    0.0,
    'All bboxes in a multi-bbox date candidate must be excluded using the full candidate span.'
);

$placeGeometry = primary_date_same_line_geometry([
    ['text' => 'Karlstad', 'x0' => 100, 'x1' => 170],
    ['text' => '2026-05-17', 'x0' => 176, 'x1' => 276],
]);
$placeCandidate = primary_date_same_line_candidate($placeGeometry);
$placeMatch = primary_date_place_distance_match($placeCandidate, [$placeGeometry['text']], [$placeGeometry]);
assert_primary_date_same_line(
    is_array($placeMatch) && ($placeMatch['context'] ?? null) === 'same_line_before',
    'The test locality must be identified by the existing place signal.'
);
$placeSignal = primary_date_same_line_text_signal($placeCandidate, [$placeGeometry], $placeMatch);
assert_primary_date_same_line_close(
    (float) ($placeSignal['signalValue'] ?? -1.0),
    0.0,
    'A locality identified by the existing place signal must be excluded from same-line text.'
);
assert_primary_date_same_line(
    ($placeSignal['ignoredPlace'] ?? null) === 'Karlstad'
        && (int) ($placeSignal['ignoredPlaceCharacters'] ?? 0) === 8,
    'Debug must identify the ignored locality and its excluded character count.'
);

$labelGeometry = primary_date_same_line_geometry([
    ['text' => 'Datum:', 'x0' => 100, 'x1' => 155],
    ['text' => '2026-05-17', 'x0' => 161, 'x1' => 261],
]);
$labelCandidate = primary_date_same_line_candidate($labelGeometry);
$labelMatch = [
    'matchedText' => 'Datum',
    'lineIndex' => 0,
    'start' => 0,
    'end' => strlen('Datum'),
    'confidence' => 1.0,
];
$labelSignal = primary_date_same_line_text_signal($labelCandidate, [$labelGeometry], null, $labelMatch);
assert_primary_date_same_line_close(
    (float) ($labelSignal['signalValue'] ?? -1.0),
    0.0,
    'An established date label on the same row must be excluded from ordinary same-line text.'
);
assert_primary_date_same_line(
    ($labelSignal['ignoredDateLabel'] ?? null) === 'Datum:'
        && (int) ($labelSignal['ignoredDateLabelCharacters'] ?? 0) === 6,
    'Debug must identify the ignored date label including its delimiter.'
);

$disabledSignals = [];
foreach (array_keys(default_primary_date_heuristics()['bonuses']) as $signalCode) {
    $disabledSignals['bonuses'][$signalCode] = ['enabled' => false];
}
foreach (array_keys(default_primary_date_heuristics()['penalties']) as $signalCode) {
    $disabledSignals['penalties'][$signalCode] = ['enabled' => false];
}
$disabledSignals['penalties']['same_line_text'] = [
    'enabled' => true,
    'curve' => [
        ['x' => 0.0, 'y' => 0.0],
        ['x' => 50.0, 'y' => -100.0],
    ],
];
$labelScored = score_primary_date_candidate(
    $labelCandidate,
    [$labelGeometry['text']],
    [$labelGeometry],
    $disabledSignals
);
assert_primary_date_same_line(
    ($labelScored['sameLineText']['ignoredDateLabel'] ?? null) === 'Datum:'
        && (float) ($labelScored['sameLineTextWeightedCharacters'] ?? -1.0) === 0.0,
    'Scoring must reuse the existing date-label matcher when excluding same-line text.'
);
$scored = score_primary_date_candidate(
    $longCandidate,
    [$longGeometry['text']],
    [$longGeometry],
    $disabledSignals
);
$scoredSignal = array_values(array_filter(
    $scored['signals'] ?? [],
    static fn(mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'same_line_text'
))[0] ?? null;
assert_primary_date_same_line(is_array($scoredSignal), 'Primary date scoring must emit the new same-line signal.');
$weightedValue = (float) ($scored['sameLineTextWeightedCharacters'] ?? 0.0);
assert_primary_date_same_line_close(
    (float) ($scoredSignal['score'] ?? 0.0),
    -2.0 * $weightedValue,
    'The adjustable curve must receive weighted same-line characters as its input.'
);
assert_primary_date_same_line(
    is_array($scoredSignal['debug'] ?? null)
        && ($scoredSignal['debug']['textBefore'] ?? null) === 'Beslut om ekonomiskt bistånd –',
    'The emitted signal must include structured debug data.'
);

$matches = primary_date_result_matches(['candidates' => [$scored]], [$longGeometry]);
$snapshot = debug_export_accepted_candidate($matches[0], 0);
assert_primary_date_same_line(
    is_array($snapshot['sameLineText'] ?? null)
        && is_numeric($snapshot['sameLineTextWeightedCharacters'] ?? null)
        && is_array($snapshot['signals'][0]['debug'] ?? null),
    'Snapshots must include same-line values, signal score, and structured debug data.'
);

fwrite(STDOUT, "primary date same-line text signal tests passed\n");
