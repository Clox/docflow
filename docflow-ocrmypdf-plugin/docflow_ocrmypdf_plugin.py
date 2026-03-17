from __future__ import annotations

"""Docflow OCRmyPDF plugin.

This plugin starts from OCRmyPDF's built-in Tesseract behavior and adds a hook
for rewriting recognized text before OCRmyPDF renders the PDF text layer.

Run with:
  ocrmypdf --plugin /abs/path/to/docflow_ocrmypdf_plugin.py ...
"""

import importlib.util
import json
import math
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


def _parse_score(title: str) -> float | None:
    confidence = _parse_confidence(title)
    if confidence is None:
        return None
    return confidence / 100.0


def _get_image_size(input_file: str | Path) -> tuple[int, int] | None:
    try:
        with Image.open(input_file) as image:
            return image.size
    except Exception:
        return None


def _get_image_dpi(input_file: str | Path) -> tuple[float, float] | None:
    try:
        with Image.open(input_file) as image:
            dpi = image.info.get('dpi')
            if isinstance(dpi, tuple) and len(dpi) >= 2:
                xdpi = float(dpi[0]) if isinstance(dpi[0], (int, float)) else 0.0
                ydpi = float(dpi[1]) if isinstance(dpi[1], (int, float)) else 0.0
                if xdpi > 0 and ydpi > 0:
                    return (xdpi, ydpi)
    except Exception:
        return None
    return None


def _scale_bbox_value(value: float | int, scale: float) -> int:
    return int(round(float(value) / scale))


def _scale_down_debug_bbox(bbox: Any, scale_x: float, scale_y: float) -> Any:
    if scale_x == 1.0 and scale_y == 1.0:
        return bbox

    if isinstance(bbox, list):
        if len(bbox) == 4 and all(isinstance(value, (int, float)) for value in bbox):
            return [
                _scale_bbox_value(bbox[0], scale_x),
                _scale_bbox_value(bbox[1], scale_y),
                _scale_bbox_value(bbox[2], scale_x),
                _scale_bbox_value(bbox[3], scale_y),
            ]
        scaled_points = []
        for point in bbox:
            if isinstance(point, (list, tuple)) and len(point) >= 2:
                scaled_points.append([
                    _scale_bbox_value(point[0], scale_x),
                    _scale_bbox_value(point[1], scale_y),
                ])
        return scaled_points if scaled_points else bbox

    if isinstance(bbox, dict):
        scaled = dict(bbox)
        for key in ('x0', 'x1'):
            if isinstance(scaled.get(key), (int, float)):
                scaled[key] = _scale_bbox_value(scaled[key], scale_x)
        for key in ('y0', 'y1'):
            if isinstance(scaled.get(key), (int, float)):
                scaled[key] = _scale_bbox_value(scaled[key], scale_y)
        return scaled

    return bbox


def _scale_down_tesseract_payload(
    payload: dict[str, Any],
    scale_x: float,
    scale_y: float,
    original_image_path: str | Path,
) -> dict[str, Any]:
    if scale_x == 1.0 and scale_y == 1.0:
        image_size = _get_image_size(original_image_path)
        if image_size is not None:
            payload['pageWidth'] = int(image_size[0])
            payload['pageHeight'] = int(image_size[1])
        payload['sourceImage'] = Path(original_image_path).name
        return payload

    scaled_words = []
    for word in payload.get('words') or []:
        if not isinstance(word, dict):
            continue
        scaled_word = dict(word)
        scaled_word['bbox'] = _scale_down_debug_bbox(word.get('bbox'), scale_x, scale_y)
        raw = word.get('raw')
        if isinstance(raw, dict):
            title = raw.get('hocrTitle')
            if isinstance(title, str):
                bbox = _parse_bbox(title)
                if bbox is not None:
                    scaled_bbox = {
                        'x0': _scale_bbox_value(bbox[0], scale_x),
                        'y0': _scale_bbox_value(bbox[1], scale_y),
                        'x1': _scale_bbox_value(bbox[2], scale_x),
                        'y1': _scale_bbox_value(bbox[3], scale_y),
                    }
                    score = word.get('score')
                    scaled_raw = dict(raw)
                    scaled_raw['hocrTitle'] = _replace_title_bbox_and_score(
                        title,
                        scaled_bbox,
                        float(score) if isinstance(score, (int, float)) else None,
                    )
                    scaled_word['raw'] = scaled_raw
        scaled_words.append(scaled_word)

    image_size = _get_image_size(original_image_path)
    scaled_payload = {
        **payload,
        'sourceImage': Path(original_image_path).name,
        'words': scaled_words,
    }
    if image_size is not None:
        scaled_payload['pageWidth'] = int(image_size[0])
        scaled_payload['pageHeight'] = int(image_size[1])
    return scaled_payload


