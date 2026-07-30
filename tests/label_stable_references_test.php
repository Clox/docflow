<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_label_reference_support(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$renamed = normalize_label_definition([
    'id' => 'stable-label-id',
    'name' => 'Ett helt nytt visningsnamn',
    'rules' => [],
]);
assert_label_reference_support(
    is_array($renamed) && ($renamed['id'] ?? null) === 'stable-label-id',
    'Renaming a custom label must preserve its stored ID.'
);

$newLabel = normalize_label_definition([
    'name' => 'Ny etikett',
    'rules' => [],
]);
assert_label_reference_support(
    is_array($newLabel) && ($newLabel['id'] ?? null) === 'ny-etikett',
    'A new label without an ID must still receive an ID derived from its initial name.'
);

$systemDefaults = system_label_definitions()['invoice'];
$renamedSystemLabel = normalize_system_label_with_defaults('invoice', [
    'id' => 'stable-system-label-id',
    'name' => 'Nytt systemetikettnamn',
], $systemDefaults);
assert_label_reference_support(
    ($renamedSystemLabel['id'] ?? null) === 'stable-system-label-id',
    'Renaming a system label must preserve its stored ID.'
);

$archiveFolders = [[
    'id' => 'folder-1',
    'name' => 'Mapp',
    'filenameTemplates' => [[
        'id' => 'rule-1',
        'labelIds' => ['existing-label', 'missing-top-level'],
        'template' => [
            'parts' => [[
                'type' => 'ifLabels',
                'labelIds' => ['missing-nested'],
                'thenParts' => [[
                    'type' => 'text',
                    'value' => 'match',
                ]],
                'elseParts' => [],
            ]],
        ],
    ]],
]];
$missing = missing_archive_structure_label_references($archiveFolders, ['existing-label']);
$missingIds = array_values(array_unique(array_map(
    static fn (array $reference): string => (string) ($reference['labelId'] ?? ''),
    $missing
)));
sort($missingIds);
assert_label_reference_support(
    $missingIds === ['missing-nested', 'missing-top-level'],
    'Missing-label validation must cover both filename-rule conditions and nested label conditions.'
);
assert_label_reference_support(
    count(array_filter(
        $missing,
        static fn (array $reference): bool =>
            ($reference['folderId'] ?? null) === 'folder-1'
            && ($reference['filenameTemplateId'] ?? null) === 'rule-1'
    )) === 2,
    'Missing references must retain stable folder and filename-rule navigation targets.'
);

fwrite(STDOUT, "label stable references tests passed\n");
