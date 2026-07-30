<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

use Docflow\Archiving\ExtractionFieldRepository;
use Docflow\Archiving\ZoneRepository;
use Docflow\Database\Connection;
use Docflow\Database\MigrationRunner;

function assert_description_support(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$customDescription = "  Dokumentets huvudsakliga belopp.\nInte ett delbelopp.  ";
$normalizedFields = normalize_extraction_fields([[
    'key' => 'amount_under_test',
    'name' => 'Belopp',
    'description' => $customDescription,
    'valueType' => 'amount',
    'ruleSets' => [[
        'requiresSearchTerms' => true,
        'searchTerms' => ['Belopp'],
    ]],
]]);
assert_description_support(
    ($normalizedFields[0]['description'] ?? null) === "Dokumentets huvudsakliga belopp.\nInte ett delbelopp.",
    'Data field normalization must preserve Unicode-capable multiline descriptions and trim their outer whitespace.'
);

$legacyFields = normalize_extraction_fields([[
    'key' => 'legacy_field',
    'name' => 'Äldre fält',
    'valueType' => 'text',
    'ruleSets' => [],
]]);
assert_description_support(
    array_key_exists('description', $legacyFields[0] ?? []) && $legacyFields[0]['description'] === '',
    'Legacy data fields without a description must normalize to an empty string.'
);

$normalizedZones = normalize_archiving_zones([[
    'id' => 'content_zone',
    'name' => 'Innehåll',
    'description' => "  Avgränsar åäö.\nAndra raden.  ",
    'pattern' => 'Innehåll',
]]);
assert_description_support(
    ($normalizedZones[0]['description'] ?? null) === "Avgränsar åäö.\nAndra raden.",
    'Zone normalization must preserve Unicode-capable multiline descriptions.'
);

$legacyZones = normalize_archiving_zones([[
    'id' => 'legacy_zone',
    'name' => 'Äldre zon',
    'pattern' => 'Äldre zon',
]]);
assert_description_support(
    array_key_exists('description', $legacyZones[0] ?? []) && $legacyZones[0]['description'] === '',
    'Legacy zones without a description must normalize to an empty string.'
);

$rulesRoundTrip = normalize_archiving_rules_set(json_decode((string) json_encode([
    'fields' => $normalizedFields,
    'zones' => $normalizedZones,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true));
assert_description_support(
    ($rulesRoundTrip['fields'][0]['description'] ?? null) === $normalizedFields[0]['description'],
    'Data field descriptions must survive the archiving-rules import/export format.'
);
assert_description_support(
    ($rulesRoundTrip['zones'][0]['description'] ?? null) === $normalizedZones[0]['description'],
    'Zone descriptions must survive the archiving-rules import/export format.'
);

$systemFields = normalize_system_extraction_fields([[
    'systemFieldKey' => 'title',
    'description' => 'Dokumentets rubrik.',
]]);
$titleField = array_values(array_filter(
    $systemFields,
    static fn (mixed $field): bool => is_array($field) && ($field['key'] ?? null) === 'title'
))[0] ?? null;
assert_description_support(
    is_array($titleField) && ($titleField['description'] ?? null) === 'Dokumentets rubrik.',
    'System data field descriptions must survive state normalization.'
);

$label = normalize_label_definition([
    'name' => 'Beskriven etikett',
    'description' => 'Befintligt etikettstöd.',
    'rules' => [['type' => 'text', 'text' => 'etikett']],
]);
assert_description_support(
    is_array($label) && ($label['description'] ?? null) === 'Befintligt etikettstöd.',
    'Existing label description support must remain unchanged.'
);

$databasePath = sys_get_temp_dir() . '/docflow-descriptions-' . bin2hex(random_bytes(5)) . '.sqlite';
try {
    $pdo = Connection::make($databasePath);
    (new MigrationRunner($pdo, PROJECT_ROOT . 'database/migrations'))->migrate();

    $fieldRepository = new ExtractionFieldRepository($pdo);
    $fieldWithoutDescription = $normalizedFields;
    $fieldWithoutDescription[0]['description'] = '';
    $fieldRepository->replaceScopes([
        'fields' => $fieldWithoutDescription,
        'predefinedFields' => [],
    ], [
        'fields' => $legacyFields,
        'predefinedFields' => [],
    ]);
    assert_description_support(
        ($fieldRepository->loadScope('active')['fields'][0]['description'] ?? null) === '',
        'Data fields created without a description must persist an empty string.'
    );

    $fieldRepository->replaceScopes([
        'fields' => $normalizedFields,
        'predefinedFields' => [],
    ], [
        'fields' => $legacyFields,
        'predefinedFields' => [],
    ]);
    $loadedFields = $fieldRepository->loadScope('active');
    assert_description_support(
        ($loadedFields['fields'][0]['description'] ?? null) === $normalizedFields[0]['description'],
        'A data field description must survive update, repository persistence, and reload.'
    );

    $updatedFields = $loadedFields['fields'];
    $updatedFields[0]['description'] = '';
    $fieldRepository->replaceScopes([
        'fields' => $updatedFields,
        'predefinedFields' => [],
    ], [
        'fields' => $legacyFields,
        'predefinedFields' => [],
    ]);
    assert_description_support(
        ($fieldRepository->loadScope('active')['fields'][0]['description'] ?? null) === '',
        'Saving an empty string must remove a data field description.'
    );

    $zoneRepository = new ZoneRepository($pdo);
    $zoneWithoutDescription = $normalizedZones;
    $zoneWithoutDescription[0]['description'] = '';
    $zoneRepository->replaceScopes($zoneWithoutDescription, $legacyZones);
    assert_description_support(
        ($zoneRepository->loadScope('active')[0]['description'] ?? null) === '',
        'Zones created without a description must persist an empty string.'
    );
    $zoneRepository->replaceScopes($normalizedZones, $legacyZones);
    assert_description_support(
        ($zoneRepository->loadScope('active')[0]['description'] ?? null) === $normalizedZones[0]['description'],
        'A zone description must survive update, repository persistence, and reload.'
    );

    $updatedZones = $zoneRepository->loadScope('active');
    $updatedZones[0]['description'] = '';
    $zoneRepository->replaceScopes($updatedZones, $legacyZones);
    assert_description_support(
        ($zoneRepository->loadScope('active')[0]['description'] ?? null) === '',
        'Saving an empty string must remove a zone description.'
    );
} finally {
    unset($pdo);
    @unlink($databasePath);
}

fwrite(STDOUT, "data field and zone description tests passed\n");
