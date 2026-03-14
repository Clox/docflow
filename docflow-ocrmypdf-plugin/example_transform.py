"""Example Docflow OCR transform script."""

REPLACEMENTS = {
    'KARLSTADS': 'TESTSTADS',
}


def transform_word(text, *, page_number, bbox, title, options):
    return REPLACEMENTS.get(text, text)


def transform_sidecar(text, *, page_number, options):
    for source, replacement in REPLACEMENTS.items():
        text = text.replace(source, replacement)
    return text
