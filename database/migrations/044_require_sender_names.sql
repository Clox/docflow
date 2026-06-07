UPDATE senders
SET name = '(Namnlös)'
WHERE name IS NULL
   OR trim(name) = '';

CREATE TRIGGER IF NOT EXISTS trg_senders_require_name_insert
BEFORE INSERT ON senders
FOR EACH ROW
WHEN NEW.name IS NULL OR trim(NEW.name) = ''
BEGIN
    SELECT RAISE(ABORT, 'Sender name is required');
END;

CREATE TRIGGER IF NOT EXISTS trg_senders_require_name_update
BEFORE UPDATE OF name ON senders
FOR EACH ROW
WHEN NEW.name IS NULL OR trim(NEW.name) = ''
BEGIN
    SELECT RAISE(ABORT, 'Sender name is required');
END;
