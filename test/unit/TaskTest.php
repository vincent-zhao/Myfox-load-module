<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Task;

require_once(__DIR__ . '/../../lib/TestShell.php');

class TaskTest extends \Myfox\Lib\TestShell
{

    private static $mysql;

    /* {{{ protected void setUp() */
    protected function setUp()
    {
        parent::setUp();

        \Myfox\Lib\Mysql::register('default', __DIR__ . '/ini/mysql.ini');
        self::$mysql    = \Myfox\Lib\Mysql::instance('default');

        self::cleanTable('default', 'task_queque');

        \Myfox\Lib\Config::register('default', __DIR__ . '/ini/myfox.ini');
    }
    /* }}} */

    /* {{{ protected void tearDown() */
    protected function tearDown()
    {
        parent::tearDown();
    }
    /* }}} */

    /* {{{ private static Boolean create_test_table() */
    private static function create_test_table($host, $table, $like)
    {
        self::drop_test_table($host, $table);
        return \Myfox\App\Model\Server::instance($host)->getlink()->query(sprintf(
            'CREATE TABLE %s LIKE %s', $table, $like
        ));
    }
    /* }}} */

    /* {{{ private static Boolean drop_test_table() */
    private static function drop_test_table($host, $table)
    {
        return (bool)\Myfox\App\Model\Server::instance($host)->getlink()->query(sprintf(
            'DROP TABLE IF EXISTS %s', $table
        ));
    }
    /* }}} */

    /* {{{ private static Boolean check_table_exists() */
    private static function check_table_exists($host, $table)
    { 
        return (bool)\Myfox\App\Model\Server::instance($host)->getlink()->query(sprintf(
            'DESC %s', $table
        ));
    }
    /* }}} */

    /* {{{ public void test_should_static_create_works_fine() */
    public function test_should_static_create_works_fine()
    {
        foreach (array('import', 'delete', 'ready', 'rsplit', 'example') AS $type) {
            $object = \Myfox\App\Task::create(array(
                'id'        => -1,
                'type'      => $type,
                'status'    => '',
                'info'      => '',
            ));
            $this->assertTrue($object instanceof \Myfox\App\Task);
        }
    }
    /* }}} */

    /* {{{ public void test_should_example_task_works_fine() */
    public function test_should_example_task_works_fine()
    {
        $task   = new \Myfox\App\Task\Example(-1, array('a' => 'none'));
        $this->assertEquals('none', $task->option('a'));
        $this->assertEquals(Task::FAIL, $task->execute());
        $this->assertContains('Required column named as "type"', $task->lastError());

        $task   = new \Myfox\App\Task\Example(-1, array('type' => 'none'));

        $this->assertEquals(0, $task->counter);
        $this->assertEquals(Task::SUCC, $task->execute());
        $this->assertEquals(1, $task->counter);

        $this->assertEquals(Task::FAIL, $task->wait());
        $this->assertContains('None sense for wait', $task->lastError());
    }
    /* }}} */

    /* {{{ public void test_should_delete_task_works_fine() */
    public function test_should_delete_task_works_fine()
    {
        $task   = new \Myfox\App\Task\Delete(-1, array(
            'path'  => 'mirror_0.t_42_0',
            'where' => '',
        ));
        $this->assertEquals(Task::IGNO, $task->execute());

        self::create_test_table('edp1_9801', 'mirror_0.task_test', 'mirror_0.mirror_583_2');
        self::create_test_table('edp2_9902', 'mirror_0.task_test', 'mirror_0.mirror_583_2');

        $this->assertEquals(true,   self::check_table_exists('edp1_9801', 'mirror_0.task_test'));
        $this->assertEquals(true,   self::check_table_exists('edp2_9902', 'mirror_0.task_test'));

        $task   = new \Myfox\App\Task\Delete(-1, array(
            'host'  => '1,3,-1,2,1',
            'path'  => 'mirror_0.task_test',
            'where' => '',
        ));
        $this->assertEquals(Task::WAIT, $task->execute());
        $this->assertEquals(Task::SUCC, $task->wait());
        $this->assertEquals('edp2_9902,edp2_8510,edp1_9801', $task->result());
        $this->assertEquals(false,  self::check_table_exists('edp1_9801', 'mirror_0.task_test'));
        $this->assertEquals(false,  self::check_table_exists('edp2_9902', 'mirror_0.task_test'));

        // xxx: 带WHERE条件的
        self::create_test_table('edp1_9801', 'mirror_0.task_test', 'mirror_0.mirror_583_2');

        $task   = new \Myfox\App\Task\Delete(-1, array(
            'host'  => '1,3,-1,2,1',
            'path'  => 'mirror_0.task_test',
            'where' => '1=1 AND 0 < 2',
        ));
        $this->assertEquals(Task::WAIT, $task->execute());

        // xxx: host_02_01 上不存在
        $this->assertEquals(Task::FAIL, $task->wait());
        $this->assertContains("Table 'mirror_0.task_test' doesn't exist", $task->lastError());

        $this->assertEquals(true,   self::check_table_exists('edp1_9801', 'mirror_0.task_test'));
        $this->assertEquals(false,  self::check_table_exists('edp2_9902', 'mirror_0.task_test'));
    }
    /* }}} */

