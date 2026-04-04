CREATE TABLE sender_alternative_names (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL REFERENCES senders(id) ON DELETE CASCADE,
    name TEXT NOT NULL COLLATE NOCASE,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX idx_sender_alternative_names_sender_name
    ON sender_alternative_names(sender_id, name);

CREATE INDEX idx_sender_alternative_names_sender_id
    ON sender_alternative_names(sender_id);

CREATE INDEX idx_sender_alternative_names_name
    ON sender_alternative_names(name);
