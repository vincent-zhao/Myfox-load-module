<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Queque;
use \Myfox\App\Task;

require_once(__DIR__ . '/../../lib/TestShell.php');

class QuequeTest extends \Myfox\Lib\TestShell
{

    private static $mysql;

    /* {{{ protected void setUp() */
    protected function setUp()
    {
        parent::setUp();

        \Myfox\Lib\Mysql::register('default', __DIR__ . '/ini/mysql.ini');
        self::$mysql    = \Myfox\Lib\Mysql::instance('default');

        self::cleanTable('default', 'task_queque');
    }
    /* }}} */

    /* {{{ protected void tearDown() */
    protected function tearDown()
    {
        parent::tearDown();
    }
    /* }}} */

    /* {{{ public void test_should_queque_insert_and_fetch_works_fine() */
    public function test_should_queque_insert_and_fetch_works_fine()
    {
        $queque = Queque::instance();
        $this->assertEquals(null, $queque->fetch());
        $this->assertTrue($queque->insert(
            'test ', array(
                'src' => 'http://www.taobao.com',
            ),
            1,
            array(
                'trytimes'  => 2,
                'adduser'   => 'unittest',
                'priority'  => 201,
            )
        ));

        $task   = $queque->fetch(1, 2);
        $this->assertEquals(array(
            'id'        => 1,
            'type'      => 'test',
            'status'    => '',
            'info'      => json_encode(array(
                'src' => 'http://www.taobao.com',
            )),
        ), $task);

        $this->assertEquals(1, $queque->update($task['id'], array(
            'trytimes'  => 'trytimes + 1',
            'priority'  => 202,
        ), array(
            'trytimes'  => true,
        )));

        $this->assertEquals(array(
            'trytimes'  => 3,
            'priority'  => 202,
        ), self::$mysql->getRow(self::$mysql->query(sprintf(
            'SELECT trytimes, priority FROM %s.%stask_queque WHERE autokid = %d',
            self::$mysql->option('dbname', 'meta_myfox_config'),
            self::$mysql->option('prefix', ''),
            $task['id']
        ))));
    }
    /* }}} */

    /* {{{ public void test_should_close_race_queque_works_fine() */
    public function test_should_close_race_queque_works_fine()
    {
        $queque = Queque::instance();
        $this->assertEquals(null, $queque->fetch());

        $this->assertTrue($queque->insert(
            'test ', array(
                'src' => 'http://www.taobao.com',
            ),
            1,
            array(
                'trytimes'  => 2,
                'adduser'   => 'unittest',
                'priority'  => 201,
                'openrace'  => 0,
            )
        ));

        $this->assertEquals(null, $queque->fetch(1, 2));
        $this->assertEquals(array(
            'id'        => 1,
            'type'      => 'test',
            'status'    => '',
            'info'      => json_encode(array(
                'src' => 'http://www.taobao.com',
            )),
        ), $queque->fetch(1, 1));
    }
    /* }}} */

    /* {{{ public void test_should_queque_fetch_by_type_works_fine() */
    public function test_should_queque_fetch_by_type_works_fine()
    {
        $queque = Queque::instance();
        $this->assertTrue($queque->insert(
            'test ', array(
                'src' => 'http://www.taobao.com',
            ),
            1,
            array(
                'trytimes'  => 2,
                'adduser'   => 'unittest',
                'priority'  => 201,
            )
        ));
        $this->assertTrue($queque->insert(
            'test2', array(
                'src' => 'http://www.taobao.com',
            ),
            1,
            array(
                'trytimes'  => 2,
                'adduser'   => 'unittest',
                'priority'  => 200,
            )
        ));

        $task   = $queque->fetch(1, 2, Queque::FLAG_WAIT, 'test');
        $this->assertEquals(array(
            'id'        => 1,
            'type'      => 'test',
            'status'    => '',
            'info'      => json_encode(array(
                'src' => 'http://www.taobao.com',
            )),
        ), $task);
    }
    /* }}} */

}

