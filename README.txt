Docflow (job-based skeleton)

This local PHP tool reviews PDFs through a job pipeline.

How it works:
- Inbox: incoming untouched PDFs (configured in data/config.json).
- Runtime JSON and other local state in `data/` are installation-local and are not meant to be versioned. See `docs/RUNTIME_DATA.md`.
- Jobs: each claimed PDF gets its own jobs/<jobId>/ folder.
- job.json is the source of truth for job state.

Job flow:
1. Client calls /api/get-state.php.
2. Server scans inbox for stable PDFs (older than 2 seconds).
3. Each stable PDF is claimed into jobs/<jobId>/source.pdf.
4. job.json is created with status "processing".
5. review.pdf is created (copy of source.pdf).
6. ocr.txt is created:
   - uses pdftotext if available
   - otherwise fallback to same-named .txt next to inbox PDF
   - otherwise empty text
7. extracted.json is written with matchedClientDirName from personal identity number matching.
8. job.json is updated last to status "ready" (or "failed" on error).

Processing model:
- get-state only claims stable inbox files and marks jobs as "processing".
- Actual processing runs in a separate background worker process.
- This keeps the UI responsive while processing continues.

SQLite sender lookup:
- SQLite file: data/docflow.sqlite
- Used for sender lookup data (senders + bankgiro/plusgiro metadata).
- Archiving rules now live in data/archiving-rules.json.
- Requires PHP extension: pdo_sqlite
- Run DB migrations:
  ./scripts/migrate.php

UI behavior:
- Sidebar lists only ready jobs.
- Header shows "PDF-filer" plus a spinner and "Bearbetar N fil(er)..." while processing jobs exist.
- State auto-refreshes every 3 seconds so new ready jobs appear automatically.
- Selecting a ready job loads /api/get-job-pdf.php?id=<jobId> in the iframe.
- Client select is populated from data/clients.json and auto-selects matched client for selected job.
- "Inställningar" modal includes:
  - expandable "Huvudmän" editor for data/clients.json
  - "Reset all jobs" button that restores each job's source.pdf to inbox and removes job folders

Configuration:
- data/config.json
  - inboxDirectory: absolute path to incoming PDFs
  - jobsDirectory: absolute path to jobs root
- data/clients.json
  - name: display label
  - dirName: matched client value
  - personalIdentityNumber: used for OCR text matching (hyphen/no-hyphen supported)

Testing note:
- Processing currently has an intentional delay of 10 seconds per file in the worker to make spinner/processing-state behavior easy to verify.

Run:
chmod +x start.sh
./start.sh

App URL:
http://127.0.0.1:4321
