ALTER TABLE archiving_labels
ADD COLUMN system_label_key TEXT NOT NULL DEFAULT '';

UPDATE archiving_labels
SET system_label_key = 'invoice'
WHERE is_system = 1
  AND (
    id = 'faktura'
    OR lower(name) = 'faktura'
  );

UPDATE archiving_labels
SET system_label_key = 'autogiro'
WHERE is_system = 1
  AND (
    id = 'autogiro'
    OR lower(name) = 'autogiro'
  );

CREATE INDEX IF NOT EXISTS idx_archiving_labels_system_label_key
    ON archiving_labels (system_label_key, is_system, name, id);
