<?php

date_default_timezone_set('UTC');

if (!defined('PHP_BINARY')) {
    define('PHP_BINARY', PHP_BINDIR . '/php');
}

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.6.9');
}

$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/tests/support/PrestaShopHarness.php';

require_once $repoRoot . '/statsproduct.php';
require_once $repoRoot . '/tests/support/StatsproductProbe.php';
require_once $repoRoot . '/tests/support/StatsproductTestCase.php';

StatsFixture::installSchema();
