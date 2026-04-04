CREATE TABLE IF NOT EXISTS archiving_data_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rules_scope TEXT NOT NULL CHECK (rules_scope IN ('active', 'draft')),
    field_type TEXT NOT NULL CHECK (field_type IN ('custom', 'predefined')),
    field_key TEXT NOT NULL,
    name TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE (rules_scope, field_type, field_key)
);

CREATE INDEX IF NOT EXISTS idx_archiving_data_fields_scope_type_order
    ON archiving_data_fields (rules_scope, field_type, sort_order, id);

CREATE TABLE IF NOT EXISTS archiving_data_field_rule_sets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    data_field_id INTEGER NOT NULL,
    requires_search_terms INTEGER NOT NULL DEFAULT 1,
    search_terms_json TEXT NOT NULL DEFAULT '[]',
    value_pattern TEXT NOT NULL DEFAULT '',
    normalization_type TEXT NOT NULL DEFAULT 'none',
    normalization_chars TEXT NOT NULL DEFAULT '',
    normalization_regex_pattern TEXT NOT NULL DEFAULT '',
    normalization_regex_replacement TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (data_field_id) REFERENCES archiving_data_fields(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_archiving_data_field_rule_sets_field_order
    ON archiving_data_field_rule_sets (data_field_id, sort_order, id);
