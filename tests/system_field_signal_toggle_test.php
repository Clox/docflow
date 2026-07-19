<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_system_field_signal_toggle(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$primaryDate = normalize_primary_date_heuristics([
    'bonuses' => ['near_title' => ['enabled' => false]],
    'penalties' => ['text_density' => ['enabled' => false]],
]);
assert_system_field_signal_toggle(
    ($primaryDate['bonuses']['near_title']['enabled'] ?? true) === false,
    'Disabled primary date bonus signals must remain disabled after normalization.'
);
assert_system_field_signal_toggle(
    ($primaryDate['penalties']['text_density']['enabled'] ?? true) === false,
    'Disabled primary date penalty signals must remain disabled after normalization.'
);

$title = normalize_title_heuristics([
    'signals' => ['vertical_position' => ['enabled' => false]],
]);
assert_system_field_signal_toggle(
    ($title['signals']['vertical_position']['enabled'] ?? true) === false,
    'Disabled title signals must remain disabled after normalization.'
);

$senderMark = normalize_sender_mark_heuristics([
    'signals' => ['letter_ratio' => ['enabled' => false]],
]);
assert_system_field_signal_toggle(
    ($senderMark['signals']['letter_ratio']['enabled'] ?? true) === false,
    'Disabled sender mark signals must remain disabled after normalization.'
);

$candidate = [
    'value' => 'RUBRIK',
    'raw' => 'RUBRIK',
    'line' => 'RUBRIK',
    'lineIndex' => 0,
    'start' => 0,
    'end' => 6,
    'bbox' => ['x0' => 20.0, 'y0' => 20.0, 'x1' => 120.0, 'y1' => 40.0],
    'valueBBoxIndexes' => [0],
];
$geometry = [[
    'text' => 'RUBRIK',
    'pageNumber' => 1,
    'pageWidth' => 600.0,
    'pageHeight' => 800.0,
    'segments' => [[
        'start' => 0,
        'end' => 6,
        'wordIndex' => 0,
        'bbox' => ['x0' => 20.0, 'y0' => 20.0, 'x1' => 120.0, 'y1' => 40.0],
    ]],
]];
$scoredTitle = score_title_candidate(
    $candidate,
    ['RUBRIK'],
    $geometry,
    ['signals' => ['vertical_position' => ['enabled' => false]]]
);
$verticalSignals = array_filter(
    $scoredTitle['signals'] ?? [],
    static fn (mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'vertical_position'
);
assert_system_field_signal_toggle(
    $verticalSignals === [],
    'A disabled title signal must not be included in candidate scoring.'
);

fwrite(STDOUT, "system field signal toggle tests passed\n");
