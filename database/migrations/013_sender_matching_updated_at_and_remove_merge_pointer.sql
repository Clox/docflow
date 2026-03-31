-- no-transaction
PRAGMA foreign_keys = OFF;

CREATE TABLE senders_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    domain TEXT NULL,
    kind TEXT NULL,
    notes TEXT NULL,
    confidence REAL NOT NULL DEFAULT 1,
    matching_updated_at TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

INSERT INTO senders_new (
    id,
    name,
    domain,
    kind,
    notes,
    confidence,
    matching_updated_at,
    created_at,
    updated_at
)
SELECT
    id,
    name,
    domain,
    kind,
    notes,
    confidence,
    COALESCE(NULLIF(trim(updated_at), ''), NULLIF(trim(created_at), ''), CURRENT_TIMESTAMP),
    created_at,
    updated_at
FROM senders;

DROP TABLE senders;

ALTER TABLE senders_new RENAME TO senders;

CREATE UNIQUE INDEX IF NOT EXISTS idx_sender_organization_numbers_number
    ON sender_organization_numbers(organization_number);
CREATE INDEX IF NOT EXISTS idx_sender_organization_numbers_sender_id
    ON sender_organization_numbers(sender_id);
CREATE UNIQUE INDEX IF NOT EXISTS idx_sender_payment_numbers_type_number
    ON sender_payment_numbers(type, number);
CREATE INDEX IF NOT EXISTS idx_sender_payment_numbers_sender_id
    ON sender_payment_numbers(sender_id);

PRAGMA foreign_keys = ON;
