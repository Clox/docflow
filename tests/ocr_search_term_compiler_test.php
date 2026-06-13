<?php
declare(strict_types=1);

require_once __DIR__ . '/../public/api/_bootstrap.php';

function assert_ocr_search_compiler(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assert_ocr_search_compiler(
    build_label_rule_text_regex('org nr', false, []) === '/org\s+nr/ium',
    'Plain whitespace in literal label rules must compile to \s+.'
);
assert_ocr_search_compiler(
    build_label_rule_text_regex('org ?nr', false, []) === '/org\s*nr/ium',
    'Whitespace followed by ? in literal label rules must compile to \s* and consume the ?.'
);
assert_ocr_search_compiler(
    build_label_rule_text_regex('org *nr', false, []) === '/org\s*nr/ium',
    'Whitespace followed by * in literal label rules must compile to \s* and consume the *.'
);
assert_ocr_search_compiler(
    build_label_rule_text_regex('org\.? ?nr', true, []) === '/org\.?\s*nr/ium',
    'Whitespace followed by ? in regex label rules must compile to \s* and consume the ?.'
);
assert_ocr_search_compiler(
    build_data_field_search_term_regex('org ?nr', [], false) === '/\borg\s*nr\b/ium',
    'Data field search terms must use the same optional-whitespace literal compilation.'
);
assert_ocr_search_compiler(
    build_data_field_search_term_regex('org\.? ?nr', [], true) === '/org\.?\s*nr/ium',
    'Regex data field search terms must use the same optional-whitespace compilation.'
);
assert_ocr_search_compiler(
    build_archiving_zone_regex('org *nr') === '/org\s*nr/ium',
    'Archiving zone regex compilation must use the same optional-whitespace rule.'
);
assert_ocr_search_compiler(
    literal_pattern_with_whitespace_wildcards('org ?nr', '~') === 'org\s*nr',
    'Literal OCR helper must consume ? after whitespace.'
);
assert_ocr_search_compiler(
    regex_pattern_with_whitespace_wildcards('org\.? ?nr') === 'org\.?\s*nr',
    'Regex OCR helper must consume ? after whitespace.'
);

$examplePattern = build_data_field_search_term_regex('org\.? ?nr', [], true);
assert_ocr_search_compiler(is_string($examplePattern), 'The example regex pattern must compile.');
foreach (['org.nr', 'org nr', 'org. nr'] as $exampleText) {
    assert_ocr_search_compiler(
        @preg_match($examplePattern, $exampleText) === 1,
        'The example regex pattern must match ' . $exampleText . '.'
    );
}

$literalOptionalWhitespacePattern = build_data_field_search_term_regex('org ?nr', [], false);
assert_ocr_search_compiler(is_string($literalOptionalWhitespacePattern), 'The literal optional-whitespace pattern must compile.');
foreach (['orgnr', 'org nr'] as $exampleText) {
    assert_ocr_search_compiler(
        @preg_match($literalOptionalWhitespacePattern, $exampleText) === 1,
        'The literal optional-whitespace pattern must match ' . $exampleText . '.'
    );
}

fwrite(STDOUT, "OCR search term compiler tests passed\n");
