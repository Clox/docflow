DOCFLOW RULE SYSTEM

Purpose

This document describes how Docflow classifies documents using rules and scoring.

The rule system determines which category a document belongs to and therefore
which archive folder it will be placed in.

The system must be:

- deterministic
- transparent
- easy to debug
- easy to extend


------------------------------------------------------------
CORE IDEA
------------------------------------------------------------

Documents are classified using a scoring system.

Each category contains rules.

Rules give points when they match a document.

The category with the highest score wins.

The winning category determines the destination folder.


------------------------------------------------------------
FOLDER AND CATEGORY STRUCTURE
------------------------------------------------------------

Folders represent archive destinations.

Categories represent classification logic.

Each category belongs to exactly one folder.

Example configuration:

Folders:

[
  {
    name: "Fakturor",
    path: "dokument/03 fakturor",
    categories: [
      {
        name: "Faktura",
        minScore: 3,
        rules: [...]
      },
      {
        name: "Hyresavi",
        minScore: 4,
        rules: [...]
      }
    ]
  }
]

Important rules:

- categories never define their own path
- the parent folder determines the archive destination
- a category belongs to exactly one folder


------------------------------------------------------------
CATEGORY SCORING
------------------------------------------------------------

Each category accumulates a score based on its rules.

Example:

Category: Faktura

Rules:
text "förfallodatum" score 2
text "belopp" score 1
documentProperty "looksLikeInvoice" score 5

If a document matches:

förfallodatum → +2
looksLikeInvoice → +5

Total score = 7

If score >= minScore then the category is considered valid.

All valid categories compete and the highest score wins.


------------------------------------------------------------
RULE TYPES
------------------------------------------------------------

Rules may use different signal sources.

Supported rule types:

TEXT RULE

Matches OCR text.

Example:

type: text
text: "förfallodatum"
score: 2

Match occurs if the OCR text contains the string.


DOCUMENT PROPERTY RULE

Matches extracted document signals.

Example:

type: documentProperty
property: "looksLikeInvoice"
score: 5


SENDER RULE

Matches the detected sender.

Example:

type: sender
value: "forshaga-bostader"
score: 5


CLIENT RULE (optional future rule)

Matches the detected client.

Example:

type: client
value: "rickard-henriksen"
score: 5


------------------------------------------------------------
OCR MATCHING BEHAVIOR
------------------------------------------------------------

OCR text often contains recognition errors.

To improve matching accuracy the system should support:

- case-insensitive matching
- character substitutions
- optional regex rules (future)

Example substitution rules:

ö may equal é
ä may equal a
å may equal a

This substitution layer is applied before rule matching.


------------------------------------------------------------
RULE EVALUATION PROCESS
------------------------------------------------------------

Classification occurs in this order:

1. Extract document properties
2. Detect sender
3. Detect client
4. Evaluate rules for all categories
5. Calculate category scores
6. Select highest scoring category
7. Determine archive folder from parent folder


------------------------------------------------------------
TIE BREAKING
------------------------------------------------------------

If multiple categories have identical scores:

Preferred tie-break order:

1. highest minScore
2. category priority (optional future)
3. first defined category


------------------------------------------------------------
RULE EVOLUTION
------------------------------------------------------------

Rules should evolve over time.

Recommended strategy:

1. start with a small rule set
2. observe real classification errors
3. adjust rules when needed

Do not attempt to create a perfect rule system initially.


------------------------------------------------------------
DESIGN GOALS
------------------------------------------------------------

The rule system should always be:

- explainable
- deterministic
- predictable
- easy to modify

The user should always be able to understand why a document
was classified into a specific category.