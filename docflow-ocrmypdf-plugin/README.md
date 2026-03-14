# Docflow OCRmyPDF Plugin

This folder contains a minimal OCRmyPDF plugin that mirrors OCRmyPDF's built-in
Tesseract OCR behavior.

The current implementation is aligned with OCRmyPDF 17.x.

Purpose:
- give Docflow a clean starting point for OCR customization
- keep OCRmyPDF in charge of the PDF pipeline
- make it possible to later replace only the OCR engine behavior

## Why this exists

OCRmyPDF already ships with a built-in Tesseract plugin. This project-local
plugin wraps that built-in behavior so Docflow can evolve from a known baseline.

Right now the plugin does not change OCR output. It behaves like standard
OCRmyPDF + Tesseract, with one difference:
- the PDF creator tag includes `+ Docflow plugin`

## Files

- `docflow_ocrmypdf_plugin.py`
  The plugin entry point.

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
- re-exposes the same Tesseract CLI options
- reuses the same option validation
- reuses the same OCR image filtering
- uses a Docflow engine class that currently delegates to the built-in
  `TesseractOcrEngine`

## Where to customize later

The place to start is `DocflowTesseractOcrEngine`.

Most likely extension points:
- `generate_hocr(...)`
- `generate_pdf(...)`

If Docflow later needs corrected text inside the PDF text layer, this plugin is
where that work should begin.

## Important limitation

This starter plugin does not yet alter recognized text before it goes into the
PDF. It is only a stable baseline that mimics standard behavior.

## Version note

This plugin was first written against OCRmyPDF 15.x and then updated to match
OCRmyPDF 17.x, where the plugin API now expects:
- registered option namespaces via `register_options()`
- nested `options.tesseract.*`
- `get_ocr_engine(options)`
