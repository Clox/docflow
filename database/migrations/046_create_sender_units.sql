-- no-transaction
PRAGMA foreign_keys = OFF;

CREATE TABLE IF NOT EXISTS sender_units (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL REFERENCES senders(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    normalized_name TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

INSERT OR IGNORE INTO sender_units (
    sender_id,
    name,
    normalized_name,
    sort_order,
    created_at,
    updated_at
)
SELECT
    sender_id,
    name,
    normalized_name,
    id,
    created_at,
    updated_at
FROM sender_match_names
WHERE EXISTS (
    SELECT 1
    FROM sqlite_master
    WHERE type = 'table'
      AND name = 'sender_match_names'
);

DROP INDEX IF EXISTS idx_sender_match_names_sender_normalized;
DROP INDEX IF EXISTS idx_sender_match_names_sender_id;
DROP INDEX IF EXISTS idx_sender_match_names_normalized_name;
DROP TABLE IF EXISTS sender_match_names;

CREATE UNIQUE INDEX IF NOT EXISTS idx_sender_units_sender_normalized
    ON sender_units(sender_id, normalized_name);

CREATE INDEX IF NOT EXISTS idx_sender_units_sender_sort_order
    ON sender_units(sender_id, sort_order);

CREATE INDEX IF NOT EXISTS idx_sender_units_normalized_name
    ON sender_units(normalized_name);

ALTER TABLE jobs ADD COLUMN analysis_sender_name TEXT NULL;
ALTER TABLE jobs ADD COLUMN analysis_sender_unit_id INTEGER NULL;

PRAGMA foreign_keys = ON;
