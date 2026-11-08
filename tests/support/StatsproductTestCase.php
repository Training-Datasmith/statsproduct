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
}
