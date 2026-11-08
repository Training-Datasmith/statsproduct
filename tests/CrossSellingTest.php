<?php

class CrossSellingTest extends StatsproductTestCase
{
    private function seedProduct($id, $name, $reference = 'REF')
    {
        StatsFixture::insertProduct($id, $reference . $id, Product::STATE_SAVED);
        StatsFixture::insertProductLang($id, 1, 1, $name);
    }

  public function testCrossSellingAggregatesOtherProductsBoughtByThoseCustomers()
    {
        $this->seedProduct(10, 'Anchor', 'ANC-');
        $this->seedProduct(20, 'Bravo', 'BRV-');
        $this->seedProduct(21, 'Cedar', 'CED-');
        StatsFixture::insertProductLang(21, 2, 1, 'Cedro');
        $this->seedProduct(22, 'Delta', 'DLT-');
        $this->seedProduct(23, 'Echo', 'ECH-');
        $this->seedProduct(24, 'Foxtrot', 'FOX-');
        $this->seedProduct(25, 'Golf', 'GLF-');

        StatsFixture::insertOrder(1, 1, 1, 1, '2019-06-10 10:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor', 1, 10, 10);
        StatsFixture::insertOrder(2, 1, 1, 1, '2019-06-10 11:00:00');
        StatsFixture::insertOrderDetail(2, 2, 20, 0, 'Bravo', 1, 10, 10);
        StatsFixture::insertOrder(3, 1, 1, 1, '2019-06-10 12:00:00');
        StatsFixture::insertOrderDetail(3, 3, 20, 0, 'Bravo', 2, 40, 80);
        StatsFixture::insertOrder(4, 1, 1, 1, '2019-06-11 10:00:00');
        StatsFixture::insertOrderDetail(4, 4, 21, 0, 'Cedar', 9, 5, 45);
        StatsFixture::insertOrder(5, 1, 1, 1, '2019-06-12 10:00:00');
        StatsFixture::insertOrderDetail(5, 5, 10, 0, 'Anchor', 50, 1, 50);
        StatsFixture::insertOrder(6, 1, 1, 0, '2019-06-12 11:00:00');
        StatsFixture::insertOrderDetail(6, 6, 23, 0, 'Echo', 1, 1, 1);
        StatsFixture::insertOrder(7, 1, 1, 1, '2019-05-01 10:00:00');
        StatsFixture::insertOrderDetail(7, 7, 24, 0, 'Foxtrot', 1, 1, 1);
        StatsFixture::insertOrder(8, 1, 2, 1, '2019-06-12 10:00:00');
        StatsFixture::insertOrderDetail(8, 8, 22, 0, 'Delta', 1, 1, 1);
        StatsFixture::insertOrder(9, 2, 3, 1, '2019-06-12 10:00:00');
        StatsFixture::insertOrderDetail(9, 9, 10, 0, 'Anchor', 1, 1, 1);
        StatsFixture::insertOrder(10, 2, 3, 1, '2019-06-12 11:00:00');
        StatsFixture::insertOrderDetail(10, 10, 25, 0, 'Golf', 1, 1, 1);

        $_GET['id_product'] = '10';
        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertContains('Cross selling', $html);
        $cedarPos = strpos($html, 'Cedar');
        $bravoPos = strpos($html, 'Bravo');
        $this->assertNotFalse($cedarPos);
        $this->assertNotFalse($bravoPos);
        $this->assertTrue($cedarPos < $bravoPos);
        $this->assertContains('9', $html);
        $this->assertContains('3', $html);
        $this->assertContains('25.00 EUR', $html);
        $this->assertContains('5.00 EUR', $html);
        $this->assertNotContains('Cedro', $html);
        $this->assertNotContains('Delta', $html);
        $this->assertNotContains('Echo', $html);
        $this->assertNotContains('Foxtrot', $html);
        $this->assertNotContains('Golf', $html);
    }

    public function testCrossSellingIgnoresCustomersWhoBoughtTheProductOnlyInAnotherShop()
    {
        $this->seedProduct(10, 'Anchor', 'ANC-');
        $this->seedProduct(26, 'Hotel', 'HTL-');
        $this->seedProduct(27, 'India', 'IND-');

        StatsFixture::insertOrder(1, 1, 7, 1, '2019-06-10 10:00:00');
        StatsFixture::insertOrderDetail(1, 1, 10, 0, 'Anchor', 1, 1, 1);
        StatsFixture::insertOrder(2, 1, 7, 1, '2019-06-10 11:00:00');
        StatsFixture::insertOrderDetail(2, 2, 26, 0, 'Hotel', 4, 5, 20);
        StatsFixture::insertOrder(3, 2, 5, 1, '2019-06-10 12:00:00');
        StatsFixture::insertOrderDetail(3, 3, 10, 0, 'Anchor', 1, 1, 1);
        StatsFixture::insertOrder(4, 1, 5, 1, '2019-06-10 13:00:00');
        StatsFixture::insertOrderDetail(4, 4, 27, 0, 'India', 6, 2, 12);

        $_GET['id_product'] = '10';
        $html = $this->probe->hookDisplayAdminStatsModules();
        $this->assertContains('Hotel', $html);
        $this->assertNotContains('India', $html);
    }
}
