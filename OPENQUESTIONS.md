<!-- Purpose: log of open questions about requirements. A question is never deleted; when answered it moves to "Closed" with its resolution. -->

# Open Questions

Format per entry: **ID**, date opened, question, who can answer, status (Open / Closed), date closed, and the answer or the DECISIONS.md ID that closed it.

Add a question here instead of guessing when a requirement is unclear. If the answer cannot wait, make the most conservative choice that keeps the PROJECTBRIEF.md §2 scope intact, record it in DECISIONS.md, and close the question with that decision ID.

## Open

_None._

## Closed

### OQ1 — Bundled DM Sans or system font stack?

- **Date opened:** 2026-09-20
- **Question:** Should the card page load DM Sans from a bundled font file, or use a system font stack only? (Affects plugin size and wordpress.org review.)
- **Who can answer:** Project owner; the developer may decide within PROJECTBRIEF.md §4.
- **Status:** Closed
- **Date closed:** 2026-09-20
- **Resolution:** System font stack only. See D14.

### OQ2 — Rewrite base `card` or `contact`?

- **Date opened:** 2026-09-20
- **Question:** Should the rewrite base default to `card` or `contact`?
- **Who can answer:** Project owner; the developer may decide.
- **Status:** Closed
- **Date closed:** 2026-09-20
- **Resolution:** `card`. See D15.

### OQ3 — vCard endpoint 404 or 410 for unpublished cards?

- **Date opened:** 2026-09-20
- **Question:** When a card is unpublished or trashed, should the vCard endpoint return 404 or 410?
- **Who can answer:** Developer.
- **Status:** Closed
- **Date closed:** 2026-09-20
- **Resolution:** 404. See D16.

### OQ4 — QR regenerated automatically or on demand?

- **Date opened:** 2026-09-20
- **Question:** Should the QR code be regenerated on every permalink change automatically, or on demand from the edit screen?
- **Who can answer:** Developer.
- **Status:** Closed
- **Date closed:** 2026-09-20
- **Resolution:** Automatically, by comparing the stored URL with the current permalink whenever the QR is requested. See D17.

### OQ5 — Is the Wallet pass PNG (v2) worth building?

- **Date opened:** 2026-09-20
- **Question:** Is the Wallet pass PNG (v2) worth building, given that iPhone users will use a saved image instead?
- **Who can answer:** Project owner.
- **Status:** Closed
- **Date closed:** 2026-09-20
- **Resolution:** Deferred to v2; not built in v1. See D18. Reopen if v2 planning wants it.