    /* {{{ public void test_should_import_numsplit_works_fine() */
    public function test_should_import_numsplit_works_fine()
    {
        $task   = new \Myfox\App\Task\Import(-1, array('table' => 'numsplit_v2'));
        $this->assertEquals(Task::IGNO, $task->execute());

        self::cleanTable('default', 'route_info');
        \Myfox\App\Model\Router::set('numsplit_v2', array(
            array(
                'field' => array(
                    'thedate'   => '2011-06-10',
                    'cid'       => 1,
                ),
                'count' => 1201,
            ),
            array(
                'field' => array(
                    'thedate'   => '2011-06-10',
                    'cid'       => 2,
                ),
                'count' => 998,
            ),
        ));

        $task   = new \Myfox\App\Task\Import(10, array(
            'table'     => 'numsplit_v2',
            'route'     => 'cid=1,thedate=20110610',
            'file'      => realpath(__DIR__ . '/resource/numsplit_import_data_file.txt'),
            'bucket'    => 'numsplit_0.t_2_0',
            'hosts'     => '3,1',
        ), '1,1,999999,-98');
        $this->assertEquals(Task::WAIT, $task->execute());
        $this->assertEquals(Task::SUCC, $task->wait());
        //var_dump($task->lastError());
        $this->assertEquals('3', $task->result());
    }
    /* }}} */

    /* {{{ public void test_should_import_mirror_works_fine() */
    public function test_should_import_mirror_works_fine()
    {
        self::cleanTable('default', 'route_info');
        $route  = \Myfox\App\Model\Router::set('mirror_v2', array());
        $route  = reset($route);
        $route  = reset($route);

        $task   = new \Myfox\App\Task\Import(10, array(
            'table'     => 'mirror_v2',
            'route'     => '',
            'file'      => realpath(__DIR__ . '/resource/mirror_import_data_file.txt'),
            'bucket'    => $route['table'],
            'hosts'     => $route['hosts'],
        ), '999999,-98');
        $this->assertEquals(Task::WAIT, $task->execute());
        $this->assertEquals(Task::SUCC, $task->wait());
        //$task->execute();
        //$task->wait();
        //echo $task->lastError();
        $where  = \Myfox\App\Model\Router::instance('mirror_v2')->where(null);
        $route  = self::$mysql->getOne(self::$mysql->query(sprintf(
            "SELECT hosts_list FROM %s WHERE table_name='mirror_v2' AND real_table='%s' AND route_flag = %d",
            $where['table'], $route['table'], \Myfox\App\Model\Router::FLAG_IMPORT_END
        )));

        $route  = array_filter(explode(',', trim($route, '{}$')));
        sort($route);
        $this->assertEquals(array(1,2,3,4,5), $route);
    }
    /* }}} */

