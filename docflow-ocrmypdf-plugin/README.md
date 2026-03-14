# Docflow OCRmyPDF Plugin

This folder contains an OCRmyPDF plugin that starts from OCRmyPDF's built-in
Tesseract OCR behavior and can optionally rewrite recognized text before the
PDF text layer is rendered.

The current implementation is aligned with OCRmyPDF 17.x.

Purpose:
- give Docflow a clean starting point for OCR customization
- keep OCRmyPDF in charge of the PDF pipeline
- make it possible to rewrite OCR text before it is embedded into the PDF layer

## Why this exists

OCRmyPDF already ships with a built-in Tesseract plugin. This project-local
plugin wraps that built-in behavior so Docflow can evolve from a known baseline.

With no transform script configured, the plugin behaves like standard OCRmyPDF
+ Tesseract, with one visible difference:
- the PDF creator tag includes `+ Docflow plugin`

## Files

- `docflow_ocrmypdf_plugin.py`
  The plugin entry point.
- `example_transform.py`
  Minimal transform example that replaces recognized words before they are
  rendered into the PDF text layer.

## How to run it

Use the plugin file path with `ocrmypdf --plugin`.

Example:

```bash
ocrmypdf \
  --plugin /home/oscar/projects/docflow/docflow-ocrmypdf-plugin/docflow_ocrmypdf_plugin.py \
  -l swe \
  --deskew \
  --oversample 400 \
  --tesseract-thresholding sauvola \
  --tesseract-pagesegmode 6 \
  --output-type pdf \
  --mode skip \
  input.pdf output.pdf
```

To rewrite text before it is embedded into the PDF text layer:

```bash
~/.local/bin/ocrmypdf \
  --plugin /home/oscar/projects/docflow/docflow-ocrmypdf-plugin/docflow_ocrmypdf_plugin.py \
  --docflow-transform-script /home/oscar/projects/docflow/docflow-ocrmypdf-plugin/example_transform.py \
  -l swe \
  --output-type pdf \
  input.pdf output.pdf
```

If you are on an older OCRmyPDF release such as 15.x, the equivalent flag is:

```bash
--skip-text
```

instead of:

```bash
--mode skip
```

## What it does today

The plugin:
- blocks OCRmyPDF's built-in Tesseract plugin
- registers the same `tesseract` option model as the built-in plugin
- registers a `docflow` option model for transform settings
- re-exposes the same Tesseract CLI options
- reuses the same option validation
- reuses the same OCR image filtering
- uses a Docflow engine class that delegates to the built-in
  `TesseractOcrEngine` and adds a text rewrite step

## Where to customize later

The place to start is `DocflowTesseractOcrEngine`.

The plugin now uses OCRmyPDF 17.x's `generate_ocr()` path:
- it runs Tesseract to hOCR
- rewrites recognized word text if a transform script is configured
- parses the hOCR into OCRmyPDF's `OcrElement` tree
- returns that modified tree to OCRmyPDF's renderer

The transform script API is intentionally small:
- `transform_word(text, *, page_number, bbox, title, options) -> str`
- `transform_sidecar(text, *, page_number, options) -> str`
- if only `transform_text()` exists, it is used as a generic fallback

## Important limitation

`--pdf-renderer sandwich` bypasses the editable hOCR/tree path. When
`--docflow-transform-script` is used, the plugin rejects that combination and
expects OCRmyPDF's default `auto`/`fpdf2` renderer path.

## Version note

This plugin was first written against OCRmyPDF 15.x and then updated to match
OCRmyPDF 17.x, where the plugin API now expects:
- registered option namespaces via `register_options()`
- nested `options.tesseract.*`
- `get_ocr_engine(options)`
