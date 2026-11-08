<?php

class HookTest extends StatsproductTestCase
{
    private function seedAnchorProduct($withAttributes = false)
    {
        StatsFixture::insertProduct(10, 'REF-10', Product::STATE_SAVED);
        StatsFixture::insertProductLang(10, 1, 1, 'Anchor');
        if ($withAttributes) {
            StatsFixture::insertProductAttribute(9, 10);
        }
    }

    public function testCatalogPageListsCategoriesProductsAndEscapedExportLink()
    {
        StatsFixture::insertCategory(1, 0, 1, 'Home', 0);
        StatsFixture::insertCategory(2, 0, 1, 'Summer', 1);
        StatsFixture::insertCategory(3, 0, 0, 'Hidden', 2);
        StatsFixture::insertProduct(2, 'REF-B', Product::STATE_SAVED);
        StatsFixture::insertProductLang(2, 1, 1, 'Bravo');
        StatsFixture::insertProduct(1, 'REF-A', Product::STATE_SAVED);
        StatsFixture::insertProductLang(1, 1, 1, 'Alpha');
        StatsFixture::insertStock(1, 0, 1, 0, 8);
        $_SERVER['REQUEST_URI'] = '/admin/index.php?controller=AdminStats&token=abc"';

        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertContains('Product details', $html);
        $this->assertContains('Products available', $html);
        $this->assertContains('value="0"', $html);
        $this->assertContains('Home', $html);
        $this->assertContains('Summer', $html);
        $this->assertNotContains('Hidden', $html);
        $this->assertContains('REF-A', $html);
        $this->assertContains('Alpha', $html);
        $this->assertContains('8', $html);
        $this->assertContains('REF-B', $html);
        $this->assertContains('Bravo', $html);
        $this->assertTrue(strpos($html, 'Alpha') < strpos($html, 'Bravo'));
        $this->assertContains('controller=AdminStats', $html);
        $this->assertContains('module=statsproduct', $html);
        $this->assertContains('id_product=1', $html);
        $this->assertContains('token=abc&quot;&amp;export=1', $html);
    }

    public function testCatalogSelectsTheRequestedCategory()
    {
        StatsFixture::insertCategory(1, 0, 1, 'Home', 0);
        StatsFixture::insertCategory(2, 0, 1, 'Summer', 1);
        $_GET['id_category'] = '2';
        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertContains('value="2" selected="selected">Summer', $html);
        $this->assertContains('value="1">Home', $html);
    }

    public function testProductPageShowsTotalsConversionAndLineGraph()
    {
        $this->seedAnchorProduct(false);
        StatsFixture::insertOrder(15, 1, 7, 1, '2019-06-15 14:05:00');
        StatsFixture::insertOrderDetail(1, 15, 10, 0, 'Anchor - Blue', 2, 4.25, 7.10);
        StatsFixture::insertPageView(10, 1, '2019-06-10 00:00:00', '2019-06-10 23:59:59', 4);
        StatsFixture::insertPageView(10, 1, '2019-06-11 00:00:00', '2019-06-11 23:59:59', 4);
        $_GET['id_product'] = '10';
        $_SERVER['REQUEST_URI'] = '/admin/index.php?controller=AdminStats&token=abc"';

        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertContains('Anchor - Details', $html);
        $this->assertContains('Total bought 2', $html);
        $this->assertContains('Sales (tax excluded) 7.10 EUR', $html);
        $this->assertContains('Total Viewed 8', $html);
        $this->assertContains('Conversion rate 0.25', $html);
        $this->assertContains('graph-engine', $html);
        $this->assertSame(array('layers' => 2, 'type' => 'line', 'option' => '1-10'), $this->probe->lastEngineParams);
        $this->assertContains('2019-06-15 14:05:00', $html);
        $this->assertContains('controller=AdminOrders', $html);
        $this->assertContains('id_order=15', $html);
        $this->assertContains('controller=AdminCustomers', $html);
        $this->assertContains('id_customer=7', $html);
        $this->assertContains('8.50 EUR', $html);
        $this->assertNotContains('Products available', $html);
        $this->assertContains('token=abc&quot;&export=1&exportType=1', $html);
    }

    public function testConversionRateIsZeroWhenProductWasNotViewed()
    {
        $this->seedAnchorProduct(false);
        StatsFixture::insertOrder(1, 1, 1, 1, '2019-06-15 10:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor', 2, 1, 2);
        $_GET['id_product'] = '10';
        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertContains('Conversion rate 0.00', $html);
    }

