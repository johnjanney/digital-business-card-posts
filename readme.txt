=== Digital Business Card Posts ===
Contributors: johnjanney
Tags: business card, vcard, qr code, contact, digital card
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Digital business card pages, one per person, each with a downloadable vCard and a QR code.

== Description ==

Digital Business Card Posts adds a **Business Cards** post type. Each card gets its own mobile-first page at `/card/{slug}/` with one primary button, **Save contact**, that downloads a vCard 3.0 file with the contact photo embedded. A QR code pointing to the card is generated for each card and can be downloaded from the edit screen.

* One post per card, with first and last name, job title, company, tagline, work and mobile phone, email, website, address, logo and accent color.
* Card page rendered by the plugin so it looks the same on every theme. Override the template with the `dbcp_template_path` filter.
* vCard generated on request at `/card/{slug}/vcard/`, never stored on disk. The featured image is embedded as a 400 × 400 JPEG.
* QR code at error-correction level H, cached as PNG, regenerated when the card URL changes.
* `[digital_business_card id="123"]` shortcode embeds a card in any post or page.
* **Card Holder** role: hand a card to its owner by changing the post author. Administrators and Editors manage all cards.
* `noindex` on by default per card.
* No external requests, fonts or scripts.

QR codes are generated with phpqrcode by Dominik Dzienia (LGPL-3.0), included in `vendor/phpqrcode/`.

== Installation ==

1. Upload the plugin zip through **Plugins → Add New → Upload Plugin**, or upload the `digital-business-card-posts` folder to `wp-content/plugins/`.
2. Activate the plugin.
3. Go to **Business Cards → Add New**, fill in the fields, set the featured image (contact photo) and publish.
4. Open the card URL on a phone and tap **Save contact**. Download the QR code from the edit screen.

The PHP GD extension is required.

== Frequently Asked Questions ==

= The card page returns 404. =

Go to **Settings → Permalinks** and click **Save Changes** to flush rewrite rules.

= Can I change the `/card/` part of the URL? =

Yes. **Settings → Digital Business Cards → Card URL base**.

= Does deleting the plugin delete my cards? =

No, unless **Delete data on uninstall** is enabled in the settings.

== Changelog ==

See CHANGELOG.md in the repository.
