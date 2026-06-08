<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_title_sender_name_signal(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$lookup = title_sender_name_lookup_from_entries([
    [
        'name' => 'Karlstads kommun',
        'normalizedName' => 'karlstads kommun',
        'type' => 'sender_name',
    ],
    [
        'name' => 'Karlstads-Hammarö överförmyndarnämnd',
        'normalizedName' => '',
        'type' => 'sender_unit',
    ],
]);

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
assert_title_sender_name_signal((float) ($senderSignals[0]['score'] ?? 0.0) === -40.0, 'Sender name penalty should be -40.');
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

fwrite(STDOUT, "title sender name signal tests passed\n");
