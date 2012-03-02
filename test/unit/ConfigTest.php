<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Config;

require_once(__DIR__ . '/../../lib/TestShell.php');

class ConfigTest extends \Myfox\Lib\TestShell
{

    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        Config::removeAllNames();
        parent::tearDown();
    }

    /* {{{ public void test_should_config_factory_works_fine() */
    public function test_should_config_factory_works_fine()
    {
        try {
            Config::instance('i_am_not_ eXists');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof \Myfox\Lib\Exception);
            $this->assertContains('Undefined config name as "i_am_not_exists"', $e->getMessage());
        }

        Config::register('confIg1', 'http://localhost/a.ini');
        $obj1   = Config::instance('config1');
        $obj2   = Config::instance('CONF  Ig1');
        $this->assertEquals($obj1, $obj2);
    }
    /* }}} */

    /* {{{ public void test_should_config_with_ini_works_fine() */
    public function test_should_config_with_ini_works_fine()
    {
        $config = new Config(__DIR__ . '/ini/config_test.ini', 'unittest');
        $this->assertEquals('abc', $config->get('key1'));
        $this->assertEquals(array(
            'a' => 1.23456789,
            'b' => 9.87654321,
        ), $config->get('key2'));
        $this->assertEquals('null', $config->get('key3', 'null'));

        $config = Config::instance('unittest');
        $this->assertEquals('', $config->get('key2/a'));
        $this->assertEquals(9.87654321, $config->get('key2/b'));
    }
    /* }}} */

}

