<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_title_ocr(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assert_title_ocr_close(float $actual, float $expected, string $message, float $epsilon = 0.000001): void
{
    assert_title_ocr(abs($actual - $expected) <= $epsilon, $message . " Expected {$expected}, got {$actual}.");
}

function title_ocr_disabled_signals(bool $ocrEnabled = true): array
{
    $signals = [];
    foreach (array_keys(default_title_heuristics()['signals']) as $code) {
        $signals[$code] = ['enabled' => false];
    }
    $signals['ocr_confidence'] = ['enabled' => $ocrEnabled];
    return $signals;
}

function title_ocr_single(float|null $confidence, array $signalOverrides = []): array
{
    $segment = [
        'text' => 'Rubrik',
        'start' => 0,
        'end' => 6,
        'wordIndex' => 0,
        'bbox' => ['x0' => 10.0, 'y0' => 10.0, 'x1' => 110.0, 'y1' => 30.0],
    ];
    if ($confidence !== null) {
        $segment['ocrConfidence'] = $confidence;
    }
    $candidate = [
        'value' => 'Rubrik',
        'raw' => 'Rubrik',
        'line' => 'Rubrik',
        'lineIndex' => 0,
        'start' => 0,
        'end' => 6,
        'bbox' => $segment['bbox'],
        'valueBBoxIndexes' => [1],
        'pageNumber' => 1,
    ];
    $geometry = [[
        'text' => 'Rubrik',
        'segments' => [$segment],
        'pageNumber' => 1,
        'pageWidth' => 600.0,
        'pageHeight' => 800.0,
    ]];
    $signals = title_ocr_disabled_signals();
    $signals['ocr_confidence'] = array_merge($signals['ocr_confidence'], $signalOverrides);
    return score_title_candidate($candidate, ['Rubrik'], $geometry, ['signals' => $signals]);
}

function title_ocr_signal(array $scored): array
{
    foreach ($scored['signals'] ?? [] as $signal) {
        if (is_array($signal) && ($signal['code'] ?? null) === 'ocr_confidence') {
            return $signal;
        }
    }
    throw new RuntimeException('OCR-säkerhet signal saknas.');
}

foreach ([
    [0.58, -40.0],
    [0.20, -40.0],
    [0.80, 0.0],
    [0.95, 0.0],
    [0.69, -20.0],
    [0.60, -36.3636363636],
    [0.0, -40.0],
] as [$confidence, $expectedScore]) {
    $scored = title_ocr_single($confidence);
    $signal = title_ocr_signal($scored);
    assert_title_ocr_close((float) ($signal['score'] ?? NAN), $expectedScore, "Kurvan gav fel poäng för confidence {$confidence}.", 0.00001);
    assert_title_ocr_close((float) ($scored['score'] ?? NAN), $expectedScore, 'OCR-signalen ska adderas till den vanliga Rubrik-poängsumman.', 0.00001);
    assert_title_ocr((float) ($signal['score'] ?? 0.0) <= 0.0, 'Hög OCR-confidence får inte ge positiv poäng.');
}

$single = title_ocr_single(0.71);
$singleSignal = title_ocr_signal($single);
assert_title_ocr_close((float) ($single['candidateOcrConfidence'] ?? NAN), 0.71, 'En bbox ska använda bboxens confidence direkt.');
assert_title_ocr(($singleSignal['debug']['aggregationMethod'] ?? null) === 'single_bbox_direct', 'Debug ska redovisa direkt aggregering för en bbox.');

$missing = title_ocr_single(null);
$missingSignal = title_ocr_signal($missing);
assert_title_ocr_close((float) ($missingSignal['score'] ?? NAN), 0.0, 'Saknad OCR-confidence ska vara neutral.');
assert_title_ocr(($missingSignal['debug']['aggregationMethod'] ?? null) === 'missing_ocr_confidence', 'Saknad confidence ska framgå av debug.');
assert_title_ocr(array_key_exists('candidateOcrConfidence', $missing) && $missing['candidateOcrConfidence'] === null, 'Saknad confidence får inte konverteras till explicit noll.');

$dcSegments = [
    [
        'text' => 'D', 'start' => 0, 'end' => 1, 'wordIndex' => 2, 'ocrConfidence' => 0.55226,
        'bbox' => ['x0' => 10.0, 'y0' => 10.0, 'x1' => 310.0, 'y1' => 110.0],
    ],
    [
        'text' => 'C', 'start' => 1, 'end' => 2, 'wordIndex' => 3, 'ocrConfidence' => 0.55943,
        'bbox' => ['x0' => 311.0, 'y0' => 10.0, 'x1' => 321.0, 'y1' => 20.0],
    ],
];
$dcCandidate = [
    'value' => 'DC', 'raw' => 'DC', 'line' => 'DC', 'lineIndex' => 0, 'start' => 0, 'end' => 2,
    'bbox' => ['x0' => 10.0, 'y0' => 10.0, 'x1' => 321.0, 'y1' => 110.0],
    'valueBBoxIndexes' => [3, 4], 'pageNumber' => 1,
];
$dcGeometry = [[
    'text' => 'DC', 'segments' => $dcSegments, 'pageNumber' => 1, 'pageWidth' => 1000.0, 'pageHeight' => 1400.0,
]];
$dcEnabled = score_title_candidate($dcCandidate, ['DC'], $dcGeometry, ['signals' => title_ocr_disabled_signals(true)]);
$dcDisabled = score_title_candidate($dcCandidate, ['DC'], $dcGeometry, ['signals' => title_ocr_disabled_signals(false)]);
$dcSignal = title_ocr_signal($dcEnabled);
$expectedDcConfidence = ((0.55226 * title_character_weight_sum('D')) + (0.55943 * title_character_weight_sum('C')))
    / (title_character_weight_sum('D') + title_character_weight_sum('C'));
assert_title_ocr_close((float) ($dcEnabled['candidateOcrConfidence'] ?? NAN), $expectedDcConfidence, 'DC ska använda implementationens faktiska teckenvikter.');
assert_title_ocr_close((float) ($dcSignal['score'] ?? NAN), -40.0, 'Det verkliga DC-fallet ska få −40.');
assert_title_ocr_close((float) ($dcEnabled['score'] ?? NAN), (float) ($dcDisabled['score'] ?? NAN) - 40.0, 'DC-totalpoängen ska minska med exakt 40.');
assert_title_ocr(($dcSignal['debug']['aggregationMethod'] ?? null) === 'character_weighted_mean', 'DC ska redovisa teckenviktat medelvärde.');
assert_title_ocr(count($dcSignal['debug']['candidateBboxes'] ?? []) === 2, 'DC-debug ska innehålla båda bboxarna.');
assert_title_ocr_close((float) ($dcSignal['debug']['candidateBboxes'][0]['ocrConfidence'] ?? NAN), 0.55226, 'D-confidence ska bevaras i debug.');
assert_title_ocr_close((float) ($dcSignal['debug']['candidateBboxes'][1]['ocrConfidence'] ?? NAN), 0.55943, 'C-confidence ska bevaras i debug.');
assert_title_ocr_close((float) ($dcSegments[0]['ocrConfidence'] ?? NAN), 0.55226, 'Ursprunglig OCR-confidence får inte ändras.');
assert_title_ocr_close((float) ($dcSegments[1]['ocrConfidence'] ?? NAN), 0.55943, 'Ursprunglig OCR-confidence får inte ändras.');
foreach (['curveInput', 'curveScore', 'lowerThreshold', 'upperThreshold', 'minimumScore', 'maximumScore'] as $debugKey) {
    assert_title_ocr(array_key_exists($debugKey, $dcSignal['debug'] ?? []), "Debug saknar {$debugKey}.");
}
foreach (['bboxIndex', 'text', 'ocrConfidence', 'characterWeight', 'weightRatio', 'weightedContribution'] as $debugKey) {
    assert_title_ocr(array_key_exists($debugKey, $dcSignal['debug']['candidateBboxes'][0] ?? []), "BBox-debug saknar {$debugKey}.");
}

$longShortCandidate = [
    'value' => 'A MMMMM', 'raw' => 'A MMMMM', 'line' => 'A MMMMM', 'lineIndex' => 0, 'start' => 0, 'end' => 7,
    'bbox' => ['x0' => 0.0, 'y0' => 0.0, 'x1' => 1000.0, 'y1' => 100.0],
    'valueBBoxIndexes' => [1, 2], 'pageNumber' => 1,
];
$longShortGeometry = [[
    'text' => 'A MMMMM', 'pageNumber' => 1, 'pageWidth' => 1200.0, 'pageHeight' => 1600.0,
    'segments' => [
        ['text' => 'A', 'start' => 0, 'end' => 1, 'wordIndex' => 0, 'ocrConfidence' => 0.0, 'bbox' => ['x0' => 0.0, 'y0' => 0.0, 'x1' => 900.0, 'y1' => 100.0]],
        ['text' => 'MMMMM', 'start' => 2, 'end' => 7, 'wordIndex' => 1, 'ocrConfidence' => 1.0, 'bbox' => ['x0' => 901.0, 'y0' => 0.0, 'x1' => 910.0, 'y1' => 10.0]],
    ],
]];
$longShort = title_candidate_ocr_confidence_analysis($longShortCandidate, $longShortGeometry, $longShortCandidate['bbox']);
$expectedLongShort = title_character_weight_sum('MMMMM') / (title_character_weight_sum('A') + title_character_weight_sum('MMMMM'));
assert_title_ocr_close((float) ($longShort['candidateOcrConfidence'] ?? NAN), $expectedLongShort, 'Olika textlängd ska viktas efter tecken, inte bbox-area.');
assert_title_ocr((float) ($longShort['candidateOcrConfidence'] ?? 0.0) > 0.8, 'Den långa lilla bboxens text ska väga mer än den korta stora bboxen.');

$multilineCandidate = [
    'value' => 'Första andra', 'raw' => 'Första andra', 'lineIndex' => 0, 'start' => 0, 'end' => 6,
    'bbox' => ['x0' => 10.0, 'y0' => 10.0, 'x1' => 150.0, 'y1' => 60.0],
    'valueBBoxIndexes' => [1, 2], 'pageNumber' => 1,
    'blockParts' => [
        ['lineIndex' => 0, 'start' => 0, 'end' => 6, 'text' => 'Första', 'bbox' => ['x0' => 10.0, 'y0' => 10.0, 'x1' => 100.0, 'y1' => 30.0]],
        ['lineIndex' => 1, 'start' => 0, 'end' => 5, 'text' => 'andra', 'bbox' => ['x0' => 10.0, 'y0' => 40.0, 'x1' => 90.0, 'y1' => 60.0]],
    ],
];
$multilineGeometry = [
    ['text' => 'Första', 'pageNumber' => 1, 'pageWidth' => 600.0, 'pageHeight' => 800.0, 'segments' => [[
        'text' => 'Första', 'start' => 0, 'end' => 6, 'wordIndex' => 0, 'ocrConfidence' => 0.6,
        'bbox' => ['x0' => 10.0, 'y0' => 10.0, 'x1' => 100.0, 'y1' => 30.0],
    ]]],
    ['text' => 'andra', 'pageNumber' => 1, 'pageWidth' => 600.0, 'pageHeight' => 800.0, 'segments' => [[
        'text' => 'andra', 'start' => 0, 'end' => 5, 'wordIndex' => 1, 'ocrConfidence' => 0.9,
        'bbox' => ['x0' => 10.0, 'y0' => 40.0, 'x1' => 90.0, 'y1' => 60.0],
    ]]],
];
$multiline = title_candidate_ocr_confidence_analysis($multilineCandidate, $multilineGeometry, $multilineCandidate['bbox']);
assert_title_ocr(count($multiline['candidateBboxes'] ?? []) === 2, 'Flerradig kandidat ska använda bboxar från samtliga rader.');
assert_title_ocr(array_column($multiline['candidateBboxes'], 'lineIndex') === [0, 1], 'Flerradig debug ska redovisa båda radindexen.');

$custom = normalize_title_heuristics(['signals' => ['ocr_confidence' => [
    'enabled' => false,
    'curve' => [['x' => 0.4, 'y' => -70], ['x' => 0.9, 'y' => 5]],
]]]);
assert_title_ocr(($custom['signals']['ocr_confidence']['enabled'] ?? true) === false, 'Aktiveringsläget ska kunna sparas och hämtas.');
assert_title_ocr(($custom['signals']['ocr_confidence']['curve'] ?? null) === [['x' => 0.4, 'y' => -70.0], ['x' => 0.9, 'y' => 5.0]], 'Anpassad kurva ska kunna sparas och hämtas.');
$legacy = normalize_title_heuristics(['signals' => []]);
assert_title_ocr(($legacy['signals']['ocr_confidence']['curve'] ?? null) === [['x' => 0.58, 'y' => -40.0], ['x' => 0.8, 'y' => 0.0]], 'Äldre konfiguration ska få säker standardkurva.');
assert_title_ocr(!isset(default_primary_date_heuristics()['bonuses']['ocr_confidence']) && !isset(default_primary_date_heuristics()['penalties']['ocr_confidence']), 'Signalen får inte införas för andra datafält.');

$matches = title_result_matches(['candidates' => [$dcEnabled]], $dcGeometry);
$snapshot = debug_export_accepted_candidate($matches[0], 0);
assert_title_ocr_close((float) ($snapshot['candidateOcrConfidence'] ?? NAN), $expectedDcConfidence, 'Snapshots ska innehålla kandidatens OCR-confidence.');
assert_title_ocr(is_array($snapshot['ocrConfidenceAnalysis'] ?? null), 'Snapshots ska innehålla OCR-analysdata.');
assert_title_ocr(is_array($snapshot['signals'][0]['debug'] ?? null), 'Snapshots ska innehålla signalens strukturerade debug.');

$appSource = file_get_contents(__DIR__ . '/../public/app.js');
assert_title_ocr(is_string($appSource) && str_contains($appSource, "ocr_confidence: 'OCR-säkerhet'"), 'Frontend ska visa signalens svenska namn.');
assert_title_ocr(is_string($appSource) && str_contains($appSource, "xAxisTitle: 'OCR-confidence (%)'"), 'Frontend ska använda den generella kurvredigeraren för OCR-confidence.');

fwrite(STDOUT, "title OCR confidence signal tests passed\n");
