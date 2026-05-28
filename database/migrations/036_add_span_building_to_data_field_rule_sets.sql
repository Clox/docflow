ALTER TABLE archiving_data_field_rule_sets
    ADD COLUMN unbounded_value_pattern_span INTEGER NOT NULL DEFAULT 0;

UPDATE archiving_data_field_rule_sets
SET unbounded_value_pattern_span = 1
WHERE use_value_pattern = 1
  AND requires_search_terms = 0;
