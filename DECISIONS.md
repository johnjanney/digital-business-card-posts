<!-- Purpose: log of major design decisions. Entries are never deleted. To reverse one, add a new entry that supersedes it and reference the old ID. -->

# Decisions

Format per entry: **ID**, date, decision, context, alternatives considered, consequences. Entries D1–D13 come from PROJECTBRIEF.md §3. Later entries were made during development; those that close an open question reference the OQ number.

---

## D1 — Cards are a custom post type

- **Date:** 2026-09-20
- **Decision:** Cards are a custom post type (`business_card`), not a shortcode-only feature.
- **Context:** Each card needs its own URL, an author, and capabilities.
- **Alternatives considered:** A shortcode with attributes holding all fields; a custom table with a front controller.
- **Consequences:** WordPress provides the URL, author and capability model for free. Cards appear in the admin as a normal post list.

## D2 — Fields are post meta registered with `register_post_meta()`

- **Date:** 2026-09-20
- **Decision:** Every card field is post meta, registered with `register_post_meta()` and `show_in_rest => true`.
- **Context:** The block editor, the REST API and future import/export all read post meta natively.
- **Alternatives considered:** A custom table; a single serialized meta value.
- **Consequences:** No custom tables. Each field has its own sanitize and auth callback. Meta keys are a compatibility surface and renaming one is a MAJOR change.

## D3 — Ownership via `map_meta_cap` and a `card_holder` role

- **Date:** 2026-09-20
- **Decision:** The post type uses `capability_type => 'business_card'` with `map_meta_cap => true`. Administrators and Editors get the full capability set on activation. A `card_holder` role gets the "own" set only. No custom permission code.
- **Context:** The default case is one webmaster creating all cards; it must work with zero configuration. Later a card can be handed to its owner by changing the post author.
- **Alternatives considered:** Custom `user_has_cap` filters; a per-card "owner" meta field checked by the plugin.
- **Consequences:** Standard WordPress behavior throughout. See D19 and D20 for two small additions that keep this model working in practice.

## D4 — vCard version 3.0

- **Date:** 2026-09-20
- **Decision:** The vCard endpoint emits vCard 3.0 (RFC 2426), not 4.0.
- **Context:** iOS and Android Contacts accept 3.0 without problems. Some clients still reject 4.0 properties.
- **Alternatives considered:** vCard 4.0; offering both.
- **Consequences:** Photo uses `PHOTO;ENCODING=b;TYPE=JPEG`, phones use `TEL;TYPE=WORK,VOICE` and `TEL;TYPE=CELL,VOICE`.

## D5 — vCard is generated on request

- **Date:** 2026-09-20
- **Decision:** The `.vcf` is built from post meta at request time. Nothing is written to disk.
- **Context:** The vCard must always match the fields.
- **Alternatives considered:** Writing a `.vcf` into uploads on save.
- **Consequences:** No orphaned files. Slightly more work per request (photo resize), acceptable for a card page.

## D6 — Photo is embedded as base64

- **Date:** 2026-09-20
- **Decision:** The photo is embedded inline (`ENCODING=b`), not linked by URL.
- **Context:** iOS Contacts ignores `PHOTO;VALUE=uri`.
- **Alternatives considered:** `PHOTO;VALUE=uri:https://...`.
- **Consequences:** The vCard is larger (~16–30 KB) and must be folded correctly.

## D7 — Photo resized to 400 × 400 JPEG at output

- **Date:** 2026-09-20
- **Decision:** The featured image is resized and cropped to 400 × 400 pixels, JPEG quality ~82, when the vCard is built.
- **Context:** Large vCards are rejected by iOS. Around 16–30 KB is safe.
- **Alternatives considered:** Embedding the original; a registered image size generated on upload.
- **Consequences:** Predictable file size regardless of what the editor uploads. See D23 for the implementation.

## D8 — Save-contact button links to the vCard endpoint

