<?php

class GuardTest extends StatsproductTestCase
{
    private function createGuardWrapper($includePath)
    {
        $wrapper = tempnam(sys_get_temp_dir(), 'guard');
        $contents = '<?php' . "\n"
            . 'error_reporting(-1);' . "\n"
            . 'ini_set("display_errors", 1);' . "\n"
            . 'ini_set("display_startup_errors", 1);' . "\n"
            . 'ini_set("log_errors", 0);' . "\n"
            . 'include ' . var_export($includePath, true) . ';' . "\n"
            . 'echo "FELL_THROUGH";' . "\n";
        file_put_contents($wrapper, $contents);

        return $wrapper;
    }

    private function assertGuardedIncludeExitsCleanly($relativePath)
    {
        $repoRoot = dirname(__DIR__);
        $target = $repoRoot . '/' . ltrim($relativePath, '/');
        $wrapper = $this->createGuardWrapper($target);
        try {
            $descriptors = array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w'),
            );
            $command = escapeshellarg(PHP_BINARY)
                . ' -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d log_errors=0 '
                . escapeshellarg($wrapper);
            $process = proc_open($command, $descriptors, $pipes);
            $this->assertTrue(is_resource($process));
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            $this->assertSame(0, $exitCode);
            $this->assertNoPhpDiagnostics($stdout);
            $this->assertNoPhpDiagnostics($stderr);
            $this->assertNotContains('FELL_THROUGH', $stdout);
        } finally {
            if (is_file($wrapper)) {
                unlink($wrapper);
            }
        }
    }

    private function assertNoPhpDiagnostics($output)
    {
        $patterns = array(
            'Notice:',
            'Warning:',
            'Deprecated:',
            'Strict Standards:',
            'Parse error:',
            'Fatal error:',
        );
        foreach ($patterns as $pattern) {
            $this->assertNotContains($pattern, $output, 'Unexpected diagnostic: ' . $pattern);
        }
    }

    public function testModuleFileExitsWhenPsVersionConstantMissing()
    {
        $repoRoot = dirname(__DIR__);
        $moduleFile = $repoRoot . '/statsproduct.php';
        $wrapper = tempnam(sys_get_temp_dir(), 'guard');
        $contents = '<?php' . "\n"
            . 'error_reporting(-1);' . "\n"
            . 'ini_set("display_errors", 1);' . "\n"
            . 'ini_set("display_startup_errors", 1);' . "\n"
            . 'ini_set("log_errors", 0);' . "\n"
            . 'include ' . var_export($moduleFile, true) . ';' . "\n"
            . 'echo "FELL_THROUGH";' . "\n";
        file_put_contents($wrapper, $contents);
        try {
            $descriptors = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
            $command = escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 ' . escapeshellarg($wrapper);
            $process = proc_open($command, $descriptors, $pipes);
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
            $this->assertSame(0, $exitCode);
            $this->assertNoPhpDiagnostics($stdout);
            $this->assertNoPhpDiagnostics($stderr);
            $this->assertNotContains('FELL_THROUGH', $stdout);
        } finally {
            if (is_file($wrapper)) {
                unlink($wrapper);
            }
        }
    }

    public function testRootIndexExitsCleanly()
    {
        $this->assertGuardedIncludeExitsCleanly('index.php');
    }

    public function testNestedTestsIndexExitsCleanly()
    {
        $this->assertGuardedIncludeExitsCleanly('tests/index.php');
    }

    public function testPhpstanIndexExitsCleanly()
    {
        $this->assertGuardedIncludeExitsCleanly('tests/phpstan/index.php');
    }

    public function testTranslationsIndexExitsCleanly()
    {
        $this->assertGuardedIncludeExitsCleanly('translations/index.php');
    }

    public function testUpgradeIndexExitsCleanly()
    {
        $this->assertGuardedIncludeExitsCleanly('upgrade/index.php');
    }
}
