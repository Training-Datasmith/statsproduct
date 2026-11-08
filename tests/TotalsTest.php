<?php

class TotalsTest extends StatsproductTestCase
{
    public function testGetTotalBoughtCountsOnlyValidCurrentShopRowsInsideTheWindow()
    {
        StatsFixture::insertOrder(1, 1, 1, 1, '2019-06-15 10:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor', 2, 1, 2);
        StatsFixture::insertOrder(2, 1, 1, 1, '2019-06-15 11:00:00');
        StatsFixture::insertOrderDetail(2, 2, 10, 0, 'Anchor', 3, 1, 3);
        StatsFixture::insertOrder(3, 1, 2, 0, '2019-06-15 12:00:00');
        StatsFixture::insertOrderDetail(3, 3, 10, 0, 'Anchor', 9, 1, 9);
        StatsFixture::insertOrder(4, 2, 3, 1, '2019-06-15 12:00:00');
        StatsFixture::insertOrderDetail(4, 4, 10, 0, 'Anchor', 100, 1, 100);
        StatsFixture::insertOrder(5, 1, 4, 1, '2019-06-15 12:00:00');
        StatsFixture::insertOrderDetail(5, 5, 11, 0, 'Other', 40, 1, 40);
        StatsFixture::insertOrder(6, 1, 5, 1, '2019-05-01 10:00:00');
        StatsFixture::insertOrderDetail(6, 6, 10, 0, 'Anchor', 7, 1, 7);

        $total = $this->probe->getTotalBought(10);
        $this->assertInternalType('int', $total);
        $this->assertSame(5, $total);
    }

    public function testGetTotalBoughtIsZeroWhenNoRowsMatch()
    {
        $this->assertSame(0, $this->probe->getTotalBought(10));
    }

    public function testGetTotalSalesSumsTaxExcludedLineTotals()
    {
        StatsFixture::insertOrder(1, 1, 1, 1, '2019-06-15 10:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor', 2, 4.25, 7.10);
        StatsFixture::insertOrder(2, 1, 1, 1, '2019-06-16 10:00:00');
        StatsFixture::insertOrderDetail(2, 2, 10, 0, 'Anchor', 1, 1.00, 0.40);
        StatsFixture::insertOrder(3, 1, 2, 0, '2019-06-15 12:00:00');
        StatsFixture::insertOrderDetail(3, 3, 10, 0, 'Anchor', 1, 1, 100);
        StatsFixture::insertOrder(4, 2, 3, 1, '2019-06-15 12:00:00');
        StatsFixture::insertOrderDetail(4, 4, 10, 0, 'Anchor', 1, 1, 100);
        StatsFixture::insertOrder(5, 1, 4, 1, '2019-06-15 12:00:00');
        StatsFixture::insertOrderDetail(5, 5, 11, 0, 'Other', 1, 1, 100);
        StatsFixture::insertOrder(6, 1, 5, 1, '2019-05-01 10:00:00');
        StatsFixture::insertOrderDetail(6, 6, 10, 0, 'Anchor', 1, 1, 100);

        $total = $this->probe->getTotalSales(10);
        $this->assertInternalType('float', $total);
        $this->assertEquals(7.50, $total, '', 0.001);
    }

    public function testGetTotalSalesIsZeroWhenNoRowsMatch()
    {
        $total = $this->probe->getTotalSales(10);
        $this->assertInternalType('float', $total);
        $this->assertEquals(0.0, $total, '', 0.001);
    }

    public function testGetTotalViewedCountsFullyContainedProductPageRanges()
    {
        StatsFixture::insertPageView(10, 1, '2019-06-10 00:00:00', '2019-06-10 23:59:59', 4);
        StatsFixture::insertPageView(10, 1, '2019-06-11 00:00:00', '2019-06-11 23:59:59', 8);
        StatsFixture::insertPageView(10, 1, '2019-05-31 00:00:00', '2019-06-02 23:59:59', 999);
        StatsFixture::insertCategoryPageView(99, 1, '2019-06-10 00:00:00', '2019-06-10 23:59:59', 50);
        StatsFixture::insertPageView(10, 2, '2019-06-10 00:00:00', '2019-06-10 23:59:59', 70);
        StatsFixture::insertPageView(11, 1, '2019-06-10 00:00:00', '2019-06-10 23:59:59', 30);

        $viewed = $this->probe->getTotalViewed(10);
        $this->assertTrue(is_numeric($viewed));
        $this->assertEquals(12, (int) $viewed);
    }

    public function testGetTotalViewedIsZeroWhenNoRowsMatch()
    {
        $this->assertSame(0, $this->probe->getTotalViewed(10));
    }
}
