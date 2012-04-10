<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Queque;
use \Myfox\App\Worker\Monitor;
use \Myfox\App\Model\Server;

require_once(__DIR__ . '/../../lib/TestShell.php');

class MonitorTest extends \Myfox\Lib\TestShell
{
    private static $mysql;

    /* {{{ protected void setUp() */
    protected function setUp()
    {
        parent::setUp();

        \Myfox\Lib\Mysql::register('default', __DIR__ . '/ini/mysql.ini');
        self::$mysql    = \Myfox\Lib\Mysql::instance('default');

        self::cleanTable('default', 'route_info');
        self::cleanTable('default', 'task_queque');
    }
    /* }}} */

	/* {{{ public void test_should_queque_status_monitor_works_fine() */
	public function test_should_queque_status_monitor_works_fine()
    {
        $status = Monitor::queque_status_monitor();
        $this->assertEquals(array(
            'failed'    => 0,
            'pending'   => 0,
        ), $status['']);

        self::$mysql->query(sprintf(
            "INSERT INTO %stask_queque (addtime,task_flag,trytimes) VALUES('%s',%u,1),('%s',%u,4),('%s',%u,0),('%s',%u,0)",
            self::$mysql->option('prefix'), date('Y-m-d H:i:s', time() - 301), \Myfox\App\Queque::FLAG_WAIT,
            date('Y-m-d H:i:s', time() - 290), \Myfox\App\Queque::FLAG_WAIT,
            date('Y-m-d H:i:s', time() - 290), \Myfox\App\Queque::FLAG_WAIT,
            date('Y-m-d H:i:s', time() - 390), \Myfox\App\Queque::FLAG_NEW
        ));
        $status = Monitor::queque_status_monitor();
        $this->assertEquals(array(
            'failed'    => 1,
            'pending'   => 1,
        ), $status['']);

        $monitor    = new Monitor(array('sleep' => 22));
        $this->assertEquals(true, $monitor->execute(true));
        $this->assertEquals(22, $monitor->interval());
    }
    /* }}} */

    /* {{{ public void test_should_import_consist_works_fine() */
    public function test_should_import_consist_works_fine()
    {
        $hosts  = self::$mysql->getAll(self::$mysql->query(sprintf(
            'SELECT host_id, host_name FROM %shost_list WHERE host_type = %d',
            self::$mysql->option('prefix'), Server::TYPE_REALITY
        )));

        $create = "CREATE TABLE IF NOT EXISTS test.test_a (
            id int(10) unsigned not null auto_increment primary key,
            num1 int(10) not null default 0,
            char1 varchar(15) not null default ''
        ) ENGINE=MYISAM DEFAULT CHARSET=UTF8";

        $ids    = array();
        foreach ($hosts AS $server) {
            $mysql  = Server::instance($server['host_name'])->getlink();
            $mysql->query('DROP TABLE IF EXISTS test.test_a');
            $mysql->query($create);
            $this->assertEquals(3, $mysql->query(
                "INSERT INTO test.test_a (id,num1,char1) VALUES (1, 2, 'bbb'),(2,3,'cccc'),(3,4,'dddd')"
            ));
            $ids[]  = (int)$server['host_id'];
        }

        $this->assertEquals(1, self::$mysql->query(sprintf(
            "INSERT INTO %sroute_info (real_table,hosts_list,table_name,modtime,route_flag)".
            " VALUES ('test.test_a', '%s', 'test',%d,%d)",
            self::$mysql->option('prefix'), implode(',', $ids), time(), \Myfox\App\Model\Router::FLAG_IMPORT_END
        )));

        \Myfox\App\Setting::set('monitor_consist_check', date('Y-m-d H:i:s', time() - 3600));
        $this->assertEquals(array(), Monitor::import_consist_monitor());

        $server = reset($hosts);
        Server::instance($server['host_name'])->getlink()->query(sprintf(
            'DELETE FROM test.test_a ORDER BY id ASC LIMIT 1'
        ));

        \Myfox\App\Setting::set('monitor_consist_check', date('Y-m-d H:i:s', time() - 3600));
        $result = Monitor::import_consist_monitor();

        $this->assertTrue(!empty($result));
        $result = reset($result);
        $this->assertTrue(!empty($result['checks']));
    }
    /* }}} */

}

