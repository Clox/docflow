-- no-transaction
PRAGMA foreign_keys = OFF;

-- Keep principal ids stable while making personal identity numbers optional and
-- storing a canonical value for safe matching and uniqueness.
CREATE TABLE clients_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    folder_name TEXT NOT NULL,
    personal_identity_number TEXT NULL,
    normalized_personal_identity_number TEXT NULL,
    preferred_first_name_index INTEGER NULL,
    sort_order INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

INSERT INTO clients_new (
    id,
    first_name,
    last_name,
    folder_name,
    personal_identity_number,
    normalized_personal_identity_number,
    preferred_first_name_index,
    sort_order,
    created_at,
    updated_at
)
SELECT
    id,
    first_name,
    last_name,
    folder_name,
    NULLIF(trim(personal_identity_number), ''),
    CASE
        WHEN length(replace(replace(replace(trim(personal_identity_number), '-', ''), ' ', ''), '+', '')) = 12
         AND replace(replace(replace(trim(personal_identity_number), '-', ''), ' ', ''), '+', '') NOT GLOB '*[^0-9]*'
        THEN replace(replace(replace(trim(personal_identity_number), '-', ''), ' ', ''), '+', '')
        ELSE NULL
    END,
    preferred_first_name_index,
    sort_order,
    created_at,
    updated_at
FROM clients;

-- Legacy duplicates remain readable but are deliberately excluded from automatic
-- matching until a user corrects them. This makes the migration safe to apply.
UPDATE clients_new
SET normalized_personal_identity_number = NULL
WHERE normalized_personal_identity_number IN (
    SELECT normalized_personal_identity_number
    FROM clients_new
    WHERE normalized_personal_identity_number IS NOT NULL
    GROUP BY normalized_personal_identity_number
    HAVING count(*) > 1
);

DROP INDEX IF EXISTS idx_clients_personal_identity_number;
DROP INDEX IF EXISTS idx_clients_sort_order;
DROP TABLE clients;
ALTER TABLE clients_new RENAME TO clients;

CREATE UNIQUE INDEX idx_clients_normalized_personal_identity_number
    ON clients(normalized_personal_identity_number)
    WHERE normalized_personal_identity_number IS NOT NULL
      AND trim(normalized_personal_identity_number) <> '';
CREATE INDEX idx_clients_sort_order ON clients(sort_order, id);

CREATE TABLE bank_connections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workspace_key TEXT NOT NULL,
    bank_key TEXT NOT NULL,
    external_customer_id TEXT NULL,
    customer_name TEXT NULL,
    customer_personal_identity_number TEXT NULL,
    normalized_customer_personal_identity_number TEXT NULL,
    connection_label TEXT NULL,
    last_synced_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX idx_bank_connections_external_customer
    ON bank_connections(workspace_key, bank_key, external_customer_id)
    WHERE external_customer_id IS NOT NULL AND trim(external_customer_id) <> '';
CREATE INDEX idx_bank_connections_workspace
    ON bank_connections(workspace_key, bank_key, id);