    /* {{{ public void test_should_import_numsplit_to_ib_works_fine() */
    public function _test_should_import_numsplit_to_ib_works_fine()
    {
        $task   = new \Myfox\App\Task\Import(-1, array('table' => 'numsplit_v2'));
        $this->assertEquals(Task::IGNO, $task->execute());

        self::cleanTable('default', 'route_info');
        \Myfox\App\Model\Router::set('numsplit_v2', array(
            array(
                'field' => array(
                    'thedate'   => '2011-06-10',
                    'cid'       => 1,
                ),
                'count' => 1201,
            ),
            array(
                'field' => array(
                    'thedate'   => '2011-06-10',
                    'cid'       => 2,
                ),
                'count' => 998,
            ),
        ));

        $task   = new \Myfox\App\Task\Import(10, array(
            'table'     => 'numsplit_v2',
            'route'     => 'cid=1,thedate=20110610',
            'file'      => realpath(__DIR__ . '/resource/numsplit_import_data_file.txt'),
            'bucket'    => 'numsplit_0.t_2_0',
            'hosts'     => '4,5',
            'engine'    => 'BRIGHTHOUSE',
        ), '1,1,999999,-98');
        $this->drop_test_table('ibtest_1', 'numsplit_0.t_2_0');
        $this->drop_test_table('ibtest_2', 'numsplit_0.t_2_0');
        $this->assertEquals(Task::WAIT, $task->execute());
        $this->assertEquals(Task::SUCC, $task->wait());
        $this->assertEquals('4,5', $task->result());

        $ib1 = \Myfox\App\Model\Server::instance('ibtest_1')->getlink();
        $ib2 = \Myfox\App\Model\Server::instance('ibtest_2')->getlink();
        $this->assertContains(
            'BRIGHTHOUSE',
            json_encode($ib1->getAll( $ib1->query('SHOW CREATE TABLE numsplit_0.t_2_0')))
        );
        $this->assertContains(
            'BRIGHTHOUSE',
            json_encode($ib2->getAll( $ib1->query('SHOW CREATE TABLE numsplit_0.t_2_0')))
        );
        $this->assertEquals(
            10,
            $ib1->getOne($ib1->query('SELECT COUNT(*) FROM numsplit_0.t_2_0'))
        );
        $this->assertEquals(
            10,
            $ib2->getOne($ib2->query('SELECT COUNT(*) FROM numsplit_0.t_2_0'))
        );
    }
    /* }}} */

    /* {{{ public void test_should_import_mirror_to_ib_works_fine() */
    public function test_should_import_mirror_to_ib_works_fine()
    {
        self::cleanTable('default', 'route_info');
        $route  = \Myfox\App\Model\Router::set('mirror_v2', array());
        $route  = reset($route);
        $route  = reset($route);

        $task   = new \Myfox\App\Task\Import(10, array(
            'table'     => 'mirror_v2',
            'route'     => '',
            'file'      => realpath(__DIR__ . '/resource/mirror_import_data_file.txt'),
            'bucket'    => $route['table'],
            'hosts'     => '4,5',
            'engine'    => 'BRIGHTHOUSE',
        ), '999999,-98');
        $this->assertEquals(Task::WAIT, $task->execute());
        $this->assertEquals(Task::SUCC, $task->wait());

        $where  = \Myfox\App\Model\Router::instance('mirror_v2')->where(null);
        $route  = self::$mysql->getOne(self::$mysql->query(sprintf(
            "SELECT hosts_list FROM %s WHERE table_name='mirror_v2' AND real_table='%s' AND route_flag = %d",
            $where['table'], $route['table'], \Myfox\App\Model\Router::FLAG_IMPORT_END
        )));

        $route  = array_filter(explode(',', trim($route, '{}$')));
        sort($route);
        $this->assertEquals(array(4,5), $route);
        $this->assertEquals(true, self::check_table_exists('ibtest_1', 'mirror_v2_0.t_1_0'));
        $this->assertEquals(true, self::check_table_exists('ibtest_1', 'mirror_v2_0.t_1_1'));
        $this->assertEquals(true, self::check_table_exists('ibtest_1', 'mirror_v2_0.t_1_2'));
        $this->assertEquals(true, self::check_table_exists('ibtest_2', 'mirror_v2_0.t_1_0'));
        $this->assertEquals(true, self::check_table_exists('ibtest_2', 'mirror_v2_0.t_1_1'));
        $this->assertEquals(true, self::check_table_exists('ibtest_2', 'mirror_v2_0.t_1_2'));
    }
    /* }}} */

