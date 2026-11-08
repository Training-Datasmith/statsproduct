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
        $this->assertIntegerScalar(1, $values[0][0], 'hour 0 sales bucket');
        $this->assertIntegerScalar(4, $values[0][14], 'hour 14 sales bucket');
        $this->assertIntegerScalar(0, $values[0][1], 'hour 1 sales bucket');
        $this->assertDecimalScalarEquals(2.0, $values[1][14], 0.001, 'hour 14 visits bucket (x100 scaled)');
        $this->assertIntegerScalar(0, $values[1][0], 'hour 0 visits bucket');
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
        $this->assertIntegerScalar(2, $values[0][6], 'day 6 sales bucket');
        $this->assertIntegerScalar(5, $values[0][15], 'day 15 sales bucket');
        $this->assertIntegerScalar(0, $values[0][1], 'day 1 sales bucket');
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
        $this->assertIntegerScalar(6, $values[0][3], 'month 3 sales bucket');
        $this->assertIntegerScalar(0, $values[0][1], 'month 1 sales bucket');
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
        $this->assertIntegerScalar(4, $values[0][2017], 'year 2017 sales bucket');
        $this->assertIntegerScalar(1, $values[0][2019], 'year 2019 sales bucket');
        $this->assertIntegerScalar(0, $values[0][2016], 'year 2016 sales bucket');
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
        $this->assertIntegerScalar(5, $map['Anchor - Large'], 'attribute pie Anchor - Large qty');
        $this->assertIntegerScalar(2, $map['Anchor'], 'attribute pie Anchor qty');
        $this->assertCount(2, $map);
    }
}
