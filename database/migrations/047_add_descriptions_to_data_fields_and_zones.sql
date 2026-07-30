ALTER TABLE archiving_data_fields
    ADD COLUMN description TEXT NOT NULL DEFAULT '';

ALTER TABLE archiving_zones
    ADD COLUMN description TEXT NOT NULL DEFAULT '';
