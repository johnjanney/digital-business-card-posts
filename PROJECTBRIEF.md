# Project Brief — Digital Business Card Posts (WordPress plugin)

**Prepared:** 2026-09-20
**Owner:** John Janney
**Status:** Approved for development. No code exists yet.

---

## 1. Purpose

Build a WordPress plugin that lets a site publish digital business card pages, one per person, each with a downloadable vCard file and a QR code that points to the page.

The plugin replaces a hand-built prototype (static HTML page + `.vcf` file) that was made for John Janney at Baitulmaal, Inc. The prototype is the reference for design and behavior. See §9.

## 2. Scope

### v1 (build this)

1. **Custom post type `business_card`.** One post per card. Public, with its own single view at `/card/{slug}/`. Rewrite base is a setting (default `card`).
2. **Card fields** (post meta, registered with `register_post_meta()`, `show_in_rest => true`):
   - `first_name`, `last_name` (post title = display name, editable)
   - `job_title`
   - `company`
   - `tagline` (optional)
   - `phone_work`, `phone_mobile` (optional each)
   - `email`
   - `website`
   - `address_street`, `address_suite`, `address_city`, `address_state`, `address_postal`, `address_country`
   - `logo_id` (attachment ID, optional)
   - `accent_color` (hex, default `#f7c600`)
   - `noindex` (boolean, default `true`)
   - Featured image = contact photo.
3. **Card page template**, rendered by the plugin (`template_include`), not by the theme. Design per §9. Mobile first. One primary button: **Save contact**. Tap targets: work phone, mobile phone, email, website, directions (Google Maps link built from the address). Filter `dbcp_template_path` allows a theme or child plugin to override the template file.
4. **vCard endpoint.** `/card/{slug}/vcard/` (rewrite rule + `template_redirect`). Builds vCard **3.0** from meta at request time. No file on disk. Embeds the featured image as base64 JPEG, resized to 400 × 400, quality ~82. Headers: `Content-Type: text/vcard; charset=utf-8` and `Content-Disposition: attachment; filename="{slug}.vcf"`. Lines end with CRLF. Long lines fold at 75 octets with a single leading space on continuation lines (RFC 2426).
5. **QR code.** Generated server-side from the card's permalink at error-correction level H, cached as PNG in `wp-content/uploads/digital-business-card-posts/{post_id}.png`. Regenerated when the permalink changes. Shown in the edit screen with a download link. Library: `phpqrcode` (LGPL-3.0, single file), vendored under `vendor/phpqrcode/` with its LICENSE file.
6. **Shortcode** `[digital_business_card id="123"]` embeds the card in any post or page. Optional feature; keep it.
7. **Ownership.** Register the CPT with `capability_type => 'business_card'` and `map_meta_cap => true`. On activation:
   - Grant the full capability set to `administrator` and `editor`.
   - Create role `card_holder` with only the "own" set (`edit_business_cards`, `publish_business_cards`, `delete_business_cards`, `upload_files`, `read`).
   - On uninstall, remove the role and the capabilities. Do **not** delete card posts on uninstall unless a setting says so.
   
   Expected use: one webmaster (Administrator) creates all cards. The `card_holder` role exists so a card can later be handed to its owner by changing the post author. This must work with zero configuration.
8. **Settings page** (Settings → Digital Business Cards): rewrite base, default accent color, default `noindex`, "delete data on uninstall" toggle.

### v2 (do not build in v1; log in OPENQUESTIONS.md if needed)

- Google Wallet pass PNG (card image with logo, name, title, QR) generated with GD or Imagick.
- Gutenberg block wrapping the shortcode.
- CSV import/export of cards.
- Dark-mode logo variant field.
- Apple Wallet `.pkpass` — requires an Apple Pass Type ID certificate; likely out of scope forever, but record the decision.

## 3. Design decisions already made

Record these in DECISIONS.md as the first entries, dated 2026-09-20.

