SQLite In Docflow

Why SQLite
- Docflow is a local desktop-style tool and should stay lightweight.
- SQLite gives simple relational storage without running a DB server.
- The database is a single local file: `data/docflow.sqlite`.
- Requires PHP with `pdo_sqlite` enabled.

What is stored in SQLite
1. `senders`
- One row per canonical sender identity.
- Stores sender display data and `matching_updated_at`.
- Exact matching identifiers no longer live directly on this table.

2. `sender_organization_numbers`
- Stores observed organization numbers and optional observed names.
- Supports exact lookup by unique `organization_number`.
- `sender_id` is nullable so an organization number can exist before it is linked to a sender.

3. `sender_payment_numbers`
- Stores sender bankgiro/plusgiro numbers and metadata.
- Supports exact lookup by unique `(type, number)`.
- `requires_ocr` stores payment-number behavior metadata (`0`/`1`).

4. `jobs`
- Stores relational job metadata that is being moved out of `job.json` incrementally.
- First fields:
  - `sender_id`
  - `auto_sender_id`
- Analysis snapshot fields:
  - `analysis_client_id`
  - `analysis_sender_id`
  - `analysis_category_id`
  - `analysis_labels_json`
  - `analysis_fields_json`
  - `analysis_system_fields_json`
  - `analyzed_at`
- These correspond to archived sender ids for:
  - approved archiving state
  - auto-detected state at approval

5. `clients`
- Stores principals/clients used for folder selection and personal identity number matching.
- Keeps first name, last name, folder name, PIN, and UI sort order.

6. `archiving_rules_state`
- Stores the full active/draft archiving-rules state in SQLite.
- Includes archive folders, categories, labels, data fields, predefined fields, system fields, and active rules version.

Number semantics
- `number`: canonical normalized value used for lookup and uniqueness.
- `original_number`: optional raw input value for traceability/debugging.

Example
- Input: `152-1806`
- `number`: `1521806`
- `original_number`: `152-1806`

Migrations
- Migration SQL files live in `database/migrations`.
- Run migrations with:

```bash
./scripts/migrate.php
```

This creates/updates `data/docflow.sqlite` and records applied migrations in the `migrations` table.