def _prepare_tesseract_ocr_input(
    input_file: str | Path,
    workdir: Path,
    *,
    target_dpi: float = 500.0,
) -> tuple[Path, float, float]:
    input_path = Path(input_file)
    source_dpi = _get_image_dpi(input_path) or (300.0, 300.0)
    scale_x = target_dpi / max(source_dpi[0], 1.0)
    scale_y = target_dpi / max(source_dpi[1], 1.0)
    if max(scale_x, scale_y) <= 1.01:
        return input_path, 1.0, 1.0

    with Image.open(input_path) as image:
        target_width = max(1, int(round(image.width * scale_x)))
        target_height = max(1, int(round(image.height * scale_y)))
        resized = image.resize((target_width, target_height), resample=Image.Resampling.LANCZOS)
        output_path = workdir / 'tesseract_ocr_input.png'
        resized.save(output_path, format='PNG', dpi=(target_dpi, target_dpi))

    return output_path, scale_x, scale_y


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


def _normalize_bbox(bbox: Any) -> dict[str, float] | None:
    if isinstance(bbox, dict):
        try:
            x0 = float(bbox['x0'])
            y0 = float(bbox['y0'])
            x1 = float(bbox['x1'])
            y1 = float(bbox['y1'])
        except (KeyError, TypeError, ValueError):
            return None
        if x1 <= x0 or y1 <= y0:
            return None
        return {'x0': x0, 'y0': y0, 'x1': x1, 'y1': y1}

    if isinstance(bbox, (list, tuple)):
        if len(bbox) == 4 and all(isinstance(value, (int, float)) for value in bbox):
            x0, y0, x1, y1 = (float(value) for value in bbox)
            if x1 <= x0 or y1 <= y0:
                return None
            return {'x0': x0, 'y0': y0, 'x1': x1, 'y1': y1}
        points: list[tuple[float, float]] = []
        for point in bbox:
            if isinstance(point, (list, tuple)) and len(point) >= 2:
                try:
                    points.append((float(point[0]), float(point[1])))
                except (TypeError, ValueError):
                    return None
        if len(points) >= 2:
            xs = [point[0] for point in points]
            ys = [point[1] for point in points]
            x0 = min(xs)
            y0 = min(ys)
            x1 = max(xs)
            y1 = max(ys)
            if x1 <= x0 or y1 <= y0:
                return None
            return {'x0': x0, 'y0': y0, 'x1': x1, 'y1': y1}

    return None


def _bbox_area(bbox: dict[str, float]) -> float:
    return max(0.0, bbox['x1'] - bbox['x0']) * max(0.0, bbox['y1'] - bbox['y0'])


def _bbox_intersection_area(left: dict[str, float], right: dict[str, float]) -> float:
    x0 = max(left['x0'], right['x0'])
    y0 = max(left['y0'], right['y0'])
    x1 = min(left['x1'], right['x1'])
    y1 = min(left['y1'], right['y1'])
    if x1 <= x0 or y1 <= y0:
        return 0.0
    return (x1 - x0) * (y1 - y0)


def _bbox_iou(left: dict[str, float], right: dict[str, float]) -> float:
    intersection = _bbox_intersection_area(left, right)
    if intersection <= 0.0:
        return 0.0
    union = _bbox_area(left) + _bbox_area(right) - intersection
    if union <= 0.0:
        return 0.0
    return intersection / union


def _bbox_center_distance_ratio(left: dict[str, float], right: dict[str, float]) -> float:
    left_center_x = (left['x0'] + left['x1']) / 2.0
    left_center_y = (left['y0'] + left['y1']) / 2.0
    right_center_x = (right['x0'] + right['x1']) / 2.0
    right_center_y = (right['y0'] + right['y1']) / 2.0
    distance = math.dist((left_center_x, left_center_y), (right_center_x, right_center_y))
    left_diag = math.dist((left['x0'], left['y0']), (left['x1'], left['y1']))
    right_diag = math.dist((right['x0'], right['y0']), (right['x1'], right['y1']))
    baseline = max(left_diag, right_diag, 1.0)
    return distance / baseline


def _lowercase_text(text: str) -> str:
    return text.lower()


def _normalize_text_for_segment_match(text: str) -> str:
    return re.sub(r'\s+', '', _lowercase_text(text)).strip()


_SWEDISH_DIACRITICS = {'å', 'ä', 'ö', 'Å', 'Ä', 'Ö'}


def _is_swedish_diacritic_char(char: str) -> bool:
    return char in _SWEDISH_DIACRITICS


def _fold_char_for_diacritic_match(char: str) -> str:
    lower = _lowercase_text(char)
    if lower in {'å', 'ä', 'à', 'á', 'â', 'ã', 'ā'}:
        return 'a'
    if lower in {'ö', 'ò', 'ó', 'ô', 'õ', 'ø', 'ō'}:
        return 'o'
    if lower in {'ü', 'ù', 'ú', 'û', 'ū'}:
        return 'u'
    if lower in {'é', 'è', 'ê', 'ë', 'ē'}:
        return 'e'
    if lower in {'í', 'ì', 'î', 'ï', 'ī'}:
        return 'i'
    return lower


def _normalize_text_for_diacritic_match(text: str) -> str:
    return ''.join(
        _fold_char_for_diacritic_match(char)
        for char in text
        if not char.isspace()
    )


