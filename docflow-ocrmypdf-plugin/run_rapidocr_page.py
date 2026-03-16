from __future__ import annotations

import json
import logging
import sys
from pathlib import Path


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
        words = []
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

        text_chunks = []
        for line in output.to_json() or []:
            if not isinstance(line, dict):
                continue
            value = line.get('txt', '')
            if isinstance(value, str) and value.strip() != '':
                text_chunks.append(value)

        payload = {
            'available': True,
            'engine': 'rapidocr',
            'lines': output.to_json(),
            'words': words,
            'text': '\n'.join(text_chunks).strip(),
        }
        print(json.dumps(payload, ensure_ascii=False))
        return 0
    except Exception as exc:
        print(json.dumps({'available': False, 'error': f'RapidOCR execution failed: {exc}', 'words': []}))
        return 0


if __name__ == '__main__':
    raise SystemExit(main())