| # | Decision | Reason |
|---|----------|--------|
| D1 | Cards are a custom post type, not a shortcode-only feature. | Each card needs its own URL, author, and capabilities. WordPress gives all three to a CPT for free. |
| D2 | Fields are post meta, registered with `register_post_meta()` and exposed to REST. | Makes the block editor, REST API, and import/export work without custom tables. |
| D3 | Ownership via `map_meta_cap` and a `card_holder` role. No custom permission code. | Standard WordPress capability model; one webmaster creating all cards is the default case and needs no setup. |
| D4 | vCard version 3.0, not 4.0. | iOS and Android Contacts accept 3.0 without problems. Some clients still reject 4.0 fields. |
| D5 | vCard is generated on request, not stored as a file. | Always in sync with the fields. No orphaned files. |
| D6 | Photo is embedded (base64), not linked by URL. | iOS Contacts ignores `PHOTO;VALUE=uri`. |
| D7 | Photo is resized to 400 × 400 JPEG at output. | Large vCards are rejected by iOS. ~16–30 KB is safe. |
| D8 | Save-contact button links to the vCard endpoint, never a `data:` URI. | iOS Safari blocks top-level navigation to `data:` URLs. |
| D9 | QR code uses error-correction level H. | Reads reliably from a phone screen at small sizes. |
| D10 | `noindex` is on by default, per card. | Card pages are public but should not be searchable; most people do not want a mobile number in Google's index. |
| D11 | Two escaping functions: HTML (`esc_html`, `esc_attr`, `esc_url`) and vCard (`\\`, `\,`, `\;`, `\n`). | The two formats have different escaping rules. Never reuse one for the other. |
| D12 | Vendored QR library is `phpqrcode` (LGPL-3.0). | Single file, GD-based, GPL-compatible, no Composer dependency. |
| D13 | Card page template is rendered by the plugin, overridable by filter. | Consistent output on any theme; still customizable. |

## 4. Non-goals

- No SaaS, no external API calls, no CDN-hosted scripts or fonts in the plugin. The web font (DM Sans) from the prototype is replaced by a system font stack, or bundled locally, so the plugin works offline and passes wordpress.org review.
- No page builder integration.
- No NFC. Phone-to-phone NFC sharing does not exist on Android or iOS; an NFC tag with the card URL is the user's answer, outside the plugin.

## 5. Technical constraints

- PHP 7.4+ (use `declare(strict_types=1)` where practical), WordPress 6.0+.
- GD extension required for QR and photo resizing; check on activation and show an admin notice if missing.
- Follow WordPress Coding Standards (PHPCS with `WordPress` ruleset). Nonces on every form. Capability check on every save and every admin action. Escape at output.
- Text domain `digital-business-card-posts`; all strings translatable.
- Plugin slug and folder: `digital-business-card-posts`. Main file `digital-business-card-posts.php`. Function/class prefix `dbcp_` / `DBCP_`.
- Suggested structure:
  ```
  digital-business-card-posts/
    digital-business-card-posts.php
    uninstall.php
    includes/
      class-dbcp-plugin.php        (bootstrap, hooks)
      class-dbcp-post-type.php     (CPT, caps, role)
      class-dbcp-meta.php          (meta registration, meta box, save)
      class-dbcp-template.php      (template_include, shortcode)
      class-dbcp-vcard.php         (endpoint, builder, escaping, folding)
      class-dbcp-qr.php            (generation, cache)
      class-dbcp-settings.php
    templates/
      single-business-card.php
    assets/
      card.css
      admin.js  (media picker for logo)
      admin.css
    vendor/phpqrcode/
      phpqrcode.php
      LICENSE
    languages/
  ```

## 6. Testing checklist (before any release)

1. Create a card as Administrator. Open `/card/{slug}/` on iPhone Safari and Android Chrome. Tap **Save contact**. Confirm the contact preview opens and the photo shows.
2. Confirm both phone numbers show as "work" and "mobile" in the saved contact.
3. Scan the QR code with the iPhone camera and with Google Lens. Confirm it opens the card URL.
4. Log in as a `card_holder`. Confirm the user sees only their own card and no other post types.
5. Change the rewrite base in settings. Confirm old URLs 404 and new URLs work (flush rewrite rules on save).
6. Deactivate and reactivate. Confirm the role and capabilities survive.
7. Run PHPCS with the WordPress ruleset; zero errors.
8. Validate a generated `.vcf` with a vCard validator, and open it in macOS Contacts, Outlook, and Gmail Contacts.

## 7. Required project documents

Create these files in the repository root as part of development. Create them **before** writing plugin code, then keep them current with every change. Each file has a short description of its purpose at the top.

