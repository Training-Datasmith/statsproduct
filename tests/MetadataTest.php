<?php

class MetadataTest extends StatsproductTestCase
{
    public function testConstructorPublishesModuleIdentity()
    {
        $module = new statsproduct();
        $this->assertSame('statsproduct', $module->name);
        $this->assertSame('administration', $module->tab);
        $this->assertSame('2.1.4', $module->version);
        $this->assertSame('PrestaShop', $module->author);
        $this->assertSame(0, $module->need_instance);
        $this->assertInternalType('integer', $module->need_instance);
        $this->assertSame('Product details', $module->displayName);
        $this->assertSame('Enrich your stats, add detailed statistics for each product of your catalog.', $module->description);
        $this->assertSame('1.7.6.0', $module->ps_versions_compliancy['min']);
        $this->assertSame('1.7.6.9', $module->ps_versions_compliancy['max']);
    }

    public function testConfigXmlNameAndVersionMatchConstructor()
    {
        $module = new statsproduct();
        $xml = simplexml_load_file(dirname(__DIR__) . '/config.xml');
        $this->assertSame((string) $module->name, (string) $xml->name);
        $this->assertSame((string) $module->version, (string) $xml->version);
    }

    public function testInstallRegistersDisplayAdminStatsModules()
    {
        Module::resetModuleTestState();
        $module = new statsproduct();
        $this->assertTrue($module->install());
        $this->assertSame(array('displayAdminStatsModules'), Module::$registeredHooks);
    }

    public function testInstallAbortsWhenParentInstallFails()
    {
        Module::resetModuleTestState();
        Module::$parentInstallResult = false;
        $module = new statsproduct();
        $this->assertFalse($module->install());
        $this->assertSame(array(), Module::$registeredHooks);
    }

    public function testInstallAbortsWhenHookRegistrationFails()
    {
        Module::resetModuleTestState();
        Module::$registerHookResult = false;
        $module = new statsproduct();
        $this->assertFalse($module->install());
    }

    public function testUpgrade211MovesLegacyHook()
    {
        Module::resetModuleTestState();
        require_once dirname(__DIR__) . '/upgrade/upgrade-2.1.1.php';
        $module = new statsproduct();
        $this->assertTrue(upgrade_module_2_1_1($module));
        $this->assertSame(array('AdminStatsModules'), Module::$unregisteredHooks);
        $this->assertSame(array('displayAdminStatsModules'), Module::$registeredHooks);
    }
}
