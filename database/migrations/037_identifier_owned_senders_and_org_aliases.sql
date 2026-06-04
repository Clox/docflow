-- no-transaction
PRAGMA foreign_keys = OFF;

CREATE TABLE senders_existing_ids (
    id INTEGER PRIMARY KEY
);

INSERT INTO senders_existing_ids (id)
SELECT id FROM senders;

INSERT INTO senders (
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
    'Org.nr ' || substr(organization_number, 1, 6) || '-' || substr(organization_number, 7) AS name,
    NULL,
    NULL,
    '',
    1.0,
    COALESCE(updated_at, datetime('now')),
    COALESCE(created_at, datetime('now')),
    COALESCE(updated_at, datetime('now'))
FROM sender_organization_numbers
WHERE sender_id IS NULL;

CREATE TABLE sender_organization_numbers_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_number TEXT NOT NULL,
    organization_name TEXT NULL,
    sender_id INTEGER NOT NULL REFERENCES senders(id) ON DELETE CASCADE,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    source TEXT NULL,
    CHECK(length(organization_number) = 10)
);

INSERT INTO sender_organization_numbers_new (
    id,
    organization_number,
    organization_name,
    sender_id,
    created_at,
    updated_at,
    source
)
SELECT
    o.id,
    o.organization_number,
    o.organization_name,
    COALESCE(
        o.sender_id,
        (
            SELECT s.id
            FROM senders s
            WHERE s.id NOT IN (SELECT id FROM senders_existing_ids)
              AND s.name = 'Org.nr ' || substr(o.organization_number, 1, 6) || '-' || substr(o.organization_number, 7)
            ORDER BY s.id ASC
            LIMIT 1
        )
    ) AS sender_id,
    o.created_at,
    o.updated_at,
    o.source
FROM sender_organization_numbers o;

DROP TABLE sender_organization_numbers;
ALTER TABLE sender_organization_numbers_new RENAME TO sender_organization_numbers;

CREATE UNIQUE INDEX idx_sender_organization_numbers_number
    ON sender_organization_numbers(organization_number);
CREATE INDEX idx_sender_organization_numbers_sender_id
    ON sender_organization_numbers(sender_id);

INSERT INTO senders (
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
    CASE
        WHEN type = 'plusgiro' THEN 'Plusgiro ' || number
        ELSE 'Bankgiro ' || number
    END AS name,
    NULL,
    NULL,
    '',
    1.0,
    COALESCE(updated_at, datetime('now')),
    COALESCE(created_at, datetime('now')),
    COALESCE(updated_at, datetime('now'))
FROM sender_payment_numbers
WHERE sender_id IS NULL;

CREATE TABLE sender_payment_numbers_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL REFERENCES senders(id) ON DELETE CASCADE,
    type TEXT NOT NULL,
    number TEXT NOT NULL,
    original_number TEXT NULL,
    requires_ocr INTEGER NOT NULL DEFAULT 0,
    source TEXT NULL,
    confidence REAL NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    payee_name TEXT NULL,
    payee_lookup_status TEXT NULL,
    CHECK(type IN ('bankgiro','plusgiro')),
    CHECK(requires_ocr IN (0,1)),
    CHECK(length(number) BETWEEN 5 AND 12)
);

INSERT INTO sender_payment_numbers_new (
    id,
    sender_id,
    type,
    number,
    original_number,
    requires_ocr,
    source,
    confidence,
    created_at,
    updated_at,
    payee_name,
    payee_lookup_status
)
SELECT
    p.id,
    COALESCE(
        p.sender_id,
        (
            SELECT s.id
            FROM senders s
            WHERE s.id NOT IN (SELECT id FROM senders_existing_ids)
              AND s.name = CASE
                    WHEN p.type = 'plusgiro' THEN 'Plusgiro ' || p.number
                    ELSE 'Bankgiro ' || p.number
                  END
            ORDER BY s.id ASC
            LIMIT 1
        )
    ) AS sender_id,
    p.type,
    p.number,
    p.original_number,
    p.requires_ocr,
    p.source,
    p.confidence,
    p.created_at,
    p.updated_at,
    p.payee_name,
    p.payee_lookup_status
FROM sender_payment_numbers p;

DROP TABLE sender_payment_numbers;
ALTER TABLE sender_payment_numbers_new RENAME TO sender_payment_numbers;

CREATE UNIQUE INDEX idx_sender_payment_numbers_type_number
    ON sender_payment_numbers(type, number);
CREATE INDEX idx_sender_payment_numbers_bankgiro
    ON sender_payment_numbers(number)
    WHERE type = 'bankgiro';
CREATE INDEX idx_sender_payment_numbers_plusgiro
    ON sender_payment_numbers(number)
    WHERE type = 'plusgiro';
CREATE INDEX idx_sender_payment_numbers_sender_id
    ON sender_payment_numbers(sender_id);
CREATE INDEX idx_sender_payment_numbers_payee_name
    ON sender_payment_numbers(payee_name);
CREATE INDEX idx_sender_payment_numbers_payee_lookup_status
    ON sender_payment_numbers(payee_lookup_status);

DROP TABLE IF EXISTS sender_alternative_names;
CREATE TABLE sender_alternative_names (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_organization_number_id INTEGER NOT NULL REFERENCES sender_organization_numbers(id) ON DELETE CASCADE,
    name TEXT NOT NULL COLLATE NOCASE,
    normalized_name TEXT NOT NULL,
    source TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX idx_sender_alternative_names_org_normalized
    ON sender_alternative_names(sender_organization_number_id, normalized_name);
CREATE INDEX idx_sender_alternative_names_org_id
    ON sender_alternative_names(sender_organization_number_id);
CREATE INDEX idx_sender_alternative_names_normalized_name
    ON sender_alternative_names(normalized_name);
CREATE INDEX idx_sender_alternative_names_name
    ON sender_alternative_names(name);

DROP TABLE senders_existing_ids;

PRAGMA foreign_keys = ON;
