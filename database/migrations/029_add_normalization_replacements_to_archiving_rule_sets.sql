ALTER TABLE archiving_data_field_rule_sets
    ADD COLUMN normalization_replacements_json TEXT NOT NULL DEFAULT '[]';
