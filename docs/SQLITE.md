SQLite In Docflow

Why SQLite
- Docflow is a local desktop-style tool and should stay lightweight.
- SQLite gives simple relational storage without running a DB server.
- The database is a single local file: `data/docflow.sqlite`.
- Requires PHP with `pdo_sqlite` enabled.

What stays in JSON
- `data/archiving-rules.json` remains the source of truth for archive folders, categories, labels, data fields, and archiving-rule state.
- SQLite is not used for archive structure in this version.

What is stored in SQLite
1. `senders`
- One row per sender organization/authority/company.
- Supports exact lookup by normalized organization number.

2. `sender_payment_numbers`
- Stores sender bankgiro/plusgiro numbers and metadata.
- Supports exact lookup by `(type, number)`.
- `requires_ocr` stores payment-number behavior metadata (`0`/`1`).

3. `jobs`
- Stores relational job metadata that is being moved out of `job.json` incrementally.
- First fields:
  - `sender_id`
  - `auto_sender_id`
- These correspond to archived sender ids for:
  - approved archiving state
  - auto-detected state at approval

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
