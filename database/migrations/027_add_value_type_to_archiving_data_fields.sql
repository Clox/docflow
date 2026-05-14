ALTER TABLE archiving_data_fields
    ADD COLUMN value_type TEXT NOT NULL DEFAULT 'text';

UPDATE archiving_data_fields
SET value_type = COALESCE((
    SELECT CASE archiving_data_field_rule_sets.rule_type
        WHEN 'date' THEN 'date'
        WHEN 'amount' THEN 'amount'
        ELSE 'text'
    END
    FROM archiving_data_field_rule_sets
    WHERE archiving_data_field_rule_sets.data_field_id = archiving_data_fields.id
    ORDER BY archiving_data_field_rule_sets.sort_order ASC, archiving_data_field_rule_sets.id ASC
    LIMIT 1
), 'text');