def _utf8_levenshtein_distance(left: str, right: str) -> int:
    left_chars = list(left)
    right_chars = list(right)
    if not left_chars:
        return len(right_chars)
    if not right_chars:
        return len(left_chars)

    previous_row = list(range(len(right_chars) + 1))
    for index_left, left_char in enumerate(left_chars, start=1):
        current_row = [index_left]
        for index_right, right_char in enumerate(right_chars, start=1):
            cost = 0 if left_char == right_char else 1
            current_row.append(min(
                previous_row[index_right] + 1,
                current_row[index_right - 1] + 1,
                previous_row[index_right - 1] + cost,
            ))
        previous_row = current_row
    return int(previous_row[-1])


def _texts_are_diacritic_compatible(left: str, right: str) -> bool:
    normalized_left = _normalize_text_for_diacritic_match(left)
    normalized_right = _normalize_text_for_diacritic_match(right)
    if normalized_left == '' or normalized_right == '':
        return False
    if normalized_left == normalized_right:
        return True
    max_len = max(len(normalized_left), len(normalized_right))
    if max_len == 0:
        return True
    distance = _utf8_levenshtein_distance(normalized_left, normalized_right)
    return distance <= max(1, math.floor(max_len * 0.2))


def _transfer_swedish_diacritics(source_text: str, truth_text: str) -> str:
    source_chars = list(source_text)
    truth_chars = list(truth_text)
    if not source_chars or not truth_chars:
        return source_text

    source_folded = [_fold_char_for_diacritic_match(char) for char in source_chars]
    truth_folded = [_fold_char_for_diacritic_match(char) for char in truth_chars]

    dp = [[0] * (len(truth_chars) + 1) for _ in range(len(source_chars) + 1)]
    for row in range(len(source_chars) + 1):
        dp[row][0] = row
    for col in range(len(truth_chars) + 1):
        dp[0][col] = col

    for row in range(1, len(source_chars) + 1):
        for col in range(1, len(truth_chars) + 1):
            cost = 0 if source_folded[row - 1] == truth_folded[col - 1] else 1
            dp[row][col] = min(
                dp[row - 1][col] + 1,
                dp[row][col - 1] + 1,
                dp[row - 1][col - 1] + cost,
            )

    result: list[str] = []
    row = len(source_chars)
    col = len(truth_chars)
    while row > 0 or col > 0:
        diagonal_cost = None
        if row > 0 and col > 0:
            diagonal_cost = dp[row - 1][col - 1] + (0 if source_folded[row - 1] == truth_folded[col - 1] else 1)
        if diagonal_cost is not None and dp[row][col] == diagonal_cost:
            source_char = source_chars[row - 1]
            truth_char = truth_chars[col - 1]
            if (
                source_folded[row - 1] == truth_folded[col - 1]
                and _is_swedish_diacritic_char(truth_char)
                and not _is_swedish_diacritic_char(source_char)
            ):
                result.append(truth_char)
            else:
                result.append(source_char)
            row -= 1
            col -= 1
            continue
        if row > 0 and dp[row][col] == dp[row - 1][col] + 1:
            result.append(source_chars[row - 1])
            row -= 1
            continue
        if col > 0 and dp[row][col] == dp[row][col - 1] + 1:
            col -= 1
            continue
        if row > 0:
            result.append(source_chars[row - 1])
            row -= 1
        elif col > 0:
            col -= 1

    result.reverse()
    return ''.join(result)


def _build_debug_word_from_fragments(
    fragments: list[dict[str, Any]],
    text: str,
    fallback_bbox: dict[str, float] | None = None,
    fallback_score: float | None = None,
) -> dict[str, Any] | None:
    if not fragments:
        if fallback_bbox is None or text.strip() == '':
            return None
        return {
            'text': text.strip(),
            'bbox': fallback_bbox,
            'score': fallback_score,
        }

    bbox: dict[str, float] | None = None
    scores: list[float] = []
    for fragment in fragments:
        fragment_bbox = _normalize_bbox(fragment.get('bbox'))
        if fragment_bbox is not None:
            if bbox is None:
                bbox = dict(fragment_bbox)
            else:
                bbox = {
                    'x0': min(bbox['x0'], fragment_bbox['x0']),
                    'y0': min(bbox['y0'], fragment_bbox['y0']),
                    'x1': max(bbox['x1'], fragment_bbox['x1']),
                    'y1': max(bbox['y1'], fragment_bbox['y1']),
                }
        score = fragment.get('score')
        if isinstance(score, (int, float)):
            scores.append(max(0.0, min(1.0, float(score))))

    if bbox is None:
        bbox = fallback_bbox
    if bbox is None or text.strip() == '':
        return None

    return {
        'text': text.strip(),
        'bbox': bbox,
        'score': (sum(scores) / len(scores)) if scores else fallback_score,
    }


