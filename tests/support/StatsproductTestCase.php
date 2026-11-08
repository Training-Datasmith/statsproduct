<?php

class StatsproductTestCase extends PHPUnit_Framework_TestCase
{
    /** @var StatsproductProbe */
    protected $probe;

    protected function setUp()
    {
        StatsFixture::truncateAll();
        StatsFixture::resetRequestState();
        Context::resetInstance();
        HarnessDb::resetInstance();
        Shop::resetTestState();
        Module::resetModuleTestState();
        StatsFixture::setStatsWindow('2019-06-01', '2019-06-30');

        $this->probe = new StatsproductProbe();
        $this->probe->setEmployee(1);
    }

    protected function newProbe()
    {
        $probe = new StatsproductProbe();
        $probe->setEmployee(1);

        return $probe;
    }

    protected function isIntegerScalar($value)
    {
        if (is_int($value)) {
            return true;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return true;
        }

        return false;
    }

    protected function assertIntegerScalar($expected, $actual, $message = '')
    {
        $this->assertTrue(
            $this->isIntegerScalar($actual),
            ($message !== '' ? $message . ': ' : '') . 'expected int or integer-only string, got ' . var_export($actual, true)
        );
        $normalized = is_int($actual) ? $actual : (int) $actual;
        $this->assertSame($expected, $normalized, $message);
    }
}
