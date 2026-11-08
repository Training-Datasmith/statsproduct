<?php

if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}
if (!defined('_PS_USE_SQL_SLAVE_')) {
    define('_PS_USE_SQL_SLAVE_', false);
}

function pSQL($string)
{
    return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $string);
}

function bqSQL($string)
{
    return str_replace('`', '\\`', $string);
}

class PrestaShopDatabaseException extends Exception
{
}

class HarnessDb
{
    /** @var HarnessDb */
    private static $instance;

    /** @var PDO */
    private $link;

    public static function resetInstance()
    {
        self::$instance = null;
    }

    public static function getInstance($slave = false)
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

  private function __construct()
    {
        $host = getenv('STATS_DB_HOST') ? getenv('STATS_DB_HOST') : '127.0.0.1';
        $port = getenv('STATS_DB_PORT') ? getenv('STATS_DB_PORT') : '3306';
        $name = getenv('STATS_DB_NAME') ? getenv('STATS_DB_NAME') : 'statsproduct';
        $user = getenv('STATS_DB_USER') ? getenv('STATS_DB_USER') : 'root';
        $pass = getenv('STATS_DB_PASSWORD') ? getenv('STATS_DB_PASSWORD') : 'statsproduct';
        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
        $this->link = new PDO($dsn, $user, $pass, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ));
        $this->link->exec("SET SESSION sql_mode = ''");
    }

    public function executeS($sql)
    {
        if (!preg_match('#^\s*\(?\s*(select|show|explain|describe|desc)\s#i', $sql)) {
            throw new PrestaShopDatabaseException('executeS() only supports select-style queries: ' . $sql);
        }

        try {
            $stmt = $this->link->query($sql);
        } catch (PDOException $e) {
            throw new PrestaShopDatabaseException($e->getMessage() . ' SQL: ' . $sql);
        }

        if ($stmt === false) {
            return array();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRow($sql)
    {
        $sql = rtrim($sql, " \t\n\r\0\x0B;") . ' LIMIT 1';
        $rows = $this->executeS($sql);
        if (empty($rows)) {
            return false;
        }

        return $rows[0];
    }

    public function getValue($sql)
    {
        $row = $this->getRow($sql);
        if ($row === false) {
            return false;
        }

        return array_shift($row);
    }

    public function getPdo()
    {
        return $this->link;
    }
}

class Db
{
    public static function getInstance($slave = false)
    {
        return HarnessDb::getInstance($slave);
    }
}

class Tools
{
    public static function getValue($key, $default = false)
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        return $default;
    }

    public static function safeOutput($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public static function displayDate($date)
    {
        return $date;
    }
}

class Combination
{
    public static function isFeatureActive()
    {
        return false;
    }
}

class StockAvailable
{
    public static function addSqlShopRestriction($sql = null, $shop = null, $alias = null)
    {
        if (!empty($alias)) {
            $alias .= '.';
        }
        $shopId = (int) Context::getContext()->shop->id;

        return ' AND ' . pSQL($alias) . 'id_shop = ' . $shopId . ' AND ' . pSQL($alias) . 'id_shop_group = 0 ';
    }
}

class Shop
{
    const CONTEXT_SHOP = 1;
    const SHARE_ORDER = 'share_order';

    private static $contextShopId = 1;
    private static $shareOrder = false;

    public static function resetTestState()
    {
        self::$contextShopId = 1;
        self::$shareOrder = false;
    }

    public static function setShareOrder($share)
    {
        self::$shareOrder = (bool) $share;
    }

    public static function getContext()
    {
        return self::CONTEXT_SHOP;
    }

    public static function getContextShopID()
    {
        return self::$contextShopId;
    }

    public static function getContextListShopID($share = false)
    {
        return array(self::$contextShopId);
    }

    public static function addSqlRestriction($share = false, $alias = null)
    {
        if ($alias) {
            $alias .= '.';
        }

        return ' AND ' . $alias . 'id_shop IN (' . (int) self::$contextShopId . ') ';
    }

    public static function addSqlRestrictionOnLang($alias = null)
    {
        $idShop = (int) Context::getContext()->shop->id;
        $prefix = $alias ? $alias . '.' : '';

        return ' AND ' . $prefix . 'id_shop = ' . $idShop . ' ';
    }

    public static function addSqlAssociation($table, $alias, $inner_join = true)
    {
        $tableAlias = $table . '_shop';

        return (($inner_join) ? ' INNER' : ' LEFT') . ' JOIN `' . _DB_PREFIX_ . $table . '_shop` ' . $tableAlias . '
            ON (' . $tableAlias . '.id_' . $table . ' = ' . $alias . '.id_' . $table . '
            AND ' . $tableAlias . '.id_shop = ' . (int) self::$contextShopId . ')';
    }
}

class Product
{
    const STATE_TEMP = 0;
    const STATE_SAVED = 1;

    public $id;
    public $name;

    public function __construct($id, $full = false, $id_lang = null)
    {
        $this->id = (int) $id;
        if ($id_lang === null) {
            $id_lang = (int) Context::getContext()->language->id;
        }
        $idShop = (int) Context::getContext()->shop->id;
        $sql = 'SELECT pl.name FROM `' . _DB_PREFIX_ . 'product_lang` pl
            WHERE pl.id_product = ' . (int) $id . '
            AND pl.id_lang = ' . (int) $id_lang . '
            AND pl.id_shop = ' . $idShop;
        $name = Db::getInstance()->getValue($sql);
        $this->name = $name ? $name : '';
    }

    public function hasAttributes()
    {
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'product_attribute` WHERE id_product = ' . (int) $this->id;

        return (int) Db::getInstance()->getValue($sql) > 0;
    }

    public static function sqlStock($product_alias, $product_attribute = 0, $inner_join = false)
    {
        $sql = (($inner_join) ? ' INNER ' : ' LEFT ')
            . 'JOIN ' . _DB_PREFIX_ . 'stock_available stock
            ON (stock.id_product = `' . bqSQL($product_alias) . '`.id_product
            AND stock.id_product_attribute = ' . (int) $product_attribute . '
            ' . StockAvailable::addSqlShopRestriction(null, null, 'stock') . ' )';

        return $sql;
    }
}

class Category
{
    public static function getCategories($id_lang, $active = true, $order = true)
    {
        $shopId = (int) Context::getContext()->shop->id;
        $activeSql = $active ? ' AND c.active = 1' : '';
        $sql = 'SELECT c.id_category, cl.name, c.id_parent
            FROM `' . _DB_PREFIX_ . 'category` c
            INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl
                ON cl.id_category = c.id_category AND cl.id_lang = ' . (int) $id_lang . ' AND cl.id_shop = ' . $shopId . '
            INNER JOIN `' . _DB_PREFIX_ . 'category_shop` cs ON cs.id_category = c.id_category AND cs.id_shop = ' . $shopId . '
            WHERE 1' . $activeSql . '
            ORDER BY cs.position ASC, cl.name ASC';
        $rows = Db::getInstance()->executeS($sql);
        if (!$order) {
            $flat = array();
            foreach ($rows as $row) {
                $flat[] = array(
                    'id_category' => (int) $row['id_category'],
                    'name' => $row['name'],
                );
            }

            return $flat;
        }

        return $rows;
    }
}

class Employee
{
    public $id;
    public $stats_date_from;
    public $stats_date_to;

    public function __construct($id)
    {
        $this->id = (int) $id;
        $this->stats_date_from = '2019-06-01';
        $this->stats_date_to = '2019-06-30';
    }
}

class Currency
{
    public $iso_code = 'EUR';
}

class HarnessLocale
{
    public function formatPrice($amount, $iso)
    {
        return number_format((float) $amount, 2, '.', '') . ' ' . $iso;
    }
}

class Link
{
    public function getAdminLink($controller, $withToken = true, $params = array(), $extra = array())
    {
        $query = array_merge(array('controller' => $controller), $extra);
        if ($withToken) {
            $query['token'] = 'test-token';
        }

        return 'index.php?' . http_build_query($query);
    }
}

class Context
{
    /** @var Context */
    private static $instance;

    public $shop;
    public $language;
    public $currency;
    public $employee;
    public $link;

    private function __construct()
    {
        $this->shop = new stdClass();
        $this->shop->id = 1;
        $this->language = new stdClass();
        $this->language->id = 1;
        $this->currency = new Currency();
        $this->employee = new Employee(1);
        $this->link = new Link();
    }

    public static function getContext()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function resetInstance()
    {
        self::$instance = null;
    }

    public function getCurrentLocale()
    {
        return new HarnessLocale();
    }
}

class Module
{
    public static $parentInstallResult = true;
    public static $registerHookResult = true;
    public static $registeredHooks = array();
    public static $unregisteredHooks = array();

    public $context;
    public $name;
    public $tab;
    public $version;
    public $author;
    public $need_instance;
    public $displayName;
    public $description;
    public $ps_versions_compliancy;

    public static function resetModuleTestState()
    {
        self::$parentInstallResult = true;
        self::$registerHookResult = true;
        self::$registeredHooks = array();
        self::$unregisteredHooks = array();
    }

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    public function install()
    {
        return self::$parentInstallResult;
    }

    public function registerHook($hookName)
    {
        self::$registeredHooks[] = $hookName;

        return self::$registerHookResult;
    }

    public function unregisterHook($hookName)
    {
        self::$unregisteredHooks[] = $hookName;

        return true;
    }

    public function trans($string, $params = array(), $domain = null)
    {
        return $string;
    }
}

abstract class ModuleGraph extends Module
{
    protected $_values = array();
    protected $_legend = array();
    protected $_titles = array('main' => null, 'x' => null, 'y' => null);
    protected $_employee;
    protected $_id_lang;

    public function setEmployee($id_employee)
    {
        $this->_employee = new Employee($id_employee);
    }

    public function setLang($id_lang)
    {
        $this->_id_lang = (int) $id_lang;
    }

    protected static function getEmployee($employee = null)
    {
        if ($employee === null || !is_object($employee)) {
            $employee = Context::getContext()->employee;
        }
        if (empty($employee->stats_date_from) || empty($employee->stats_date_to)) {
            $employee->stats_date_from = '2019-06-01';
            $employee->stats_date_to = '2019-06-30';
        }

        return $employee;
    }

    public static function getDateBetween($employee = null)
    {
        $employee = self::getEmployee($employee);

        return ' \'' . pSQL($employee->stats_date_from) . ' 00:00:00\' AND \'' . pSQL($employee->stats_date_to) . ' 23:59:59\' ';
    }

    public function getDate()
    {
        if (!is_object($this->_employee)) {
            return self::getDateBetween(null);
        }

        return self::getDateBetween($this->_employee);
    }

    protected function setDateGraph($layers, $legend = false)
    {
        $employee = self::getEmployee($this->_employee);
        $fromArray = getdate(strtotime($employee->stats_date_from));
        $toArray = getdate(strtotime($employee->stats_date_to));

        if ($employee->stats_date_from === $employee->stats_date_to) {
            if ($legend) {
                for ($i = 0; $i < 24; ++$i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = ($i % 2) ? '' : sprintf('%02dh', $i);
                }
            }
            if (is_callable(array($this, 'setDayValues'))) {
                $this->setDayValues($layers);
            }
        } elseif (strtotime($employee->stats_date_to) - strtotime($employee->stats_date_from) <= 2678400) {
            $days = array();
            if ($fromArray['mon'] == $toArray['mon']) {
                for ($i = $fromArray['mday']; $i <= $toArray['mday']; ++$i) {
                    $days[] = $i;
                }
            } else {
                $imax = (int) date('t', mktime(0, 0, 0, $fromArray['mon'], 1, $fromArray['year']));
                for ($i = $fromArray['mday']; $i <= $imax; ++$i) {
                    $days[] = $i;
                }
                for ($i = 1; $i <= $toArray['mday']; ++$i) {
                    $days[] = $i;
                }
            }
            if ($legend) {
                foreach ($days as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = ($i % 2) ? '' : sprintf('%02d', $i);
                }
            }
            if (is_callable(array($this, 'setMonthValues'))) {
                $this->setMonthValues($layers);
            }
        } elseif (strtotime('-1 year', strtotime($employee->stats_date_to)) < strtotime($employee->stats_date_from)) {
            $months = array();
            if ($fromArray['year'] == $toArray['year']) {
                for ($i = $fromArray['mon']; $i <= $toArray['mon']; ++$i) {
                    $months[] = $i;
                }
            } else {
                for ($i = $fromArray['mon']; $i <= 12; ++$i) {
                    $months[] = $i;
                }
                for ($i = 1; $i <= $toArray['mon']; ++$i) {
                    $months[] = $i;
                }
            }
            if ($legend) {
                foreach ($months as $i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = sprintf('%02d', $i);
                }
            }
            if (is_callable(array($this, 'setYearValues'))) {
                $this->setYearValues($layers);
            }
        } else {
            if ($legend) {
                for ($i = $fromArray['year']; $i <= $toArray['year']; ++$i) {
                    if ($layers == 1) {
                        $this->_values[$i] = 0;
                    } else {
                        for ($j = 0; $j < $layers; ++$j) {
                            $this->_values[$j][$i] = 0;
                        }
                    }
                    $this->_legend[$i] = sprintf('%04d', $i);
                }
            }
            if (is_callable(array($this, 'setAllTimeValues'))) {
                $this->setAllTimeValues($layers);
            }
        }
    }
}

class StatsFixture
{
    private static $tables = array(
        'ps_category_product',
        'ps_product_attribute',
        'ps_page_viewed',
        'ps_date_range',
        'ps_page',
        'ps_page_type',
        'ps_stock_available',
        'ps_order_detail',
        'ps_orders',
        'ps_product_lang',
        'ps_product_shop',
        'ps_product',
        'ps_category_lang',
        'ps_category_shop',
        'ps_category',
    );

    public static function installSchema()
    {
        $pdo = HarnessDb::getInstance()->getPdo();
        $schemaPath = dirname(__FILE__) . '/schema.sql';
        $sql = file_get_contents($schemaPath);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }

    public static function truncateAll()
    {
        $pdo = HarnessDb::getInstance()->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (self::$tables as $table) {
            $pdo->exec('TRUNCATE TABLE `' . $table . '`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    public static function resetRequestState()
    {
        $_GET = array();
        $_POST = array();
        $_REQUEST = array();
        $_SERVER['REQUEST_URI'] = '';
    }

    public static function setStatsWindow($from, $to)
    {
        $ctx = Context::getContext();
        $ctx->employee->stats_date_from = $from;
        $ctx->employee->stats_date_to = $to;
    }

    public static function insertOrder($idOrder, $idShop, $idCustomer, $valid, $dateAdd)
    {
        $pdo = HarnessDb::getInstance()->getPdo();
        $stmt = $pdo->prepare('INSERT INTO ps_orders (id_order, id_shop, id_customer, valid, date_add) VALUES (?,?,?,?,?)');
        $stmt->execute(array($idOrder, $idShop, $idCustomer, $valid, $dateAdd));
    }

    public static function insertOrderDetail($idDetail, $idOrder, $productId, $attrId, $name, $qty, $unit, $total)
    {
        $pdo = HarnessDb::getInstance()->getPdo();
        $stmt = $pdo->prepare('INSERT INTO ps_order_detail (id_order_detail, id_order, product_id, product_attribute_id, product_name, product_quantity, unit_price_tax_excl, total_price_tax_excl, tax_name) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array($idDetail, $idOrder, $productId, $attrId, $name, $qty, $unit, $total, ''));
    }

    public static function insertProduct($id, $reference, $state, $shopId = 1)
    {
        $pdo = HarnessDb::getInstance()->getPdo();
        $stmt = $pdo->prepare('INSERT INTO ps_product (id_product, reference, state) VALUES (?,?,?)');
        $stmt->execute(array($id, $reference, $state));
        $stmt = $pdo->prepare('INSERT INTO ps_product_shop (id_product, id_shop) VALUES (?,?)');
        $stmt->execute(array($id, $shopId));
    }

    public static function insertProductLang($productId, $langId, $shopId, $name)
    {
        $pdo = HarnessDb::getInstance()->getPdo();
        $stmt = $pdo->prepare('INSERT INTO ps_product_lang (id_product, id_lang, id_shop, name) VALUES (?,?,?,?)');
        $stmt->execute(array($productId, $langId, $shopId, $name));
    }

    public static function insertStock($productId, $attrId, $shopId, $groupId, $qty)
    {
        $pdo = HarnessDb::getInstance()->getPdo();
        $stmt = $pdo->prepare('INSERT INTO ps_stock_available (id_product, id_product_attribute, id_shop, id_shop_group, quantity) VALUES (?,?,?,?,?)');
        $stmt->execute(array($productId, $attrId, $shopId, $groupId, $qty));
    }

    public static function insertCategory($id, $parent, $active, $name, $position = 0)
    {
        $pdo = HarnessDb::getInstance()->getPdo();
        $stmt = $pdo->prepare('INSERT INTO ps_category (id_category, id_parent, active, level_depth) VALUES (?,?,?,1)');
        $stmt->execute(array($id, $parent, $active));
        $stmt = $pdo->prepare('INSERT INTO ps_category_lang (id_category, id_lang, id_shop, name) VALUES (?,?,1,?)');
        $stmt->execute(array($id, 1, $name));
        $stmt = $pdo->prepare('INSERT INTO ps_category_shop (id_category, id_shop, position) VALUES (?,1,?)');
        $stmt->execute(array($id, $position));
    }

    public static function linkCategoryProduct($categoryId, $productId)
    {
        $pdo = HarnessDb::getInstance()->getPdo();
        $stmt = $pdo->prepare('INSERT INTO ps_category_product (id_category, id_product) VALUES (?,?)');
        $stmt->execute(array($categoryId, $productId));
    }

    public static function insertProductAttribute($idAttr, $productId)
    {
        $pdo = HarnessDb::getInstance()->getPdo();
        $stmt = $pdo->prepare('INSERT INTO ps_product_attribute (id_product_attribute, id_product) VALUES (?,?)');
        $stmt->execute(array($idAttr, $productId));
    }

    public static function ensurePageTypes()
    {
        $pdo = HarnessDb::getInstance()->getPdo();
        $stmt = $pdo->query("SELECT COUNT(*) FROM ps_page_type WHERE name = 'product'");
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec("INSERT INTO ps_page_type (id_page_type, name) VALUES (1, 'product')");
        }
        $stmt = $pdo->query("SELECT COUNT(*) FROM ps_page_type WHERE name = 'category'");
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec("INSERT INTO ps_page_type (id_page_type, name) VALUES (2, 'category')");
        }
    }

    public static function insertPageView($productId, $shopId, $start, $end, $counter)
    {
        self::ensurePageTypes();
        $pdo = HarnessDb::getInstance()->getPdo();
        static $rangeSeq = 3000;
        $pageId = (int) (1000 + $productId);
        $rangeId = $rangeSeq;
        ++$rangeSeq;
        $stmt = $pdo->prepare('INSERT IGNORE INTO ps_page (id_page, id_page_type, id_object) VALUES (?,?,?)');
        $stmt->execute(array($pageId, 1, $productId));
        $stmt = $pdo->prepare('INSERT INTO ps_date_range (id_date_range, time_start, time_end) VALUES (?,?,?)');
        $stmt->execute(array($rangeId, $start, $end));
        $stmt = $pdo->prepare('INSERT INTO ps_page_viewed (id_page, id_date_range, id_shop, counter) VALUES (?,?,?,?)');
        $stmt->execute(array($pageId, $rangeId, $shopId, $counter));
    }

    public static function insertCategoryPageView($objectId, $shopId, $start, $end, $counter)
    {
        self::ensurePageTypes();
        $pdo = HarnessDb::getInstance()->getPdo();
        static $categoryRangeSeq = 6000;
        $pageId = (int) (5000 + $objectId);
        $rangeId = $categoryRangeSeq;
        ++$categoryRangeSeq;
        $stmt = $pdo->prepare('INSERT IGNORE INTO ps_page (id_page, id_page_type, id_object) VALUES (?,?,?)');
        $stmt->execute(array($pageId, 2, $objectId));
        $stmt = $pdo->prepare('INSERT INTO ps_date_range (id_date_range, time_start, time_end) VALUES (?,?,?)');
        $stmt->execute(array($rangeId, $start, $end));
        $stmt = $pdo->prepare('INSERT INTO ps_page_viewed (id_page, id_date_range, id_shop, counter) VALUES (?,?,?,?)');
        $stmt->execute(array($pageId, $rangeId, $shopId, $counter));
    }
}
