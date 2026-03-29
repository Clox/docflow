ALTER TABLE sender_payment_numbers
ADD COLUMN payee_name TEXT NULL;

CREATE INDEX IF NOT EXISTS idx_sender_payment_numbers_payee_name
    ON sender_payment_numbers(payee_name);
