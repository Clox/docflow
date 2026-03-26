DOCFLOW ARCHITECTURE GUIDELINES

Purpose
This document defines the architectural principles and structural rules for the Docflow system.

The goal is to keep the system:

- deterministic
- debuggable
- easy to extend
- easy to understand
- consistent in archival behavior

Whenever possible, Docflow should rely on structured extraction and deterministic rules rather than AI.

AI may be added later as an optional layer, but it must not be the foundation of the system.


------------------------------------------------------------
CORE PRINCIPLE
------------------------------------------------------------

Docflow separates four conceptual layers:

1. Data extracted directly from the document
2. Conclusions derived from that data
3. User decisions and overrides
4. Final archival placement

The system must clearly distinguish between extracted facts and interpreted results.


------------------------------------------------------------
JOBS
------------------------------------------------------------

Every processed document is treated as a job.

A job represents one scanned document and all derived data related to it.

A job contains:

- source PDF
- review PDF
- OCR text
- extracted structured data
- metadata and processing state

Example structure:

docflow/
  jobs/
    2026/
      03/
        20260308_221533_f4k92m/
          source.pdf
          review.pdf
          ocr.txt
          extracted.json
          job.json
          processing.lock
          ready.flag

Job folders must be immutable containers for processing artifacts.

They should remain even after the document has been committed.


------------------------------------------------------------
INBOX
------------------------------------------------------------

The inbox directory contains newly scanned files.

The inbox location is configurable and may exist anywhere in the filesystem.

Rules:

- inbox contains only untouched files
- when Docflow claims a file, it immediately moves it into a job folder
- inbox must never contain files already being processed

This guarantees that any file in inbox has not yet been handled by the system.


------------------------------------------------------------
JOB STATES
------------------------------------------------------------

Each job must have an explicit state.

Recommended states:

processing
ready
failed
committed

Definitions:

processing
The system is currently working on the document.

ready
The job is finished processing and ready for user review.

failed
Processing failed and requires inspection.

committed
The document has been placed into the archive.

Only ready jobs should appear in the main review list.

The UI may optionally allow viewing committed jobs via a filter.


------------------------------------------------------------
DATA LAYERS
------------------------------------------------------------

Extracted data should be stored in three layers.

raw
Facts extracted directly from the document.

analysis
System-generated interpretations and guesses.

user
Values selected or confirmed by the user.

Example structure:

raw:
  personNumbers: ["19920101-1337"]
  bankgiro: ["405-2213"]
  orgNumbers: ["212000-1850"]
  dates: ["2026-03-04"]
  amounts: [10157]

analysis:
  client: "Rickard Henriksen"
  sender: "karlstads-kommun"
  category: "Arvodesbeslut"
  filenameSuggestion: "2026-03-04_karlstads-kommun_arvodesbeslut.pdf"

user:
  client: "Rickard Henriksen"
  category: "Arvodesbeslut"
  filename: "2026-03-04_karlstads-kommun_arvodesbeslut.pdf"

The raw layer must never be modified once extracted.


------------------------------------------------------------
DOCUMENT PROPERTIES
------------------------------------------------------------

Document properties are values that can be extracted directly from the document without external databases.

Examples include:

- personal identity numbers
- organisation numbers
- bankgiro numbers
- plusgiro numbers
- dates
- amounts
- OCR numbers

The system may also detect higher level signals such as:

- hasDueDate
- hasPaymentAmount
- hasOCRNumber
- looksLikeInvoice

These signals are still considered document properties because they are derived from the document itself.


------------------------------------------------------------
CLIENT IDENTIFICATION
------------------------------------------------------------

Client identification is derived from document properties.

Primary method:

personal identity number match.

The system extracts detected personal identity numbers and compares them against the client list.

Secondary method:

name matching.

Custom per-client rules may be added later but are not required initially.

Client identification should avoid duplicated manual rules whenever possible.


------------------------------------------------------------
SENDER IDENTIFICATION
------------------------------------------------------------

Sender identification should rely primarily on structured identifiers.

Priority order:

1. organisation number
2. bankgiro
3. plusgiro
4. sender name rules

Sender lookup data is stored in SQLite (`data/docflow.sqlite`) using:
- `senders` (organization-level sender rows)
- `sender_payment_numbers` (bankgiro/plusgiro + metadata)

