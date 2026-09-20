<!-- Purpose: instructions for AI coding agents (Claude Code and others) working in this repository. Written for an agent with no chat history. -->

# AGENTS.md

You are working on **Digital Business Card Posts**, a WordPress plugin. This file tells you how to work in this repository. Read it in full before doing anything.

## 1. Read these first, every time

1. `PROJECTBRIEF.md` — the source of truth for scope (§2), the decisions already made (§3), non-goals (§4), technical constraints (§5), the testing checklist (§6) and the reference prototype (§9).
2. `DECISIONS.md` — every design decision with its reasoning.
3. `OPENQUESTIONS.md` — what is unresolved and what was resolved.
4. `CHANGELOG.md` `[Unreleased]` — what has changed since the last release.

Do not start a task until you have read all four.

## 2. Rules about decisions and questions

- **Do not reverse a logged decision** (`DECISIONS.md`) without adding a **new** entry that supersedes it. The new entry must name the old ID, explain what changed, and list the alternatives considered. Never edit or delete an old entry.
- **When a requirement is unclear, do not guess.** Add an entry to `OPENQUESTIONS.md` (next `OQ` number, date, question, who can answer, status Open). If the work cannot wait for an answer, make the most conservative choice that keeps the §2 scope intact, log it in `DECISIONS.md` with the alternatives you considered, and close the question with that decision ID. A closed question is never deleted; it moves to the "Closed" section.
- Anything that changes the v1 scope in §2, adds or removes a feature, reverses a §3 decision, needs a credential or purchase, or raises a licensing question about vendored code must be put to the project owner. Everything else is within your authority.

## 3. Changelog and versioning

- **Update `CHANGELOG.md` in the same commit as any behavior change.** Put the line under `[Unreleased]` in the right section (Added / Changed / Fixed / Removed). Documentation-only and tooling-only commits do not need a changelog line.
- **Follow `VERSIONING.md` for every release.** Semantic Versioning; the version lives in exactly three places (plugin header, `DBCP_VERSION`, `readme.txt` Stable tag) and must match; every release produces `dist/digital-business-card-posts-{version}.zip` via `bin/build.sh`, plus a git tag `v{version}` and a GitHub Release.
- **Never modify, rename or delete an existing zip in `dist/`.** `bin/build.sh` refuses to overwrite; do not work around it.

## 4. Repository layout

```
digital-business-card-posts.php   plugin bootstrap: header, constants, requires, activation hooks
uninstall.php                     runs when the plugin is deleted
includes/class-dbcp-plugin.php    DBCP_Plugin: wires components, activation/deactivation, admin notices
includes/class-dbcp-post-type.php DBCP_Post_Type: CPT, capabilities, card_holder role, admin list scope
includes/class-dbcp-meta.php      DBCP_Meta: field definitions, register_post_meta, meta box, save
includes/class-dbcp-template.php  DBCP_Template: template_include, card data, shortcode, stylesheet
includes/class-dbcp-vcard-builder.php  DBCP_VCard_Builder: pure vCard 3.0 builder (no WordPress calls)
includes/class-dbcp-vcard.php     DBCP_VCard: rewrite rule, endpoint, photo resize, headers
includes/class-dbcp-qr.php        DBCP_QR: QR generation, cache, edit-screen box
includes/class-dbcp-settings.php  DBCP_Settings: options page, defaults, rewrite flush
templates/single-business-card.php full-page card document (overridable via dbcp_template_path)
templates/card.php                card markup partial used by the page and the shortcode
assets/card.css, admin.js, admin.css
vendor/phpqrcode/                 vendored LGPL-3.0 library, do not modify
languages/                        .pot file
tests/                            PHPUnit tests (no WordPress needed)
bin/build.sh                      release zip builder
bin/test-docker.sh                runs the tests in a PHP+GD container
dist/                             released zips, committed, never modified
reference/                        the prototype (see §7 below), not shipped
screenshots/                      README screenshots from the Docker test site, not shipped
RELEASE-CHECKLIST.md              §6 checklist results per release and pending manual tests
```

Text domain: `digital-business-card-posts`. Prefixes: `dbcp_` for functions, options, meta keys, hooks and CSS classes; `DBCP_` for classes and constants.

## 5. Coding standards and security (PROJECTBRIEF.md §5)

- PHP 7.4+ syntax only. `declare(strict_types=1);` at the top of every plugin PHP file except the templates.
- WordPress 6.0+ APIs only.
- **WordPress Coding Standards**: `composer lint` (PHPCS with the `WordPress` ruleset in `phpcs.xml`) must report zero errors before any commit. Use `composer lint:fix` (phpcbf) for formatting, then fix the rest by hand. Do not add `phpcs:ignore` comments without a reason in the comment.
- **Nonces on every form.** Meta box saves and settings saves verify a nonce.
- **Capability check on every save and every admin action.** Use `current_user_can( 'edit_post', $post_id )` for card saves, `manage_options` for settings.
- **Sanitize on input**: every meta field has a `sanitize_callback` in `DBCP_Meta::fields()`. Use it; do not sanitize ad hoc.
- **Escape at output**: `esc_html`, `esc_attr`, `esc_url` in templates and admin markup. Late escaping, at the `echo`.
- **Two escapers, never mixed** (D11): the vCard builder uses vCard escaping only; templates use WordPress escaping only.
- All strings translatable with the `digital-business-card-posts` text domain.
- No external requests, no CDN assets, no fonts from Google (§4).
- GD is required. Do not add a Composer runtime dependency. Do not modify `vendor/phpqrcode/`.

## 6. Testing

- `composer test` runs PHPUnit against the vCard builder. Add a test for every escaping or folding change.
- If the local PHP lacks GD, `bin/test-docker.sh` runs the same tests plus the QR smoke test in a container.
- Before any release, run the checklist in `PROJECTBRIEF.md` §6. Items that need a phone or a live site go into `RELEASE-CHECKLIST.md` under "Manual tests pending" with the version they apply to.

## 7. The reference prototype

`reference/` holds the hand-built prototype that defines the target output. It is the **visual and behavioral baseline**:

- `reference/index.html` — the card page. Palette, typography, spacing, button and contact rows. When in doubt about how the card should look, match this file.
- `reference/john-janney.vcf` — a working vCard 3.0 with two phone types, work address, URL and an embedded 400 × 400 JPEG, folded correctly. When in doubt about vCard output, match this file (except that commas are escaped per D11).
- `reference/logo.png` — sample logo.
- `reference/john-janney-wallet-pass.png`, `reference/john-janney-qr.png` — v2 references.

Sample data for testing is in `PROJECTBRIEF.md` §9. `reference/` is not shipped in the zip.

## 8. Commits

- **Conventional Commits**: `feat:`, `fix:`, `docs:`, `chore:`, `test:`, `refactor:`, `build:`, `style:`. Add a scope when useful: `feat(vcard): fold lines at 75 octets`.
- One logical change per commit. The changelog line goes in the same commit as the behavior change.
- Release commits: `chore(release): X.Y.Z`, then `chore(release): add dist zip for X.Y.Z`, then tag `vX.Y.Z`.
- Do not commit `vendor/` other than `vendor/phpqrcode/`, `node_modules/`, or `*:Zone.Identifier` files.

## 9. Working style

- Keep the four documents in §1 current with every change. A change that is not in `CHANGELOG.md` and (if it was a choice) in `DECISIONS.md` is not done.
- Prefer small, standard WordPress mechanisms over custom code (D3, D13, D27).
- When you finish a task, state what you changed, which tests you ran, and anything you could not verify.
