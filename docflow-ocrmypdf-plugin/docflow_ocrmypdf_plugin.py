from __future__ import annotations

"""Docflow OCRmyPDF plugin.

This plugin starts from OCRmyPDF's built-in Tesseract behavior and adds a hook
for rewriting recognized text before OCRmyPDF renders the PDF text layer.

Run with:
  ocrmypdf --plugin /abs/path/to/docflow_ocrmypdf_plugin.py ...
"""

import importlib.util
import json
import re
import shutil
import subprocess
import sys
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
    debug_output_dir: str | None = Field(
        default=None,
        description='Optional directory where per-page OCR debug artifacts are written',
    )
    rapidocr_python: str | None = Field(
        default=None,
        description='Optional Python interpreter used to run RapidOCR side-by-side',
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
    group.add_argument(
        '--docflow-debug-output-dir',
        dest='docflow_debug_output_dir',
        metavar='DIR',
        help='Directory where Docflow writes per-page OCR debug JSON artifacts.',
    )
    group.add_argument(
        '--docflow-rapidocr-python',
        dest='docflow_rapidocr_python',
        metavar='PYTHON',
        help='Python interpreter used to run RapidOCR per page for Docflow debug artifacts.',
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


def _get_debug_output_dir(options) -> Path | None:
    value = None
    extra_attrs = getattr(options, 'extra_attrs', {})
    if isinstance(extra_attrs, dict):
        value = extra_attrs.get('docflow_debug_output_dir')
    if not value:
        value = getattr(getattr(options, 'docflow', None), 'debug_output_dir', None)
    if not value:
        return None
    return Path(value).expanduser().resolve()


def _get_rapidocr_python(options) -> Path | None:
    value = None
    extra_attrs = getattr(options, 'extra_attrs', {})
    if isinstance(extra_attrs, dict):
        value = extra_attrs.get('docflow_rapidocr_python')
    if not value:
        value = getattr(getattr(options, 'docflow', None), 'rapidocr_python', None)
    if not value:
        return None
    return Path(value).expanduser().resolve()


@hookimpl
def check_options(options) -> None:
    builtin_tesseract_ocr.check_options(options)
    script_path = _get_transform_script_path(options)
    if script_path is not None and not script_path.is_file():
        raise BadArgsError(f'Docflow transform script not found: {script_path}')
    config_path = _get_transform_config_path(options)
    if config_path is not None and not config_path.is_file():
        raise BadArgsError(f'Docflow transform config not found: {config_path}')
    debug_output_dir = _get_debug_output_dir(options)
    if debug_output_dir is not None:
        debug_output_dir.mkdir(parents=True, exist_ok=True)
    rapidocr_python = _get_rapidocr_python(options)
    if rapidocr_python is not None and not rapidocr_python.is_file():
        raise BadArgsError(f'Docflow RapidOCR python not found: {rapidocr_python}')
    if script_path is not None and options.pdf_renderer == 'sandwich':
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


def _parse_confidence(title: str) -> int | None:
    match = re.search(r'x_wconf\s+(\d+)', title)
    if not match:
        return None
    return int(match.group(1))


def _page_debug_path(output_dir: Path, engine: str, page_number: int) -> Path:
    return output_dir / f'{engine}_page_{page_number + 1:02d}.json'


def _page_debug_text_path(output_dir: Path, engine: str, page_number: int) -> Path:
    return output_dir / f'{engine}_page_{page_number + 1:02d}.txt'


def _write_debug_json(output_dir: Path | None, engine: str, page_number: int, payload: dict[str, Any]) -> None:
    if output_dir is None:
        return
    output_dir.mkdir(parents=True, exist_ok=True)
    path = _page_debug_path(output_dir, engine, page_number)
    path.write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + '\n',
        encoding='utf-8',
    )


def _write_debug_text(output_dir: Path | None, engine: str, page_number: int, text: str) -> None:
    if output_dir is None:
        return
    output_dir.mkdir(parents=True, exist_ok=True)
    path = _page_debug_text_path(output_dir, engine, page_number)
    path.write_text(text, encoding='utf-8')


def _extract_tesseract_debug_words(hocr_path: Path) -> list[dict[str, Any]]:
    tree = ET.parse(hocr_path)
    root = tree.getroot()
    namespace = ''
    match = re.match(r'({.*})html', root.tag)
    if match:
        namespace = match.group(1)

    xpath = f".//{namespace}span[@class='ocrx_word']"
    words: list[dict[str, Any]] = []
    for word_elem in root.iterfind(xpath):
        text = ''.join(word_elem.itertext()).strip()
        if text == '':
            continue
        title = word_elem.attrib.get('title', '')
        bbox = _parse_bbox(title)
        words.append({
            'text': text,
            'bbox': list(bbox) if bbox is not None else None,
            'confidence': _parse_confidence(title),
            'title': title,
        })
    return words


@lru_cache(maxsize=1)
def _rapidocr_engine():
    from rapidocr import LangDet, LangRec, ModelType, OCRVersion, RapidOCR

    params = {
        'Global.text_score': 0.5,
        'Global.use_cls': True,
        'Det.lang_type': LangDet.EN,
        'Det.ocr_version': OCRVersion.PPOCRV4,
        'Det.model_type': ModelType.MOBILE,
        'Det.box_thresh': 0.5,
        'Det.unclip_ratio': 1.6,
        'Det.limit_side_len': 736,
        'Rec.lang_type': LangRec.LATIN,
        'Rec.ocr_version': OCRVersion.PPOCRV5,
        'Rec.model_type': ModelType.MOBILE,
    }

    return RapidOCR(params=params)


def _run_rapidocr_in_process(input_file: str) -> dict[str, Any] | None:
    try:
        output = _rapidocr_engine()(input_file, return_word_box=True)
    except Exception as exc:
        return {
            'available': False,
            'error': f'RapidOCR in-process execution failed: {exc}',
            'words': [],
        }

    words: list[dict[str, Any]] = []
    for line in getattr(output, 'word_results', ()) or ():
        if not isinstance(line, tuple):
            continue
        for item in line:
            if not isinstance(item, tuple) or len(item) < 3:
                continue
            text, score, box = item
            words.append({
                'text': text,
                'score': score,
                'bbox': box,
            })

    text_chunks: list[str] = []
    for line in output.to_json() or []:
        if isinstance(line, dict):
            value = line.get('txt', '')
            if isinstance(value, str) and value.strip() != '':
                text_chunks.append(value)

    return {
        'available': True,
        'engine': 'rapidocr',
        'lines': output.to_json(),
        'words': words,
        'text': '\n'.join(text_chunks).strip(),
    }


def _run_rapidocr_via_subprocess(input_file: str, python_path: Path) -> dict[str, Any]:
    runner = Path(__file__).with_name('run_rapidocr_page.py')
    if not runner.is_file():
        return {
            'available': False,
            'error': f'RapidOCR runner script not found: {runner}',
            'words': [],
        }

    completed = subprocess.run(
        [str(python_path), str(runner), input_file],
        capture_output=True,
        text=True,
        check=False,
    )
    if completed.returncode != 0:
        return {
            'available': False,
            'error': (completed.stderr or completed.stdout or 'RapidOCR subprocess failed').strip(),
            'words': [],
        }
    try:
        payload = json.loads(completed.stdout)
    except Exception as exc:
        return {
            'available': False,
            'error': f'Could not parse RapidOCR output: {exc}',
            'words': [],
        }
    if isinstance(payload, dict):
        return payload
    return {
        'available': False,
        'error': 'RapidOCR subprocess returned invalid payload',
        'words': [],
    }


def _rapidocr_python_candidates(options) -> list[Path]:
    candidates: list[Path] = []
    configured = _get_rapidocr_python(options)
    if configured is not None:
        candidates.append(configured)

    system_python = Path('/usr/bin/python3')
    if system_python.is_file():
        candidates.append(system_python.resolve())

    for candidate in ('python3', 'python'):
        resolved = shutil.which(candidate)
        if resolved:
            candidates.append(Path(resolved).expanduser().resolve())

    current_python = Path(sys.executable).expanduser().resolve()
    unique: list[Path] = []
    seen: set[str] = set()
    for path in candidates:
        key = str(path)
        if key in seen:
            continue
        seen.add(key)
        unique.append(path)

    if str(current_python) not in seen:
        unique.append(current_python)
    return unique


def _build_rapidocr_debug_payload(input_file, options, *, page_number: int) -> dict[str, Any]:
    payload: dict[str, Any] | None = None
    current_python = Path(sys.executable).expanduser().resolve()
    candidate_pythons = _rapidocr_python_candidates(options)

    for python_path in candidate_pythons:
        attempts: list[dict[str, Any] | None] = []
        if python_path == current_python:
            attempts.append(_run_rapidocr_in_process(str(input_file)))
            attempts.append(_run_rapidocr_via_subprocess(str(input_file), python_path))
        else:
            attempts.append(_run_rapidocr_via_subprocess(str(input_file), python_path))

        for candidate_payload in attempts:
            if candidate_payload is None:
                continue
            payload = candidate_payload
            if payload.get('available') is True:
                break
        if payload is not None and payload.get('available') is True:
            break

    if payload is None:
        payload = {
            'available': False,
            'error': 'RapidOCR is not available in the current OCR runtime',
            'words': [],
        }

    payload.setdefault('engine', 'rapidocr')
    payload['pageNumber'] = page_number + 1
    payload['pageIndex'] = page_number
    payload['sourceImage'] = Path(input_file).name
    return payload


def _write_page_debug_artifacts(input_file, hocr_path: Path, options, *, page_number: int) -> None:
    output_dir = _get_debug_output_dir(options)
    if output_dir is None:
        return

    tesseract_text_path = hocr_path.with_suffix('.txt')
    tesseract_text = ''
    if tesseract_text_path.is_file():
        try:
            tesseract_text = tesseract_text_path.read_text(encoding='utf-8')
        except Exception:
            tesseract_text = ''

    tesseract_payload = {
        'engine': 'tesseract',
        'pageNumber': page_number + 1,
        'pageIndex': page_number,
        'sourceImage': Path(input_file).name,
        'words': _extract_tesseract_debug_words(hocr_path),
        'text': tesseract_text,
    }
    _write_debug_json(output_dir, 'tesseract', page_number, tesseract_payload)
    _write_debug_text(output_dir, 'tesseract', page_number, tesseract_text)

    rapidocr_payload = _build_rapidocr_debug_payload(input_file, options, page_number=page_number)
    _write_debug_json(output_dir, 'rapidocr', page_number, rapidocr_payload)
    _write_debug_text(
        output_dir,
        'rapidocr',
        page_number,
        str(rapidocr_payload.get('text', '') or ''),
    )


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

    _write_page_debug_artifacts(
        input_file,
        output_hocr,
        options,
        page_number=page_number,
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
