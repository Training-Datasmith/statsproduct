<?php

class CatalogTest extends StatsproductTestCase
{
    public function testSavedProductsAreListedByNameWithAttributeZeroStock()
    {
        StatsFixture::insertProduct(2, 'REF-B', Product::STATE_SAVED);
        StatsFixture::insertProductLang(2, 1, 1, 'Bravo');
        StatsFixture::insertProduct(1, 'REF-A', Product::STATE_SAVED);
        StatsFixture::insertProductLang(1, 1, 1, 'Alpha');
        StatsFixture::insertProductLang(1, 2, 1, 'Alpha-FR');
        StatsFixture::insertStock(1, 0, 1, 0, 8);
        StatsFixture::insertStock(1, 5, 1, 0, 100);
        StatsFixture::insertProduct(3, 'REF-Z', Product::STATE_TEMP);
        StatsFixture::insertProductLang(3, 1, 1, 'Zulu');
        StatsFixture::insertProduct(4, 'REF-Y', Product::STATE_SAVED, 2);
        StatsFixture::insertProductLang(4, 1, 2, 'Yankee');

        $this->probe->setOption('42');
        $this->probe->runGetData(1);
        $values = $this->probe->exposeValues();
        $legend = $this->probe->exposeLegend();

        $this->assertSame(array('Alpha', 'Bravo'), $values[1]);
        $this->assertSame(array('REF-A', 'REF-B'), $values[0]);
        $this->assertIntegerScalar(8, $values[2][0], 'Alpha stock quantity');
        $this->assertIntegerScalar(0, $values[2][1], 'Bravo stock quantity without stock row');
        $this->assertIntegerScalar(1, $legend[0], 'legend product id Alpha');
        $this->assertIntegerScalar(2, $legend[1], 'legend product id Bravo');
    }

    public function testCategoryFilterKeepsOnlyTheRequestedCategory()
    {
        StatsFixture::insertProduct(1, 'REF-A', Product::STATE_SAVED);
        StatsFixture::insertProductLang(1, 1, 1, 'Alpha');
        StatsFixture::insertProduct(2, 'REF-B', Product::STATE_SAVED);
        StatsFixture::insertProductLang(2, 1, 1, 'Bravo');
        StatsFixture::linkCategoryProduct(2, 1);
        StatsFixture::linkCategoryProduct(3, 2);
        $_GET['id_category'] = '2';

        $this->probe->setOption('42');
        $this->probe->runGetData(1);
        $names = $this->probe->exposeValues()[1];
        $this->assertSame(array('Alpha'), $names);
    }

    public function testNonNumericCategorySuffixIsCastBeforeFiltering()
    {
        StatsFixture::insertProduct(1, 'REF-A', Product::STATE_SAVED);
        StatsFixture::insertProductLang(1, 1, 1, 'Alpha');
        StatsFixture::insertProduct(2, 'REF-B', Product::STATE_SAVED);
        StatsFixture::insertProductLang(2, 1, 1, 'Bravo');
        StatsFixture::linkCategoryProduct(2, 1);
        StatsFixture::linkCategoryProduct(3, 2);
        $_GET['id_category'] = '2abc';

        $this->probe->setOption('42');
        $this->probe->runGetData(1);
        $names = $this->probe->exposeValues()[1];
        $this->assertSame(array('Alpha'), $names);
    }
}
