ALTER TABLE sender_organization_numbers
ADD COLUMN source_job_id TEXT NULL;

ALTER TABLE sender_organization_numbers
ADD COLUMN source_bbox_indexes TEXT NULL;

ALTER TABLE sender_payment_numbers
ADD COLUMN source_job_id TEXT NULL;

ALTER TABLE sender_payment_numbers
ADD COLUMN source_bbox_indexes TEXT NULL;
