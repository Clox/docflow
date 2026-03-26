-- Stores clients / principals used for folder selection and PIN matching.
CREATE TABLE IF NOT EXISTS clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    folder_name TEXT NOT NULL,
    personal_identity_number TEXT NOT NULL UNIQUE,
    sort_order INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_clients_personal_identity_number
    ON clients(personal_identity_number);
CREATE INDEX IF NOT EXISTS idx_clients_sort_order
    ON clients(sort_order, id);