    public function testAttributeSectionRequiresBothAttributesAndPurchases()
    {
        $this->seedAnchorProduct(true);
        StatsFixture::insertOrder(1, 1, 1, 1, '2019-06-15 10:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 9, 'Anchor - Blue', 2, 1, 2);
        $_GET['id_product'] = '10';
        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertContains('Attribute sales distribution', $html);
        $this->assertContains('Anchor - Blue', $html);
        $this->assertSame(2, $this->probe->engineCallCount);
        $this->assertSame(array('type' => 'pie', 'option' => '3-10'), $this->probe->lastEngineParams);

        StatsFixture::truncateAll();
        StatsFixture::resetRequestState();
        $this->probe = $this->newProbe();
        $this->seedAnchorProduct(true);
        $_GET['id_product'] = '10';
        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertNotContains('Attribute sales distribution', $html);
        $this->assertContains('Total bought 0', $html);
    }

    public function testPlainProductOmitsAttributeColumn()
    {
        $this->seedAnchorProduct(false);
        StatsFixture::insertOrder(1, 1, 1, 1, '2019-06-15 10:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor - Blue', 2, 1, 2);
        $_GET['id_product'] = '10';
        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertNotContains('Attribute sales distribution', $html);
        $this->assertNotContains('Anchor - Blue', $html);
        $this->assertContains('>2<', $html);
    }

    public function testSalesTableHeightGrowsWithRows()
    {
        $this->seedAnchorProduct(false);
        StatsFixture::insertOrder(1, 1, 1, 1, '2019-06-15 10:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor', 1, 1, 1);
        $_GET['id_product'] = '10';
        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertContains('height:64px', $html);
    }

    public function testSalesTableHeightCapsAtFourHundredPixels()
    {
        $this->seedAnchorProduct(false);
        $detailId = 1;
        for ($i = 1; $i <= 13; ++$i) {
            StatsFixture::insertOrder($i, 1, 1, 1, '2019-06-15 10:00:00');
            StatsFixture::insertOrderDetail($detailId, $i, 10, 0, 'Anchor', 1, 1, 1);
            ++$detailId;
        }
        $_GET['id_product'] = '10';
        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertContains('height:400px', $html);
    }

    public function testExportWithoutTypeDownloadsTheCatalog()
    {
        $_GET['export'] = '1';
        try {
            $this->probe->hookDisplayAdminStatsModules();
            $this->fail('Expected StatsproductExportInterrupt');
        } catch (StatsproductExportInterrupt $e) {
            $this->assertSame(array('layers' => 2, 'type' => 'line', 'option' => '42'), $this->probe->lastCsvExportParams);
        }
    }

    public function testExportTypeOneDownloadsPopularity()
    {
        $this->seedAnchorProduct(false);
        $_GET['id_product'] = '10';
        $_GET['export'] = '1';
        $_GET['exportType'] = '1';
        try {
            $this->probe->hookDisplayAdminStatsModules();
            $this->fail('Expected StatsproductExportInterrupt');
        } catch (StatsproductExportInterrupt $e) {
            $this->assertSame(array('layers' => 2, 'type' => 'line', 'option' => '1-10'), $this->probe->lastCsvExportParams);
        }
    }

    public function testExportTypeTwoDownloadsAttributes()
    {
        $this->seedAnchorProduct(false);
        $_GET['id_product'] = '10';
        $_GET['export'] = '1';
        $_GET['exportType'] = '2';
        try {
            $this->probe->hookDisplayAdminStatsModules();
            $this->fail('Expected StatsproductExportInterrupt');
        } catch (StatsproductExportInterrupt $e) {
            $this->assertSame(array('type' => 'pie', 'option' => '3-10'), $this->probe->lastCsvExportParams);
        }
    }

    public function testUnrecognizedExportTypeStaysOnTheProductPage()
    {
        $this->seedAnchorProduct(false);
        StatsFixture::insertOrder(1, 1, 1, 1, '2019-06-15 10:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor', 1, 1, 1);
        $_GET['id_product'] = '10';
        $_GET['export'] = '1';
        $_GET['exportType'] = '9';
        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertContains('Total bought', $html);
        $this->assertNull($this->probe->lastCsvExportParams);
    }
}
