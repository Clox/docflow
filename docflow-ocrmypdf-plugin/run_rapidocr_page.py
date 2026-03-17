from __future__ import annotations

import json
import logging
import sys
from pathlib import Path


def normalize_word_item(item):
    if not isinstance(item, tuple) or len(item) < 3:
        return None
    text, score, box = item
    return {
        'text': text,
        'score': score,
        'bbox': box,
    }


def build_payload_from_output(output):
    raw_lines = output.to_json() or []
    raw_word_lines = getattr(output, 'word_results', ()) or ()

    lines = []
    words = []

    line_count = max(len(raw_lines), len(raw_word_lines))
    for line_index in range(line_count):
        raw_line = raw_lines[line_index] if line_index < len(raw_lines) and isinstance(raw_lines[line_index], dict) else {}
        raw_word_line = raw_word_lines[line_index] if line_index < len(raw_word_lines) else ()
        line_words = []
        if isinstance(raw_word_line, tuple):
            for item in raw_word_line:
                normalized = normalize_word_item(item)
                if normalized is None:
                    continue
                line_words.append(normalized)
                words.append(normalized)

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

    text_chunks = []
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


def main() -> int:
    if len(sys.argv) < 2:
        print(json.dumps({'available': False, 'error': 'Missing input image path', 'words': []}))
        return 1

    image_path = Path(sys.argv[1]).expanduser().resolve()
    if not image_path.is_file():
        print(json.dumps({'available': False, 'error': f'Input image not found: {image_path}', 'words': []}))
        return 1

    logging.getLogger().setLevel(logging.ERROR)

    try:
        from rapidocr import LangDet, LangRec, ModelType, OCRVersion, RapidOCR
    except Exception as exc:
        print(json.dumps({'available': False, 'error': f'RapidOCR import failed: {exc}', 'words': []}))
        return 0

    try:
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
        engine = RapidOCR(params=params)
        output = engine(str(image_path), return_word_box=True)
        payload = build_payload_from_output(output)
        print(json.dumps(payload, ensure_ascii=False))
        return 0
    except Exception as exc:
        print(json.dumps({'available': False, 'error': f'RapidOCR execution failed: {exc}', 'words': []}))
        return 0


if __name__ == '__main__':
    raise SystemExit(main())
