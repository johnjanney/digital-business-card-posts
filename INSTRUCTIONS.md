<!-- Purpose: how to use the plugin. Every field explained, the card URL, the QR code, the shortcode, the Card Holder role and troubleshooting. -->

# Instructions

## Creating a card

1. Go to **Business Cards → Add New**.
2. The **title** is the display name shown on the card and used as the vCard `FN`. It is filled in automatically from *First name* and *Last name* when you leave it empty.
3. Fill in the **Card details** box (see the field list below).
4. Set the **Featured Image**. This is the contact photo embedded in the vCard. Use a square image if possible; it is resized to 400 × 400 pixels at download time.
5. Optionally pick a **Logo** with the media picker.
6. Click **Publish**. The card URL and the QR code appear in the **QR code** box after publishing.

## Fields

| Field | Required | What it is used for |
|-------|----------|--------------------|
| First name | yes | vCard `N` (given name); default title |
| Last name | yes | vCard `N` (family name); default title |
| Job title | no | Shown under the name; vCard `TITLE` |
| Company | no | Shown under the title; vCard `ORG` |
| Tagline | no | Italic line under the company. Not included in the vCard. |
| Work phone | no | *Work* row on the card; vCard `TEL;TYPE=WORK,VOICE` |
| Mobile phone | no | *Mobile* row on the card; vCard `TEL;TYPE=CELL,VOICE` |
| Email | no | *Email* row; vCard `EMAIL;TYPE=WORK,INTERNET` |
| Website | no | *Website* row (shown without `https://`); vCard `URL` |
| Street, Suite, City, State, Postal code, Country | no | *Directions* row linking to Google Maps; vCard `ADR;TYPE=WORK`. The row appears when at least street or city is set. |
| Logo | no | Image shown at the top left of the card, 96 px tall. Use a PNG with a transparent background, trimmed, at least 400 px wide. |
| Accent color | no | Hex color for the square after the name, the button and focus outlines. Default comes from the settings (`#f7c600`). |
| Hide from search engines (`noindex`) | no | When checked, the card page sends `noindex, nofollow` to search engines. Default comes from the settings (on). |

**Phone numbers:** type them as you want them displayed, for example `(214) 810-1131` or `+1 214-810-1131`. Always include the country code so the number dials correctly abroad: the plugin strips everything except digits and the leading `+` for the `tel:` link and the vCard.

**Website:** enter the full URL including `https://`. The card shows only the host name.

## The card URL

Every published card lives at `https://your-site.example/card/{slug}/`. The slug is the post slug, editable under the title in the edit screen. The base (`card`) can be changed in **Settings → Digital Business Cards**.

The vCard for a card is always at `{card URL}vcard/`, for example `https://your-site.example/card/john-janney/vcard/`. The **Save contact** button on the card links there.

With plain permalinks the URLs are `/?business_card={slug}` and `/?business_card={slug}&dbcp_vcard=1`.

## Downloading the QR code

Open the card in the editor. The **QR code** box in the sidebar shows the code and a **Download PNG** link. The code encodes the card URL at error-correction level H, so it stays readable when printed small or shown on a phone screen.

The QR code is regenerated automatically whenever the card's URL changes (slug edit, rewrite base change, site address change). It is not generated for drafts, because a draft has no public URL.

## The `noindex` setting

Card pages are public but usually should not be found by search engines. Each card has a **Hide from search engines** checkbox, checked by default. The site-wide default for new cards is in **Settings → Digital Business Cards**. When on, the card page sends the `X-Robots-Tag: noindex, nofollow` header and a matching `<meta name="robots">` tag. The vCard endpoint always sends `noindex`.

## The shortcode

Embed a card inside any post or page:

```
[digital_business_card id="123"]
```

`id` is the card's post ID (shown in the edit screen URL as `post=123`). The embedded card uses the same markup and stylesheet as the card page, inside the theme's layout. Only published cards render; an unpublished or missing card renders nothing for visitors.

## The Card Holder role

Activation creates a **Card Holder** role. A Card Holder can:

- log in and see the dashboard,
- see, edit, publish and delete **only their own** business cards,
- upload images (for the photo and logo).

A Card Holder cannot see or edit posts, pages, other people's cards, or settings.

Administrators and Editors can create and edit all cards.

## Handing a card to its owner

The expected workflow is that one webmaster creates all cards. To let a person manage their own card later:

1. **Users → Add New**, create the person's account with the role **Card Holder** (or change an existing user's role).
2. Open their card in **Business Cards**, find the **Author** box (enable it under **Screen Options** if hidden), and select the person.
3. Click **Update**.

The person can now log in and will see just that card under **Business Cards**.

## Settings

**Settings → Digital Business Cards**

| Setting | Default | Notes |
|---------|---------|-------|
| Card URL base | `card` | Lowercase letters, numbers and hyphens. Changing it flushes rewrite rules; old URLs stop working immediately and QR codes are regenerated as each card is opened. |
| Default accent color | `#f7c600` | Used for new cards. Existing cards keep their own color. |
| Hide new cards from search engines | on | Default for the per-card `noindex` checkbox. |
| Delete data on uninstall | off | When on, deleting the plugin also deletes all business card posts. Roles, capabilities, settings and the QR cache are always removed on uninstall. |

## Troubleshooting

**The vCard opens as text in the browser instead of downloading.**
Something on the server is overriding the `Content-Type` or `Content-Disposition` header. Check for a caching plugin or CDN rule that rewrites headers for `.vcf` paths, and check that no other plugin outputs anything (even whitespace) before headers are sent. Enable `WP_DEBUG` and look for "headers already sent" notices.

**The Save contact button does nothing on iPhone.**
The link must point to the `vcard/` URL, not to a `data:` URL. If a page-caching plugin serves the card page from a cache, make sure it also excludes `/card/*/vcard/` from caching so the correct headers are sent.

**The photo does not show in the saved contact.**
Make sure the card has a Featured Image and that the file is a JPEG, PNG or WebP that WordPress can read. Very large vCards are rejected by iOS; the plugin resizes to 400 × 400 pixels so this should not happen unless a plugin filters the output.

**The QR code does not scan.**
Confirm the PHP GD extension is installed (the plugin shows an admin notice if it is missing). Download the PNG rather than screenshotting a scaled preview. Keep the code at least 2 cm wide when printed and leave the white margin intact.

**The QR code box says "Publish the card to generate its QR code."**
Drafts have no public URL. Publish the card.

**The card page returns 404.**
Go to **Settings → Permalinks** and click **Save Changes** to flush the rewrite rules. This is needed after changing the rewrite base outside the settings page, after a migration, or if another plugin overwrote the rules. Also check that the card is published and that you are using the current base from the settings.

**A Card Holder sees "Sorry, you are not allowed to access this page."**
They are opening a card they do not own, or the role lost its capabilities. Deactivate and reactivate the plugin to restore capabilities.

**The card looks different from the prototype.**
The plugin uses a system font stack instead of DM Sans (see DECISIONS.md D14). A theme can add a font by hooking the `dbcp_head` action or by overriding the template with the `dbcp_template_path` filter.
