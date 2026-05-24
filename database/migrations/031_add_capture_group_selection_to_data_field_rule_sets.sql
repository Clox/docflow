ALTER TABLE archiving_data_field_rule_sets
    ADD COLUMN capture_group INTEGER DEFAULT NULL;

ALTER TABLE archiving_data_field_rule_sets
    ADD COLUMN amount_whole_group INTEGER DEFAULT NULL;

ALTER TABLE archiving_data_field_rule_sets
    ADD COLUMN amount_fraction_group INTEGER DEFAULT NULL;

UPDATE archiving_data_field_rule_sets
SET
    amount_whole_group = 1,
    amount_fraction_group = 2
WHERE data_field_id IN (
    SELECT id
    FROM archiving_data_fields
    WHERE value_type = 'amount'
)
AND value_pattern LIKE '%(%'
AND (
    value_pattern LIKE '%ÖREN%'
    OR value_pattern LIKE '%OREN%'
)
AND amount_whole_group IS NULL
AND amount_fraction_group IS NULL;

UPDATE archiving_data_field_rule_sets
SET
    amount_whole_group = 1,
    amount_fraction_group = 1
WHERE data_field_id IN (
    SELECT id
    FROM archiving_data_fields
    WHERE value_type = 'amount'
)
AND value_pattern LIKE '%(%'
AND amount_whole_group IS NULL
AND amount_fraction_group IS NULL;

UPDATE archiving_data_field_rule_sets
SET capture_group = 1
WHERE data_field_id IN (
    SELECT id
    FROM archiving_data_fields
    WHERE value_type <> 'amount'
)
AND value_pattern LIKE '%(%'
AND value_pattern NOT LIKE '%(?:%'
AND value_pattern NOT LIKE '%(?=%'
AND value_pattern NOT LIKE '%(?!%'
AND capture_group IS NULL;
