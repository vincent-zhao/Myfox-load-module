<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Queque;
use \Myfox\App\Model\Router;
use \Myfox\App\Worker\Cleaner;

require_once(__DIR__ . '/../../lib/TestShell.php');

class CleanerTest extends \Myfox\Lib\TestShell
{
    private static $mysql;

    private $route_table    = null;

    private $queque_table   = null;

    /* {{{ protected void setUp() */
    protected function setUp()
    {
        parent::setUp();

        \Myfox\Lib\Mysql::register('default', __DIR__ . '/ini/mysql.ini');
        self::$mysql    = \Myfox\Lib\Mysql::instance('default');

        $this->route_table  = self::cleanTable('default', 'route_info');
        $this->queque_table = self::cleanTable('default', 'task_queque');
    }
    /* }}} */

    /* {{{ public void test_should_clean_deleted_route_works_fine() */
    public function test_should_clean_deleted_route_works_fine()
    {
        $query  = sprintf(
            'INSERT INTO %s (route_flag,hosts_list,real_table,route_text,addtime,modtime) VALUES ' . 
            "(%d,'{1,2,3}$$','a.t_1_1','cid:1,thedate:2222',%d,%d)," . 
            "(%d,'{1,2,3}','a.t_1_2','cid:2,thedate:2222',%d,%d)," .
            "(%d,'{1,2,3}','a.t_1_3','',%d,%d)," .
            "(%d,'}$','a.t_1_4','cid:4,thedate:2222',%d,%d)," .
            "(%d,'}$','a.t_1_5','cid:5,thedate:2222',%d,%d)," .
            "(%d,'}$','a.t_1_6','cid:6,thedate:2222',%d,%d)",
            $this->route_table,
            Router::FLAG_IS_DELETED, time() - 100100,   time() - 100100,        // OK
            Router::FLAG_IS_DELETED, time() - 99900,    time() - 100000,        // modtime
            Router::FLAG_IS_DELETED, time() - 100100,   time() - 100100,        // where empty
            Router::FLAG_IS_DELETED, time() - 100100,   time() - 100100,        // empty hosts
            Router::FLAG_PRE_IMPORT, time() - 865000,   time() - 100100,        // delete
            Router::FLAG_PRE_IMPORT, time() - 863000,   time() - 100100         // don't delete
        );

        $this->assertEquals(6, self::$mysql->query($query));

        Cleaner::clean_invalidated_routes();

        $this->assertEquals(array(
            array(
                'real_table'    => 'a.t_1_6',
            ),
            array(
                'real_table'    => 'a.t_1_2',
            ),
        ), self::$mysql->getAll(self::$mysql->query(sprintf(
            'SELECT real_table FROM %s ORDER BY autokid DESC LIMIT 6', $this->route_table
        ))));

        $this->assertEquals(array(
            array(
                'task_flag' => Queque::FLAG_WAIT,
                'task_info' => json_encode(array(
                    'host'  => '1,2,3',
                    'path'  => 'a.t_1_3',
                    'where' => '',
                )),
            ),
            array(
                'task_flag' => Queque::FLAG_WAIT,
                'task_info' => json_encode(array(
                    'host'  => '1,2,3',
                    'path'  => 'a.t_1_1',
                    'where' => 'cid = 1 AND thedate = 2222',
                )),
            ),
        ), self::$mysql->getAll(self::$mysql->query(sprintf(
            "SELECT task_flag,task_info FROM %s WHERE task_type = 'delete' ORDER BY autokid DESC LIMIT 6",
            $this->queque_table
        ))));

        $clean  = new Cleaner(array());
        $this->assertEquals(false, $clean->execute(false));
    }
    /* }}} */

}

