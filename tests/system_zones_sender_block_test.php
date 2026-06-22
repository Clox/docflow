<?php
declare(strict_types=1);

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
assert_system_zone_sender_block(!in_array('text_size', $signalCodes, true), 'Sender block must not use a text size signal.');
assert_system_zone_sender_block(count(array_filter($zone['signals'], static fn (array $signal): bool => str_starts_with((string) ($signal['code'] ?? ''), 'text_match_'))) >= 2, 'The sender block should expose concrete text match signals.');
assert_system_zone_sender_block((int) ($zone['debug']['totalPoints'] ?? 0) >= 65, 'The sender block should expose total point debug data.');
assert_system_zone_sender_block((int) ($zone['debug']['strongTextMatchPoints'] ?? 0) >= 25, 'The sender block should expose strong text match debug data.');

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

echo "system_zones_sender_block_test: ok\n";
