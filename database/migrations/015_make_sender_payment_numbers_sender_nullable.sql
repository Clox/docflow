PRAGMA foreign_keys = OFF;

ALTER TABLE sender_payment_numbers RENAME TO sender_payment_numbers_old;

CREATE TABLE sender_payment_numbers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NULL,
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
    FOREIGN KEY (sender_id) REFERENCES senders(id) ON DELETE SET NULL,
    CHECK(type IN ('bankgiro','plusgiro')),
    CHECK(requires_ocr IN (0,1)),
    CHECK(length(number) BETWEEN 5 AND 12)
);

INSERT INTO sender_payment_numbers (
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
FROM sender_payment_numbers_old;

DROP TABLE sender_payment_numbers_old;

CREATE UNIQUE INDEX idx_sender_payment_numbers_type_number
    ON sender_payment_numbers(type, number);
CREATE INDEX idx_sender_payment_numbers_sender_id
    ON sender_payment_numbers(sender_id);
CREATE INDEX idx_sender_payment_numbers_bankgiro
    ON sender_payment_numbers(number)
    WHERE type = 'bankgiro';
CREATE INDEX idx_sender_payment_numbers_plusgiro
    ON sender_payment_numbers(number)
    WHERE type = 'plusgiro';
CREATE INDEX idx_sender_payment_numbers_payee_name
    ON sender_payment_numbers(payee_name);
CREATE INDEX idx_sender_payment_numbers_payee_lookup_status
    ON sender_payment_numbers(payee_lookup_status);

PRAGMA foreign_keys = ON;