def _fallback_merge_rapidocr_line_fragments(
    fragments: list[dict[str, Any]],
    line_bbox: dict[str, float] | None = None,
    line_score: float | None = None,
) -> list[dict[str, Any]]:
    if not fragments:
        return []

    fragments = sorted(
        fragments,
        key=lambda fragment: (
            (_normalize_bbox(fragment.get('bbox')) or {'x0': 0.0, 'y0': 0.0})['x0'],
            (_normalize_bbox(fragment.get('bbox')) or {'x0': 0.0, 'y0': 0.0})['y0'],
        ),
    )

    groups: list[list[dict[str, Any]]] = []
    current_group: list[dict[str, Any]] = []
    previous_bbox: dict[str, float] | None = None
    for fragment in fragments:
        bbox = _normalize_bbox(fragment.get('bbox'))
        if bbox is None:
            continue
        if not current_group or previous_bbox is None:
            current_group = [fragment]
            previous_bbox = bbox
            continue

        previous_height = max(1.0, previous_bbox['y1'] - previous_bbox['y0'])
        current_height = max(1.0, bbox['y1'] - bbox['y0'])
        gap = bbox['x0'] - previous_bbox['x1']
        merge_threshold = max(4.0, min(previous_height, current_height) * 0.22)
        if gap <= merge_threshold:
            current_group.append(fragment)
        else:
            groups.append(current_group)
            current_group = [fragment]
        previous_bbox = bbox

    if current_group:
        groups.append(current_group)

    segments: list[dict[str, Any]] = []
    for group in groups:
        text = ''.join(str(fragment.get('text') or '') for fragment in group)
        segment = _build_debug_word_from_fragments(group, text, line_bbox, line_score)
        if segment is not None:
            segments.append(segment)
    return segments


def _merge_rapidocr_line_into_segments(line: dict[str, Any]) -> list[dict[str, Any]]:
    line_text = str(line.get('text') or '').strip()
    line_score = float(line['score']) if isinstance(line.get('score'), (int, float)) else None
    if line_score is not None:
        line_score = max(0.0, min(1.0, line_score))
    line_bbox = _normalize_bbox(line.get('bbox'))

    fragments: list[dict[str, Any]] = []
    for fragment in line.get('words') or []:
        if not isinstance(fragment, dict):
            continue
        text = str(fragment.get('text') or '').strip()
        bbox = _normalize_bbox(fragment.get('bbox'))
        if text == '' or bbox is None:
            continue
        score = fragment.get('score')
        normalized_score = max(0.0, min(1.0, float(score))) if isinstance(score, (int, float)) else None
        fragments.append({'text': text, 'bbox': bbox, 'score': normalized_score})

    if not fragments:
        return []

    fragments.sort(key=lambda fragment: (fragment['bbox']['x0'], fragment['bbox']['y0']))
    tokens = [token for token in re.split(r'\s+', line_text) if token.strip() != '']
    if not tokens:
        return _fallback_merge_rapidocr_line_fragments(fragments, line_bbox, line_score)

    segments: list[dict[str, Any]] = []
    fragment_index = 0
    for token in tokens:
        normalized_token = _normalize_text_for_segment_match(token)
        if normalized_token == '':
            continue

        candidate_fragments: list[dict[str, Any]] = []
        candidate_text = ''
        matched = False
        while fragment_index < len(fragments):
            fragment = fragments[fragment_index]
            fragment_index += 1
            normalized_fragment = _normalize_text_for_segment_match(str(fragment.get('text') or ''))
            if normalized_fragment == '':
                continue
            candidate_fragments.append(fragment)
            candidate_text += normalized_fragment
            if candidate_text == normalized_token:
                matched = True
                break
            if not normalized_token.startswith(candidate_text):
                matched = False
                break

        if not matched:
            return _fallback_merge_rapidocr_line_fragments(fragments, line_bbox, line_score)

        segment = _build_debug_word_from_fragments(candidate_fragments, token, line_bbox, line_score)
        if segment is not None:
            segments.append(segment)

    if fragment_index < len(fragments):
        segments.extend(_fallback_merge_rapidocr_line_fragments(fragments[fragment_index:], line_bbox, line_score))

    return segments or _fallback_merge_rapidocr_line_fragments(fragments, line_bbox, line_score)


def _normalize_debug_words_for_merge(payload: dict[str, Any], engine: str) -> list[dict[str, Any]]:
    words = payload.get('words')
    if not isinstance(words, list):
        return []
    normalized: list[dict[str, Any]] = []
    for index, word in enumerate(words):
        if not isinstance(word, dict):
            continue
        text = str(word.get('text') or '').strip()
        bbox = _normalize_bbox(word.get('bbox'))
        if text == '' or bbox is None:
            continue
        score = word.get('score')
        normalized_score = max(0.0, min(1.0, float(score))) if isinstance(score, (int, float)) else None
        normalized.append({
            'engine': engine,
            'index': index,
            'text': text,
            'bbox': bbox,
            'score': normalized_score,
        })
    return normalized


