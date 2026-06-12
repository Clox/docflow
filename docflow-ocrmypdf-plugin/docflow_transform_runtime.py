from __future__ import annotations

"""Static Docflow OCR transform runtime.

This module is intentionally static. Data-driven settings are loaded from the
JSON file pointed to by --docflow-transform-config.
"""

import json
import re
import subprocess
import sys
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


def _substitutions(options) -> list[dict[str, Any]]:
    config_path = _config_path_from_options(options)
    if config_path is None:
        return []
    payload = _load_config(str(config_path))
    rows = payload.get('substitutions')
    if not isinstance(rows, list):
        return []

    normalized: list[dict[str, Any]] = []
    for row in rows:
        if not isinstance(row, dict):
            continue
        source = row.get('from', '')
        replacement = row.get('to', '')
        enabled = row.get('enabled', True)
        is_regex = row.get('isRegex', False)
        if not isinstance(source, str) or not isinstance(replacement, str):
            continue
        if source == '' or enabled is False:
            continue
        normalized.append({
            'from': source,
            'to': replacement,
            'isRegex': is_regex is True,
        })
    return normalized


def _dictionary_correction_config(options) -> dict[str, Any]:
    config_path = _config_path_from_options(options)
    if config_path is None:
        return {}
    payload = _load_config(str(config_path))
    config = payload.get('dictionaryCorrection')
    return config if isinstance(config, dict) else {}


def _normalize_dictionary_word(text: str) -> str:
    return text.strip().casefold()


@lru_cache(maxsize=8)
def _load_custom_dictionary_words(path_value: str) -> frozenset[str]:
    path = Path(path_value)
    if not path.is_file():
        return frozenset()
    try:
        lines = path.read_text(encoding='utf-8').splitlines()
    except Exception:
        return frozenset()

    words: set[str] = set()
    for line in lines:
        word = line.strip()
        if word == '' or word.startswith('#'):
            continue
        words.add(_normalize_dictionary_word(word))
    return frozenset(words)


def _custom_dictionary_contains(word: str, config: dict[str, Any]) -> bool:
    path = config.get('customDictionaryPath')
    if not isinstance(path, str) or path.strip() == '':
        return False
    return _normalize_dictionary_word(word) in _load_custom_dictionary_words(path.strip())


@lru_cache(maxsize=2)
def _load_spylls_dictionary(aff_path_value: str, dic_path_value: str):
    aff_path = Path(aff_path_value)
    dic_path = Path(dic_path_value)
    if not aff_path.is_file() or not dic_path.is_file():
        return None

    try:
        from spylls.hunspell import Dictionary
    except Exception:
        return None

    if aff_path.with_suffix('').name == dic_path.with_suffix('').name and aff_path.parent == dic_path.parent:
        try:
            return Dictionary.from_files(str(aff_path.with_suffix('')))
        except Exception:
            pass

    try:
        from spylls.hunspell import readers
        from spylls.hunspell.readers import FileReader

        aff, context = readers.read_aff(FileReader(str(aff_path)))
        dic = readers.read_dic(FileReader(str(dic_path), encoding=context.encoding), aff=aff, context=context)
        return Dictionary(aff, dic)
    except Exception:
        return None


def _spylls_dictionary_lookup_in_process(word: str, config: dict[str, Any]) -> bool | None:
    aff_path = config.get('systemDictionaryAffPath')
    dic_path = config.get('systemDictionaryDicPath')
    if not isinstance(aff_path, str) or not isinstance(dic_path, str):
        return False
    dictionary = _load_spylls_dictionary(aff_path, dic_path)
    if dictionary is None:
        return None
    try:
        return bool(dictionary.lookup(word) or dictionary.lookup(word.casefold()))
    except Exception:
        return False


@lru_cache(maxsize=512)
def _spylls_dictionary_lookup_subprocess(
    python_path: str,
    aff_path: str,
    dic_path: str,
    word: str,
) -> bool | None:
    script = r'''
import json
import sys

def load_dictionary(aff_path, dic_path):
    from pathlib import Path
    from spylls.hunspell import Dictionary
    aff = Path(aff_path)
    dic = Path(dic_path)
    if aff.with_suffix('').name == dic.with_suffix('').name and aff.parent == dic.parent:
        return Dictionary.from_files(str(aff.with_suffix('')))
    from spylls.hunspell import readers
    from spylls.hunspell.readers import FileReader
    aff_data, context = readers.read_aff(FileReader(str(aff)))
    words = readers.read_dic(FileReader(str(dic), encoding=context.encoding), aff=aff_data, context=context)
    return Dictionary(aff_data, words)

payload = json.load(sys.stdin)
dictionary = load_dictionary(payload['affPath'], payload['dicPath'])
word = payload['word']
json.dump({'valid': bool(dictionary.lookup(word) or dictionary.lookup(word.casefold()))}, sys.stdout)
'''
    try:
        completed = subprocess.run(
            [python_path, '-c', script],
            input=json.dumps({'affPath': aff_path, 'dicPath': dic_path, 'word': word}, ensure_ascii=False),
            text=True,
            capture_output=True,
            check=False,
            timeout=10,
        )
    except Exception:
        return None
    if completed.returncode != 0:
        return None
    try:
        payload = json.loads(completed.stdout)
    except Exception:
        return None
    return bool(payload.get('valid'))


