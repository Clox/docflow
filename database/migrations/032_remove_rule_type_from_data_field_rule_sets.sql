CREATE TABLE archiving_data_field_rule_sets_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    data_field_id INTEGER NOT NULL,
    requires_search_terms INTEGER NOT NULL DEFAULT 1,
    search_terms_json TEXT NOT NULL DEFAULT '[]',
    value_pattern TEXT NOT NULL DEFAULT '',
    normalization_type TEXT NOT NULL DEFAULT 'none',
    normalization_chars TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    date_position TEXT NOT NULL DEFAULT 'first',
    amount_position TEXT NOT NULL DEFAULT 'first',
    scope_json TEXT NOT NULL DEFAULT '{}',
    use_value_pattern INTEGER NOT NULL DEFAULT 0,
    normalization_replacements_json TEXT NOT NULL DEFAULT '[]',
    capture_group INTEGER DEFAULT NULL,
    amount_whole_group INTEGER DEFAULT NULL,
    amount_fraction_group INTEGER DEFAULT NULL,
    FOREIGN KEY (data_field_id) REFERENCES archiving_data_fields(id) ON DELETE CASCADE
);

INSERT INTO archiving_data_field_rule_sets_new (
    id,
    data_field_id,
    requires_search_terms,
    search_terms_json,
    value_pattern,
    normalization_type,
    normalization_chars,
    sort_order,
    created_at,
    updated_at,
    date_position,
    amount_position,
    scope_json,
    use_value_pattern,
    normalization_replacements_json,
    capture_group,
    amount_whole_group,
    amount_fraction_group
)
SELECT
    id,
    data_field_id,
    requires_search_terms,
    search_terms_json,
    value_pattern,
    normalization_type,
    normalization_chars,
    sort_order,
    created_at,
    updated_at,
    date_position,
    amount_position,
    scope_json,
    use_value_pattern,
    normalization_replacements_json,
    capture_group,
    amount_whole_group,
    amount_fraction_group
FROM archiving_data_field_rule_sets;

DROP TABLE archiving_data_field_rule_sets;

ALTER TABLE archiving_data_field_rule_sets_new RENAME TO archiving_data_field_rule_sets;

CREATE INDEX IF NOT EXISTS idx_archiving_data_field_rule_sets_field_order
    ON archiving_data_field_rule_sets (data_field_id, sort_order, id);
