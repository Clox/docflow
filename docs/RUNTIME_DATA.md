Runtime data lives in `data/` and is intentionally local.

What belongs in `data/`:
- user-edited JSON such as `config.json`, `clients.json`, `archive-structure.json`, `labels.json`, `extraction-fields.json`
- SQLite databases
- job event queues, install status files, locks, logs, and generated helper scripts

What does not belong in git:
- anything under `data/`
- Python bytecode caches such as `__pycache__/` and `*.pyc`

What should be versioned instead:
- source code
- static reference assets under `public/assets/`
- documented built-in defaults that are hardcoded in PHP/JS

Practical rule:
- if a file is expected to be edited by the running app or by a specific local installation, keep it in `data/` and out of git
- if a file is part of the application itself, keep it outside `data/` and version it normally
