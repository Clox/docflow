CREATE TABLE IF NOT EXISTS archiving_labels (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    min_score INTEGER NOT NULL DEFAULT 1,
    is_system INTEGER NOT NULL DEFAULT 0,
    rules_json TEXT NOT NULL DEFAULT '[]',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_archiving_labels_system_name
    ON archiving_labels (is_system, name, id);

CREATE TABLE IF NOT EXISTS document_labels (
    job_id TEXT NOT NULL,
    label_id TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY (job_id, label_id),
    FOREIGN KEY (label_id) REFERENCES archiving_labels(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS document_data_values (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id TEXT NOT NULL,
    archiving_data_field_id INTEGER NOT NULL,
    value TEXT NOT NULL,
    is_primary INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (archiving_data_field_id) REFERENCES archiving_data_fields(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_document_data_values_job_field
    ON document_data_values (job_id, archiving_data_field_id, id);

INSERT OR REPLACE INTO archiving_labels (
    id,
    name,
    description,
    min_score,
    is_system,
    rules_json,
    created_at,
    updated_at
)
SELECT
    trim(json_extract(label.value, '$.id')),
    trim(json_extract(label.value, '$.name')),
    COALESCE(json_extract(label.value, '$.description'), ''),
    COALESCE(json_extract(label.value, '$.minScore'), 1),
    0,
    json(COALESCE(json_extract(label.value, '$.rules'), '[]')),
    datetime('now'),
    datetime('now')
FROM archiving_rules_state state,
    json_each(state.active_archiving_rules_json, '$.labels') AS label
WHERE state.id = 1
    AND trim(COALESCE(json_extract(label.value, '$.id'), '')) <> ''
    AND trim(COALESCE(json_extract(label.value, '$.name'), '')) <> '';

INSERT OR REPLACE INTO archiving_labels (
    id,
    name,
    description,
    min_score,
    is_system,
    rules_json,
    created_at,
    updated_at
)
SELECT
    trim(COALESCE(json_extract(label.value, '$.id'), label.key)),
    trim(json_extract(label.value, '$.name')),
    COALESCE(json_extract(label.value, '$.description'), ''),
    COALESCE(json_extract(label.value, '$.minScore'), 1),
    1,
    json(COALESCE(json_extract(label.value, '$.rules'), '[]')),
    datetime('now'),
    datetime('now')
FROM archiving_rules_state state,
    json_each(state.active_archiving_rules_json, '$.systemLabels') AS label
WHERE state.id = 1
    AND trim(COALESCE(json_extract(label.value, '$.id'), label.key, '')) <> ''
    AND trim(COALESCE(json_extract(label.value, '$.name'), '')) <> '';

UPDATE archiving_rules_state
SET active_archiving_rules_json = json_remove(active_archiving_rules_json, '$.labels', '$.systemLabels'),
    draft_archiving_rules_json = json_remove(draft_archiving_rules_json, '$.labels', '$.systemLabels'),
    updated_at = datetime('now')
WHERE id = 1;
