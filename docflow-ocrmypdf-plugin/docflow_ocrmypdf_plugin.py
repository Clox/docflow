from __future__ import annotations

"""Docflow OCRmyPDF plugin.

This plugin starts from OCRmyPDF's built-in Tesseract behavior and adds a hook
for rewriting recognized text before OCRmyPDF renders the PDF text layer.

Run with:
  ocrmypdf --plugin /abs/path/to/docflow_ocrmypdf_plugin.py ...
"""

import importlib.util
import re
from functools import lru_cache
from pathlib import Path
from tempfile import TemporaryDirectory
from typing import Any
from xml.etree import ElementTree as ET

from PIL import Image
from pydantic import BaseModel, Field

from ocrmypdf import hookimpl
from ocrmypdf._jobcontext import PageContext
from ocrmypdf.builtin_plugins import tesseract_ocr as builtin_tesseract_ocr
from ocrmypdf.exceptions import BadArgsError
from ocrmypdf.hocrtransform import HocrParser


@hookimpl
def initialize(plugin_manager) -> None:
    """Disable the built-in Tesseract plugin so this plugin becomes the OCR engine."""
    plugin_manager.set_blocked('ocrmypdf.builtin_plugins.tesseract_ocr')


class DocflowOptions(BaseModel):
    transform_script: str | None = Field(
        default=None,
        description='Optional Python file that rewrites OCR text before rendering',
    )
    transform_config: str | None = Field(
        default=None,
        description='Optional JSON file with Docflow OCR transform settings',
    )


@hookimpl
def add_options(parser) -> None:
    builtin_tesseract_ocr.add_options(parser)
    group = parser.add_argument_group(
        'Docflow OCR',
        'Docflow-specific OCR text transformation options',
    )
    group.add_argument(
        '--docflow-transform-script',
        dest='docflow_transform_script',
        metavar='FILE',
        help=(
            'Python file that can rewrite recognized text before OCRmyPDF renders '
            'the PDF text layer. The file may define transform_word() and/or '
            'transform_sidecar().'
        ),
    )
    group.add_argument(
        '--docflow-transform-config',
        dest='docflow_transform_config',
        metavar='FILE',
        help='JSON file with data used by the Docflow transform runtime.',
    )


@hookimpl
def register_options():
    models = dict(builtin_tesseract_ocr.register_options())
    models['docflow'] = DocflowOptions
    return models


def _get_transform_script_path(options) -> Path | None:
    script = None
    extra_attrs = getattr(options, 'extra_attrs', {})
    if isinstance(extra_attrs, dict):
        script = extra_attrs.get('docflow_transform_script')
    if not script:
        script = getattr(getattr(options, 'docflow', None), 'transform_script', None)
    if not script:
        return None
    return Path(script).expanduser().resolve()


def _get_transform_config_path(options) -> Path | None:
    config = None
    extra_attrs = getattr(options, 'extra_attrs', {})
    if isinstance(extra_attrs, dict):
        config = extra_attrs.get('docflow_transform_config')
    if not config:
        config = getattr(getattr(options, 'docflow', None), 'transform_config', None)
    if not config:
        return None
    return Path(config).expanduser().resolve()


@hookimpl
def check_options(options) -> None:
    builtin_tesseract_ocr.check_options(options)
    script_path = _get_transform_script_path(options)
    if script_path is None:
        return
    if not script_path.is_file():
        raise BadArgsError(f'Docflow transform script not found: {script_path}')
    config_path = _get_transform_config_path(options)
    if config_path is not None and not config_path.is_file():
        raise BadArgsError(f'Docflow transform config not found: {config_path}')
    if options.pdf_renderer == 'sandwich':
        raise BadArgsError(
            'Docflow text transformation requires pdf_renderer auto/fpdf2. '
            'sandwich bypasses the editable HOCR/text path.'
        )


@hookimpl
def validate(pdfinfo, options) -> None:
    builtin_tesseract_ocr.validate(pdfinfo, options)


@hookimpl
def filter_ocr_image(page: PageContext, image: Image.Image) -> Image.Image:
    return builtin_tesseract_ocr.filter_ocr_image(page, image)


_BBOX_RE = re.compile(r'bbox\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)')