- **Date:** 2026-09-20
- **Decision:** The button is a plain link to `/card/{slug}/vcard/`, never a `data:` URI.
- **Context:** iOS Safari blocks top-level navigation to `data:` URLs.
- **Alternatives considered:** `data:text/vcard;base64,...`; a Blob URL built in JavaScript.
- **Consequences:** The page needs no JavaScript. The endpoint must send `Content-Type: text/vcard` and a `Content-Disposition: attachment` header.

## D9 — QR code error-correction level H

- **Date:** 2026-09-20
- **Decision:** QR codes are generated at error-correction level H.
- **Context:** Reads reliably from a phone screen at small sizes and tolerates a logo overlay later.
- **Alternatives considered:** Levels L, M, Q (smaller codes).
- **Consequences:** Slightly denser code. Card URLs are short, so the code stays small.

## D10 — `noindex` on by default, per card

- **Date:** 2026-09-20
- **Decision:** Each card has a `noindex` flag, default true, that emits `<meta name="robots" content="noindex, nofollow">` and an `X-Robots-Tag` header.
- **Context:** Card pages are public but most people do not want their mobile number in Google's index.
- **Alternatives considered:** A site-wide setting only; no control.
- **Consequences:** The site-wide default is a setting; each card can override it.

## D11 — Two escaping functions, never mixed

- **Date:** 2026-09-20
- **Decision:** HTML output uses `esc_html`, `esc_attr`, `esc_url`. vCard output uses vCard escaping (`\\`, `\,`, `\;`, `\n`).
- **Context:** The two formats have different escaping rules.
- **Alternatives considered:** None.
- **Consequences:** The vCard builder never calls WordPress escaping functions; the template never calls the vCard escaper.

## D12 — Vendored QR library is `phpqrcode`

- **Date:** 2026-09-20
- **Decision:** QR codes use `phpqrcode` (LGPL-3.0), the single-file merged build, vendored unmodified under `vendor/phpqrcode/` with its LICENSE.
- **Context:** Single file, GD-based, GPL-compatible, no Composer dependency at runtime.
- **Alternatives considered:** `endroid/qr-code` (Composer, many dependencies); `chillerlan/php-qrcode`; a JavaScript generator.
- **Consequences:** GD is required. The library defines global constants and classes, so it is loaded only when a QR is generated.

## D13 — Card page template rendered by the plugin

- **Date:** 2026-09-20
- **Decision:** The single card view is rendered by the plugin through `template_include`, and the template path can be replaced with the `dbcp_template_path` filter.
- **Context:** Consistent output on any theme, still customizable.
- **Alternatives considered:** A theme template hierarchy file (`single-business_card.php`); a block theme template part.
- **Consequences:** The card looks the same on every site. See D21 for how the document is built.

---

## D14 — System font stack only (closes OQ1)

- **Date:** 2026-09-20
- **Decision:** The card page uses a system font stack (`system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif`). DM Sans is not bundled and is not loaded from Google Fonts.
- **Context:** OQ1. The prototype loads DM Sans from Google Fonts. §4 forbids CDN-hosted fonts, and bundling the variable font adds ~100 KB and a font license to audit.
- **Alternatives considered:** Bundle DM Sans (OFL) in `assets/fonts/`; load from Google Fonts.
- **Consequences:** Smaller plugin, no external requests, passes wordpress.org review. The card looks slightly different from the prototype on each platform. A theme can add a font through the `dbcp_head` action or a template override.

## D15 — Rewrite base defaults to `card` (closes OQ2)

- **Date:** 2026-09-20
- **Decision:** The default rewrite base is `card`, giving `/card/{slug}/`.
- **Context:** OQ2. PROJECTBRIEF.md §2 already uses `/card/` in every example.
- **Alternatives considered:** `contact`.
- **Consequences:** None beyond the default; the base is a setting.

## D16 — vCard endpoint returns 404 for unpublished cards (closes OQ3)

