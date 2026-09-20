<!-- Purpose: record every user-visible change per version, in Keep a Changelog format. Every commit that changes behavior adds a line under [Unreleased]. -->

# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html). See [VERSIONING.md](VERSIONING.md).

## [Unreleased]

### Added

- Project documents, repository scaffolding, GPL-2.0-or-later license, build script and PHPCS configuration.
- `business_card` post type with `capability_type => 'business_card'` and `map_meta_cap`, public single view under the configurable rewrite base (default `card`), excluded from site search.
- Activation grants the full capability set to Administrator and Editor and creates the Card Holder role with the "own" capability set (D19). Deactivation keeps roles and capabilities.
- Uninstall removes the role, capabilities, settings and the QR cache; card posts are deleted only when the "Delete data on uninstall" setting is on (D28).
- Admin notice when the PHP GD extension is missing.
- Business Cards list scoped to the current user's cards for users without `edit_others_business_cards` (D20).
- Settings storage with defaults: rewrite base, default accent color, default `noindex`, delete data on uninstall. Rewrite rules are flushed when the base changes.

### Changed

### Fixed

### Removed

[Unreleased]: https://github.com/johnjanney/digital-business-card-posts/compare/main...HEAD
