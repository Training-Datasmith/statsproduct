<?php

class GraphTest extends StatsproductTestCase
{
    public function testSameDayWindowBucketsHoursAndScalesVisits()
    {
        $this->probe->setEmployeeStatsWindow('2019-06-15', '2019-06-15');

        StatsFixture::insertOrder(1, 1, 1, 1, '2019-06-15 00:30:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor', 1, 1, 1);
        StatsFixture::insertOrder(2, 1, 1, 1, '2019-06-15 14:05:00');
        StatsFixture::insertOrderDetail(2, 2, 10, 0, 'Anchor', 4, 1, 4);
        StatsFixture::insertOrder(3, 1, 2, 0, '2019-06-15 14:05:00');
        StatsFixture::insertOrderDetail(3, 3, 10, 0, 'Anchor', 9, 1, 9);
        StatsFixture::insertOrder(4, 2, 3, 1, '2019-06-15 14:05:00');
        StatsFixture::insertOrderDetail(4, 4, 10, 0, 'Anchor', 8, 1, 8);
        StatsFixture::insertPageView(10, 1, '2019-06-15 14:00:00', '2019-06-15 14:59:59', 200);

        $this->probe->resetGraphState();
        $this->probe->setOption('1-10');
        $this->probe->runGetData(2);
        $values = $this->probe->exposeValues();
        $this->assertSame(1, (int) $values[0][0]);
        $this->assertSame(4, (int) $values[0][14]);
        $this->assertSame(0, (int) $values[0][1]);
        $this->assertEquals(2.0, (float) $values[1][14], '', 0.001);
        $this->assertSame(0, (int) $values[1][0]);
    }

    public function testMonthWindowSumsQuantitiesOntoTheDayOfMonth()
    {
        StatsFixture::insertOrder(1, 1, 1, 1, '2019-06-06 09:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor', 2, 1, 2);
        StatsFixture::insertOrder(2, 1, 1, 1, '2019-06-15 10:00:00');
        StatsFixture::insertOrderDetail(2, 2, 10, 0, 'Anchor', 3, 1, 3);
        StatsFixture::insertOrder(3, 1, 1, 1, '2019-06-15 18:00:00');
        StatsFixture::insertOrderDetail(3, 3, 10, 0, 'Anchor', 2, 1, 2);
        StatsFixture::insertOrder(4, 1, 2, 1, '2019-05-31 10:00:00');
        StatsFixture::insertOrderDetail(4, 4, 10, 0, 'Anchor', 7, 1, 7);
        StatsFixture::insertOrder(5, 1, 3, 0, '2019-06-15 10:00:00');
        StatsFixture::insertOrderDetail(5, 5, 10, 0, 'Anchor', 9, 1, 9);
        StatsFixture::insertOrder(6, 2, 4, 1, '2019-06-15 10:00:00');
        StatsFixture::insertOrderDetail(6, 6, 10, 0, 'Anchor', 8, 1, 8);
        StatsFixture::insertOrder(7, 1, 5, 1, '2019-06-15 10:00:00');
        StatsFixture::insertOrderDetail(7, 7, 11, 0, 'Other', 4, 1, 4);

        $this->probe->resetGraphState();
        $this->probe->setOption('1-10');
        $this->probe->runGetData(2);
        $values = $this->probe->exposeValues();
        $this->assertSame(2, (int) $values[0][6]);
        $this->assertSame(5, (int) $values[0][15]);
        $this->assertSame(0, (int) $values[0][1]);
    }

    public function testSubYearWindowBucketsByMonth()
    {
        $this->probe->setEmployeeStatsWindow('2019-01-15', '2019-08-15');

        StatsFixture::insertOrder(1, 1, 1, 1, '2019-03-02 12:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor', 6, 1, 6);

        $this->probe->resetGraphState();
        $this->probe->setOption('1-10');
        $this->probe->runGetData(2);
        $values = $this->probe->exposeValues();
        $this->assertSame(6, (int) $values[0][3]);
        $this->assertSame(0, (int) $values[0][1]);
    }

    public function testMultiYearWindowBucketsByYear()
    {
        $this->probe->setEmployeeStatsWindow('2016-01-01', '2019-06-30');

        StatsFixture::insertOrder(1, 1, 1, 1, '2017-04-01 12:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor', 4, 1, 4);
        StatsFixture::insertOrder(2, 1, 1, 1, '2019-02-01 12:00:00');
        StatsFixture::insertOrderDetail(2, 2, 10, 0, 'Anchor', 1, 1, 1);

        $this->probe->resetGraphState();
        $this->probe->setOption('1-10');
        $this->probe->runGetData(2);
        $values = $this->probe->exposeValues();
        $this->assertSame(4, (int) $values[0][2017]);
        $this->assertSame(1, (int) $values[0][2019]);
        $this->assertSame(0, (int) $values[0][2016]);
    }

    public function testAttributePieUsesSnapshotNamesAndQuantities()
    {
        StatsFixture::insertOrder(1, 1, 1, 1, '2019-06-10 12:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 9, 'Anchor - Large', 5, 5, 25);
        StatsFixture::insertOrder(2, 1, 1, 1, '2019-06-11 12:00:00');
        StatsFixture::insertOrderDetail(2, 2, 10, 0, 'Anchor', 2, 2, 4);
        StatsFixture::insertOrder(3, 1, 2, 0, '2019-06-12 12:00:00');
        StatsFixture::insertOrderDetail(3, 3, 10, 0, 'Nope', 9, 1, 9);
        StatsFixture::insertOrder(4, 2, 3, 1, '2019-06-12 12:00:00');
        StatsFixture::insertOrderDetail(4, 4, 10, 0, 'OtherShop', 8, 1, 8);
        StatsFixture::insertOrder(5, 1, 4, 1, '2019-06-12 12:00:00');
        StatsFixture::insertOrderDetail(5, 5, 11, 0, 'OtherProduct', 4, 1, 4);

        $this->probe->resetGraphState();
        $this->probe->setOption('3-10');
        $this->probe->runGetData(1);
        $map = array();
        foreach ($this->probe->exposeLegend() as $idx => $label) {
            $map[$label] = $this->probe->exposeValues()[$idx];
        }
        $this->assertSame(5, (int) $map['Anchor - Large']);
        $this->assertSame(2, (int) $map['Anchor']);
        $this->assertCount(2, $map);
    }
}
