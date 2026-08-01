<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_filename_v2(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function legacy_filename_render(array $parts, array $values): string
{
    $result = '';
    foreach ($parts as $part) {
        if (!is_array($part)) {
            continue;
        }
        $type = $part['type'] ?? 'text';
        if ($type === 'text') {
            $result .= (string) ($part['value'] ?? '');
            continue;
        }
        if ($type === 'ifLabels') {
            $selected = is_array($values['__labelIds'] ?? null) ? $values['__labelIds'] : [];
            $wanted = is_array($part['labelIds'] ?? null) ? $part['labelIds'] : [];
            $matched = ($part['mode'] ?? 'any') === 'all'
                ? $wanted !== [] && array_diff($wanted, $selected) === []
                : array_intersect($wanted, $selected) !== [];
            $value = legacy_filename_render($matched ? ($part['thenParts'] ?? []) : ($part['elseParts'] ?? []), $values);
        } elseif ($type === 'firstAvailable') {
            $value = '';
            foreach ($part['parts'] ?? [] as $candidate) {
                $value = legacy_filename_render([$candidate], $values);
                if ($value !== '') break;
            }
        } else {
            $key = (string) ($part['key'] ?? '');
            $value = trim((string) ($values[$key] ?? ''));
        }
        if ($value !== '') {
            $result .= legacy_filename_render($part['prefixParts'] ?? [], $values);
            $result .= $value;
            $result .= legacy_filename_render($part['suffixParts'] ?? [], $values);
        }
    }
    return $result;
}

function assert_no_legacy_affixes(array $parts): void
{
    foreach ($parts as $part) {
        if (!is_array($part)) continue;
        assert_filename_v2(!array_key_exists('prefixParts', $part) && !array_key_exists('suffixParts', $part), 'V2-noder får inte bära gamla affixegenskaper.');
        foreach (['parts', 'thenParts', 'elseParts'] as $key) {
            if (is_array($part[$key] ?? null)) assert_no_legacy_affixes($part[$key]);
        }
    }
}

$cases = [
    [['type' => 'dataField', 'key' => 'amount', 'prefixParts' => [], 'suffixParts' => []], ['dataField']],
    [['type' => 'dataField', 'key' => 'amount', 'prefixParts' => [['type' => 'text', 'value' => ' - ']], 'suffixParts' => []], ['prefix', 'dataField']],
    [['type' => 'dataField', 'key' => 'amount', 'prefixParts' => [], 'suffixParts' => [['type' => 'text', 'value' => 'kr']]], ['dataField', 'suffix']],
    [['type' => 'dataField', 'key' => 'amount', 'prefixParts' => [['type' => 'text', 'value' => ' ( ']], 'suffixParts' => [['type' => 'text', 'value' => ' kr ']]], ['prefix', 'dataField', 'suffix']],
];
foreach ($cases as [$legacyPart, $expectedTypes]) {
    $migrated = migrate_filename_template_to_v2(['parts' => [$legacyPart]]);
    assert_filename_v2(($migrated['version'] ?? null) === 2, 'Varje migrerad mall ska få version 2.');
    assert_filename_v2(array_column($migrated['parts'], 'type') === $expectedTypes, 'Affixnoder ska infogas som syskon i rätt ordning.');
    assert_no_legacy_affixes($migrated['parts']);
}
$whitespace = migrate_filename_template_to_v2(['parts' => [$cases[3][0]]]);
assert_filename_v2(($whitespace['parts'][0]['value'] ?? null) === ' ( ', 'Prefixets blanksteg ska bevaras exakt.');
assert_filename_v2(($whitespace['parts'][2]['value'] ?? null) === ' kr ', 'Suffixets blanksteg ska bevaras exakt.');

$legacy = ['templateSetting' => 'preserved', 'parts' => [[
    'type' => 'ifLabels', 'mode' => 'any', 'labelIds' => ['invoice'],
    'thenParts' => [[
        'type' => 'dataField', 'key' => 'amount',
        'prefixParts' => [['type' => 'text', 'value' => ' - ']],
        'suffixParts' => [['type' => 'text', 'value' => 'kr']],
        'customSetting' => 'kept',
    ]],
    'elseParts' => [['type' => 'text', 'value' => 'Annat']],
    'prefixParts' => [['type' => 'text', 'value' => '[']],
    'suffixParts' => [['type' => 'text', 'value' => ']']],
]]];
$migrated = migrate_filename_template_to_v2($legacy);
assert_filename_v2(($migrated['templateSetting'] ?? null) === 'preserved', 'Inställningar på mallnivå ska bevaras.');
assert_no_legacy_affixes($migrated['parts']);
$condition = $migrated['parts'][1] ?? [];
assert_filename_v2(array_column($condition['thenParts'] ?? [], 'type') === ['prefix', 'dataField', 'suffix'], 'Migreringen ska vara rekursiv i villkorsgrenar.');
assert_filename_v2(($condition['thenParts'][1]['customSetting'] ?? null) === 'kept', 'Övriga nodinställningar ska bevaras.');
foreach ([
    ['amount' => '20000,00', '__labelIds' => ['invoice']],
    ['amount' => '', '__labelIds' => ['invoice']],
    ['amount' => null, '__labelIds' => ['invoice']],
    ['amount' => '20000,00', '__labelIds' => []],
] as $context) {
    assert_filename_v2(
        legacy_filename_render($legacy['parts'], $context) === evaluate_filename_template_parts_backend($migrated['parts'], $context),
        'Rendering före och efter migrering ska vara identisk i samtliga villkorsfall.'
    );
}
$again = migrate_filename_template_to_v2($migrated);
assert_filename_v2($again === $migrated, 'Migreringen ska vara idempotent.');

$render = static fn(array $parts, mixed $amount): string => evaluate_filename_template_parts_backend($parts, ['amount' => $amount]);
$affixParts = [
    ['type' => 'prefix', 'value' => ' - '],
    ['type' => 'prefix', 'value' => '('],
    ['type' => 'dataField', 'key' => 'amount'],
    ['type' => 'suffix', 'value' => 'kr'],
    ['type' => 'suffix', 'value' => ')'],
];
assert_filename_v2($render($affixParts, '20000,00') === ' - (20000,00kr)', 'Flera prefix och suffix ska följa sekvensordningen.');
foreach ([null, '', []] as $empty) {
    assert_filename_v2($render($affixParts, $empty) === '', 'Alla kopplade affix ska försvinna när huvudvärdet saknas.');
}
assert_filename_v2($render($affixParts, '0') === ' - (0kr)', 'Strängen 0 ska vara renderbar.');
assert_filename_v2($render([['type' => 'prefix', 'value' => 'x']], '1') === '', 'Ogiltigt prefix ska inte renderas.');
assert_filename_v2($render([['type' => 'suffix', 'value' => 'x']], '1') === '', 'Ogiltigt suffix ska inte renderas.');
assert_filename_v2($render([
    ['type' => 'prefix', 'parts' => [['type' => 'text', 'value' => '[']]],
    ['type' => 'dataField', 'key' => 'amount'],
], '10') === '[10', 'Ett migrerat sammansatt affix ska fortsatt kunna rendera en nästlad sekvens.');

$representativeTemplate = [
    ['type' => 'systemField', 'key' => 'primary_date'],
    ['type' => 'prefix', 'value' => ' - '],
    ['type' => 'systemField', 'key' => 'title'],
    ['type' => 'prefix', 'value' => ' - '],
    ['type' => 'dataField', 'key' => 'amount'],
    ['type' => 'suffix', 'value' => 'kr'],
];
assert_filename_v2(
    evaluate_filename_template_parts_backend($representativeTemplate, [
        'primary_date' => '2026-03-25',
        'title' => 'Beslut',
        'amount' => '860,00',
    ]) === '2026-03-25 - Beslut - 860,00kr',
    'Den representativa datum/rubrik/belopp-mallen ska ge samma fullständiga filnamn när alla värden finns.'
);
assert_filename_v2(
    evaluate_filename_template_parts_backend($representativeTemplate, [
        'primary_date' => '2026-03-25',
        'title' => 'Beslut',
        'amount' => null,
    ]) === '2026-03-25 - Beslut',
    'Beloppets prefix och suffix ska försvinna tillsammans när beloppet saknas.'
);

$conditionParts = [[
    'type' => 'prefix', 'value' => ' - ',
], [
    'type' => 'ifLabels', 'mode' => 'any', 'labelIds' => ['invoice'],
    'thenParts' => [['type' => 'dataField', 'key' => 'amount']],
    'elseParts' => [],
]];
assert_filename_v2(evaluate_filename_template_parts_backend($conditionParts, ['amount' => '10', '__labelIds' => ['invoice']]) === ' - 10', 'Prefix före villkor ska bero på grenens slutresultat.');
assert_filename_v2(evaluate_filename_template_parts_backend($conditionParts, ['amount' => '10', '__labelIds' => []]) === '', 'Prefix före tom villkorsgren ska försvinna.');
$branchBoundary = [[
    'type' => 'ifLabels', 'mode' => 'any', 'labelIds' => ['invoice'],
    'thenParts' => [['type' => 'prefix', 'value' => 'x']],
    'elseParts' => [['type' => 'text', 'value' => 'y']],
], ['type' => 'dataField', 'key' => 'amount']];
assert_filename_v2(evaluate_filename_template_parts_backend($branchBoundary, ['amount' => '10', '__labelIds' => ['invoice']]) === '10', 'Affix får inte passera en villkorsgräns.');

$oldFirstAvailable = ['parts' => [[
    'type' => 'firstAvailable', 'parts' => [[
        'type' => 'dataField', 'key' => 'missing', 'prefixParts' => [['type' => 'text', 'value' => 'x']], 'suffixParts' => [],
    ], [
        'type' => 'dataField', 'key' => 'amount', 'prefixParts' => [['type' => 'text', 'value' => ' - ']], 'suffixParts' => [['type' => 'text', 'value' => 'kr']],
    ]], 'prefixParts' => [], 'suffixParts' => [],
]]];
$newFirstAvailable = migrate_filename_template_to_v2($oldFirstAvailable);
assert_filename_v2(evaluate_filename_template_parts_backend($newFirstAvailable['parts'], ['amount' => '20']) === ' - 20kr', 'Alternativa kandidaters egna affix ska bevaras i en nästlad sekvens.');

$rules = ['archiveFolders' => [[
    'id' => 'folder', 'pathTemplate' => ['parts' => [['type' => 'text', 'value' => 'Path']]],
    'filenameTemplates' => [['id' => 'rule', 'template' => $legacy, 'labelIds' => []]],
]]];
$stats = null;
$migratedRules = migrate_archiving_rules_filename_templates_to_v2($rules, $stats);
assert_filename_v2(($stats['migratedTemplates'] ?? 0) === 1 && ($stats['migratedPathTemplates'] ?? 0) === 1, 'Alla sparade malltyper ska migreras, inte bara öppnade UI-objekt.');
$secondStats = null;
$secondRules = migrate_archiving_rules_filename_templates_to_v2($migratedRules, $secondStats);
assert_filename_v2(($secondStats['migratedTemplates'] ?? -1) === 0 && $secondRules === $migratedRules, 'Lagringsmigreringen ska vara idempotent.');

$app = file_get_contents(__DIR__ . '/../public/app.js');
$css = file_get_contents(__DIR__ . '/../public/style.css');
assert_filename_v2(is_string($app) && str_contains($app, "type: 'prefix'") && str_contains($app, "type: 'suffix'"), 'UI ska kunna lägga till separata affixchip.');
assert_filename_v2(is_string($app) && str_contains($app, 'syncFilenameTemplateSelectWidth'), 'Chipselecter ska använda gemensam dynamisk breddmätning.');
assert_filename_v2(
    is_string($app)
    && str_contains($app, 'filenameTemplateControlHorizontalChrome')
    && is_string($css)
    && preg_match(
        '/\.filename-template-inline-token-select,\s*\.filename-template-inline-token-input\s*\{[^}]*padding:\s*0;/s',
        $css
    ) === 1
    && preg_match(
        '/\.filename-template-inline-token-input\s*\{[^}]*padding:\s*0 2px;/s',
        $css
    ) === 1,
    'Chipselecter ska sakna sidpadding medan textkontroller ska ha två pixlar; breddmätningen ska använda deras verkliga kanter.'
);
assert_filename_v2(
    is_string($app)
    && str_contains($app, 'syncFilenameTemplateTextInputWidth')
    && str_contains($app, 'measureFilenameTemplateControlText'),
    'Prefix- och suffixfält ska mäta innehållet dynamiskt med samma textmätning som chipselecter.'
);
assert_filename_v2(
    is_string($css)
    && str_contains($css, '--filename-template-chip-corner-radius: 10px;')
    && str_contains($css, '--filename-template-chip-corner-radius: 18px;')
    && preg_match(
        '/\.filename-template-inline-token-select,\s*\.filename-template-inline-token-input\s*\{[^}]*border-radius:\s*var\(--filename-template-chip-corner-radius\);/s',
        $css
    ) === 1
    && preg_match(
        '/\.filename-template-inline-token-center \.filename-template-inline-token-slot--(?:candidates|branch)\.is-slot\s*\{[^}]*border-radius:\s*var\(--filename-template-chip-corner-radius\);/s',
        $css
    ) === 1,
    'Fält och interna ytor ska använda samma hörnradie som sitt yttre chip.'
);
assert_filename_v2(
    is_string($css)
    && preg_match(
        '/\.filename-template-inline-token-center--stacked\s*> \.filename-template-inline-token-select\s*\+ \.filename-template-inline-token-floating-field\s*\{[^}]*margin-top:\s*9px;/s',
        $css
    ) === 1,
    'Ett sekundärt selectfält ska lämna plats för sin flytande etikett under föregående kontroll.'
);
assert_filename_v2(
    is_string($app)
    && str_contains($app, 'option.textContent = formatOption.label;')
    && str_contains($app, "dateFormatSelect.setAttribute('aria-label', 'Datumformat');")
    && is_string($css)
    && str_contains($css, '.filename-template-inline-token-center--stacked.filename-template-inline-token-center--date'),
    'Datumchip ska visa korta formatnamn och inte ärva sammansatta chips fasta minimibredd.'
);
$insertOptionsStart = is_string($app) ? strpos($app, 'function filenameTemplateInsertOptions()') : false;
$insertOptionsEnd = $insertOptionsStart !== false ? strpos($app, 'function createFilenameTemplateLabelPicker', $insertOptionsStart) : false;
$insertOptionsSource = $insertOptionsStart !== false && $insertOptionsEnd !== false
    ? substr($app, $insertOptionsStart, $insertOptionsEnd - $insertOptionsStart)
    : '';
assert_filename_v2(!str_contains($insertOptionsSource, "type: 'text'"), 'Fast text ska skrivas direkt och får inte erbjudas som chiptyp.');
assert_filename_v2(!str_contains($insertOptionsSource, "type: 'labels'"), 'Etiketter ska inte erbjudas som chiptyp i filnamnseditorn.');
assert_filename_v2(
    is_string($app)
    && str_contains($app, "new Set(['sender_name_in_document', 'sender_mark_in_document'])")
    && str_contains($app, 'function filenameTemplateSystemFieldDefinition(fieldKey)')
    && str_contains($app, "} else if (tokenPart.type === 'systemField')"),
    'Systemdatafält ska vara fasta chip per fält, samtidigt som interna avsändarträffar inte erbjuds som nya chip.'
);
assert_filename_v2(
    is_string($app)
    && str_contains($app, "if (part && part.type === 'text')")
    && str_contains($app, 'buildRootTextSlot(pendingText, rootSequence)'),
    'Sparade textdelar ska återladdas som direkt redigerbar text, inte som chip.'
);
assert_filename_v2(is_string($css) && str_contains($css, 'flex-wrap: nowrap') && str_contains($css, 'margin: 8px 0 10px;'), 'Rotsekvensen ska ligga på en rad och linjera utan gammalt vänsterindrag.');
assert_filename_v2(is_string($css) && str_contains($css, '.filename-template-dom-token.is-affix-invalid'), 'Ogiltiga affix ska ha en visuell varningsstatus.');
assert_filename_v2(
    is_string($css)
    && preg_match('/\.filename-template-root-slot\.is-slot\s*\{[^}]*min-width:\s*8px;/s', $css) === 1
    && str_contains(
        $css,
        '.filename-template-dom-token.is-affix-linked:not(.is-affix-group-end) + .filename-template-root-slot'
    ),
    'Separata chip ska ha luft emellan medan tomrummet endast kollapsar inne i en prefix-/suffixgrupp.'
);
assert_filename_v2(
    is_string($css)
    && preg_match('/\.filename-template-inline-token-center\s*\{[^}]*margin:\s*-1px 0;/s', $css) === 1,
    'Chipets synliga mittdel får inte sträcka sig utanför den horisontella plats som reserveras i textflödet.'
);
assert_filename_v2(
    is_string($app)
    && str_contains($app, 'const tokenNavigationTargets = (token) =>')
    && str_contains($app, 'const focusTokenNavigationTarget = (target, direction) =>')
    && str_contains($app, 'const moveCaretAcrossTextInputBoundary = (input, direction) =>')
    && str_contains($app, "target.setSelectionRange(offset, offset);")
    && str_contains($app, "'.filename-template-inline-token-input, .filename-template-editable'")
    && str_contains($app, 'focusRootSlotAdjacentToToken(nearbyToken, direction)')
    && str_contains($app, 'setCaretAdjacentToNode(nearbyOwnerEditable, nearbyToken, direction)')
    && str_contains($app, "event.stopPropagation();"),
    'Piltangenter ska gå in i redigerbara chipytor och hoppa över chip utan redigerbar kontroll.'
);

fwrite(STDOUT, "filename template v2 tests passed\n");
