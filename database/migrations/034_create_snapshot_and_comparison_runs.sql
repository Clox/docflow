CREATE TABLE IF NOT EXISTS analysis_snapshots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    folder_name TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL,
    scope TEXT NOT NULL DEFAULT 'jobs',
    filter_label TEXT NOT NULL DEFAULT 'Jobb',
    export_directory TEXT NOT NULL,
    job_ids_json TEXT NOT NULL DEFAULT '[]',
    skipped_job_ids_json TEXT NOT NULL DEFAULT '[]',
    total_jobs INTEGER NOT NULL DEFAULT 0,
    completed_jobs INTEGER NOT NULL DEFAULT 0,
    failed_jobs INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    completed_at TEXT NULL,
    created_from_rules_version INTEGER NULL,
    requires_reanalysis INTEGER NOT NULL DEFAULT 1,
    error_message TEXT NULL
);

CREATE TABLE IF NOT EXISTS analysis_snapshot_jobs (
    snapshot_id INTEGER NOT NULL,
    job_id TEXT NOT NULL,
    status TEXT NOT NULL,
    error_message TEXT NULL,
    completed_at TEXT NULL,
    PRIMARY KEY (snapshot_id, job_id),
    FOREIGN KEY (snapshot_id) REFERENCES analysis_snapshots(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_analysis_snapshot_jobs_job_status
    ON analysis_snapshot_jobs (job_id, status);

CREATE TABLE IF NOT EXISTS comparison_runs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    left_folder_name TEXT NOT NULL,
    right_folder_name TEXT NOT NULL,
    live_side TEXT NOT NULL,
    status TEXT NOT NULL,
    scope TEXT NOT NULL DEFAULT 'jobs',
    filter_label TEXT NOT NULL DEFAULT 'Jobb',
    job_ids_json TEXT NOT NULL DEFAULT '[]',
    total_jobs INTEGER NOT NULL DEFAULT 0,
    completed_jobs INTEGER NOT NULL DEFAULT 0,
    failed_jobs INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    completed_at TEXT NULL,
    result_json TEXT NULL,
    error_message TEXT NULL
);

CREATE TABLE IF NOT EXISTS comparison_run_jobs (
    comparison_run_id INTEGER NOT NULL,
    job_id TEXT NOT NULL,
    status TEXT NOT NULL,
    error_message TEXT NULL,
    completed_at TEXT NULL,
    PRIMARY KEY (comparison_run_id, job_id),
    FOREIGN KEY (comparison_run_id) REFERENCES comparison_runs(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_comparison_run_jobs_job_status
    ON comparison_run_jobs (job_id, status);
