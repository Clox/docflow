-- no-transaction
PRAGMA foreign_keys = OFF;

CREATE TABLE senders_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NULL,
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
    s.id,
    CASE
        WHEN trim(COALESCE(s.name, '')) = '' THEN NULL
        WHEN trim(s.name) LIKE 'Org.nr %' THEN NULL
        WHEN trim(s.name) LIKE 'Bankgiro %' THEN NULL
        WHEN trim(s.name) LIKE 'Plusgiro %' THEN NULL
        WHEN EXISTS (
            SELECT 1
            FROM sender_organization_numbers o
            WHERE o.sender_id = s.id
              AND o.organization_name IS NOT NULL
              AND lower(trim(o.organization_name)) = lower(trim(s.name))
        ) THEN NULL
        WHEN EXISTS (
            SELECT 1
            FROM sender_payment_numbers p
            WHERE p.sender_id = s.id
              AND p.payee_name IS NOT NULL
              AND lower(trim(p.payee_name)) = lower(trim(s.name))
        ) THEN NULL
        ELSE s.name
    END AS name,
    s.domain,
    s.kind,
    s.notes,
    s.confidence,
    s.matching_updated_at,
    s.created_at,
    s.updated_at
FROM senders s;

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
