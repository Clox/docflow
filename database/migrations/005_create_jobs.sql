-- Stores selected and auto-detected sender ids for archived jobs.
CREATE TABLE IF NOT EXISTS jobs (
    id TEXT PRIMARY KEY,
    sender_id INTEGER NULL,
    auto_sender_id INTEGER NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (sender_id) REFERENCES senders(id) ON DELETE SET NULL,
    FOREIGN KEY (auto_sender_id) REFERENCES senders(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_jobs_sender_id ON jobs(sender_id);
CREATE INDEX IF NOT EXISTS idx_jobs_auto_sender_id ON jobs(auto_sender_id);
