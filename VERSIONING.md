<!-- Purpose: define how this plugin is versioned, where the version lives, and how a release is built and packaged. -->

# Versioning

## Scheme

This plugin uses [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html): `MAJOR.MINOR.PATCH`.

- **MAJOR**: a change that breaks existing sites, such as renaming a meta key, changing the post type name, dropping a WordPress or PHP version, or changing the vCard endpoint URL.
- **MINOR**: a new feature that is backwards compatible, such as a new field, a new setting or a new filter.
- **PATCH**: a bug fix with no new feature.

Pre-release versions (`1.1.0-beta.1`) are allowed for testing but are never uploaded to a production site.

## The version lives in exactly three places

All three must match on every release:

| Location | Example |
|----------|---------|
| Plugin header `Version:` in `digital-business-card-posts.php` | ` * Version: 1.0.0` |
| Constant `DBCP_VERSION` in `digital-business-card-posts.php` | `define( 'DBCP_VERSION', '1.0.0' );` |
| `Stable tag:` in `readme.txt` | `Stable tag: 1.0.0` |

Nowhere else. Do not add the version to CSS files, JS files or documentation, other than CHANGELOG.md headings.

### Pre-release check

`bin/build.sh` reads all three values and refuses to build if they differ. Run it, or check by hand:

```bash
grep -E '^ \* Version:' digital-business-card-posts.php
grep -E "define\( 'DBCP_VERSION'" digital-business-card-posts.php
grep -E '^Stable tag:' readme.txt
```

## Every release produces a zip

- Every release is packaged as `dist/digital-business-card-posts-{version}.zip`.
- `dist/` is committed to the repository. **Previous versions are kept.** Never delete, rename or overwrite a released zip. `bin/build.sh` refuses to overwrite an existing zip.
- Each zip contains a single top-level folder `digital-business-card-posts/` and is installable on a clean WordPress site via **Plugins → Add New → Upload Plugin**.
- The zip is built from a clean git export: no `.git`, no `node_modules`, no `tests/`, no `dist/`, no development configuration and no repository documents. Files are excluded with `export-ignore` in `.gitattributes`.

## Release procedure

1. Make sure every behavior change is already recorded under `[Unreleased]` in CHANGELOG.md.
2. Move `[Unreleased]` to a new `[X.Y.Z] - YYYY-MM-DD` section and add a fresh empty `[Unreleased]` above it.
3. Set the version in the three places above.
4. Run the tests and PHPCS (`composer test`, `composer lint`) and the checklist in PROJECTBRIEF.md §6. Record results in RELEASE-CHECKLIST.md.
5. Commit: `chore(release): X.Y.Z`.
6. Run `bin/build.sh`. It checks the versions, checks the working tree is clean, exports `HEAD`, and writes `dist/digital-business-card-posts-X.Y.Z.zip`.
7. Commit the zip: `chore(release): add dist zip for X.Y.Z`.
8. Tag: `git tag -a vX.Y.Z -m "vX.Y.Z"` and push the tag.
9. Create a GitHub Release for `vX.Y.Z` and attach the zip from `dist/`.

## Updating an installed site

Uploading a newer zip over an installed copy is supported by WordPress 5.5 and newer ("Replace current with uploaded"). Alternatively deactivate, delete (data is kept unless "Delete data on uninstall" is enabled), and upload the new zip. See INSTALLATION.md.