CREATE TABLE bank_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workspace_key TEXT NOT NULL,
    bank_key TEXT NOT NULL,
    bank_connection_id INTEGER NULL REFERENCES bank_connections(id) ON DELETE SET NULL,
    external_account_id TEXT NULL,
    identity_fallback_key TEXT NULL,

    account_number TEXT NULL,
    clearing_number TEXT NULL,
    iban TEXT NULL,
    bic TEXT NULL,
    account_name TEXT NULL,
    account_type TEXT NULL,
    currency TEXT NULL,

    account_holder_name TEXT NULL,
    account_holder_personal_identity_number TEXT NULL,
    normalized_account_holder_personal_identity_number TEXT NULL,

    principal_id INTEGER NULL REFERENCES clients(id) ON DELETE SET NULL,
    linkage_method TEXT NULL,
    linkage_status TEXT NOT NULL DEFAULT 'unlinked',
    linked_at TEXT NULL,
    linked_by_user_id TEXT NULL,

    can_view_balance INTEGER NULL,
    can_view_transactions INTEGER NULL,
    can_register_payments INTEGER NULL,
    can_register_transfers INTEGER NULL,
    can_view_pending_payments INTEGER NULL,
    capabilities_json TEXT NULL,

    first_seen_at TEXT NOT NULL,
    last_seen_at TEXT NOT NULL,
    last_synced_at TEXT NULL,
    external_created_at TEXT NULL,
    external_updated_at TEXT NULL,

    is_active INTEGER NOT NULL DEFAULT 1,
    is_closed INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,

    CHECK(linkage_method IS NULL OR linkage_method IN (
        'personal_identity_number_auto',
        'name_suggestion_confirmed',
        'manual'
    )),
    CHECK(linkage_status IN ('unlinked', 'suggested', 'linked', 'conflict')),
    CHECK(can_view_balance IS NULL OR can_view_balance IN (0, 1)),
    CHECK(can_view_transactions IS NULL OR can_view_transactions IN (0, 1)),
    CHECK(can_register_payments IS NULL OR can_register_payments IN (0, 1)),
    CHECK(can_register_transfers IS NULL OR can_register_transfers IN (0, 1)),
    CHECK(can_view_pending_payments IS NULL OR can_view_pending_payments IN (0, 1)),
    CHECK(is_active IN (0, 1)),
    CHECK(is_closed IN (0, 1))
);

CREATE UNIQUE INDEX idx_bank_accounts_connection_external
    ON bank_accounts(workspace_key, bank_connection_id, external_account_id)
    WHERE bank_connection_id IS NOT NULL
      AND external_account_id IS NOT NULL
      AND trim(external_account_id) <> '';
CREATE UNIQUE INDEX idx_bank_accounts_bank_external_without_connection
    ON bank_accounts(workspace_key, bank_key, external_account_id)
    WHERE bank_connection_id IS NULL
      AND external_account_id IS NOT NULL
      AND trim(external_account_id) <> '';
CREATE UNIQUE INDEX idx_bank_accounts_connection_fallback_identity
    ON bank_accounts(workspace_key, bank_connection_id, identity_fallback_key)
    WHERE bank_connection_id IS NOT NULL
      AND (external_account_id IS NULL OR trim(external_account_id) = '')
      AND identity_fallback_key IS NOT NULL
      AND trim(identity_fallback_key) <> '';
CREATE UNIQUE INDEX idx_bank_accounts_bank_fallback_without_connection
    ON bank_accounts(workspace_key, bank_key, identity_fallback_key)
    WHERE bank_connection_id IS NULL
      AND (external_account_id IS NULL OR trim(external_account_id) = '')
      AND identity_fallback_key IS NOT NULL
      AND trim(identity_fallback_key) <> '';
CREATE INDEX idx_bank_accounts_workspace_status
    ON bank_accounts(workspace_key, linkage_status, is_active, id);
CREATE INDEX idx_bank_accounts_principal
    ON bank_accounts(workspace_key, principal_id);
CREATE INDEX idx_bank_accounts_holder_pin
    ON bank_accounts(workspace_key, normalized_account_holder_personal_identity_number);

CREATE TABLE bank_account_link_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workspace_key TEXT NOT NULL,
    bank_account_id INTEGER NOT NULL REFERENCES bank_accounts(id) ON DELETE CASCADE,
    previous_principal_id INTEGER NULL,
    new_principal_id INTEGER NULL,
    linkage_method TEXT NULL,
    event_type TEXT NOT NULL,
    actor_user_id TEXT NULL,
    occurred_at TEXT NOT NULL,
    details_json TEXT NULL,
    CHECK(event_type IN ('linked', 'changed', 'unlinked', 'auto_linked', 'auto_unlinked'))
);

CREATE INDEX idx_bank_account_link_events_account
    ON bank_account_link_events(workspace_key, bank_account_id, occurred_at, id);

PRAGMA foreign_keys = ON;
