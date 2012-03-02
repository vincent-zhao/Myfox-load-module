<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Model\Consist;

require_once(__DIR__ . '/../../lib/TestShell.php');

class ConsistTest extends \Myfox\Lib\TestShell
{

    private static $mysql;

    /* {{{ protected void setUp() */
    protected function setUp()
    {
        parent::setUp();

        \Myfox\Lib\Mysql::removeAllNames();
        \Myfox\Lib\Mysql::register('default', __DIR__ . '/ini/mysql.ini');
        self::$mysql    = \Myfox\Lib\Mysql::instance('default');
    }
    /* }}} */

    /* {{{ public void test_should_consist_works_fine() */
    public function test_should_consist_works_fine()
    {
        try {
            Consist::check('test.c1', array());
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof \Myfox\Lib\Exception);
            $this->assertContains("Empty server list for consistency check", $e->getMessage());
        }

        $this->assertTrue(Consist::check('i_am_not_exists.lalalla', array('edp1_9801', 'edp2_9902')));
        $this->assertTrue(Consist::check('mirror_0.mirror_583_2', array('edp1_9801', 'edp2_9902')));
    }
    /* }}} */

}

