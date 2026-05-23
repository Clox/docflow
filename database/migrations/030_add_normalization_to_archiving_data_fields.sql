ALTER TABLE archiving_data_fields
    ADD COLUMN normalization_type TEXT NOT NULL DEFAULT 'none';

ALTER TABLE archiving_data_fields
    ADD COLUMN normalization_chars TEXT NOT NULL DEFAULT '';

ALTER TABLE archiving_data_fields
    ADD COLUMN normalization_replacements_json TEXT NOT NULL DEFAULT '[]';
