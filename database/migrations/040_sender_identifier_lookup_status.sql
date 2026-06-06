ALTER TABLE sender_organization_numbers
ADD COLUMN lookup_status TEXT NULL;

ALTER TABLE sender_organization_numbers
ADD COLUMN lookup_error_code TEXT NULL;

ALTER TABLE sender_organization_numbers
ADD COLUMN lookup_error_message TEXT NULL;

UPDATE sender_organization_numbers
SET lookup_status = CASE
    WHEN organization_name IS NOT NULL AND trim(organization_name) <> '' THEN 'resolved'
    ELSE 'pending'
END;

ALTER TABLE sender_payment_numbers
ADD COLUMN lookup_error_code TEXT NULL;

ALTER TABLE sender_payment_numbers
ADD COLUMN lookup_error_message TEXT NULL;

UPDATE sender_payment_numbers
SET payee_lookup_status = CASE
    WHEN payee_name IS NOT NULL AND trim(payee_name) <> '' THEN 'resolved'
    WHEN payee_lookup_status = 'not_found' THEN 'failed'
    WHEN payee_lookup_status IS NULL OR trim(payee_lookup_status) = '' THEN 'pending'
    ELSE payee_lookup_status
END,
lookup_error_code = CASE
    WHEN payee_lookup_status = 'not_found' THEN 'PAYEE_NOT_FOUND'
    ELSE lookup_error_code
END,
lookup_error_message = CASE
    WHEN payee_lookup_status = 'not_found' THEN
        CASE
            WHEN type = 'plusgiro' THEN 'Plusgiro ' || number || ' kunde inte slås upp.'
            ELSE 'Bankgiro ' || number || ' kunde inte slås upp.'
        END
    ELSE lookup_error_message
END;

CREATE INDEX IF NOT EXISTS idx_sender_organization_numbers_lookup_status
    ON sender_organization_numbers(lookup_status);

CREATE INDEX IF NOT EXISTS idx_sender_payment_numbers_lookup_status
    ON sender_payment_numbers(payee_lookup_status);
