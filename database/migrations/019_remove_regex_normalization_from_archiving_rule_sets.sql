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
    updated_at
)
SELECT
    id,
    data_field_id,
    requires_search_terms,
    search_terms_json,
    value_pattern,
    CASE
        WHEN normalization_type = 'regex' THEN 'none'
        ELSE normalization_type
    END,
    normalization_chars,
    sort_order,
    created_at,
    updated_at
FROM archiving_data_field_rule_sets;

DROP TABLE archiving_data_field_rule_sets;

ALTER TABLE archiving_data_field_rule_sets_new RENAME TO archiving_data_field_rule_sets;

CREATE INDEX IF NOT EXISTS idx_archiving_data_field_rule_sets_field_order
    ON archiving_data_field_rule_sets (data_field_id, sort_order, id);
