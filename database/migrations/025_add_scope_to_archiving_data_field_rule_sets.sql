ALTER TABLE archiving_data_field_rule_sets
    ADD COLUMN scope_json TEXT NOT NULL DEFAULT '{}';
