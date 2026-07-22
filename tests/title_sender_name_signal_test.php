<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_title_sender_name_signal(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$senderNameResult = extract_sender_name_in_document_field_result(
    [
        'Karlstads kommun',
        'Karlstads-Hammarö överförmyndarnämnd',
    ],
    [
        [
            'text' => 'Karlstads kommun',
            'segments' => [[
                'text' => 'Karlstads kommun',
                'start' => 0,
                'end' => strlen('Karlstads kommun'),
                'wordIndex' => 0,
                'bbox' => ['x0' => 10.0, 'y0' => 10.0, 'x1' => 120.0, 'y1' => 24.0],
            ]],
            'pageNumber' => 1,
        ],
        [
            'text' => 'Karlstads-Hammarö överförmyndarnämnd',
            'segments' => [[
                'text' => 'Karlstads-Hammarö överförmyndarnämnd',
                'start' => 0,
                'end' => strlen('Karlstads-Hammarö överförmyndarnämnd'),
                'wordIndex' => 1,
                'bbox' => ['x0' => 10.0, 'y0' => 34.0, 'x1' => 250.0, 'y1' => 48.0],
            ]],
            'pageNumber' => 1,
        ],
    ],
    [],
    [
        [
            'senderId' => 1,
            'senderUnitId' => null,
            'name' => 'Karlstads kommun',
            'type' => 'sender_name',
        ],
        [
            'senderId' => 1,
            'senderUnitId' => 2,
            'name' => 'Karlstads-Hammarö överförmyndarnämnd',
            'type' => 'sender_unit',
        ],
    ],
);
$senderNameMatches = is_array($senderNameResult['matches'] ?? null) ? $senderNameResult['matches'] : [];
$lookup = title_sender_name_lookup_from_document_matches($senderNameMatches);

$score = static function (string $value) use ($lookup): array {
    return score_title_candidate(
        [
            'value' => $value,
            'raw' => $value,
            'line' => $value,
            'lineIndex' => 0,
            'start' => 0,
            'end' => strlen($value),
            'bbox' => null,
        ],
        [$value],
        [],
        [],
        $lookup
    );
};

$senderResult = $score("  KARLSTADS   KOMMUN ");
$senderSignals = array_values(array_filter(
    $senderResult['signals'] ?? [],
    static fn (mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'sender_name'
));
assert_title_sender_name_signal(count($senderSignals) === 1, 'Exact normalized sender name should trigger.');
assert_title_sender_name_signal((float) ($senderSignals[0]['score'] ?? 0.0) === -60.0, 'Sender name penalty should be -60.');
assert_title_sender_name_signal(
    ($senderSignals[0]['detail'] ?? null) === 'name:Karlstads kommun',
    'Signal detail should contain the matched sender name.'
);

$unitResult = $score('KARLSTADS-HAMMARÖ ÖVERFÖRMYNDARNÄMND');
assert_title_sender_name_signal(
    count(array_filter(
        $unitResult['signals'] ?? [],
        static fn (mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'sender_name'
    )) === 1,
    'Exact normalized sender unit name should trigger.'
);

$customPenaltyResult = score_title_candidate(
    [
        'value' => 'Karlstads kommun',
        'raw' => 'Karlstads kommun',
        'line' => 'Karlstads kommun',
        'lineIndex' => 0,
        'start' => 0,
        'end' => strlen('Karlstads kommun'),
        'bbox' => null,
    ],
    ['Karlstads kommun'],
    [],
    [
        'signals' => [
            'sender_name' => [
                'points' => -17.5,
            ],
        ],
    ],
    $lookup
);
$customPenaltySignal = array_values(array_filter(
    $customPenaltyResult['signals'] ?? [],
    static fn (mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'sender_name'
))[0] ?? null;
assert_title_sender_name_signal(
    is_array($customPenaltySignal) && (float) ($customPenaltySignal['score'] ?? 0.0) === -17.5,
    'Configured sender name penalty should control signal strength.'
);

foreach (['Beslut från Karlstads kommun', 'Information från Karlstads kommun'] as $value) {
    $result = $score($value);
    assert_title_sender_name_signal(
        count(array_filter(
            $result['signals'] ?? [],
            static fn (mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'sender_name'
        )) === 0,
        'Substring matches must not trigger.'
    );
}

$scoreWithNames = static function (string $value, array $names, array $heuristics = []): array {
    return score_title_candidate(
        [
            'value' => $value,
            'raw' => $value,
            'line' => $value,
            'lineIndex' => 0,
            'start' => 0,
            'end' => strlen($value),
            'bbox' => null,
        ],
        [$value],
        [],
        $heuristics,
        $names
    );
};
$senderSignal = static function (array $result): ?array {
    return array_values(array_filter(
        $result['signals'] ?? [],
        static fn(mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'sender_name'
    ))[0] ?? null;
};

$canonicalNames = title_sender_name_lookup_from_document_matches([], [[
    'senderId' => 279,
    'name' => 'Trygg-Hansa Försäkring filial',
    'source' => 'canonical',
]]);
$tryggHansaResult = $scoreWithNames('TRYGG HANSA', $canonicalNames);
$tryggHansaSignal = $senderSignal($tryggHansaResult);
assert_title_sender_name_signal(
    is_array($tryggHansaSignal) && (float) ($tryggHansaSignal['score'] ?? 0.0) === -60.0,
    'TRYGG HANSA must match the canonical name Trygg-Hansa Försäkring filial without changing the penalty.'
);
assert_title_sender_name_signal(
    ($tryggHansaSignal['debug']['normalizedCandidate'] ?? null) === 'trygg hansa'
        && ($tryggHansaSignal['debug']['bestMatch']['normalizedName'] ?? null) === 'trygg hansa försäkring filial'
        && ($tryggHansaSignal['debug']['bestMatch']['source'] ?? null) === 'canonical'
        && ($tryggHansaSignal['debug']['bestMatch']['matchStartToken'] ?? null) === 0
        && ($tryggHansaSignal['debug']['bestMatch']['matchType'] ?? null) === 'contiguous_whole_word_sequence',
    'Matched sender-name debug must expose normalized values, source, token start, and match type.'
);

$alternativeNames = title_sender_name_lookup_from_document_matches([], [[
    'senderId' => 279,
    'name' => 'Trygg-Hansa',
    'source' => 'alternativeName',
]]);
$alternativeResult = $scoreWithNames('TRYGG HANSA', $alternativeNames);
assert_title_sender_name_signal(
    ($senderSignal($alternativeResult)['debug']['bestMatch']['source'] ?? null) === 'alternativeName',
    'TRYGG HANSA must match when the corresponding name exists only as an alternative name.'
);

foreach ([
    ['candidate' => 'bil', 'name' => 'bil försäkring', 'matches' => true],
    ['candidate' => 'bil', 'name' => 'bilförsäkring', 'matches' => false],
    ['candidate' => 'bil försäkring', 'name' => 'bil försäkring ab', 'matches' => true],
    ['candidate' => 'försäkring', 'name' => 'bil försäkring ab', 'matches' => true],
    ['candidate' => 'trygg hansa', 'name' => 'trygg skade hansa', 'matches' => false],
] as $case) {
    $caseResult = $scoreWithNames((string) $case['candidate'], [[
        'name' => (string) $case['name'],
        'source' => 'canonical',
    ]]);
    assert_title_sender_name_signal(
        is_array($senderSignal($caseResult)) === (bool) $case['matches'],
        sprintf('Unexpected whole-word sequence result for "%s" against "%s".', $case['candidate'], $case['name'])
    );
}

assert_title_sender_name_signal(
    title_sender_name_normalize_for_signal("  Alfa-Beta.Gamma/Delta   AB  ") === 'alfa beta gamma delta ab',
    'Hyphens, periods, slashes, and repeated whitespace must become normalized word boundaries.'
);
$punctuationResult = $scoreWithNames('ALFA / BETA.GAMMA', [[
    'name' => 'Alfa-Beta / Gamma Delta AB',
    'source' => 'alternativeName',
]]);
assert_title_sender_name_signal(
    is_array($senderSignal($punctuationResult)),
    'Punctuation-separated words must match the same contiguous normalized word sequence.'
);

$nonMatchResult = $scoreWithNames('trygg hansa', [[
    'name' => 'Trygg skade Hansa',
    'source' => 'canonical',
]]);
assert_title_sender_name_signal(
    ($nonMatchResult['senderNameAnalysis']['matched'] ?? true) === false
        && ($nonMatchResult['senderNameAnalysis']['candidateTokens'] ?? null) === ['trygg', 'hansa']
        && ($nonMatchResult['senderNameAnalysis']['comparisons'][0]['nameTokens'] ?? null) === ['trygg', 'skade', 'hansa']
        && ($nonMatchResult['senderNameAnalysis']['comparisons'][0]['reason'] ?? null) === 'contiguous_whole_word_sequence_not_found',
    'Non-match debug must show the compared names, token sequences, and rejection reason.'
);

$exactResult = $scoreWithNames('Exakt Avsändare AB', [[
    'name' => 'Exakt Avsändare AB',
    'source' => 'detectedSenderName',
]]);
assert_title_sender_name_signal(
    is_array($senderSignal($exactResult))
        && ($senderSignal($exactResult)['debug']['bestMatch']['matchStartToken'] ?? null) === 0,
    'Existing exact sender-name matches must continue to work.'
);

fwrite(STDOUT, "title sender name signal tests passed\n");
