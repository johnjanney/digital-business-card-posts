#!/usr/bin/env bash
# Build dist/digital-business-card-posts-{version}.zip from a clean git export of HEAD.
#
# Checks, in order:
#   1. The version matches in the plugin header, DBCP_VERSION and readme.txt (Stable tag).
#   2. The working tree has no uncommitted changes to tracked files (the zip reflects HEAD).
#   3. Every PHP file that ships passes `php -l`.
#   4. dist/digital-business-card-posts-{version}.zip does not exist yet (never overwrite a release).
# Then exports HEAD with `git archive` (honouring export-ignore in .gitattributes) and zips it
# with a single top-level folder digital-business-card-posts/.
#
# Usage: bin/build.sh            (from anywhere inside the repository)
set -euo pipefail

SLUG="digital-business-card-posts"
REPO_ROOT="$(git -C "$(dirname "${BASH_SOURCE[0]}")" rev-parse --show-toplevel)"
cd "$REPO_ROOT"

fail() { printf 'build: %s\n' "$*" >&2; exit 1; }

MAIN_FILE="$SLUG.php"
[ -f "$MAIN_FILE" ] || fail "missing $MAIN_FILE"
[ -f readme.txt ] || fail "missing readme.txt"

# 1. Version consistency.
HEADER_VERSION="$(grep -E '^ \* Version:' "$MAIN_FILE" | head -1 | sed -E 's/^ \* Version:[[:space:]]*//; s/[[:space:]]*$//')"
CONST_VERSION="$(grep -E "define\( 'DBCP_VERSION', '" "$MAIN_FILE" | head -1 | sed -E "s/.*define\( 'DBCP_VERSION', '([^']+)'.*/\1/")"
README_VERSION="$(grep -E '^Stable tag:' readme.txt | head -1 | sed -E 's/^Stable tag:[[:space:]]*//; s/[[:space:]]*$//')"

[ -n "$HEADER_VERSION" ] || fail "could not read the Version: header from $MAIN_FILE"
[ -n "$CONST_VERSION" ] || fail "could not read DBCP_VERSION from $MAIN_FILE"
[ -n "$README_VERSION" ] || fail "could not read Stable tag from readme.txt"

if [ "$HEADER_VERSION" != "$CONST_VERSION" ] || [ "$HEADER_VERSION" != "$README_VERSION" ]; then
	fail "version mismatch: header=$HEADER_VERSION constant=$CONST_VERSION readme.txt=$README_VERSION"
fi
VERSION="$HEADER_VERSION"
if ! printf '%s' "$VERSION" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$'; then
	fail "version '$VERSION' is not a valid semantic version"
fi

# 2. Clean tree (tracked files only; untracked files are not exported anyway).
if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
	git status --short --untracked-files=no >&2
	fail "working tree has uncommitted changes; commit them first so the zip matches HEAD"
fi

# 4. Never overwrite a released zip (checked before doing any work).
mkdir -p dist
ZIP="dist/$SLUG-$VERSION.zip"
[ ! -e "$ZIP" ] || fail "$ZIP already exists; released zips are never overwritten (bump the version instead)"

# Export.
BUILD_DIR="$(mktemp -d "${TMPDIR:-/tmp}/$SLUG-build.XXXXXX")"
trap 'rm -rf "$BUILD_DIR"' EXIT
git archive --format=tar --prefix="$SLUG/" HEAD | tar -x -C "$BUILD_DIR"

# 3. Syntax check every shipped PHP file.
while IFS= read -r -d '' php_file; do
	php -l "$php_file" >/dev/null || fail "syntax error in $php_file"
done < <(find "$BUILD_DIR/$SLUG" -name '*.php' -print0)

# Sanity: things that must be in the zip, things that must not.
for required in "$SLUG.php" uninstall.php readme.txt LICENSE vendor/phpqrcode/phpqrcode.php vendor/phpqrcode/LICENSE templates/single-business-card.php assets/card.css; do
	[ -f "$BUILD_DIR/$SLUG/$required" ] || fail "export is missing $required"
done
for forbidden in .git tests dist bin phpcs.xml composer.json reference node_modules; do
	[ ! -e "$BUILD_DIR/$SLUG/$forbidden" ] || fail "export must not contain $forbidden"
done

( cd "$BUILD_DIR" && zip -q -r -X "$REPO_ROOT/$ZIP" "$SLUG" )

FILE_COUNT="$(unzip -l "$ZIP" | tail -1 | awk '{print $2}')"
printf 'build: wrote %s (version %s, %s entries)\n' "$ZIP" "$VERSION" "$FILE_COUNT"