1. **README.md** — GitHub readme. What the plugin does, screenshots (add once available), requirements, quick start, links to INSTALLATION.md and INSTRUCTIONS.md, license (GPL-2.0-or-later), and a credit line for `phpqrcode` (LGPL-3.0).
2. **CHANGELOG.md** — Keep a Changelog format (https://keepachangelog.com). Sections: Added, Changed, Fixed, Removed. An `[Unreleased]` section at the top. Every commit that changes behavior gets a line here.
3. **VERSIONING.md** — Defines the versioning approach. Requirements:
   - Semantic Versioning 2.0.0 (MAJOR.MINOR.PATCH).
   - The version appears in exactly three places and must match: the plugin header in `digital-business-card-posts.php`, the `DBCP_VERSION` constant, and `readme.txt` (`Stable tag`). Document a pre-release check that confirms they match.
   - **Every release produces a packaged zip** at `dist/digital-business-card-posts-{version}.zip`. **Previous versions are kept.** Never delete or overwrite a released zip. `dist/` is committed to the repository. Each zip must be installable on a clean WordPress site via Plugins → Add New → Upload.
   - A build script (`bin/build.sh` or a Composer/npm script) creates the zip from a clean export (no `.git`, no `node_modules`, no tests, no `dist/`).
   - Each release is also a git tag `v{version}` and a GitHub Release with the zip attached.
4. **DECISIONS.md** — Log of major decisions. Format per entry: ID, date, decision, context, alternatives considered, consequences. Seed with D1–D13 from §3.
5. **OPENQUESTIONS.md** — Log of open questions. Format per entry: ID, date opened, question, who can answer, status (Open / Closed), date closed, and the answer or the DECISIONS.md ID that closed it. A closed question is never deleted; it moves to a "Closed" section with its resolution.
6. **INSTALLATION.md** — Installing the plugin. Requirements (PHP, WordPress, GD), upload-zip method, manual FTP method, activation, first-run checks (permalinks flushed, role created), and how to update.
7. **INSTRUCTIONS.md** — Using the plugin. Creating a card, every field explained, adding the photo and logo, the card URL, downloading the QR code, the `noindex` setting, the shortcode, the Card Holder role, handing a card to its owner, and troubleshooting (vCard opens as text, QR does not scan, 404 on card page).
8. **AGENTS.md** — Instructions for AI coding agents working in this repository (Claude Code and others). Must include:
   - Read PROJECTBRIEF.md, DECISIONS.md, and OPENQUESTIONS.md before starting any task.
   - Do not reverse a logged decision without adding a new DECISIONS.md entry that supersedes it.
   - Add an OPENQUESTIONS.md entry instead of guessing when a requirement is unclear.
   - Update CHANGELOG.md in the same commit as any behavior change.
   - Follow VERSIONING.md for every release; never modify an existing zip in `dist/`.
   - Coding standards (§5), security rules (nonces, capability checks, escaping), and the testing checklist (§6).
   - Commit message convention (Conventional Commits recommended).
   - Where the reference prototype lives (§9) and that it is the visual and behavioral baseline.

## 8. Development sequence

1. Initialize repository; add the eight documents from §7 with initial content; add `.gitignore`, `.editorconfig`, `phpcs.xml`, `LICENSE` (GPL-2.0-or-later).
2. Plugin bootstrap, CPT, capabilities, role, activation/uninstall.
3. Meta registration and meta box with media picker.
4. vCard endpoint and builder, with unit tests for escaping and folding.
5. Card template and CSS, matching the prototype.
6. QR generation and admin display.
7. Shortcode.
8. Settings page.
9. Run the §6 checklist. Tag v1.0.0. Build the zip into `dist/`.

## 9. Reference prototype

The following files were built by hand on 2026-09-19/20 and define the target output. Copy them into `reference/` in the repository (they are not shipped in the plugin).

- `reference/index.html` — the card page. Palette: white paper, near-black ink (`#161616`), muted grey (`#5f5f5f`), rules (`#e4e4e4`), accent yellow (`#f7c600`). Name set large and bold with a small accent-colored square after it. Title, company, tagline in a single block with no extra spacing. Logo at top left, 96 px tall. Save-contact button full width in the accent color. Contact rows separated by hairlines, each with an icon and a small label (Work, Mobile, Email, Website, Directions). Dark-mode variables are defined. Focus-visible outlines in the accent color.
- `reference/john-janney.vcf` — a working vCard 3.0 with two phone types, work address, URL, and an embedded 400 × 400 JPEG photo, folded correctly.
- `reference/logo.png` — Baitulmaal logo, trimmed, 800 px wide, transparent background.
- `reference/john-janney-wallet-pass.png` and `reference/john-janney-qr.png` — v2 reference for the Wallet pass image and the standalone QR.

Sample data for testing (the same person as the prototype):

| Field | Value |
|-------|-------|
| Name | John Janney |
| Title | Chief Growth Officer |
| Company | Baitulmaal, Inc. |
| Tagline | Turn Your Compassion Into Hope |
| Address | 2300 Valley View Lane, Suite 370, Irving, TX 75062, United States |
| Website | https://baitulmaal.org |
| Email | johnjanney@baitulmaal.org |
| Work phone | +1 214-810-1131 |
| Mobile phone | +1 469-619-7273 |

## 10. Open questions to seed OPENQUESTIONS.md

- OQ1: Should the card page load DM Sans from a bundled font file, or use a system font stack only? (Affects plugin size and wordpress.org review.)
- OQ2: Should the rewrite base default to `card` or `contact`?
- OQ3: When a card is unpublished or trashed, should the vCard endpoint return 404 or 410?
- OQ4: Should the QR code be regenerated on every permalink change automatically, or on demand from the edit screen?
- OQ5: Is the Wallet pass PNG (v2) worth building, given that iPhone users will use a saved image instead?
