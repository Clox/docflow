from __future__ import annotations

import json
import sys
from pathlib import Path

import pikepdf


def _normalize_key(raw: str) -> pikepdf.Name:
    key = raw.strip()
    if not key.startswith('/'):
        key = '/' + key
    return pikepdf.Name(key)


def _read(path: Path, key: str) -> int:
    name = _normalize_key(key)
    with pikepdf.open(path) as pdf:
        value = pdf.docinfo.get(name)
    payload = {
        'ok': True,
        'value': None if value is None else str(value),
    }
    print(json.dumps(payload, ensure_ascii=False))
    return 0


def _write(path: Path, key: str, value: str) -> int:
    name = _normalize_key(key)
    with pikepdf.open(path, allow_overwriting_input=True) as pdf:
        pdf.docinfo[name] = str(value)
        pdf.save(path)
    print(json.dumps({'ok': True}, ensure_ascii=False))
    return 0


def _remove(path: Path, key: str) -> int:
    name = _normalize_key(key)
    with pikepdf.open(path, allow_overwriting_input=True) as pdf:
        try:
            del pdf.docinfo[name]
        except KeyError:
            pass
        pdf.save(path)
    print(json.dumps({'ok': True}, ensure_ascii=False))
    return 0


def main(argv: list[str]) -> int:
    if len(argv) < 4:
        print(json.dumps({'ok': False, 'error': 'usage'}), file=sys.stderr)
        return 2

    command = argv[1].strip().lower()
    pdf_path = Path(argv[2]).expanduser().resolve()
    key = argv[3]

    if not pdf_path.is_file():
        print(json.dumps({'ok': False, 'error': 'missing-pdf'}), file=sys.stderr)
        return 3

    try:
        if command == 'read':
            return _read(pdf_path, key)
        if command == 'write':
            if len(argv) < 5:
                print(json.dumps({'ok': False, 'error': 'missing-value'}), file=sys.stderr)
                return 4
            return _write(pdf_path, key, argv[4])
        if command == 'remove':
            return _remove(pdf_path, key)
    except Exception as exc:
        print(json.dumps({'ok': False, 'error': str(exc)}), file=sys.stderr)
        return 1

    print(json.dumps({'ok': False, 'error': 'unknown-command'}), file=sys.stderr)
    return 5


if __name__ == '__main__':
    raise SystemExit(main(sys.argv))