def _spylls_dictionary_contains(word: str, config: dict[str, Any]) -> bool:
    in_process = _spylls_dictionary_lookup_in_process(word, config)
    if in_process is not None:
        return in_process

    python_path = config.get('spyllsPython')
    aff_path = config.get('systemDictionaryAffPath')
    dic_path = config.get('systemDictionaryDicPath')
    if not all(isinstance(value, str) and value.strip() != '' for value in (python_path, aff_path, dic_path)):
        return False
    result = _spylls_dictionary_lookup_subprocess(
        python_path.strip(),
        aff_path.strip(),
        dic_path.strip(),
        word,
    )
    return bool(result)


def _valid_dictionary_sources(word: str, config: dict[str, Any]) -> list[str]:
    sources: list[str] = []
    if _custom_dictionary_contains(word, config):
        sources.append('custom_dictionary')
    if _spylls_dictionary_contains(word, config):
        sources.append('system_dictionary')
    return sources


def _a_diaeresis_to_a_ring_candidates(word: str, max_positions: int, max_candidates: int) -> list[str] | None:
    positions = [index for index, char in enumerate(word) if char == 'Ä']
    if not positions:
        return []
    if len(positions) > max_positions:
        return None

    candidate_count = 2 ** len(positions)
    if candidate_count > max_candidates:
        return None

    chars = list(word)
    candidates: list[str] = []
    for mask in range(candidate_count):
        next_chars = chars[:]
        for bit_index, char_index in enumerate(positions):
            if mask & (1 << bit_index):
                next_chars[char_index] = 'Å'
        candidates.append(''.join(next_chars))
    return candidates


_DICTIONARY_CORRECTIONS: list[dict[str, Any]] = []
_DICTIONARY_STATUS_NOTES: set[str] = set()


def correct_dictionary_word_detail(text, *, page_number, bbox, title, options):
    if not isinstance(text, str):
        return {'text': text}

    config = _dictionary_correction_config(options)
    if config.get('enabled') is not True:
        return {'text': text}
    if 'Ä' not in text:
        return {'text': text}

    max_positions = int(config.get('maxVariantPositions') or 5)
    max_candidates = int(config.get('maxCandidates') or 32)
    max_positions = max(1, min(max_positions, 12))
    max_candidates = max(2, min(max_candidates, 4096))

    if _valid_dictionary_sources(text, config):
        return {'text': text}

    candidates = _a_diaeresis_to_a_ring_candidates(text, max_positions, max_candidates)
    if candidates is None:
        _DICTIONARY_STATUS_NOTES.add('dictionary_candidate_limit_exceeded')
        return {'text': text}

    valid: list[tuple[str, list[str]]] = []
    seen: set[str] = set()
    for candidate in candidates:
        if candidate == text or candidate in seen:
            continue
        seen.add(candidate)
        sources = _valid_dictionary_sources(candidate, config)
        if sources:
            valid.append((candidate, sources))

    if len(valid) != 1:
        return {'text': text}

    corrected, sources = valid[0]
    correction = {
        'original': text,
        'corrected': corrected,
        'reason': 'Ä->Å',
        'source': '+'.join(sources),
        'pageNumber': page_number,
        'bbox': bbox,
    }
    _DICTIONARY_CORRECTIONS.append(correction)
    print(
        'OCR dictionary correction:\n'
        f'{text} -> {corrected}\n'
        'reason: Ä->Å\n'
        f"source: {correction['source']}",
        file=sys.stderr,
    )
    return {
        'text': corrected,
        'correction': correction,
    }


def get_dictionary_correction_debug():
    return {
        'corrections': list(_DICTIONARY_CORRECTIONS),
        'notes': sorted(_DICTIONARY_STATUS_NOTES),
    }


def _expand_dollar_groups(replacement: str, match: re.Match[str]) -> str:
    def replace_group(group_match: re.Match[str]) -> str:
        group_index = int(group_match.group(1))
        try:
            value = match.group(group_index)
        except IndexError:
            return ''
        return value or ''

    return re.sub(r'\$(\d+)', replace_group, replacement)


def _apply(text: str, options) -> str:
    if not isinstance(text, str):
        return text
    for row in _substitutions(options):
        if row.get('isRegex') is True:
            try:
                pattern = re.compile(row['from'])
            except re.error:
                continue
            text = pattern.sub(lambda match: _expand_dollar_groups(row['to'], match), text)
        else:
            text = text.replace(row['from'], row['to'])
    return text


def transform_word(text, *, page_number, bbox, title, options):
    corrected = correct_dictionary_word_detail(
        text,
        page_number=page_number,
        bbox=bbox,
        title=title,
        options=options,
    ).get('text', text)
    return _apply(corrected, options)


def transform_sidecar(text, *, page_number, options):
    return _apply(text, options)
