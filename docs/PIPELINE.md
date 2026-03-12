DOCFLOW PROCESSING PIPELINE

Purpose

This document describes the step-by-step lifecycle of a document in Docflow,
from initial scan to final archive placement.


------------------------------------------------------------
OVERVIEW
------------------------------------------------------------

The document processing pipeline follows these steps:

1. file arrives in inbox
2. job is created
3. OCR processing
4. document property extraction
5. client identification
6. sender identification
7. category scoring
8. filename suggestion
9. user review
10. commit to archive


------------------------------------------------------------
STEP 1: INCOMING FILE
------------------------------------------------------------

A scanned file appears in the inbox directory.

Inbox characteristics:

- configurable location
- contains only untouched files
- files are usually PDFs produced by the scanner

Docflow continuously monitors the inbox directory.


------------------------------------------------------------
STEP 2: JOB CREATION
------------------------------------------------------------

When a new file appears in inbox:

1. Docflow generates a unique job ID
2. a job directory is created
3. the file is moved into the job directory

Example:

jobs/2026/03/20260308_221533_f4k92m/

Files created:

source.pdf
job.json

The inbox should now be empty of this file.


------------------------------------------------------------
STEP 3: OCR PROCESSING
------------------------------------------------------------

The system runs OCR on source.pdf.

Output:

ocr.txt

This contains the raw OCR text extracted from the document.


STEP 4: DOCUMENT PROPERTY EXTRACTION
------------------------------------------------------------

Structured data is extracted from the OCR text.

Examples:

- person numbers
- organization numbers
- bankgiro numbers
- plusgiro numbers
- dates
- amounts

Additional signals may be derived:

looksLikeInvoice
hasOCRNumber
hasDueDate
hasPaymentAmount

All extracted values are stored in extracted.json.


STEP 5: CLIENT IDENTIFICATION
------------------------------------------------------------

Detected personal identity numbers are compared against
the client database.

If a match is found:

client = matched client

Secondary identification may use name matching.


STEP 6: SENDER IDENTIFICATION
------------------------------------------------------------

Sender detection uses structured identifiers.

Priority:

1. organization number
2. bankgiro
3. plusgiro
4. text rules

The sender database maps these identifiers to known senders.


STEP 7: CATEGORY SCORING
------------------------------------------------------------

All categories are evaluated using the rule system.

Rules may match based on:

- OCR text
- document properties
- sender

Each category accumulates a score.

The highest scoring category wins.


STEP 8: FILENAME GENERATION
------------------------------------------------------------

A filename suggestion is generated using structured data.

Recommended format:

YYYY-MM-DD_sender_description.pdf

Examples:

2026-03-04_karlstads-kommun_arvodesbeslut.pdf
2026-03-31_ellevio_faktura.pdf


STEP 9: USER REVIEW
------------------------------------------------------------

The document appears in the Docflow interface.

The UI shows:

- PDF viewer
- detected client
- detected sender
- detected category
- suggested filename

The user may override any value.


STEP 10: COMMIT
------------------------------------------------------------

When the user commits the job:

1. the review PDF is copied to the client archive
2. the destination folder is determined by the category
3. the filename is applied

Example destination:

Huvudmän/Rickard Henriksen/Dokument/03 Fakturor/2026-03-31_ellevio_faktura.pdf

The job state becomes:

committed


STEP 11: UNCOMMIT
------------------------------------------------------------

If the user chooses to undo the operation:

1. the archived file is removed
2. job state returns to ready

This allows correction without reprocessing the document.


------------------------------------------------------------
PIPELINE DESIGN GOALS
------------------------------------------------------------

The pipeline should be:

- predictable
- restart-safe
- transparent
- resumable

Each step should produce explicit artifacts so the system
can recover from crashes without losing progress.