- **Date:** 2026-09-20
- **Decision:** If the card is not published (draft, pending, private for a visitor, trashed, or deleted), the vCard endpoint returns a normal WordPress 404.
- **Context:** OQ3. 410 Gone tells crawlers a resource is permanently removed. A card that is unpublished may come back, and the plugin cannot distinguish "temporarily unpublished" from "deleted", since deleted posts leave no trace.
- **Alternatives considered:** 410 for trashed cards, 404 otherwise.
- **Consequences:** Simple and consistent with how the card page itself behaves. The same 404 is used for the card page.

## D17 — QR regenerated automatically when the permalink changes (closes OQ4)

- **Date:** 2026-09-20
- **Decision:** The plugin stores the URL encoded in each cached QR PNG in post meta `_dbcp_qr_url`. Whenever the QR is requested (edit screen, download link) it compares that value with the current permalink and regenerates the PNG if they differ or the file is missing. It also regenerates on `save_post` for published cards and deletes the PNG when the card is deleted.
- **Context:** OQ4. The permalink changes when the slug is edited, when the rewrite base setting changes, or when the site URL changes. A manual "Regenerate" button would be forgotten.
- **Alternatives considered:** Regenerate on demand from a button; regenerate every card when the setting changes (slow with many cards).
- **Consequences:** No stale QR codes. Changing the rewrite base does not touch every card immediately; each card's QR is refreshed the next time it is viewed in the admin, which is before anyone can download it.

## D18 — Wallet pass PNG deferred to v2 (closes OQ5)

- **Date:** 2026-09-20
- **Decision:** The Google Wallet pass image and the standalone QR image with name and logo are not built in v1.
- **Context:** OQ5. iPhone users save an image to Photos rather than using a pass; the downloadable QR PNG covers the immediate need.
- **Alternatives considered:** Build it in v1 with GD.
- **Consequences:** Stays on the v2 list in PROJECTBRIEF.md. `reference/john-janney-wallet-pass.png` remains the design reference.

## D19 — Card Holder role also gets `edit_published_business_cards` and `delete_published_business_cards`

- **Date:** 2026-09-20
- **Decision:** The `card_holder` role receives `read`, `upload_files`, `edit_business_cards`, `publish_business_cards`, `delete_business_cards`, `edit_published_business_cards` and `delete_published_business_cards`. It does not receive any `*_others_*` or `*_private_*` capability.
- **Context:** PROJECTBRIEF.md §2.7 lists five capabilities. With `map_meta_cap`, editing your own *published* card maps to `edit_published_business_cards`, and without it a Card Holder could edit their card only while it is a draft. That contradicts the purpose of the role (hand a published card to its owner).
- **Alternatives considered:** Keep the five capabilities and tell owners to ask an administrator to unpublish first; filter `map_meta_cap` to special-case published posts (violates D3).
- **Consequences:** A Card Holder can edit and delete their own card in any status, and nothing else. This is the standard WordPress "Author" pattern applied to the card post type.

## D20 — Admin list scoped to own cards for users without `edit_others_business_cards`

- **Date:** 2026-09-20
- **Decision:** On the Business Cards list screen, users who lack `edit_others_business_cards` see only cards they authored (a `pre_get_posts` author constraint in the admin, no front-end effect).
- **Context:** WordPress defaults the list to "Mine" for such users but still lets them switch to "All" and see other people's published cards (without edit links). §6 item 4 requires a Card Holder to see only their own card.
- **Alternatives considered:** Rely on the default "Mine" view; hide the status links with CSS.
- **Consequences:** A query constraint, not a permission check, so D3 still holds. Administrators and Editors are unaffected.

## D21 — Card page is a standalone HTML document

