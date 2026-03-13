-- Stores BG/PG payment numbers connected to senders.
CREATE TABLE IF NOT EXISTS sender_payment_numbers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    -- Canonical normalized number used for lookup and uniqueness.
    number TEXT NOT NULL,
    -- Optional raw/imported/OCR value for traceability.
    original_number TEXT NULL,
    requires_ocr INTEGER NOT NULL DEFAULT 0,
    source TEXT NULL,
    confidence REAL NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (sender_id) REFERENCES senders(id) ON DELETE CASCADE,
    CHECK(type IN ('bankgiro','plusgiro')),
    CHECK(requires_ocr IN (0,1)),
    CHECK(length(number) BETWEEN 5 AND 12)
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_sender_payment_numbers_type_number
    ON sender_payment_numbers(type, number);
CREATE INDEX IF NOT EXISTS idx_sender_payment_numbers_sender_id
    ON sender_payment_numbers(sender_id);
CREATE INDEX IF NOT EXISTS idx_sender_payment_numbers_bankgiro
    ON sender_payment_numbers(number)
    WHERE type = 'bankgiro';
CREATE INDEX IF NOT EXISTS idx_sender_payment_numbers_plusgiro
    ON sender_payment_numbers(number)
    WHERE type = 'plusgiro';
