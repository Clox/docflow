ALTER TABLE sender_payment_numbers
ADD COLUMN payee_lookup_status TEXT NULL;

CREATE INDEX IF NOT EXISTS idx_sender_payment_numbers_payee_lookup_status
    ON sender_payment_numbers(payee_lookup_status);
