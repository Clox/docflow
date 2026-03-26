ALTER TABLE senders
ADD COLUMN merged_into_sender_id INTEGER NULL REFERENCES senders(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_senders_merged_into_sender_id
    ON senders(merged_into_sender_id);
