<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Model\Router;
use \Myfox\App\Setting;

require_once(__DIR__ . '/../../lib/TestShell.php');

class RouterTest extends \Myfox\Lib\TestShell
{

    private static $mysql;

    /* {{{ protected void setUp() */
    protected function setUp()
    {
        parent::setUp();

        \Myfox\Lib\Mysql::register('default', __DIR__ . '/ini/mysql.ini');
        self::$mysql    = \Myfox\Lib\Mysql::instance('default');
        self::$mysql->query(sprintf(
            "DELETE FROM %stable_list WHERE table_name = 'i am not exists'",
            self::$mysql->option('prefix')
        ));
        self::$mysql->query(sprintf(
            "DELETE FROM %ssettings WHERE cfgname IN ('table_route_count')",
            self::$mysql->option('prefix')
        ));

        self::cleanTable('default', 'route_info');
        Setting::set('last_assign_host', 0);
    }
    /* }}} */

    /* {{{ protected void tearDown() */
    protected function tearDown()
    {
        parent::tearDown();
    }
    /* }}} */

    /* {{{ public void test_should_mirror_table_router_set_and_get_works_fine() */
    public function test_should_mirror_table_router_set_and_get_works_fine()
    {
        try {
            Router::set('i am not exists');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof \Myfox\Lib\Exception);
            $this->assertContains('Undefined table named as "i am not exists"', $e->getMessage());
        }

        $mirror = \Myfox\App\Model\Table::instance('mirror_v2');
        foreach (array(0, 1, 2, 0) AS $key => $id) {
            Setting::set('last_assign_host', 0);
            $this->assertEquals(
                array(
                    ''  => array(
                        array(
                            'rows'  => 1300,
                            'hosts' => '4,5,1,3,2',
                            'table' => 'mirror_v2_0.t_' . $mirror->get('autokid') . '_' . $id,
                        ),
                    ),
                ),
                Router::set('mirror_v2', array(array('count' => 1300)))
            );
            if ($key == 0) {
                $this->assertEquals(array(), Router::get('mirror_v2'));
            }
        }

        $this->assertEquals(1, Setting::get('last_assign_host'));
        $this->assertEquals(4, Setting::get('table_route_count', 'mirror_v2'));

        $where  = Router::instance('mirror_v2')->where(null);
        $route  = self::$mysql->getRow(self::$mysql->query(sprintf(
            "SELECT autokid,route_flag,real_table,hittime,hosts_list FROM %s WHERE table_name='mirror_v2' AND route_flag=%d LIMIT 1",
            $where['table'], Router::FLAG_PRE_IMPORT
        )));
        $this->assertEquals(0, $route['hittime']);
        $this->assertEquals('$', $route['hosts_list']);
        $real_table = $route['real_table'];

        // XXX: 数据装完
        $this->assertEquals(1, Router::effect('mirror_v2', null, $route['real_table'], '1,2'));
        $this->assertEquals(array(
            'hosts_list'    => '1,2,$',
            'route_flag'    => Router::FLAG_IMPORT_END,
        ),self::$mysql->getRow(self::$mysql->query(sprintf(
            "SELECT hosts_list,route_flag FROM %s WHERE table_name='mirror_v2' AND real_table='%s' AND autokid = %u",
            $where['table'], $route['real_table'], $route['autokid']
        ))));

        $this->assertEquals(array(), Router::get('mirror_v2'));

        // xxx: 模拟路由生效
        Router::flush();

        $route  = Router::get('mirror_v2', null, true);
        $this->assertEquals(1, count($route));

        $route  = reset($route);
        $this->assertTrue(0 < $route['mtime']);
        $this->assertEquals('1,2', $route['hosts']);
        $this->assertEquals($real_table, $route['table']);

        Router::removeAllCache();
        $query  = sprintf(
            'SELECT hittime FROM %s WHERE %s AND route_flag=%d ORDER BY autokid DESC LIMIT 1',
            $where['table'], $where['where'], Router::FLAG_NORMAL_USE
        );
        $this->assertEquals(intval(time() / 2), intval(self::$mysql->getOne(self::$mysql->query($query)) / 2));
    }
    /* }}} */

    /* {{{ public void test_should_sharding_table_router_set_and_get_works_fine() */
    public function test_should_sharding_table_router_set_and_get_works_fine()
    {
        $table  = \Myfox\App\Model\Table::instance('numsplit_v2');
        try {
            Router::set('numsplit_v2', array(array(
                'field' => array(
                    'thedate'   => '20110610',
                ),
                'count' => 1201,
            )));
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof \Myfox\Lib\Exception);
            $this->assertContains('Column "cid" required for table "numsplit_v2"', $e->getMessage());
        }

        $this->assertEquals(
            array(
                'cid:1,thedate:20110610'    => array(
                    array(
                        'rows'  => 1000,
                        'hosts' => '1,2',
                        'table' => 'numsplit_v2_0.t_' . $table->get('autokid') . '_0',
                    ),
                    array(
                        'rows'  => 201,
                        'hosts' => '2,1',
                        'table' => 'numsplit_v2_0.t_' . $table->get('autokid') . '_1',
                    ),
                ),
                'cid:2,thedate:20110610'    => array(
                    array(
                        'rows'  => 998,
                        'hosts' => '2,1',
                        'table' => 'numsplit_v2_0.t_' . $table->get('autokid') . '_1',
                    ),
                ),
            ),
            Router::set('numsplit_v2', array(
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
            )
        ));
        $this->assertEquals(array(), Router::get('numsplit_v2', array(
            'thedate'   => '2011-6-10',
            'cid'       => 1,
            'blablala'  => 2,
        )));

        Router::effect(
            'numsplit_v2', array('thedate' => 20110610, 'cid' => 1),
            'numsplit_v2_0.t_' . $table->get('autokid') . '_1', '1,2'
        ) && Router::flush();

        $routes = Router::get('numsplit_v2', array(
            'thedate'   => '2011-6-10',
            'cid'       => 1,
            'blablala'  => 2,
        ));

        $result = array();
        foreach ($routes AS $item) {
            unset($item['tabid'], $item['seqid']);
            $item['mtime'] = $item['mtime'] > 0 ? true : false;
            $result[]   = $item;
        }
        $this->assertEquals(array(
            array(
                'tbidx' => 'test_route_info',
                'mtime' => true,
                'hosts' => '1,2',
                'table' => sprintf('numsplit_v2_0.t_%d_1', $table->get('autokid')),
            ),
        ), $result);
    }
    /* }}} */

    /* {{{ public void test_should_route_parse_works_fine() */
    public function test_should_route_parse_works_fine()
    {
        $this->assertEquals(array(), Router::parse(''));
        $this->assertEquals(array(
            'thedate'   => 20110101,
            'cid'       => 23,
        ), Router::parse('thedate:20110101,cid:23'));
    }
    /* }}} */

    /* {{{ public void test_should_the_secret_hello_function_works_fine() */
    public function test_should_the_secret_hello_function_works_fine()
    {
        $hello  = Router::instance('mirror_v2')->hello(null, $table);
        $this->assertContains('route_info', $table);
        $this->assertEquals(array(
            'route_sign'    => 1550635837,
            'table_name'    => 'mirror_v2',
            'route_text'    => '',
        ), $hello);
    }
    /* }}} */

}
