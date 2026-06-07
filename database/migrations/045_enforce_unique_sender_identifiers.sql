-- no-transaction
PRAGMA foreign_keys = OFF;

DROP INDEX IF EXISTS idx_sender_organization_numbers_number;
DROP INDEX IF EXISTS idx_sender_payment_numbers_type_number;

CREATE TEMP TABLE sender_organization_identifier_canonical AS
SELECT
    organization_number,
    (
        SELECT candidate.id
        FROM sender_organization_numbers candidate
        WHERE candidate.organization_number = grouped.organization_number
        ORDER BY
            CASE WHEN candidate.sender_id IS NULL THEN 1 ELSE 0 END ASC,
            candidate.updated_at DESC,
            candidate.id ASC
        LIMIT 1
    ) AS canonical_id
FROM sender_organization_numbers grouped
WHERE trim(grouped.organization_number) <> ''
GROUP BY grouped.organization_number;

INSERT OR IGNORE INTO sender_alternative_names (
    sender_organization_number_id,
    name,
    normalized_name,
    source,
    created_at,
    updated_at
)
SELECT
    canonical.canonical_id,
    aliases.name,
    aliases.normalized_name,
    aliases.source,
    aliases.created_at,
    aliases.updated_at
FROM sender_alternative_names aliases
INNER JOIN sender_organization_numbers rows
    ON rows.id = aliases.sender_organization_number_id
INNER JOIN sender_organization_identifier_canonical canonical
    ON canonical.organization_number = rows.organization_number
WHERE canonical.canonical_id IS NOT NULL
  AND aliases.sender_organization_number_id <> canonical.canonical_id;

DELETE FROM sender_organization_numbers
WHERE id NOT IN (
    SELECT canonical_id
    FROM sender_organization_identifier_canonical
    WHERE canonical_id IS NOT NULL
);

DELETE FROM sender_alternative_names
WHERE sender_organization_number_id NOT IN (
    SELECT id FROM sender_organization_numbers
);

DROP TABLE sender_organization_identifier_canonical;

CREATE TEMP TABLE sender_payment_identifier_canonical AS
SELECT
    type,
    number,
    (
        SELECT candidate.id
        FROM sender_payment_numbers candidate
        WHERE candidate.type = grouped.type
          AND candidate.number = grouped.number
        ORDER BY
            CASE WHEN candidate.sender_id IS NULL THEN 1 ELSE 0 END ASC,
            candidate.updated_at DESC,
            candidate.id ASC
        LIMIT 1
    ) AS canonical_id
FROM sender_payment_numbers grouped
WHERE trim(grouped.type) <> ''
  AND trim(grouped.number) <> ''
GROUP BY grouped.type, grouped.number;

DELETE FROM sender_payment_numbers
WHERE id NOT IN (
    SELECT canonical_id
    FROM sender_payment_identifier_canonical
    WHERE canonical_id IS NOT NULL
);

DROP TABLE sender_payment_identifier_canonical;

CREATE UNIQUE INDEX IF NOT EXISTS idx_sender_organization_numbers_number
    ON sender_organization_numbers(organization_number);

CREATE UNIQUE INDEX IF NOT EXISTS idx_sender_payment_numbers_type_number
    ON sender_payment_numbers(type, number);

PRAGMA foreign_keys = ON;