- **Date:** 2026-09-20
- **Decision:** `templates/single-business-card.php` outputs a complete HTML document: its own `<head>`, the plugin stylesheet, the robots meta and the site icon. It does not call `wp_head()` or `wp_footer()`, and the admin bar is not shown. Two actions, `dbcp_head` and `dbcp_footer`, let other code add tags.
- **Context:** The prototype is a standalone page. Calling `wp_head()` pulls in the theme's stylesheets, fonts and scripts, which change the typography and layout in ways that differ per theme. D13 requires consistent output on any theme.
- **Alternatives considered:** Call `wp_head()` and dequeue theme assets (fragile); a theme template hierarchy file.
- **Consequences:** Analytics and SEO plugins that hook `wp_head` do not run on card pages. That is acceptable for `noindex` pages; site owners who need them can hook `dbcp_head`. The shortcode embeds the card inside the theme normally, with only the plugin stylesheet added.

## D22 — Phone numbers are normalized for `tel:` links and the vCard, displayed as entered

- **Date:** 2026-09-20
- **Decision:** The phone fields are stored as typed. For `tel:` hrefs and vCard `TEL` values the plugin strips everything except digits and a leading `+`. The visible text on the card is the value as typed.
- **Context:** The prototype displays `(214) 810-1131` and dials `tel:+12148101131`. The brief's sample data is `+1 214-810-1131`. Editors should control how a number looks without breaking dialing.
- **Alternatives considered:** Store E.164 only and format for display (locale-dependent); use the typed value everywhere.
- **Consequences:** Editors are told in INSTRUCTIONS.md to include the country code.

## D23 — Photo resizing uses the WordPress image editor, streamed to memory

- **Date:** 2026-09-20
- **Decision:** The vCard endpoint loads the featured image with `wp_get_image_editor()`, resizes and crops to 400 × 400, sets JPEG quality 82, and captures the output of `stream()` with output buffering. No temporary file is written.
- **Context:** D5 (nothing on disk) and D7 (400 × 400 JPEG). `wp_get_image_editor()` uses Imagick when available and GD otherwise.
- **Alternatives considered:** Direct GD calls; `add_image_size()` and read the generated file.
- **Consequences:** Works with GD or Imagick. GD is still required by phpqrcode, so the activation check remains.

## D24 — Composer's `vendor/` is shared with the vendored QR library

- **Date:** 2026-09-20
- **Decision:** Development dependencies (PHPUnit, PHPCS, WordPress Coding Standards) install into `vendor/` and are ignored by git. `vendor/phpqrcode/` is the one tracked, shipped subdirectory (`.gitignore` has `vendor/*` and `!vendor/phpqrcode/`).
- **Context:** PROJECTBRIEF.md §5 fixes the path `vendor/phpqrcode/`; Composer's default vendor directory is also `vendor/`.
- **Alternatives considered:** Move Composer's vendor dir to `tools/vendor`; rename the library folder.
- **Consequences:** The release zip is built from `git archive`, so only `vendor/phpqrcode/` ships. PHPCS excludes `vendor/`.

## D25 — Release zip is built from `git archive HEAD` on a clean tree

- **Date:** 2026-09-20
- **Decision:** `bin/build.sh` refuses to run with uncommitted changes to tracked files, exports `HEAD` with `git archive --prefix=digital-business-card-posts/`, and relies on `export-ignore` attributes in `.gitattributes` to exclude tests, tooling, `dist/`, `reference/` and repository documents.
- **Context:** VERSIONING.md requires a clean export. Exporting from git guarantees the zip contains exactly what is committed and tagged.
- **Alternatives considered:** `rsync` of the working tree with an exclude list.
- **Consequences:** The version bump is committed before the zip is built; the zip is committed in a second commit and then tagged.

## D26 — The photo is not shown on the card page; the country is shown in the address row

