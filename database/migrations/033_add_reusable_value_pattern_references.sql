ALTER TABLE archiving_data_field_rule_sets
    ADD COLUMN pattern_source TEXT NOT NULL DEFAULT 'manual';

ALTER TABLE archiving_data_field_rule_sets
    ADD COLUMN value_pattern_id TEXT NOT NULL DEFAULT '';

ALTER TABLE archiving_zones
    ADD COLUMN pattern_source TEXT NOT NULL DEFAULT 'manual';

ALTER TABLE archiving_zones
    ADD COLUMN value_pattern_id TEXT NOT NULL DEFAULT '';
