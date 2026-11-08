#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$REPO_ROOT"

USE_SUDO_DOCKER="${USE_SUDO_DOCKER:-0}"
DOCKER_BIN="${DOCKER:-docker}"
if [[ "$USE_SUDO_DOCKER" == "1" ]]; then
  DOCKER_CMD=(sudo "$DOCKER_BIN")
else
  DOCKER_CMD=("$DOCKER_BIN")
fi

PHP_IMAGE='statsproduct-php56-mysql:local'
MARIADB_IMAGE='mariadb@sha256:2b7259ea544fd0bfa1c58ac83a65042a5da5ed42a19a79323d61b50c3670eacc'
NETWORK='statsproduct-net'
DB_CONTAINER='statsproduct-db'
TMPDIR_SUITE="$(mktemp -d)"
chmod 0700 "$TMPDIR_SUITE"
cleanup() {
  rm -rf "$TMPDIR_SUITE"
}
trap cleanup EXIT

"${DOCKER_CMD[@]}" build -t "$PHP_IMAGE" -f "$REPO_ROOT/tests/docker/Dockerfile.php56-mysql" "$REPO_ROOT/tests/docker"

if ! "${DOCKER_CMD[@]}" network inspect "$NETWORK" >/dev/null 2>&1; then
  "${DOCKER_CMD[@]}" network create "$NETWORK"
fi

if ! "${DOCKER_CMD[@]}" ps --format '{{.Names}}' | grep -qx "$DB_CONTAINER"; then
  if "${DOCKER_CMD[@]}" ps -a --format '{{.Names}}' | grep -qx "$DB_CONTAINER"; then
    "${DOCKER_CMD[@]}" rm -f "$DB_CONTAINER" >/dev/null
  fi
  "${DOCKER_CMD[@]}" run -d --name "$DB_CONTAINER" --network "$NETWORK" \
    -e MARIADB_DATABASE=statsproduct \
    -e MARIADB_ROOT_PASSWORD=statsproduct \
    "$MARIADB_IMAGE" >/dev/null
fi

DB_READY=0
for _ in $(seq 1 60); do
  if "${DOCKER_CMD[@]}" exec "$DB_CONTAINER" mariadb-admin ping -h 127.0.0.1 -uroot -pstatsproduct --silent >/dev/null 2>&1; then
    DB_READY=1
    break
  fi
  sleep 1
done
if [[ "$DB_READY" -ne 1 ]]; then
  echo "MariaDB not ready" >&2
  exit 1
fi

curl -fsSL --connect-timeout 30 --max-time 180 \
  -o "$TMPDIR_SUITE/phpunit-4.8.36.phar" https://phar.phpunit.de/phpunit-4.8.36.phar
curl -fsSL --connect-timeout 30 --max-time 60 \
  -o "$TMPDIR_SUITE/phpunit-4.8.36.phar.asc" https://phar.phpunit.de/phpunit-4.8.36.phar.asc
EXPECTED_SHA="ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e"
ACTUAL_SHA="$(sha256sum "$TMPDIR_SUITE/phpunit-4.8.36.phar" | awk '{print $1}')"
if [[ "$ACTUAL_SHA" != "$EXPECTED_SHA" ]]; then
  echo "PHPUnit PHAR sha256 mismatch" >&2
  exit 1
fi
export GNUPGHOME="$(mktemp -d)"
chmod 0700 "$GNUPGHOME"
gpg --keyserver hkps://keys.openpgp.org --recv-keys 4AA394086372C20A >/dev/null 2>&1
if ! gpg --status-fd 1 --verify "$TMPDIR_SUITE/phpunit-4.8.36.phar.asc" "$TMPDIR_SUITE/phpunit-4.8.36.phar" 2>/dev/null | grep -q "VALIDSIG D8406D0D82947747293778314AA394086372C20A"; then
  echo "PHPUnit PHAR signature verification failed" >&2
  exit 1
fi
rm -rf "$GNUPGHOME"

"${DOCKER_CMD[@]}" run --rm \
  --network "container:${DB_CONTAINER}" \
  -e REPO_ROOT="$REPO_ROOT" \
  -e STATS_DB_HOST=127.0.0.1 \
  -e STATS_DB_PORT=3306 \
  -e STATS_DB_NAME=statsproduct \
  -e STATS_DB_USER=root \
  -e STATS_DB_PASSWORD=statsproduct \
  -e TMPDIR_SUITE="$TMPDIR_SUITE" \
  -v "$REPO_ROOT:$REPO_ROOT:ro" \
  -v "$TMPDIR_SUITE:$TMPDIR_SUITE:rw" \
  -w "$REPO_ROOT" \
  "$PHP_IMAGE" \
  bash "$REPO_ROOT/tests/run-suite-inner.sh"
