ALTER TABLE sender_organization_numbers
    ADD COLUMN source TEXT NULL;

UPDATE sender_organization_numbers
SET source = 'document_auto'
WHERE source IS NULL OR trim(source) = '';