Archiving rules and archive structure state are stored in SQLite with active/draft versions in `archiving_rules_state`.

The system should always extract organisation numbers, bankgiro numbers, and plusgiro numbers before attempting text matching.

Sender is not a raw document property. It is an interpretation derived from raw properties.


------------------------------------------------------------
FOLDERS
------------------------------------------------------------

Folders represent archive destinations.

A folder defines where documents will ultimately be stored in the filesystem.

Folders contain:

- name
- filesystem path
- categories

Example:

Folders:

[
  {
    name: "Fakturor",
    path: "dokument/03 fakturor",
    categories: [...]
  }
]

Folders represent real archive locations.


------------------------------------------------------------
CATEGORIES
------------------------------------------------------------

Categories are classification rules used to determine where a document should be placed.

Each category belongs to exactly one folder.

Categories contain:

- name
- minScore
- rules

Categories do NOT store their own filesystem path.

The folder containing the category determines the final destination path.

Example:

Folders:

[
  {
    name: "Fakturor",
    path: "dokument/03 fakturor",
    categories: [
      {
        name: "Faktura",
        minScore: 3,
        rules: [
          { type: "text", text: "faktura", score: 3 }
        ]
      },
      {
        name: "Hyresavi",
        minScore: 4,
        rules: [
          { type: "text", text: "hyra", score: 3 },
          { type: "sender", value: "forshaga-bostader", score: 5 }
        ]
      }
    ]
  }
]

A category always belongs to exactly one folder.

Categories should not be reused across multiple folders.


------------------------------------------------------------
CATEGORY SCORING
------------------------------------------------------------

Each category accumulates a score based on matching rules.

Possible rule sources include:

- OCR text
- document properties
- sender identification

Example rule types:

text
Match OCR text.

sender
Match a recognized sender.

documentProperty
Match extracted document signals.

Example:

rule:
type: text
text: "förfallodatum"
score: 2

rule:
type: documentProperty
property: "looksLikeInvoice"
score: 5


------------------------------------------------------------
CATEGORY SELECTION
------------------------------------------------------------

All categories across all folders compete globally.

The system calculates scores for every category.

The category with the highest score wins.

The winning category determines the destination folder.

Example flow:

document processed
→ scores calculated
→ highest scoring category selected
→ parent folder of that category determines archive path


------------------------------------------------------------
FILENAME GENERATION
------------------------------------------------------------

Filename generation should be deterministic.

AI is not required.

Suggested format:

YYYY-MM-DD_sender_description.pdf

Examples:

2026-03-04_karlstads-kommun_arvodesbeslut.pdf
2026-03-01_handelsbanken_kontoutdrag.pdf
2026-03-31_ellevio_faktura.pdf

Filenames should be:

- consistent
- searchable
- understandable


------------------------------------------------------------
COMMIT AND UNCOMMIT
------------------------------------------------------------

Committing a job means copying the document to the final archive destination.

Commit operation:

- file copied to client archive
- job state becomes committed

Uncommit operation:

- archive copy removed
- job state becomes ready again

Job folders themselves should never be deleted automatically.


------------------------------------------------------------
ARCHIVE STRUCTURE
------------------------------------------------------------

Suggested structure per client:

Huvudmän
  Client Name
    Dokument
      01 Bank
      02 Inkomster
      03 Fakturor
      04 Ställföreträdarskap
      05 Myndigheter
      06 Intyg & Beslut
      99 Övrigt
    Årsredovisningar
      2026
      2025
    Ut
    registerutdrag_ställföreträdarskap.pdf

Files should normally never be placed directly in the Dokument root.


------------------------------------------------------------
RULE DEVELOPMENT
------------------------------------------------------------

Rules should evolve gradually.

Strategy:

1. start with a basic rule set
2. use the system in real workflows
3. refine rules when misclassifications occur

Do not attempt to create a perfect rule system immediately.


------------------------------------------------------------
GENERAL DESIGN GOALS
------------------------------------------------------------

Docflow should aim to be:

- deterministic
- transparent
- easy to correct
- easy to extend
- predictable in behavior

When uncertain:

prefer clear architecture over clever automation.

prefer extracted facts over guesses.

prefer user-correctable behavior over hidden logic.
