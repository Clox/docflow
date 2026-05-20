CREATE TABLE IF NOT EXISTS archiving_zones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rules_scope TEXT NOT NULL CHECK (rules_scope IN ('active', 'draft')),
    zone_key TEXT NOT NULL,
    name TEXT NOT NULL,
    enabled INTEGER NOT NULL DEFAULT 1,
    pattern TEXT NOT NULL DEFAULT '',
    is_regex INTEGER NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE (rules_scope, zone_key)
);

CREATE INDEX IF NOT EXISTS idx_archiving_zones_scope_order
    ON archiving_zones (rules_scope, sort_order, id);