@lru_cache(maxsize=16)
def _load_transform_module(script_path: str):
    path = Path(script_path)
    module_name = f'docflow_ocr_transform_{abs(hash(path))}'
    spec = importlib.util.spec_from_file_location(module_name, path)
    if spec is None or spec.loader is None:
        raise BadArgsError(f'Unable to load Docflow transform script: {path}')
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def _parse_bbox(title: str) -> tuple[int, int, int, int] | None:
    match = _BBOX_RE.search(title)
    if not match:
        return None
    return tuple(int(group) for group in match.groups())


def _call_transform(module, name: str, text: str, **kwargs: Any) -> str:
    func = getattr(module, name, None)
    if func is None:
        return text
    new_text = func(text, **kwargs)
    if new_text is None:
        return text
    if not isinstance(new_text, str):
        raise BadArgsError(
            f'Docflow transform function {name}() must return str or None'
        )
    return new_text


def _transform_sidecar_text(text: str, module, *, page_number: int, options) -> str:
    if hasattr(module, 'transform_sidecar'):
        return _call_transform(
            module,
            'transform_sidecar',
            text,
            page_number=page_number,
            options=options,
        )
    if hasattr(module, 'transform_text'):
        return _call_transform(
            module,
            'transform_text',
            text,
            kind='sidecar',
            page_number=page_number,
            bbox=None,
            title='',
            options=options,
        )
    return text


def _rewrite_hocr_words(hocr_path: Path, module, *, page_number: int, options) -> None:
    tree = ET.parse(hocr_path)
    root = tree.getroot()
    namespace = ''
    match = re.match(r'({.*})html', root.tag)
    if match:
        namespace = match.group(1)

    xpath = f".//{namespace}span[@class='ocrx_word']"
    changed = False
    for word_elem in root.iterfind(xpath):
        original = ''.join(word_elem.itertext()).strip()
        if original == '':
            continue
        title = word_elem.attrib.get('title', '')
        if hasattr(module, 'transform_word'):
            new_text = _call_transform(
                module,
                'transform_word',
                original,
                page_number=page_number,
                bbox=_parse_bbox(title),
                title=title,
                options=options,
            )
        elif hasattr(module, 'transform_text'):
            new_text = _call_transform(
                module,
                'transform_text',
                original,
                kind='word',
                page_number=page_number,
                bbox=_parse_bbox(title),
                title=title,
                options=options,
            )
        else:
            new_text = original
        if new_text == original:
            continue
        for child in list(word_elem):
            word_elem.remove(child)
        word_elem.text = new_text
        changed = True

    if changed:
        tree.write(hocr_path, encoding='utf-8', xml_declaration=True)


def _generate_transformed_hocr(
    input_file,
    output_hocr,
    output_text,
    options,
    *,
    page_number: int = 0,
) -> None:
    builtin_tesseract_ocr.TesseractOcrEngine.generate_hocr(
        input_file,
        output_hocr,
        output_text,
        options,
    )

    script_path = _get_transform_script_path(options)
    if script_path is None:
        return

    module = _load_transform_module(str(script_path))
    _rewrite_hocr_words(output_hocr, module, page_number=page_number, options=options)

    sidecar_text = ''
    if output_text.exists():
        sidecar_text = output_text.read_text(encoding='utf-8')
    output_text.write_text(
        _transform_sidecar_text(
            sidecar_text,
            module,
            page_number=page_number,
            options=options,
        ),
        encoding='utf-8',
    )


class DocflowTesseractOcrEngine(builtin_tesseract_ocr.TesseractOcrEngine):
    """Tesseract OCR engine with an optional text-rewrite step."""

    @staticmethod
    def creator_tag(options):
        base = builtin_tesseract_ocr.TesseractOcrEngine.creator_tag(options)
        return f"{base} + Docflow plugin"

    @staticmethod
    def supports_generate_ocr() -> bool:
        return True

    @staticmethod
    def generate_ocr(input_file, options, page_number=0):
        with TemporaryDirectory(prefix='docflow-ocr-') as temp_dir:
            temp_path = Path(temp_dir)
            hocr_path = temp_path / 'page.hocr'
            text_path = temp_path / 'page.txt'
            _generate_transformed_hocr(
                input_file,
                hocr_path,
                text_path,
                options,
                page_number=page_number,
            )
            page = HocrParser(hocr_path).parse()
            text_content = (
                text_path.read_text(encoding='utf-8') if text_path.exists() else ''
            )
            return page, text_content

    @staticmethod
    def generate_hocr(input_file, output_hocr, output_text, options) -> None:
        _generate_transformed_hocr(
            input_file,
            output_hocr,
            output_text,
            options,
            page_number=0,
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
