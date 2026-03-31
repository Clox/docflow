-- no-transaction
PRAGMA foreign_keys = OFF;

CREATE TABLE sender_organization_numbers_seed (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_number TEXT NOT NULL,
    organization_name TEXT NULL,
    sender_id INTEGER NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

INSERT INTO sender_organization_numbers_seed (
    organization_number,
    organization_name,
    sender_id,
    created_at,
    updated_at
)
SELECT
    s.org_number,
    s.name,
    s.id,
    s.created_at,
    s.updated_at
FROM senders s
WHERE s.org_number IS NOT NULL
  AND trim(s.org_number) <> '';

CREATE TABLE senders_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    domain TEXT NULL,
    kind TEXT NULL,
    notes TEXT NULL,
    confidence REAL NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    merged_into_sender_id INTEGER NULL REFERENCES senders(id) ON DELETE SET NULL
);

INSERT INTO senders_new (
    id,
    name,
    domain,
    kind,
    notes,
    confidence,
    created_at,
    updated_at,
    merged_into_sender_id
)
SELECT
    id,
    name,
    domain,
    kind,
    notes,
    confidence,
    created_at,
    updated_at,
    merged_into_sender_id
FROM senders;

DROP TABLE senders;

ALTER TABLE senders_new RENAME TO senders;

CREATE INDEX idx_senders_merged_into_sender_id
    ON senders(merged_into_sender_id);

CREATE TABLE sender_organization_numbers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_number TEXT NOT NULL,
    organization_name TEXT NULL,
    sender_id INTEGER NULL REFERENCES senders(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    CHECK(length(organization_number) = 10)
);

INSERT INTO sender_organization_numbers (
    id,
    organization_number,
    organization_name,
    sender_id,
    created_at,
    updated_at
)
SELECT
    id,
    organization_number,
    organization_name,
    sender_id,
    created_at,
    updated_at
FROM sender_organization_numbers_seed;

DROP TABLE sender_organization_numbers_seed;

CREATE UNIQUE INDEX idx_sender_organization_numbers_number
    ON sender_organization_numbers(organization_number);
CREATE INDEX idx_sender_organization_numbers_sender_id
    ON sender_organization_numbers(sender_id);

PRAGMA foreign_keys = ON;
