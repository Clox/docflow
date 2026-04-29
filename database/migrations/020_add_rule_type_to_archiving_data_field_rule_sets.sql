ALTER TABLE archiving_data_field_rule_sets ADD COLUMN rule_type TEXT NOT NULL DEFAULT 'regex';

ALTER TABLE archiving_data_field_rule_sets ADD COLUMN date_position TEXT NOT NULL DEFAULT 'first';

ALTER TABLE archiving_data_field_rule_sets ADD COLUMN amount_position TEXT NOT NULL DEFAULT 'first';
