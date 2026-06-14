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

$titleWithoutSenderFieldMatch = extract_title_field_result(
    ['Karlstads kommun'],
    [],
    [],
    [],
    []
);
$titleWithoutSenderSignals = array_values(array_filter(
    $titleWithoutSenderFieldMatch['selectedCandidate']['signals'] ?? [],
    static fn (mixed $signal): bool => is_array($signal) && ($signal['code'] ?? null) === 'sender_name'
));
assert_title_sender_name_signal(
    $titleWithoutSenderSignals === [],
    'Title sender-name penalty must depend on the sender_name_in_document matches, not the sender registry directly.'
);

fwrite(STDOUT, "title sender name signal tests passed\n");
