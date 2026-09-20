<!-- Purpose: how to install, activate, verify and update the plugin on a WordPress site. -->

# Installation

## Requirements

| Requirement | Minimum |
|-------------|---------|
| WordPress | 6.0 |
| PHP | 7.4 (8.x recommended) |
| PHP extensions | **GD** (required for QR codes and photo resizing). Imagick is used for photo resizing when present, but GD is still required. |
| Permalinks | Any setting works. "Post name" or another pretty structure gives `/card/{slug}/`; "Plain" gives `/?business_card={slug}`. |
| Write access | WordPress must be able to create `wp-content/uploads/digital-business-card-posts/` for the QR code cache. |

The plugin makes no external requests and loads no external fonts or scripts.

## Method 1: upload the zip (recommended)

1. Get `digital-business-card-posts-{version}.zip` from the `dist/` folder of the repository or from the GitHub Release.
2. In the WordPress admin go to **Plugins → Add New → Upload Plugin**.
3. Choose the zip and click **Install Now**.
4. Click **Activate Plugin**.

## Method 2: manual upload by FTP or SFTP

1. Unzip `digital-business-card-posts-{version}.zip` on your computer. It contains one folder, `digital-business-card-posts/`.
2. Upload that folder to `wp-content/plugins/` on the server, so that `wp-content/plugins/digital-business-card-posts/digital-business-card-posts.php` exists.
3. In the WordPress admin go to **Plugins** and click **Activate** under **Digital Business Card Posts**.

## What activation does

- Registers the **Business Cards** post type and flushes rewrite rules so `/card/{slug}/` and `/card/{slug}/vcard/` work immediately.
- Grants the business card capabilities to the **Administrator** and **Editor** roles.
- Creates the **Card Holder** role.
- Creates the QR cache folder `wp-content/uploads/digital-business-card-posts/`.
- Checks for the GD extension. If it is missing, an admin notice is shown and QR codes cannot be generated until it is installed.

## First-run checks

1. **Settings → Permalinks**: just open the page once. If a card page returns 404 after activation, click **Save Changes** on this page to flush the rewrite rules.
2. **Users → Add New**: the **Role** dropdown should list **Card Holder**.
3. **Business Cards → Add New**: the edit screen should show the *Card details* box and the *QR code* box.
4. **Settings → Digital Business Cards**: confirm the rewrite base (default `card`), the default accent color and the default `noindex` setting.
5. Create a test card, publish it, and open its URL on a phone. Tap **Save contact**.

## Updating

**Upload the new zip over the old version (WordPress 5.5+):** go to **Plugins → Add New → Upload Plugin**, choose the new zip, and on the confirmation screen click **Replace current with uploaded**. Cards, settings and roles are kept.

**Manual:** deactivate the plugin, delete it from **Plugins** (this runs the uninstall routine; cards are kept unless **Delete data on uninstall** is enabled in the plugin settings), then install the new zip and activate. Roles and capabilities are re-created on activation.

After an update, if card pages return 404, open **Settings → Permalinks** and click **Save Changes**.

## Deactivating and uninstalling

- **Deactivate** keeps everything: cards, settings, the Card Holder role and its users. Rewrite rules are flushed so the card URLs stop resolving until the plugin is reactivated.
- **Delete** (uninstall) removes the Card Holder role, removes the business card capabilities from every role, deletes the plugin settings and the QR cache folder. Card posts and their photos are **kept** unless **Delete data on uninstall** is enabled in **Settings → Digital Business Cards** before deleting the plugin. Users who had the Card Holder role are left with no role; assign them a new role afterwards.