    /* {{{ public void test_should_ready_task_works_fine() */
    public function test_should_ready_task_works_fine()
    {
        $task   = new \Myfox\App\Task\Ready(-1, array());
        $this->assertEquals(Task::IGNO, $task->execute());

        \Myfox\App\Setting::set('last_date', '2011-10-01');
        $task   = new \Myfox\App\Task\Ready(-1, array(
            'thedate'   => '2011-10-01',
            'priority'  => 1,
        ));
        $this->assertEquals(Task::IGNO, $task->execute());

        self::$mysql->query(sprintf('TRUNCATE TABLE %stask_queque', self::$mysql->option('prefix')));
        self::$mysql->query(sprintf(
            'INSERT INTO %stask_queque (autokid,priority,task_flag,task_type) VALUES '.
            "(1,21,%d,'import'),(2,20,%d,'import'),(3,20,%d,'import'),(4,19,%d,'lalallala'),(101,1,%d,'import')",
            self::$mysql->option('prefix'),
            \Myfox\App\Queque::FLAG_WAIT, \Myfox\App\Queque::FLAG_NEW,
            \Myfox\App\Queque::FLAG_IGNO, \Myfox\App\Queque::FLAG_LOCK, \Myfox\App\Queque::FLAG_WAIT
        ));

        \Myfox\App\Setting::set('last_date', '2011-10-01');
        $task   = new \Myfox\App\Task\Ready(100, array(
            'thedate'   => '2011-10-02',
            'priority'  => 20,
        ));
        $this->assertEquals(Task::FAIL, $task->execute());
        $this->assertContains('Waiting for 1 import task(s)', $task->lastError());

        self::$mysql->query(sprintf('TRUNCATE TABLE %stask_queque', self::$mysql->option('prefix')));
        \Myfox\App\Setting::set('last_date', '2011-10-01');

        $task   = new \Myfox\App\Task\Ready(100, array(
            'thedate'   => '2011-10-02',
            'priority'  => 20,
        ));
        $this->assertEquals(Task::SUCC, $task->execute());
    }
    /* }}} */

    /* {{{ public void test_should_rsplit_works_fine() */
    public function test_should_rsplit_works_fine()
    {
        $task   = new \Myfox\App\Task\Rsplit(-1, array());
        $this->assertEquals(Task::IGNO, $task->execute());

        $task   = new \Myfox\App\Task\Rsplit(-1, array(
            'table'     => 'i_am_not_exists',
            'route'     => '',
            'lines'     => 1000,
            'file'      => 'aa.txt',
            'priority'  => 3,
        ));
        $this->assertEquals(Task::IGNO, $task->execute());
        $this->assertContains('Undefined table named as "i_am_not_exists"', $task->lastError());

        $task   = new \Myfox\App\Task\Rsplit(-1, array(
            'table' => 'numsplit_v2',
            'route' => 'thedate:2011-01-01,cid:23',
            'lines' => 1201,
            'file'  => __FILE__,
            'priority'  => 3,
        ));
        $this->assertEquals(Task::SUCC, $task->execute());

        $result = self::$mysql->getRow(self::$mysql->query(sprintf(
            'SELECT openrace,priority,task_flag,task_type,task_info FROM %stask_queque LIMIT 1',
            self::$mysql->option('prefix')
        )));

        $import = json_decode($result['task_info'], true);
        // TODO: import assertEquals
        unset($result['task_info']);

        $this->assertEquals(array(
            'openrace'  => 0,
            'priority'  => 2,
            'task_flag' => \Myfox\App\Queque::FLAG_WAIT,
            'task_type' => 'import',
        ), $result);
    }
    /* }}} */

    /* {{{ public void test_should_check_table_works_fine() */
    public function test_should_check_table_works_fine()
    {
        $task   = new \Myfox\App\Task\Checktable(-1, array('host' => '1,2'));
        $this->assertEquals(Task::IGNO, $task->execute());
        $this->assertContains('Required column named as "path"', $task->lastError());

        $task   = new \Myfox\App\Task\Checktable(-1, array(
            'host'  => '1,2',
            'path'  => 'i_am.not_exists',
        ));
        $this->assertEquals(Task::SUCC, $task->execute());

        self::create_test_table('edp2_9902', 'mirror_0.task_test', 'mirror_0.mirror_583_2');
        $this->assertEquals(true,   self::check_table_exists('edp2_9902', 'mirror_0.mirror_583_2'));

        $task   = new \Myfox\App\Task\Checktable(-1, array(
            'host'  => '1,2',
            'path'  => 'mirror_0.mirror_583_2',
        ), '2');
        $this->assertEquals(Task::SUCC, $task->execute());
    }
    /* }}} */

}
