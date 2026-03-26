-- Stores the full active/draft archiving-rules state in SQLite.
CREATE TABLE IF NOT EXISTS archiving_rules_state (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    active_archiving_rules_version INTEGER NOT NULL,
    active_archiving_rules_json TEXT NOT NULL,
    draft_archiving_rules_json TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
