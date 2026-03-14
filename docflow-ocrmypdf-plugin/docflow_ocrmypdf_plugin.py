from __future__ import annotations

"""Docflow OCRmyPDF plugin starter.

This plugin mirrors OCRmyPDF's built-in Tesseract behavior so Docflow has a
clean place to start customizing OCR output later.

Run with:
  ocrmypdf --plugin /abs/path/to/docflow_ocrmypdf_plugin.py ...
"""

from PIL import Image

from ocrmypdf import hookimpl
from ocrmypdf._jobcontext import PageContext
from ocrmypdf.builtin_plugins import tesseract_ocr as builtin_tesseract_ocr


@hookimpl
def initialize(plugin_manager) -> None:
    """Disable the built-in Tesseract plugin so this plugin becomes the OCR engine."""
    plugin_manager.set_blocked('ocrmypdf.builtin_plugins.tesseract_ocr')


@hookimpl
def add_options(parser) -> None:
    builtin_tesseract_ocr.add_options(parser)


@hookimpl
def register_options():
    return builtin_tesseract_ocr.register_options()


@hookimpl
def check_options(options) -> None:
    builtin_tesseract_ocr.check_options(options)


@hookimpl
def validate(pdfinfo, options) -> None:
    builtin_tesseract_ocr.validate(pdfinfo, options)


@hookimpl
def filter_ocr_image(page: PageContext, image: Image.Image) -> Image.Image:
    return builtin_tesseract_ocr.filter_ocr_image(page, image)


class DocflowTesseractOcrEngine(builtin_tesseract_ocr.TesseractOcrEngine):
    """Start from OCRmyPDF's standard Tesseract behavior and customize later.

    The current implementation is intentionally identical to the built-in OCR
    engine. When Docflow is ready to inject OCR corrections into the PDF text
    layer, this is the class to extend.
    """

    @staticmethod
    def creator_tag(options):
        base = builtin_tesseract_ocr.TesseractOcrEngine.creator_tag(options)
        return f"{base} + Docflow plugin"

    @staticmethod
    def generate_hocr(input_file, output_hocr, output_text, options) -> None:
        builtin_tesseract_ocr.TesseractOcrEngine.generate_hocr(
            input_file,
            output_hocr,
            output_text,
            options,
        )

    @staticmethod
    def generate_pdf(input_file, output_pdf, output_text, options) -> None:
        builtin_tesseract_ocr.TesseractOcrEngine.generate_pdf(
            input_file,
            output_pdf,
            output_text,
            options,
        )


@hookimpl
def get_ocr_engine(options=None):
    return DocflowTesseractOcrEngine()
