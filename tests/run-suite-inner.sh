#!/usr/bin/env bash
set -euo pipefail

PHP_VERSION="$(php -r 'echo PHP_VERSION;')"
if [[ "$PHP_VERSION" != "5.6.40" ]]; then
  echo "Unexpected PHP version: $PHP_VERSION" >&2
  exit 1
fi
php -m | grep -qi pdo_mysql

if [[ -z "${REPO_ROOT:-}" ]]; then
  echo "REPO_ROOT is not set" >&2
  exit 1
fi
if [[ -z "${TMPDIR_SUITE:-}" ]]; then
  echo "TMPDIR_SUITE is not set" >&2
  exit 1
fi

WORK_TMP="$TMPDIR_SUITE"
PHPUNIT_PHAR="$WORK_TMP/phpunit-4.8.36.phar"
if [[ ! -f "$PHPUNIT_PHAR" ]]; then
  echo "Missing verified PHPUnit PHAR at $PHPUNIT_PHAR" >&2
  exit 1
fi

php "$REPO_ROOT/tests/support/drift-check.php" "$REPO_ROOT"

mapfile -t TEST_FILES < <(php -r '
$m = include $argv[1];
$root = $argv[2];
foreach ($m["files"] as $f) {
    echo $root . "/" . $f, "\n";
}
' "$REPO_ROOT/tests/support/test-manifest.php" "$REPO_ROOT")
EXPECTED_COUNT=40
if [[ "${#TEST_FILES[@]}" -ne 7 ]]; then
  echo "Unexpected test file count" >&2
  exit 1
fi

if [[ "${#TEST_FILES[@]}" -ne 7 ]]; then
  echo "Expected 7 test files from manifest, got ${#TEST_FILES[@]}" >&2
  exit 1
fi
printf '%s\n' "${TEST_FILES[@]}" > "$WORK_TMP/test-files.txt"
php -r 'srand(20261108); $path = $argv[1]; $files = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); shuffle($files); echo implode("\n", $files) . "\n";' "$WORK_TMP/test-files.txt" > "$WORK_TMP/shuffled-tests.txt"
mapfile -t SHUFFLED_FILES < "$WORK_TMP/shuffled-tests.txt"
if [[ "${#SHUFFLED_FILES[@]}" -ne 7 ]]; then
  echo "Expected 7 shuffled test files, got ${#SHUFFLED_FILES[@]}" >&2
  exit 1
fi

PHPUNIT_XML="$WORK_TMP/phpunit.xml"
BOOTSTRAP="$REPO_ROOT/tests/bootstrap.php"
{
  echo '<?xml version="1.0" encoding="UTF-8"?>'
  echo "<phpunit bootstrap=\"$BOOTSTRAP\" backupGlobals=\"false\" convertErrorsToExceptions=\"true\" convertNoticesToExceptions=\"true\" convertWarningsToExceptions=\"true\">"
  echo '  <testsuites>'
  echo '    <testsuite name="module-tests">'
  while IFS= read -r file; do
    if [[ -n "$file" ]]; then
      echo "      <file>$file</file>"
    fi
  done < "$WORK_TMP/shuffled-tests.txt"
  echo '    </testsuite>'
  echo '  </testsuites>'
  echo '</phpunit>'
} > "$PHPUNIT_XML"

set +e
OUTPUT="$(php -d error_reporting=-1 "$PHPUNIT_PHAR" -c "$PHPUNIT_XML" 2>&1)"
STATUS=$?
set -e
echo "$OUTPUT"

if [[ "$STATUS" -ne 0 ]]; then
  exit "$STATUS"
fi
if echo "$OUTPUT" | grep -q 'There were incomplete tests\|There were skipped tests\|There were risky tests'; then
  echo 'PHPUnit reported skipped/incomplete/risky tests' >&2
  exit 1
fi
if echo "$OUTPUT" | grep -Eq 'Notice:|Warning:|Deprecated:|Strict Standards:|Parse error:|Fatal error:'; then
  echo 'PHPUnit output contained PHP diagnostics' >&2
  exit 1
fi
if ! echo "$OUTPUT" | grep -Eq "^OK \\(${EXPECTED_COUNT} tests, [0-9]+ assertions\\)\$"; then
  echo "Expected OK (${EXPECTED_COUNT} tests, N assertions)" >&2
  exit 1
fi
