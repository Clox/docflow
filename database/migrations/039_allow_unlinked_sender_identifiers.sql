-- no-transaction
PRAGMA foreign_keys = OFF;

CREATE TABLE sender_organization_numbers_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_number TEXT NOT NULL,
    organization_name TEXT NULL,
    sender_id INTEGER NULL REFERENCES senders(id) ON DELETE SET NULL,
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
    id,
    organization_number,
    organization_name,
    CASE
        WHEN sender_id IS NOT NULL
          AND EXISTS (SELECT 1 FROM senders WHERE senders.id = sender_organization_numbers.sender_id)
        THEN sender_id
        ELSE NULL
    END,
    created_at,
    updated_at,
    source
FROM sender_organization_numbers;

DROP TABLE sender_organization_numbers;
ALTER TABLE sender_organization_numbers_new RENAME TO sender_organization_numbers;

CREATE UNIQUE INDEX idx_sender_organization_numbers_number
    ON sender_organization_numbers(organization_number);
CREATE INDEX idx_sender_organization_numbers_sender_id
    ON sender_organization_numbers(sender_id);

CREATE TABLE sender_payment_numbers_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NULL REFERENCES senders(id) ON DELETE SET NULL,
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
    id,
    CASE
        WHEN sender_id IS NOT NULL
          AND EXISTS (SELECT 1 FROM senders WHERE senders.id = sender_payment_numbers.sender_id)
        THEN sender_id
        ELSE NULL
    END,
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
FROM sender_payment_numbers;

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

PRAGMA foreign_keys = ON;
