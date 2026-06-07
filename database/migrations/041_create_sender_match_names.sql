CREATE TABLE sender_match_names (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL REFERENCES senders(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    normalized_name TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX idx_sender_match_names_sender_normalized
    ON sender_match_names(sender_id, normalized_name);

CREATE INDEX idx_sender_match_names_sender_id
    ON sender_match_names(sender_id);

CREATE INDEX idx_sender_match_names_normalized_name
    ON sender_match_names(normalized_name);