def _apply_tesseract_swedish_truth_to_segments(
    segments: list[dict[str, Any]],
    tesseract_words: list[dict[str, Any]],
) -> list[dict[str, Any]]:
    if not segments or not tesseract_words:
        return segments

    adjusted = [dict(segment) for segment in segments]
    for index, segment in enumerate(adjusted):
        segment_text = str(segment.get('text') or '').strip()
        segment_bbox = _normalize_bbox(segment.get('bbox'))
        if segment_text == '' or segment_bbox is None:
            continue

        best_candidate: dict[str, Any] | None = None
        best_match_score: float | None = None
        for candidate in tesseract_words:
            candidate_text = str(candidate.get('text') or '').strip()
            if candidate_text == '' or not any(char in _SWEDISH_DIACRITICS for char in candidate_text):
                continue
            candidate_bbox = _normalize_bbox(candidate.get('bbox'))
            if candidate_bbox is None:
                continue
            iou = _bbox_iou(segment_bbox, candidate_bbox)
            distance_ratio = _bbox_center_distance_ratio(segment_bbox, candidate_bbox)
            if iou < 0.08 and distance_ratio > 0.9:
                continue
            if not _texts_are_diacritic_compatible(segment_text, candidate_text):
                continue

            candidate_score = float(candidate.get('score')) if isinstance(candidate.get('score'), (int, float)) else 0.0
            match_score = (iou * 4.0) + max(0.0, 1.0 - min(distance_ratio, 1.0)) + candidate_score
            if best_match_score is None or match_score > best_match_score:
                best_match_score = match_score
                best_candidate = candidate

        if best_candidate is None:
            continue

        adjusted_text = _transfer_swedish_diacritics(segment_text, str(best_candidate.get('text') or ''))
        if adjusted_text != segment_text:
            adjusted[index]['text'] = adjusted_text

    return adjusted


def _build_merged_objects_payload_from_page(
    rapidocr_payload: dict[str, Any],
    page_number: int,
    tesseract_payload: dict[str, Any] | None = None,
) -> dict[str, Any]:
    tesseract_words = _normalize_debug_words_for_merge(tesseract_payload or {}, 'tesseract')
    merged_lines: list[dict[str, Any]] = []
    merged_words: list[dict[str, Any]] = []
    for line_index, line in enumerate(rapidocr_payload.get('lines') or []):
        if not isinstance(line, dict):
            continue
        segments = _merge_rapidocr_line_into_segments(line)
        segments = _apply_tesseract_swedish_truth_to_segments(segments, tesseract_words)
        line_bbox = _normalize_bbox(line.get('bbox'))
        line_score = float(line['score']) if isinstance(line.get('score'), (int, float)) else None
        if line_score is not None:
            line_score = max(0.0, min(1.0, line_score))
        merged_lines.append({
            'index': line_index,
            'text': ' '.join(str(segment.get('text') or '') for segment in segments if str(segment.get('text') or '').strip() != ''),
            'bbox': line_bbox,
            'score': line_score,
            'words': [dict(segment) for segment in segments],
        })
        merged_words.extend(dict(segment) for segment in segments)

    page_text = '\n'.join(
        line['text']
        for line in merged_lines
        if isinstance(line.get('text'), str) and line['text'].strip() != ''
    )
    return {
        'engine': 'merged_objects',
        'sourceEngine': 'rapidocr',
        'pageNumber': page_number + 1,
        'pageIndex': page_number,
        'sourceImage': rapidocr_payload.get('sourceImage'),
        'pageWidth': rapidocr_payload.get('pageWidth'),
        'pageHeight': rapidocr_payload.get('pageHeight'),
        'lines': merged_lines,
        'words': merged_words,
        'text': page_text,
    }


def _format_hocr_bbox_title(bbox: dict[str, float], *, extra: str | None = None) -> str:
    base = f"bbox {int(round(bbox['x0']))} {int(round(bbox['y0']))} {int(round(bbox['x1']))} {int(round(bbox['y1']))}"
    if extra:
        return f'{base}; {extra}'
    return base


def _line_bbox_from_words(words: list[dict[str, Any]]) -> dict[str, float] | None:
    bbox: dict[str, float] | None = None
    for word in words:
        word_bbox = _normalize_bbox(word.get('bbox'))
        if word_bbox is None:
            continue
        if bbox is None:
            bbox = dict(word_bbox)
        else:
            bbox = {
                'x0': min(bbox['x0'], word_bbox['x0']),
                'y0': min(bbox['y0'], word_bbox['y0']),
                'x1': max(bbox['x1'], word_bbox['x1']),
                'y1': max(bbox['y1'], word_bbox['y1']),
            }
    return bbox


