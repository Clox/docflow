ALTER TABLE archiving_data_field_rule_sets
    ADD COLUMN use_value_pattern INTEGER NOT NULL DEFAULT 0;

UPDATE archiving_data_field_rule_sets
SET use_value_pattern = 1
WHERE TRIM(value_pattern) <> '';
