<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Queque;
use \Myfox\App\Worker\Checktable;

require_once(__DIR__ . '/../../lib/TestShell.php');

class ChecktableTest extends \Myfox\Lib\TestShell
{
    private static $mysql;

    /* {{{ protected void setUp() */
    protected function setUp()
    {
        parent::setUp();

        \Myfox\Lib\Mysql::register('default', __DIR__ . '/ini/mysql.ini');
        self::$mysql    = \Myfox\Lib\Mysql::instance('default');
    }
    /* }}} */

    /* {{{ public void test_should_check_table_works_fine() */
    public function test_should_check_table_works_fine()
    {
        $worker = new Checktable(array(
            'sleep' => 11,
        ));
        $this->assertEquals(11, $worker->interval());
        $this->assertEquals(true, $worker->execute());
    }
    /* }}} */

}

