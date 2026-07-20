<?php
declare(strict_types=1);

defined('PROJECT_ROOT') || define('PROJECT_ROOT', dirname(__DIR__));
defined('DATA_DIR') || define('DATA_DIR', dirname(__DIR__) . '/data');
require_once __DIR__ . '/../public/api/_functions.php';

function assert_system_zone_sender_block(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

function system_zone_test_geometry(string $text, int $wordIndex, array $bbox): array
{
    return [
        'text' => $text,
        'pageNumber' => 1,
        'pageWidth' => 1000.0,
        'pageHeight' => 1400.0,
        'segments' => [[
            'start' => 0,
            'end' => strlen($text),
            'text' => $text,
            'wordIndex' => $wordIndex,
            'bbox' => $bbox,
            'confidence' => 0.95,
        ]],
    ];
}

function system_zone_test_multi_segment_geometry(array $segments, int $lineIndex): array
{
    $text = implode(' ', array_column($segments, 'text'));
    return [
        'text' => $text,
        'pageNumber' => 1,
        'pageWidth' => 2200.0,
        'pageHeight' => 1400.0,
        'segments' => array_map(static function (array $segment) use ($text): array {
            return [
                'text' => $segment['text'],
                'wordIndex' => $segment['wordIndex'],
                'bbox' => $segment['bbox'],
                'confidence' => 0.95,
            ];
        }, $segments),
    ];
}

function system_zone_job_geometries(string $jobId): array
{
    $jobDir = dirname(__DIR__) . '/jobs/' . $jobId;
    $rapidocrPages = load_job_engine_debug_pages($jobDir, 'rapidocr');
    $tesseractPages = load_job_engine_debug_pages($jobDir, 'tesseract');
    $document = build_merged_objects_document_from_rapidocr_pages($rapidocrPages, $tesseractPages);
    $normalized = normalize_stored_merged_objects_document($document);
    $geometries = [];
    foreach (is_array($normalized['pages'] ?? null) ? $normalized['pages'] : [] as $page) {
        foreach (build_grid_text_lines_from_debug_words($page['words'] ?? []) as $line) {
            $geometries[] = [
                'text' => $line['text'] ?? '',
                'segments' => $line['segments'] ?? [],
                'pageNumber' => $page['pageNumber'] ?? 1,
                'pageWidth' => $page['pageWidth'] ?? null,
                'pageHeight' => $page['pageHeight'] ?? null,
            ];
        }
    }
    return $geometries;
}

$geometries = [
    system_zone_test_geometry('KOMMUNLEDNINGSKONTORET', 0, ['x0' => 100.0, 'y0' => 80.0, 'x1' => 420.0, 'y1' => 108.0]),
    system_zone_test_geometry('Överförmyndaravdelningen', 1, ['x0' => 100.0, 'y0' => 116.0, 'x1' => 460.0, 'y1' => 142.0]),
    system_zone_test_geometry('Karlstad 2026-03-04', 2, ['x0' => 100.0, 'y0' => 150.0, 'x1' => 370.0, 'y1' => 176.0]),
    system_zone_test_geometry('Elin Hartmeier, 054-540 68 83', 3, ['x0' => 100.0, 'y0' => 184.0, 'x1' => 510.0, 'y1' => 210.0]),
    system_zone_test_geometry('elin.hartmeier@karlstad.se', 4, ['x0' => 100.0, 'y0' => 218.0, 'x1' => 500.0, 'y1' => 244.0]),
    system_zone_test_geometry('Brödtext längre ner', 5, ['x0' => 100.0, 'y0' => 800.0, 'x1' => 410.0, 'y1' => 826.0]),
];

$layout = [
    1 => [
        'leftMargin' => [
            'available' => true,
            'x' => 100.0,
        ],
    ],
];

$zones = detect_sender_block_system_zones(default_system_zones(), $geometries, $layout);

assert_system_zone_sender_block(count($zones) === 1, 'A clear sender/contact block should produce one system zone.');
$zone = $zones[0];
assert_system_zone_sender_block(($zone['type'] ?? null) === 'systemzone', 'The detected zone must be marked as a system zone.');
assert_system_zone_sender_block(($zone['systemZoneType'] ?? null) === 'sender_block', 'The system zone type must be sender_block.');
assert_system_zone_sender_block(($zone['zoneName'] ?? null) === 'Avsändarblock', 'The zone name must be Avsändarblock.');
assert_system_zone_sender_block((float) ($zone['confidence'] ?? 0.0) >= 0.65, 'The sender block should receive high confidence.');
assert_system_zone_sender_block(($zone['bboxIndexes'] ?? []) === [1, 2, 3, 4, 5], 'The zone must expose included bbox indexes.');
assert_system_zone_sender_block(str_contains((string) ($zone['matchedText'] ?? ''), 'elin.hartmeier@karlstad.se'), 'The zone text must include relevant contact text.');
assert_system_zone_sender_block(is_array($zone['signals'] ?? null) && count($zone['signals']) >= 5, 'The zone must expose signal debug data.');
assert_system_zone_sender_block(is_array($zone['debug'] ?? null), 'The zone must expose debug data.');
$signalCodes = array_map(static fn (array $signal): string => (string) ($signal['code'] ?? ''), $zone['signals']);
foreach ($zone['signals'] as $signal) {
    assert_system_zone_sender_block(in_array($signal['pointType'] ?? null, ['layout', 'content'], true), 'Every sender-block signal must identify its point group.');
}
assert_system_zone_sender_block(!in_array('text_size', $signalCodes, true), 'Sender block must not use a text size signal.');
assert_system_zone_sender_block(count(array_filter($zone['signals'], static fn (array $signal): bool => str_starts_with((string) ($signal['code'] ?? ''), 'text_match_'))) >= 2, 'The sender block should expose concrete text match signals.');
assert_system_zone_sender_block((int) ($zone['debug']['totalPoints'] ?? 0) >= 65, 'The sender block should expose total point debug data.');
assert_system_zone_sender_block((int) ($zone['debug']['layoutPoints'] ?? 0) >= (int) ($zone['debug']['minLayoutPoints'] ?? 999), 'The sender block should expose passing layout point debug data.');
assert_system_zone_sender_block((int) ($zone['debug']['contentPoints'] ?? 0) >= (int) ($zone['debug']['minContentPoints'] ?? 999), 'The sender block should expose passing content point debug data.');
assert_system_zone_sender_block(!array_key_exists('minTotalPoints', $zone['debug']), 'Sender debug must not expose or enforce a minimum total score.');
assert_system_zone_sender_block(in_array('line_count', $signalCodes, true), 'The sender block should include a line count layout signal.');
assert_system_zone_sender_block(in_array('block_width', $signalCodes, true), 'The sender block should include a block width layout signal.');

$textMatchSignals = array_values(array_filter($zone['signals'], static fn (array $signal): bool => str_starts_with((string) ($signal['code'] ?? ''), 'text_match_')));
$phoneSignals = array_values(array_filter($textMatchSignals, static fn (array $signal): bool => (string) ($signal['name'] ?? '') === 'Telefonnummer'));
assert_system_zone_sender_block(count($phoneSignals) === 1, 'A normal Swedish phone number should match the phone text match.');

$singleOrg = [
    system_zone_test_geometry('KARLSTADS KOMMUN', 0, ['x0' => 100.0, 'y0' => 80.0, 'x1' => 370.0, 'y1' => 108.0]),
];
$singleOrgZones = detect_sender_block_system_zones(default_system_zones(), $singleOrg, $layout);
assert_system_zone_sender_block($singleOrgZones === [], 'A single organization name must not produce a sender block.');

$separateColumns = [
    system_zone_test_geometry('KARLSTADS KOMMUN', 0, ['x0' => 100.0, 'y0' => 80.0, 'x1' => 370.0, 'y1' => 108.0]),
    system_zone_test_geometry('Oscar Johan Jonsson', 1, ['x0' => 620.0, 'y0' => 116.0, 'x1' => 900.0, 'y1' => 142.0]),
    system_zone_test_geometry('NORRA RINGVÄGEN 17', 2, ['x0' => 620.0, 'y0' => 150.0, 'x1' => 920.0, 'y1' => 176.0]),
    system_zone_test_geometry('681 34 KRISTINEHAMN', 3, ['x0' => 620.0, 'y0' => 184.0, 'x1' => 890.0, 'y1' => 210.0]),
];
$separateColumnZones = detect_sender_block_system_zones(default_system_zones(), $separateColumns, $layout);
assert_system_zone_sender_block($separateColumnZones === [], 'Organization text and recipient address in separate columns must not become one sender block.');

$identityNumberBlock = [
    system_zone_test_geometry('KOMMUNLEDNINGSKONTORET', 0, ['x0' => 100.0, 'y0' => 80.0, 'x1' => 420.0, 'y1' => 108.0]),
    system_zone_test_geometry('Överförmyndaravdelningen', 1, ['x0' => 100.0, 'y0' => 116.0, 'x1' => 460.0, 'y1' => 142.0]),
    system_zone_test_geometry('Karlstad 2026-03-04', 2, ['x0' => 100.0, 'y0' => 150.0, 'x1' => 370.0, 'y1' => 176.0]),
    system_zone_test_geometry('Personnummer 19920112-4212', 3, ['x0' => 100.0, 'y0' => 184.0, 'x1' => 510.0, 'y1' => 210.0]),
    system_zone_test_geometry('Kontonummer 9151 1464218', 4, ['x0' => 100.0, 'y0' => 218.0, 'x1' => 500.0, 'y1' => 244.0]),
];
$identityNumberZones = detect_sender_block_system_zones(default_system_zones(), $identityNumberBlock, $layout);
if ($identityNumberZones !== []) {
    $identityPhoneSignals = array_filter($identityNumberZones[0]['signals'] ?? [], static fn (array $signal): bool => (string) ($signal['name'] ?? '') === 'Telefonnummer');
    assert_system_zone_sender_block(count($identityPhoneSignals) === 0, 'Personnummer and account-like numbers must not match the phone text match.');
}

$phoneRule = array_values(array_filter(default_system_zones()['senderBlock']['contentMatches'], static fn(array $match): bool => ($match['name'] ?? '') === 'Telefonnummer'))[0];
foreach (['054-540 68 83', '070-123 45 67', '08-123 45 67', '+46 70 123 45 67'] as $phone) {
    $hits = system_zone_sender_block_text_match_hits('Ring ' . $phone . ' eller ' . $phone, [$phoneRule], []);
    assert_system_zone_sender_block(count($hits) === 1, $phone . ' should match as a Swedish phone number.');
    assert_system_zone_sender_block(($hits[0]['matchedValues'] ?? []) === [$phone, $phone], 'Phone debug must expose every exact matched value.');
    assert_system_zone_sender_block(($hits[0]['debug']['name'] ?? '') === 'Telefonnummer', 'Text match debug must expose the rule name.');
}
foreach (['9151 1464218', '3025 05 24171'] as $number) {
    assert_system_zone_sender_block(system_zone_sender_block_text_match_hits($number, [$phoneRule], []) === [], $number . ' must not match as a phone number.');
}

$defaultMatches = default_system_zones()['senderBlock']['contentMatches'];
$defaultNames = array_column($defaultMatches, 'name');
assert_system_zone_sender_block(!in_array('Bank', $defaultNames, true), 'The broad Bank positive text match must be removed.');
$bankHits = system_zone_sender_block_text_match_hits('Bankinformation från Swedbank', $defaultMatches, []);
$bankNameHits = array_values(array_filter($bankHits, static fn(array $hit): bool => ($hit['name'] ?? '') === 'Banknamn'));
assert_system_zone_sender_block(($bankNameHits[0]['matchedValues'] ?? []) === ['Swedbank'], 'Banknamn must expose the exact bank name and not match Bankinformation.');

$zeroCurve = [['x' => 0, 'y' => 0], ['x' => 20, 'y' => 0]];
$rowLimitFreeSettings = normalize_sender_block_system_zone(array_replace(default_system_zones()['senderBlock'], [
    'minLayoutPoints' => 0,
    'minContentPoints' => 0,
    'minTotalPoints' => 999,
    'minLines' => 10,
    'maxLines' => 1,
    'topPositionCurve' => $zeroCurve,
    'leftMarginCurve' => $zeroCurve,
    'lineCountCurve' => $zeroCurve,
    'blockWidthCurve' => $zeroCurve,
    'unidentifiedTextRatioCurve' => [['x' => 0, 'y' => 0], ['x' => 1, 'y' => 0]],
]));
assert_system_zone_sender_block(!array_key_exists('minTotalPoints', $rowLimitFreeSettings) && !array_key_exists('minLines', $rowLimitFreeSettings) && !array_key_exists('maxLines', $rowLimitFreeSettings), 'Legacy total and row limits must not survive normalization.');
$singleBlock = system_zone_geometric_blocks([$geometries[4]], 1.8, 4.5)[0] ?? [];
$singleCandidate = system_zone_sender_block_candidate($singleBlock, $rowLimitFreeSettings, $layout, []);
assert_system_zone_sender_block(($singleCandidate['accepted'] ?? false) === true, 'A one-line candidate must not be rejected by a hard minimum row count or legacy total threshold.');
$manyGeometries = [];
for ($line = 0; $line < 12; $line++) {
    $manyGeometries[] = system_zone_test_geometry('KONTAKTRAD ' . $line, $line, ['x0' => 100.0, 'y0' => 80.0 + ($line * 32), 'x1' => 400.0, 'y1' => 106.0 + ($line * 32)]);
}
$manyBlock = system_zone_geometric_blocks($manyGeometries, 1.8, 4.5)[0] ?? [];
$manyCandidate = system_zone_sender_block_candidate($manyBlock, $rowLimitFreeSettings, $layout, []);
assert_system_zone_sender_block(($manyCandidate['accepted'] ?? false) === true && (int) ($manyCandidate['zone']['debug']['lineCount'] ?? 0) === 12, 'A many-line candidate must only be affected by the line-count curve, never hard-rejected.');

$columnRows = [
    [['text' => 'KOMMUNLEDNINGSKONTORET', 'wordIndex' => 0, 'bbox' => ['x0' => 100.0, 'y0' => 80.0, 'x1' => 430.0, 'y1' => 108.0]], ['text' => 'Oscar Jonsson', 'wordIndex' => 1, 'bbox' => ['x0' => 1250.0, 'y0' => 80.0, 'x1' => 1480.0, 'y1' => 108.0]]],
    [['text' => 'Överförmyndaravdelningen', 'wordIndex' => 2, 'bbox' => ['x0' => 100.0, 'y0' => 116.0, 'x1' => 470.0, 'y1' => 142.0]], ['text' => 'NORRA RINGVÄGEN 17', 'wordIndex' => 3, 'bbox' => ['x0' => 1250.0, 'y0' => 116.0, 'x1' => 1570.0, 'y1' => 142.0]]],
    [['text' => 'Karlstad 2026-03-04', 'wordIndex' => 4, 'bbox' => ['x0' => 100.0, 'y0' => 150.0, 'x1' => 370.0, 'y1' => 176.0]], ['text' => '681 34 KRISTINEHAMN', 'wordIndex' => 5, 'bbox' => ['x0' => 1250.0, 'y0' => 150.0, 'x1' => 1570.0, 'y1' => 176.0]]],
    [['text' => 'Elin Hartmeier, 054-540 68 83', 'wordIndex' => 6, 'bbox' => ['x0' => 100.0, 'y0' => 184.0, 'x1' => 510.0, 'y1' => 210.0]]],
    [['text' => 'elin.hartmeier@karlstad.se', 'wordIndex' => 7, 'bbox' => ['x0' => 100.0, 'y0' => 218.0, 'x1' => 500.0, 'y1' => 244.0]]],
];
$columnGeometries = array_map(static fn(array $segments, int $index): array => system_zone_test_multi_segment_geometry($segments, $index), $columnRows, array_keys($columnRows));
$segmented = system_zone_visual_line_segments($columnGeometries, 4.5);
$firstSegments = $segmented['byPage'][1] ?? [];
assert_system_zone_sender_block(count(array_filter($firstSegments, static fn(array $segment): bool => (int) $segment['lineIndex'] === 0)) === 2, 'A large horizontal OCR-row gap must create two visual segments.');
$splitDebug = $firstSegments[0]['segmentation'] ?? [];
assert_system_zone_sender_block(($splitDebug['splitReason'] ?? null) === 'horizontal_gap' && (float) ($splitDebug['gapLineHeights'] ?? 0) > 4.5, 'Segmentation debug must state the split reason and normalized gap.');
$columnSender = detect_sender_block_system_zones(default_system_zones(), $columnGeometries, $layout);
$columnRecipient = detect_recipient_block_system_zones(default_system_zones(), $columnGeometries);
assert_system_zone_sender_block(count($columnSender) === 1 && !str_contains($columnSender[0]['matchedText'] ?? '', 'Oscar Jonsson'), 'Sender bbox and text must exclude the separate recipient column.');
assert_system_zone_sender_block(count($columnRecipient) === 1 && str_contains($columnRecipient[0]['matchedText'] ?? '', 'Oscar Jonsson'), 'The separate name/address column must become a Mottagarblock.');
assert_system_zone_sender_block((float) ($columnSender[0]['boundingRect']['x1'] ?? 9999) < 1000.0, 'Sender boundingRect must not span the gap to the recipient column.');

$contentRules = default_system_zones()['senderBlock']['contentMatches'];
$negativeHits = system_zone_sender_block_text_match_hits('Belopp Kontonummer Bankinformation ska överföras', $contentRules, []);
$negativeHits = array_values(array_filter($negativeHits, static fn(array $hit): bool => (int) ($hit['points'] ?? 0) < 0));
assert_system_zone_sender_block(count($negativeHits) === 4, 'Negative and positive content rules must use the same contentMatches list.');
assert_system_zone_sender_block(($negativeHits[0]['matchedValues'][0] ?? '') === 'Belopp' && (int) ($negativeHits[0]['contentPoints'] ?? 0) < 0, 'Negative content debug must retain exact text and signed content points.');

$legacySettings = normalize_sender_block_system_zone([
    'textMatches' => [['name' => 'E-postadress', 'pattern' => '[A-Z0-9._%+\\-]+@[A-Z0-9.\\-]+\\.[A-Z]{2,}', 'isRegex' => true, 'points' => 35]],
    'foreignContentMatches' => [['name' => 'Kontonummer', 'pattern' => 'kontonummer', 'isRegex' => false, 'points' => 1]],
    'foreignContentCurve' => [['x' => 0, 'y' => 0], ['x' => 1, 'y' => -25]],
]);
assert_system_zone_sender_block(array_key_exists('contentMatches', $legacySettings) && !array_key_exists('textMatches', $legacySettings) && !array_key_exists('foreignContentMatches', $legacySettings), 'Legacy positive and foreign rules must migrate into one contentMatches list.');
$migratedByName = array_column($legacySettings['contentMatches'], null, 'name');
assert_system_zone_sender_block((int) ($migratedByName['E-postadress']['points'] ?? 0) === 35 && (int) ($migratedByName['Kontonummer']['points'] ?? 0) === -25, 'Legacy match outcomes must migrate to signed content points.');
assert_system_zone_sender_block(normalize_sender_block_system_zone(['contentMatches' => []])['contentMatches'] === [], 'An explicitly empty unified content list must remain empty.');

$overlapRules = [
    ['name' => 'E-postadress', 'pattern' => '[A-Z0-9._%+\\-]+@[A-Z0-9.\\-]+\\.[A-Z]{2,}', 'isRegex' => true, 'points' => 35, 'enabled' => true],
    ['name' => 'Samma e-post', 'pattern' => 'x@y.se', 'isRegex' => false, 'points' => 5, 'enabled' => true],
];
$overlapSignals = system_zone_sender_block_text_match_hits('Email x@y.se ZZZ', $overlapRules, []);
$unidentified = system_zone_sender_block_unidentified_text_analysis('Email x@y.se ZZZ', [], $overlapSignals);
assert_system_zone_sender_block((int) $unidentified['totalRelevantCharacters'] === 12, 'Unidentified-text analysis must ignore spaces and punctuation.');
assert_system_zone_sender_block((int) $unidentified['identifiedCharacters'] === 4, 'Overlapping identified spans must not be counted twice.');
assert_system_zone_sender_block(abs((float) $unidentified['unidentifiedTextRatio'] - (8 / 12)) < 0.00001, 'Unidentified-text ratio must use relevant weighted characters.');
$unknownSettings = normalize_sender_block_system_zone(array_replace($rowLimitFreeSettings, [
    'unidentifiedTextRatioCurve' => [['x' => 0, 'y' => 0], ['x' => 1, 'y' => -30]],
]));
$unknownGeometry = [system_zone_test_geometry('Kontakt abcdef', 0, ['x0' => 100.0, 'y0' => 80.0, 'x1' => 360.0, 'y1' => 108.0])];
$unknownBlock = system_zone_geometric_blocks($unknownGeometry, 1.8, 4.5)[0] ?? [];
$unknownCandidate = system_zone_sender_block_candidate($unknownBlock, $unknownSettings, $layout, []);
$unknownSignals = $unknownCandidate['debug']['signals'] ?? ($unknownCandidate['zone']['signals'] ?? []);
$unknownRatioSignal = array_values(array_filter($unknownSignals, static fn(array $signal): bool => ($signal['code'] ?? '') === 'unidentified_text_ratio'))[0] ?? null;
assert_system_zone_sender_block(($unknownCandidate['accepted'] ?? true) === false && (int) ($unknownRatioSignal['points'] ?? 0) < 0 && (int) ($unknownCandidate['debug']['contentPoints'] ?? 0) < 0, 'Unidentified text must lower content points through its curve.');

foreach (['20260510_123604_714b9f', '20260529_222554_7c4dfe'] as $jobId) {
    $jobGeometries = system_zone_job_geometries($jobId);
    assert_system_zone_sender_block($jobGeometries !== [], 'Regression job ' . $jobId . ' must provide OCR geometry.');
    $jobLayout = title_layout_analysis_by_page($jobGeometries, normalize_layout_analysis_settings([]));
    $jobSender = detect_sender_block_system_zones(default_system_zones(), $jobGeometries, $jobLayout);
    if ($jobId === '20260510_123604_714b9f') {
        $jobRecipient = detect_recipient_block_system_zones(default_system_zones(), $jobGeometries);
        assert_system_zone_sender_block(count($jobSender) === 1 && !str_contains($jobSender[0]['matchedText'] ?? '', 'Oscar Jonsson'), 'Regression job must keep the left sender column separate.');
        assert_system_zone_sender_block(count($jobRecipient) >= 1 && str_contains($jobRecipient[0]['matchedText'] ?? '', 'Oscar Jonsson'), 'Regression job must identify the right recipient column.');
    } else {
        assert_system_zone_sender_block($jobSender === [], 'Economic bank/payment content in regression job must not be accepted as Avsändarblock.');
    }
}

echo "system_zones_sender_block_test: ok\n";
