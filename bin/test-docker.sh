#!/usr/bin/env bash
# Run the PHPUnit suite (and the QR smoke test) inside a container that has PHP with GD.
# Use this when the local PHP lacks the GD extension. Requires Docker and `composer install`
# to have been run locally (vendor/ is bind-mounted).
#
# Usage: bin/test-docker.sh [phpunit args...]
# Override the image with DBCP_TEST_IMAGE (default: exifix-wordpress:latest if present, else php:8.2-cli).
set -euo pipefail
REPO_ROOT="$(git -C "$(dirname "${BASH_SOURCE[0]}")" rev-parse --show-toplevel)"
IMAGE="${DBCP_TEST_IMAGE:-}"
if [ -z "$IMAGE" ]; then
	if docker image inspect exifix-wordpress:latest >/dev/null 2>&1; then
		IMAGE=exifix-wordpress:latest
	else
		IMAGE=php:8.2-cli
	fi
fi
exec docker run --rm -v "$REPO_ROOT:/app" -w /app -u "$(id -u):$(id -g)" "$IMAGE" php vendor/bin/phpunit "$@"
