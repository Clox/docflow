UPDATE document_data_values
SET archiving_data_field_id = (
    SELECT target.id
    FROM archiving_data_fields AS old
    INNER JOIN archiving_data_fields AS target
        ON target.rules_scope = old.rules_scope
        AND target.field_type = old.field_type
        AND target.field_key = 'primary_date'
    WHERE old.id = document_data_values.archiving_data_field_id
        AND old.field_key = 'document_date'
    LIMIT 1
)
WHERE archiving_data_field_id IN (
    SELECT old.id
    FROM archiving_data_fields AS old
    INNER JOIN archiving_data_fields AS target
        ON target.rules_scope = old.rules_scope
        AND target.field_type = old.field_type
        AND target.field_key = 'primary_date'
    WHERE old.field_key = 'document_date'
);

UPDATE archiving_data_field_rule_sets
SET data_field_id = (
    SELECT target.id
    FROM archiving_data_fields AS old
    INNER JOIN archiving_data_fields AS target
        ON target.rules_scope = old.rules_scope
        AND target.field_type = old.field_type
        AND target.field_key = 'primary_date'
    WHERE old.id = archiving_data_field_rule_sets.data_field_id
        AND old.field_key = 'document_date'
    LIMIT 1
)
WHERE data_field_id IN (
    SELECT old.id
    FROM archiving_data_fields AS old
    INNER JOIN archiving_data_fields AS target
        ON target.rules_scope = old.rules_scope
        AND target.field_type = old.field_type
        AND target.field_key = 'primary_date'
    WHERE old.field_key = 'document_date'
);

DELETE FROM archiving_data_fields
WHERE field_key = 'document_date'
    AND EXISTS (
        SELECT 1
        FROM archiving_data_fields AS target
        WHERE target.rules_scope = archiving_data_fields.rules_scope
            AND target.field_type = archiving_data_fields.field_type
            AND target.field_key = 'primary_date'
    );

UPDATE archiving_data_fields
SET field_key = 'primary_date',
    name = 'Huvuddatum',
    updated_at = datetime('now')
WHERE field_key = 'document_date';

UPDATE jobs
SET analysis_fields_json = replace(replace(analysis_fields_json, 'document_date', 'primary_date'), 'Dokumentdatum', 'Huvuddatum')
WHERE analysis_fields_json LIKE '%document_date%' OR analysis_fields_json LIKE '%Dokumentdatum%';

UPDATE jobs
SET analysis_system_fields_json = replace(replace(analysis_system_fields_json, 'document_date', 'primary_date'), 'Dokumentdatum', 'Huvuddatum')
WHERE analysis_system_fields_json LIKE '%document_date%' OR analysis_system_fields_json LIKE '%Dokumentdatum%';

UPDATE comparison_runs
SET result_json = replace(replace(result_json, 'document_date', 'primary_date'), 'Dokumentdatum', 'Huvuddatum')
WHERE result_json LIKE '%document_date%' OR result_json LIKE '%Dokumentdatum%';
