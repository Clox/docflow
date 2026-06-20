<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_layout_left_margin(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$settings = normalize_layout_analysis_settings([
    'leftMargin' => [
        'ignoreVerticalText' => true,
        'minBboxWidth' => 40,
        'minTextLength' => 3,
        'minOcrConfidence' => 0.5,
        'edgeFilterRatio' => 0.02,
        'clusterTolerance' => 12,
    ],
]);

$pageOne = [
    'pageWidth' => 1000,
    'words' => [
        ['text' => 'S:25431', 'confidence' => 0.99, 'bbox' => [5, 120, 35, 500]],
        ['text' => 'Rubrik', 'confidence' => 0.98, 'bbox' => [120, 80, 260, 110]],
        ['text' => 'första', 'confidence' => 0.97, 'bbox' => [120, 140, 230, 165]],
        ['text' => 'raden', 'confidence' => 0.97, 'bbox' => [245, 140, 340, 165]],
        ['text' => 'andra', 'confidence' => 0.96, 'bbox' => [123, 190, 230, 215]],
        ['text' => 'raden', 'confidence' => 0.96, 'bbox' => [244, 190, 340, 215]],
        ['text' => 'brus', 'confidence' => 0.10, 'bbox' => [60, 250, 130, 275]],
        ['text' => 'sidfot', 'confidence' => 0.99, 'bbox' => [980, 760, 1030, 785]],
    ],
];

$pageTwo = [
    'pageWidth' => 1000,
    'words' => [
        ['text' => 'vänster', 'confidence' => 0.98, 'bbox' => [220, 90, 340, 116]],
        ['text' => 'kolumn', 'confidence' => 0.98, 'bbox' => [352, 90, 470, 116]],
        ['text' => 'nästa', 'confidence' => 0.98, 'bbox' => [222, 135, 320, 160]],
        ['text' => 'rad', 'confidence' => 0.98, 'bbox' => [336, 135, 390, 160]],
    ],
];

$analysisOne = analyze_page_layout($pageOne, $settings);
$leftOne = $analysisOne['leftMargin'] ?? [];
assert_layout_left_margin(($leftOne['available'] ?? false) === true, 'Page one should produce a left margin.');
assert_layout_left_margin(
    abs((float) ($leftOne['x'] ?? 0.0) - 121.0) < 1.0,
    'Page one should choose the main text cluster around x=120.'
);
assert_layout_left_margin(
    ($leftOne['basisWordIndexes'] ?? []) === [1, 2, 3, 4, 5],
    'The basis should include only words from the winning margin cluster.'
);

$analysisTwo = analyze_page_layout($pageTwo, $settings);
$leftTwo = $analysisTwo['leftMargin'] ?? [];
assert_layout_left_margin(($leftTwo['available'] ?? false) === true, 'Page two should produce a separate left margin.');
assert_layout_left_margin(
    abs((float) ($leftTwo['x'] ?? 0.0) - 221.0) < 1.0,
    'Left margin should be calculated per page, not shared across the document.'
);

$strictAnalysis = analyze_page_layout($pageOne, [
    'leftMargin' => [
        ...$settings['leftMargin'],
        'minOcrConfidence' => 0.99,
    ],
]);
assert_layout_left_margin(
    ($strictAnalysis['leftMargin']['available'] ?? true) === false,
    'Confidence filtering should be able to remove all candidate rows.'
);

echo "layout_analysis_left_margin_test: ok\n";
