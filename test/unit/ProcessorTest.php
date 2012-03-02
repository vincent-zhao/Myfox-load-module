<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Queque;
use \Myfox\App\Worker\Processor;

require_once(__DIR__ . '/../../lib/TestShell.php');

class WorkerTest extends \Myfox\Lib\TestShell
{
    private static $mysql;

    /* {{{ protected void setUp() */
    protected function setUp()
    {
        parent::setUp();

        \Myfox\Lib\Mysql::register('default', __DIR__ . '/ini/mysql.ini');
        self::$mysql    = \Myfox\Lib\Mysql::instance('default');
        self::$mysql->query(sprintf('TRUNCATE TABLE %stask_queque', self::$mysql->option('prefix', '')));
    }
    /* }}} */

    /* {{{ public void test_should_undefined_task_type_ignore_works_fine() */
    public function test_should_undefined_task_type_ignore_works_fine()
    {
        $this->assertTrue(Queque::instance()->insert(
            'i am not defined', array(
                'src' => 'http://www.taobao.com',
            ),
            1, array(
                'trytimes'  => 2,
                'adduser'   => 'unittest',
                'priority'  => 201,
            )
        ));

        $worker = new Processor(array(
            'n'   => 1,
        ));

        $this->assertTrue($worker->execute());
        $this->assertEquals(1, $worker->interval());

        $queque = self::$mysql->getRow(self::$mysql->query(
            sprintf('SELECT * FROM %stask_queque LIMIT 1', self::$mysql->option('prefix', ''))
        ));
        $this->assertEquals(1, $queque['trytimes']);
        $this->assertEquals(Queque::FLAG_IGNO, $queque['task_flag']);
        $this->assertContains("Undefined task_type named as 'i am not defined'", $queque['last_error']);
    }
    /* }}} */

}

