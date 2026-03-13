-- Stores one row per sender / organization / authority / company.
CREATE TABLE IF NOT EXISTS senders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    org_number TEXT NULL UNIQUE,
    domain TEXT NULL,
    kind TEXT NULL,
    notes TEXT NULL,
    confidence REAL NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_senders_slug ON senders(slug);
CREATE UNIQUE INDEX IF NOT EXISTS idx_senders_org_number ON senders(org_number);
