from __future__ import annotations

"""Static Docflow OCR transform runtime.

This module is intentionally static. Data-driven settings are loaded from the
JSON file pointed to by --docflow-transform-config.
"""

import json
from functools import lru_cache
from pathlib import Path
from typing import Any


def _config_path_from_options(options) -> Path | None:
    config = None
    extra_attrs = getattr(options, 'extra_attrs', {})
    if isinstance(extra_attrs, dict):
        config = extra_attrs.get('docflow_transform_config')
    if not config:
        config = getattr(getattr(options, 'docflow', None), 'transform_config', None)
    if not config:
        return None
    return Path(config).expanduser().resolve()


@lru_cache(maxsize=8)
def _load_config(config_path: str) -> dict[str, Any]:
    path = Path(config_path)
    if not path.is_file():
        return {}
    try:
        payload = json.loads(path.read_text(encoding='utf-8'))
    except Exception:
        return {}
    if isinstance(payload, dict):
        return payload
    if isinstance(payload, list):
        return {'substitutions': payload}
    return {}


def _substitutions(options) -> list[dict[str, str]]:
    config_path = _config_path_from_options(options)
    if config_path is None:
        return []
    payload = _load_config(str(config_path))
    rows = payload.get('substitutions')
    if not isinstance(rows, list):
        return []

    normalized: list[dict[str, str]] = []
    for row in rows:
        if not isinstance(row, dict):
            continue
        source = row.get('from', '')
        replacement = row.get('to', '')
        if not isinstance(source, str) or not isinstance(replacement, str):
            continue
        if source == '' or replacement == '':
            continue
        normalized.append({'from': source, 'to': replacement})
    return normalized


def _apply(text: str, options) -> str:
    if not isinstance(text, str):
        return text
    for row in _substitutions(options):
        text = text.replace(row['from'], row['to'])
    return text


def transform_word(text, *, page_number, bbox, title, options):
    return _apply(text, options)


def transform_sidecar(text, *, page_number, options):
    return _apply(text, options)
