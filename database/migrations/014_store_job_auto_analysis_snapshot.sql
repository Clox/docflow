ALTER TABLE jobs ADD COLUMN analysis_client_id TEXT NULL;
ALTER TABLE jobs ADD COLUMN analysis_sender_id INTEGER NULL;
ALTER TABLE jobs ADD COLUMN analysis_category_id TEXT NULL;
ALTER TABLE jobs ADD COLUMN analysis_labels_json TEXT NULL;
ALTER TABLE jobs ADD COLUMN analysis_fields_json TEXT NULL;
ALTER TABLE jobs ADD COLUMN analysis_system_fields_json TEXT NULL;
ALTER TABLE jobs ADD COLUMN analyzed_at TEXT NULL;
