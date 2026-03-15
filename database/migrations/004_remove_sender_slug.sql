-- no-transaction
PRAGMA foreign_keys = OFF;

CREATE TABLE senders_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    org_number TEXT NULL UNIQUE,
    domain TEXT NULL,
    kind TEXT NULL,
    notes TEXT NULL,
    confidence REAL NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

INSERT INTO senders_new (
    id,
    name,
    org_number,
    domain,
    kind,
    notes,
    confidence,
    created_at,
    updated_at
)
SELECT
    id,
    name,
    org_number,
    domain,
    kind,
    notes,
    confidence,
    created_at,
    updated_at
FROM senders;

DROP TABLE senders;

ALTER TABLE senders_new RENAME TO senders;

CREATE UNIQUE INDEX idx_senders_org_number ON senders(org_number);

PRAGMA foreign_keys = ON;