- **Date:** 2026-09-20
- **Decision:** The contact photo (featured image) is only embedded in the vCard. The card page shows the logo, name, title, company, tagline, button and contact rows exactly as the prototype does. The address row shows the country on its own line when it is set.
- **Context:** PROJECTBRIEF.md §9 describes the design without a photo. The prototype omits the country because the owner's audience is local; a plugin serves international sites.
- **Alternatives considered:** Show the photo as an avatar above the name; hide the country.
- **Consequences:** Adding a photo to the page is a template override or a v2 option.

## D27 — Classic edit screen for cards

- **Date:** 2026-09-20
- **Decision:** The post type is registered with `show_in_rest => true` (for D2) but without `editor` support, so WordPress uses the classic edit screen with a meta box rather than the block editor.
- **Context:** The card has no free-form content. A meta box with labeled fields, a media picker and a color picker is the simplest reliable UI, and it works identically for Card Holders.
- **Alternatives considered:** Block editor with a custom sidebar panel (requires a JavaScript build step).
- **Consequences:** No build step. Meta remains available over REST for import/export.

## D28 — Uninstall always removes the QR cache directory

- **Date:** 2026-09-20
- **Decision:** `uninstall.php` always deletes `wp-content/uploads/digital-business-card-posts/`, the role, the capabilities and the plugin options. Card posts and their meta are deleted only when the "Delete data on uninstall" setting is enabled.
- **Context:** QR PNGs are derived from the permalink and regenerate on demand (D17), so deleting them loses nothing. Card posts are user content.
- **Alternatives considered:** Keep the PNGs unless the delete setting is on.
- **Consequences:** Reinstalling regenerates QR codes the first time each card is opened in the admin.

## D29 — Post type supports `custom-fields` so meta is exposed over REST; the generic box is removed

- **Date:** 2026-09-20
- **Decision:** `business_card` declares `custom-fields` support, and the plugin removes the generic Custom Fields meta box from the card edit screen.
- **Context:** Verified on a live site: the REST API only includes the `meta` object for post types that support `custom-fields`, even when every key is registered with `show_in_rest`. Without it D2 (fields available over REST) was not actually met. The generic box would duplicate the Card details box.
- **Alternatives considered:** Register a custom REST field per meta key (more code, same result); leave meta out of REST.
- **Consequences:** `GET /wp-json/wp/v2/business-cards/{id}` returns all card fields under `meta`. Amends D2 and D27; neither is reversed.

## D30 — PHOTO is emitted before ADR, and REV is always the last property

- **Date:** 2026-09-20
- **Decision:** The vCard builder writes properties in the order of `reference/john-janney.vcf`: `N`, `FN`, `ORG`, `TITLE`, `TEL`, `TEL`, `EMAIL`, `URL`, `PHOTO`, `ADR`, then a new `REV` property (the card's `post_modified_gmt` as `YYYYMMDDTHHMMSSZ`), then `END:VCARD`. The folded base64 photo block is therefore never the final property.
- **Context:** On an Android phone the 1.0.0 vCard opened with no photo and the base64 continuation lines shown as text inside the address. The 1.0.0 builder put `ADR` before `PHOTO`, so the photo block ran straight into `END:VCARD`; the reference file, which works, has `ADR` after the photo. AOSP's vCard 3.0 parser keeps a one-line look-ahead while unfolding, and its base64 reader peeks past that look-ahead; when nothing but `END:VCARD` follows the photo the read hits end-of-file and the client falls back to showing the raw lines. Putting a short text property after the photo avoids the edge case, and `REV` guarantees one exists even for a card with no address.
- **Alternatives considered:** Only move `PHOTO` before `ADR` (leaves the no-address case exposed); emit `PHOTO` first, right after `FN` (deviates further from the reference); append a blank line after the photo block (vCard 2.1 convention, not valid 3.0).
- **Consequences:** Every vCard now carries a `REV` line, which Contacts apps ignore or use to detect updates. `dbcp_vcard_fields` receives a `revision` key (Unix timestamp); setting it to `0` removes `REV`. Needs confirmation on a phone (RELEASE-CHECKLIST.md).
