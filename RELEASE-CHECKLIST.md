<!-- Purpose: results of the PROJECTBRIEF.md §6 checklist for the current release, and the manual tests still pending. Update for every release. -->

# Release checklist — 1.0.0

Date: 2026-09-20. Environment for the automated runs: WordPress 7.0 (official Docker image, PHP 8.2 with GD and Imagick, Twenty Twenty-Five theme, pretty permalinks `/%postname%/`), driven by wp-cli, curl and headless Chromium (Playwright). Sample data from PROJECTBRIEF.md §9.

## Automated / verified without a phone

| § | Item | Result | How it was verified |
|---|------|--------|---------------------|
| 1 (part) | Create a card as Administrator; open `/card/{slug}/`; the Save contact button links to the vCard endpoint | **Pass** | Card page returns 200 with `X-Robots-Tag: noindex, nofollow` and the robots meta; the button's `href` is `/card/john-janney/vcard/`. Screenshots in `screenshots/` match `reference/index.html` in light and dark mode (system font instead of DM Sans, D14). |
| 1 (part) | vCard endpoint output | **Pass** | `Content-Type: text/vcard; charset=utf-8`, `Content-Disposition: attachment; filename="john-janney.vcf"`, `Content-Length` set, CRLF only, longest physical line 75 octets, PHOTO decodes to a 400 × 400 JPEG of ~16 KB. |
| 2 | Both phone numbers typed as work and mobile | **Pass (file level)** | Output contains `TEL;TYPE=WORK,VOICE:+12148101131` and `TEL;TYPE=CELL,VOICE:+14696197273`. Display in Contacts apps still needs the phone test below. |
| 3 (part) | QR code encodes the card URL | **Pass** | The cached PNG (`uploads/digital-business-card-posts/{id}.png`) decoded with zbar to exactly the card permalink, before and after changing the rewrite base. |
| 4 | Log in as a Card Holder; sees only their own card and no other post types | **Pass** | Admin menu shows Dashboard, Media, Business Cards, Profile only. Business Cards list shows only the holder's card in the Mine, All and Published views. Opening the administrator's card returns 403. Posts, Pages and the settings page return 403. Own card can be edited and saved and shows its QR box. |
| 5 | Change the rewrite base; old URLs 404, new URLs work | **Pass** | Base `card` → `contact`: `/card/john-janney/` and its vCard return 404; `/contact/john-janney/` and `/contact/john-janney/vcard/` return 200; the Save contact link and the QR code (decoded) use the new base. Restored to `card`. Plain permalinks also verified: `/?business_card=john-janney` and `&dbcp_vcard=1`. |
| 6 | Deactivate and reactivate; role and capabilities survive | **Pass** | After deactivation the Card Holder role still exists, the holder user keeps the role, and card URLs return 404. After reactivation: Card Holder has 7 capabilities, Editor and Administrator have all 10 business card capabilities, card page and vCard return 200. |
| 7 | PHPCS with the WordPress ruleset, zero errors | **Pass** | `composer lint`: 0 errors, 0 warnings (WordPress, WordPress-Extra, WordPress-Docs, PHPCompatibilityWP 7.4+). |
| 8 (part) | Validate a generated `.vcf` | **Pass (structural)** | PHPUnit: 25 builder tests (escaping, folding, CRLF, TEL types, PHOTO) plus a structural comparison with `reference/john-janney.vcf` (identical after unfolding, apart from the escaped comma in ORG per D11 and the normalized TEL values per D22). QR smoke test passes in the GD container. No online validator was used. |
| — | Shortcode | **Pass** | `[digital_business_card id]` on a page renders the card inside the theme with the stylesheet enqueued; a draft card, a missing ID and no ID render nothing. |
| — | REST API | **Pass** | `GET /wp-json/wp/v2/business-cards/{id}` returns every field under `meta` (D29). |
| — | Meta box save path | **Pass** | Changing the tagline through the form and clicking Update saves through the nonce-protected save handler; the media picker opens; the color picker initialises. No browser console errors, no PHP notices in the server log. |
| — | Uninstall | **Pass** | `uninstall.php` removes the Card Holder role, all business card capabilities from every role, the settings option and the QR cache folder; cards are kept. With "Delete data on uninstall" enabled, all cards are deleted too. |
| — | Build | **Pass** | `bin/build.sh` refuses on version mismatch, dirty tree and existing zip; the zip contains a single `digital-business-card-posts/` folder without tests, bin, dist, reference or repository documents. |

## Manual tests pending

These need a phone, a Contacts app or an external validator. Test on the installed release (`dist/digital-business-card-posts-1.0.0.zip`) and tick them off here.

- [ ] **§6.1** Open `/card/{slug}/` on **iPhone Safari**. Tap **Save contact**. Confirm the contact preview opens and the photo shows.
- [ ] **§6.1** Open `/card/{slug}/` on **Android Chrome**. Tap **Save contact**. Confirm the contact preview opens (or the `.vcf` downloads and opens in Contacts) and the photo shows.
- [ ] **§6.2** In the saved contact on iPhone and Android, confirm the two numbers are labelled **work** and **mobile**.
- [ ] **§6.3** Scan the downloaded QR PNG (and the on-screen code in the edit screen) with the **iPhone Camera** and with **Google Lens**. Confirm it opens the card URL.
- [ ] **§6.8** Run a generated `.vcf` through an online vCard validator.
- [ ] **§6.8** Open a generated `.vcf` in **macOS Contacts**, **Outlook** and **Gmail Contacts** (import). Confirm name, title, company, both phones, email, URL, address and photo.
- [ ] **Install** `dist/digital-business-card-posts-1.0.0.zip` on a real site via Plugins → Add New → Upload Plugin, activate, and repeat the first-run checks in INSTALLATION.md.
- [ ] **Screenshots** in `screenshots/` were taken on the Docker test site; retake on the production site if the README should show real data.
