CREATE TABLE IF NOT EXISTS filename_template_migration_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration_key TEXT NOT NULL UNIQUE,
    migrated_at TEXT NOT NULL,
    active_rules_backup_json TEXT NOT NULL,
    draft_rules_backup_json TEXT NOT NULL,
    statistics_json TEXT NOT NULL
);
