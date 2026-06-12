from __future__ import annotations

import importlib.util
import json
import tempfile
from pathlib import Path
from types import SimpleNamespace


ROOT = Path(__file__).resolve().parents[1]
RUNTIME_PATH = ROOT / 'docflow-ocrmypdf-plugin' / 'docflow_transform_runtime.py'


def load_runtime():
    spec = importlib.util.spec_from_file_location('docflow_transform_runtime_test', RUNTIME_PATH)
    assert spec is not None and spec.loader is not None
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def make_options(config_path: Path):
    return SimpleNamespace(extra_attrs={'docflow_transform_config': str(config_path)})


def write_config(tmp: Path, words: str, *, max_positions: int = 5, max_candidates: int = 32):
    dictionary_path = tmp / 'docflow.txt'
    dictionary_path.write_text(words, encoding='utf-8')
    config_path = tmp / 'config.json'
    config_path.write_text(
        json.dumps(
            {
                'dictionaryCorrection': {
                    'enabled': True,
                    'customDictionaryPath': str(dictionary_path),
                    'systemDictionaryDicPath': str(tmp / 'missing.dic'),
                    'systemDictionaryAffPath': str(tmp / 'missing.aff'),
                    'spyllsPython': None,
                    'maxVariantPositions': max_positions,
                    'maxCandidates': max_candidates,
                }
            },
            ensure_ascii=False,
        ),
        encoding='utf-8',
    )
    return make_options(config_path)


def corrected(runtime, text: str, options) -> str:
    result = runtime.correct_dictionary_word_detail(
        text,
        page_number=1,
        bbox=None,
        title='',
        options=options,
    )
    return result['text']


def test_unique_candidate_is_corrected():
    runtime = load_runtime()
    with tempfile.TemporaryDirectory() as tmp_dir:
        options = write_config(Path(tmp_dir), 'uttagstillstånd\n')
        assert corrected(runtime, 'UTTAGSTILLSTÄND', options) == 'UTTAGSTILLSTÅND'


def test_original_valid_is_not_corrected():
    runtime = load_runtime()
    with tempfile.TemporaryDirectory() as tmp_dir:
        options = write_config(Path(tmp_dir), 'uttagstillständ\nuttagstillstånd\n')
        assert corrected(runtime, 'UTTAGSTILLSTÄND', options) == 'UTTAGSTILLSTÄND'


def test_multiple_valid_candidates_are_not_corrected():
    runtime = load_runtime()
    with tempfile.TemporaryDirectory() as tmp_dir:
        options = write_config(Path(tmp_dir), 'återfä\näterfå\n')
        assert corrected(runtime, 'ÄTERFÄ', options) == 'ÄTERFÄ'


def test_candidate_limit_prevents_correction():
    runtime = load_runtime()
    with tempfile.TemporaryDirectory() as tmp_dir:
        options = write_config(Path(tmp_dir), 'åååååå\n', max_positions=5, max_candidates=32)
        assert corrected(runtime, 'ÄÄÄÄÄÄ', options) == 'ÄÄÄÄÄÄ'


if __name__ == '__main__':
    test_unique_candidate_is_corrected()
    test_original_valid_is_not_corrected()
    test_multiple_valid_candidates_are_not_corrected()
    test_candidate_limit_prevents_correction()
    print('ocr dictionary correction tests passed')
