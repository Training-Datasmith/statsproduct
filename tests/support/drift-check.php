<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: drift-check.php <repo-root>\n");
    exit(1);
}

$repoRoot = rtrim($argv[1], '/');
$manifestPath = $repoRoot . '/tests/support/test-manifest.php';
$manifest = include $manifestPath;

$expectedFiles = $manifest['files'];
sort($expectedFiles);

$actualFiles = array();
foreach ($expectedFiles as $relative) {
    $absolute = $repoRoot . '/' . $relative;
    if (!is_file($absolute)) {
        fwrite(STDERR, "Missing test file: $relative\n");
        exit(1);
    }
    $actualFiles[] = $relative;
}

$expectedMethods = $manifest['methods'];
$foundMethods = array();

foreach ($expectedFiles as $relative) {
    $contents = file_get_contents($repoRoot . '/' . $relative);
    if (!preg_match_all('/function\s+(test[A-Za-z0-9_]+)\s*\(/', $contents, $matches)) {
        continue;
    }
    foreach ($matches[1] as $method) {
        $foundMethods[] = $method;
    }
}

sort($expectedMethods);
sort($foundMethods);

if ($expectedMethods !== $foundMethods) {
    fwrite(STDERR, "Test method drift detected.\n");
    fwrite(STDERR, 'Expected: ' . implode(', ', $expectedMethods) . "\n");
    fwrite(STDERR, 'Found: ' . implode(', ', $foundMethods) . "\n");
    exit(1);
}

if (count($foundMethods) !== 40) {
    fwrite(STDERR, "Expected 40 tests, found " . count($foundMethods) . "\n");
    exit(1);
}

echo "Drift check passed (40 tests).\n";
