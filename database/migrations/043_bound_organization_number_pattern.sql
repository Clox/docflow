UPDATE archiving_data_field_rule_sets
SET value_pattern = '(?<![\d-])\d{2}[2-9]\d{3}[- ]?\d{4}(?!\d)',
    updated_at = CURRENT_TIMESTAMP
WHERE value_pattern = '\d{2}[2-9]\d{3}[- ]?\d{4}'
  AND data_field_id IN (
      SELECT id
      FROM archiving_data_fields
      WHERE field_key = 'organisationsnummer'
  );