def _write_hocr_from_merged_payload(output_hocr: Path, merged_payload: dict[str, Any], *, page_number: int) -> bool:
    page_width = merged_payload.get('pageWidth')
    page_height = merged_payload.get('pageHeight')
    if not isinstance(page_width, (int, float)) or not isinstance(page_height, (int, float)):
        return False

    page_bbox = {
        'x0': 0.0,
        'y0': 0.0,
        'x1': float(page_width),
        'y1': float(page_height),
    }

    xhtml_ns = 'http://www.w3.org/1999/xhtml'
    ET.register_namespace('', xhtml_ns)
    html = ET.Element(f'{{{xhtml_ns}}}html')
    head = ET.SubElement(html, f'{{{xhtml_ns}}}head')
    ET.SubElement(head, f'{{{xhtml_ns}}}meta', attrib={'http-equiv': 'Content-Type', 'content': 'text/html; charset=utf-8'})
    body = ET.SubElement(html, f'{{{xhtml_ns}}}body')

    page_div = ET.SubElement(
        body,
        f'{{{xhtml_ns}}}div',
        attrib={
            'class': 'ocr_page',
            'id': f'page_{page_number + 1}',
            'title': _format_hocr_bbox_title(page_bbox, extra=f'ppageno {page_number}'),
        },
    )
    paragraph = ET.SubElement(
        page_div,
        f'{{{xhtml_ns}}}p',
        attrib={
            'class': 'ocr_par',
            'id': f'par_{page_number + 1}_1',
            'title': _format_hocr_bbox_title(page_bbox),
        },
    )

    line_counter = 1
    word_counter = 1
    for line in merged_payload.get('lines') or []:
        if not isinstance(line, dict):
            continue
        words = [
            word for word in (line.get('words') or [])
            if isinstance(word, dict)
            and str(word.get('text') or '').strip() != ''
            and _normalize_bbox(word.get('bbox')) is not None
        ]
        if not words:
            continue

        line_bbox = _normalize_bbox(line.get('bbox')) or _line_bbox_from_words(words)
        if line_bbox is None:
            continue

        line_span = ET.SubElement(
            paragraph,
            f'{{{xhtml_ns}}}span',
            attrib={
                'class': 'ocr_line',
                'id': f'line_{page_number + 1}_{line_counter}',
                'title': _format_hocr_bbox_title(line_bbox, extra='baseline 0 0'),
            },
        )

        for word in words:
            text = str(word.get('text') or '').strip()
            bbox = _normalize_bbox(word.get('bbox'))
            if text == '' or bbox is None:
                continue
            score = word.get('score')
            confidence = None
            if isinstance(score, (int, float)):
                confidence = max(0, min(100, int(round(float(score) * 100.0))))
            title_extra = f'x_wconf {confidence}' if confidence is not None else None
            word_span = ET.SubElement(
                line_span,
                f'{{{xhtml_ns}}}span',
                attrib={
                    'class': 'ocrx_word',
                    'id': f'word_{page_number + 1}_{word_counter}',
                    'title': _format_hocr_bbox_title(bbox, extra=title_extra),
                },
            )
            word_span.text = text
            word_span.tail = ' '
            word_counter += 1

        if list(line_span):
            line_span.tail = '\n'
            line_counter += 1
        else:
            paragraph.remove(line_span)

    tree = ET.ElementTree(html)
    tree.write(output_hocr, encoding='utf-8', xml_declaration=True)
    return True


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
            'score': _parse_score(title),
            'raw': {
                'hocrTitle': title,
            } if title != '' else None,
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

    raw_lines = output.to_json() or []
    raw_word_lines = getattr(output, 'word_results', ()) or ()

    lines: list[dict[str, Any]] = []
    words: list[dict[str, Any]] = []
    line_count = max(len(raw_lines), len(raw_word_lines))
    for line_index in range(line_count):
        raw_line = raw_lines[line_index] if line_index < len(raw_lines) and isinstance(raw_lines[line_index], dict) else {}
        raw_word_line = raw_word_lines[line_index] if line_index < len(raw_word_lines) else ()

        line_words: list[dict[str, Any]] = []
        if isinstance(raw_word_line, tuple):
            for item in raw_word_line:
                if not isinstance(item, tuple) or len(item) < 3:
                    continue
                text, score, box = item
                normalized_word = {
                    'text': text,
                    'score': score,
                    'bbox': box,
                }
                line_words.append(normalized_word)
                words.append(normalized_word)

        line_text = raw_line.get('txt', '')
        if not isinstance(line_text, str):
            line_text = ''

        line_score = raw_line.get('score', None)
        if not isinstance(line_score, (int, float)):
            line_score = None

        line_box = raw_line.get('box', None)
        if not isinstance(line_box, list):
            line_box = None

        lines.append({
            'text': line_text,
            'score': line_score,
            'bbox': line_box,
            'words': line_words,
        })

    text_chunks: list[str] = []
    for line in lines:
        value = line.get('text', '')
        if isinstance(value, str) and value.strip() != '':
            text_chunks.append(value)

    return {
        'available': True,
        'engine': 'rapidocr',
        'lines': lines,
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
    image_size = _get_image_size(input_file)
    if image_size is not None:
        payload['pageWidth'] = int(image_size[0])
        payload['pageHeight'] = int(image_size[1])
    return payload


def _build_page_debug_payloads(
    input_file,
    hocr_path: Path,
    options,
    *,
    page_number: int,
    tesseract_scale_x: float = 1.0,
    tesseract_scale_y: float = 1.0,
) -> tuple[dict[str, Any], dict[str, Any], dict[str, Any]]:
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
    image_size = _get_image_size(input_file)
    if image_size is not None:
        tesseract_payload['pageWidth'] = int(image_size[0])
        tesseract_payload['pageHeight'] = int(image_size[1])
    tesseract_payload = _scale_down_tesseract_payload(
        tesseract_payload,
        tesseract_scale_x,
        tesseract_scale_y,
        input_file,
    )

    rapidocr_payload = _build_rapidocr_debug_payload(input_file, options, page_number=page_number)
    merged_payload = _build_merged_objects_payload_from_page(rapidocr_payload, page_number, tesseract_payload)
    return tesseract_payload, rapidocr_payload, merged_payload


def _write_page_debug_artifacts(
    output_dir: Path | None,
    *,
    page_number: int,
    tesseract_payload: dict[str, Any],
    rapidocr_payload: dict[str, Any],
    merged_payload: dict[str, Any],
) -> None:
    if output_dir is None:
        return

    _write_debug_json(output_dir, 'tesseract', page_number, tesseract_payload)
    _write_debug_text(output_dir, 'tesseract', page_number, str(tesseract_payload.get('text', '') or ''))

    _write_debug_json(output_dir, 'rapidocr', page_number, rapidocr_payload)
    _write_debug_text(output_dir, 'rapidocr', page_number, str(rapidocr_payload.get('text', '') or ''))

    _write_debug_json(output_dir, 'merged_objects', page_number, merged_payload)
    _write_debug_text(output_dir, 'merged_objects', page_number, str(merged_payload.get('text', '') or ''))


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


def _parse_word_confidence(title: str) -> str | None:
    match = re.search(r'(x_wconf\s+)(\d+)', title)
    if not match:
        return None
    return match.group(2)


def _replace_title_bbox_and_score(title: str, bbox: dict[str, float], score: float | None) -> str:
    bbox_string = f"bbox {int(round(bbox['x0']))} {int(round(bbox['y0']))} {int(round(bbox['x1']))} {int(round(bbox['y1']))}"
    updated = _BBOX_RE.sub(bbox_string, title, count=1)
    if updated == title:
        updated = (bbox_string + ('; ' + title if title.strip() != '' else '')).strip()

    if score is not None:
        confidence = str(max(0, min(100, int(round(score * 100.0)))))
        updated = re.sub(r'(x_wconf\s+)(\d+)', r'\g<1>' + confidence, updated, count=1)
        if 'x_wconf' not in updated:
            updated = f'{updated}; x_wconf {confidence}'.strip('; ')
    return updated


def _iter_hocr_word_elements(root: ET.Element) -> tuple[str, list[ET.Element]]:
    namespace = ''
    match = re.match(r'({.*})html', root.tag)
    if match:
        namespace = match.group(1)
    xpath = f".//{namespace}span[@class='ocrx_word']"
    return namespace, list(root.iterfind(xpath))


def _build_tesseract_hocr_entries(root: ET.Element) -> list[dict[str, Any]]:
    _, word_elements = _iter_hocr_word_elements(root)
    entries: list[dict[str, Any]] = []
    for index, word_elem in enumerate(word_elements):
        original = ''.join(word_elem.itertext()).strip()
        if original == '':
            continue
        title = word_elem.attrib.get('title', '')
        bbox = _parse_bbox(title)
        normalized_bbox = _normalize_bbox(list(bbox) if bbox is not None else None)
        if normalized_bbox is None:
            continue
        score = _parse_score(title)
        entries.append({
            'index': index,
            'element': word_elem,
            'text': original,
            'bbox': normalized_bbox,
            'score': score,
            'title': title,
        })
    return entries


def _segment_and_span_match_score(
    segment: dict[str, Any],
    span_entries: list[dict[str, Any]],
) -> float | None:
    if not span_entries:
        return None
    segment_text = str(segment.get('text') or '').strip()
    segment_bbox = _normalize_bbox(segment.get('bbox'))
    if segment_text == '' or segment_bbox is None:
        return None

    span_text = ''.join(str(entry.get('text') or '') for entry in span_entries).strip()
    if span_text == '':
        return None
    if not _texts_are_diacritic_compatible(segment_text, span_text):
        return None

    span_bbox = _build_debug_word_from_fragments(span_entries, span_text)
    if span_bbox is None:
        return None
    span_bbox_normalized = _normalize_bbox(span_bbox.get('bbox'))
    if span_bbox_normalized is None:
        return None

    iou = _bbox_iou(segment_bbox, span_bbox_normalized)
    distance_ratio = _bbox_center_distance_ratio(segment_bbox, span_bbox_normalized)
    if iou < 0.02 and distance_ratio > 1.1:
        return None

    distance_score = max(0.0, 1.0 - min(distance_ratio, 1.0))
    text_norm_segment = _normalize_text_for_diacritic_match(segment_text)
    text_norm_span = _normalize_text_for_diacritic_match(span_text)
    text_distance = _utf8_levenshtein_distance(text_norm_segment, text_norm_span)
    text_baseline = max(len(text_norm_segment), len(text_norm_span), 1)
    text_score = max(0.0, 1.0 - (text_distance / text_baseline))
    span_penalty = max(0.0, (len(span_entries) - 1) * 0.08)
    return (iou * 4.0) + distance_score + (text_score * 2.0) - span_penalty


def _rewrite_hocr_with_merged_objects(hocr_path: Path, merged_payload: dict[str, Any]) -> str | None:
    words = merged_payload.get('words')
    if not isinstance(words, list) or words == []:
        return None

    tree = ET.parse(hocr_path)
    root = tree.getroot()
    entries = _build_tesseract_hocr_entries(root)
    if not entries:
        return None

    parent_map = {child: parent for parent in root.iter() for child in parent}
    consumed_indices: set[int] = set()
    changed = False

    merged_segments = [
        segment for segment in words
        if isinstance(segment, dict)
        and str(segment.get('text') or '').strip() != ''
        and _normalize_bbox(segment.get('bbox')) is not None
    ]
    merged_segments.sort(
        key=lambda segment: (
            (_normalize_bbox(segment.get('bbox')) or {'y0': 0.0, 'x0': 0.0})['y0'],
            (_normalize_bbox(segment.get('bbox')) or {'y0': 0.0, 'x0': 0.0})['x0'],
        )
    )

    max_span_length = 4
    for segment in merged_segments:
        segment_bbox = _normalize_bbox(segment.get('bbox'))
        if segment_bbox is None:
            continue
        best_start: int | None = None
        best_end: int | None = None
        best_score: float | None = None

        for start in range(len(entries)):
            if start in consumed_indices:
                continue
            start_entry = entries[start]
            start_bbox = _normalize_bbox(start_entry.get('bbox'))
            if start_bbox is None:
                continue
            if _bbox_iou(segment_bbox, start_bbox) < 0.01 and _bbox_center_distance_ratio(segment_bbox, start_bbox) > 1.25:
                continue

            span_entries: list[dict[str, Any]] = []
            for end in range(start, min(len(entries), start + max_span_length)):
                if end in consumed_indices:
                    break
                span_entries.append(entries[end])
                match_score = _segment_and_span_match_score(segment, span_entries)
                if match_score is None:
                    continue
                if best_score is None or match_score > best_score:
                    best_score = match_score
                    best_start = start
                    best_end = end

        if best_start is None or best_end is None:
            continue

        selected_entries = entries[best_start:best_end + 1]
        anchor_entry = selected_entries[0]
        anchor_element = anchor_entry['element']
        anchor_element.text = str(segment.get('text') or '').strip()
        anchor_title = str(anchor_entry.get('title') or '')
        anchor_element.attrib['title'] = _replace_title_bbox_and_score(
            anchor_title,
            segment_bbox,
            float(segment['score']) if isinstance(segment.get('score'), (int, float)) else None,
        )
        changed = True

        for entry in selected_entries[1:]:
            element = entry['element']
            parent = parent_map.get(element)
            if parent is not None:
                parent.remove(element)
        consumed_indices.update(range(best_start, best_end + 1))

    if changed:
        tree.write(hocr_path, encoding='utf-8', xml_declaration=True)
        text_lines = [
            str(line.get('text') or '').strip()
            for line in (merged_payload.get('lines') or [])
            if isinstance(line, dict) and str(line.get('text') or '').strip() != ''
        ]
        return '\n'.join(text_lines).strip()

    return None


def _generate_transformed_hocr(
    input_file,
    output_hocr,
    output_text,
    options,
    *,
    page_number: int = 0,
) -> None:
    with TemporaryDirectory(prefix='docflow-tesseract-input-') as temp_dir:
        temp_path = Path(temp_dir)
        tesseract_input, tesseract_scale_x, tesseract_scale_y = _prepare_tesseract_ocr_input(
            input_file,
            temp_path,
        )

        builtin_tesseract_ocr.TesseractOcrEngine.generate_hocr(
            str(tesseract_input),
            output_hocr,
            output_text,
            options,
        )

        output_dir = _get_debug_output_dir(options)
        tesseract_payload, rapidocr_payload, merged_payload = _build_page_debug_payloads(
            input_file,
            output_hocr,
            options,
            page_number=page_number,
            tesseract_scale_x=tesseract_scale_x,
            tesseract_scale_y=tesseract_scale_y,
        )

        merged_sidecar_text = str(merged_payload.get('text') or '').strip()
        synthesized_hocr = _write_hocr_from_merged_payload(
            output_hocr,
            merged_payload,
            page_number=page_number,
        )
        if synthesized_hocr:
            output_text.write_text(merged_sidecar_text, encoding='utf-8')
        else:
            merged_sidecar_text = _rewrite_hocr_with_merged_objects(output_hocr, merged_payload)
            if merged_sidecar_text is not None:
                output_text.write_text(merged_sidecar_text, encoding='utf-8')

        _write_page_debug_artifacts(
            output_dir,
            page_number=page_number,
            tesseract_payload=tesseract_payload,
            rapidocr_payload=rapidocr_payload,
            merged_payload=merged_payload,
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
