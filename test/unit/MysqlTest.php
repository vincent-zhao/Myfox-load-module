<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Config;
use \Myfox\Lib\Mysql;

require_once(__DIR__ . '/../../lib/TestShell.php');

class MysqlTest extends \Myfox\Lib\TestShell
{

    private static $mysql;

    /* {{{ protected void setUp() */
    protected function setUp()
    {
        parent::setUp();

        $config = new Config(__DIR__ . '/ini/mysql_test.ini');
        $logurl = parse_url($config->get('logurl', ''));

        $this->logfile  = $logurl['path'];
        Mysql::removeAllNames();

        self::$mysql    = new Mysql(__DIR__ . '/ini/mysql.ini');
        self::$mysql->query('DROP TABLE IF EXISTS only_for_test');
        self::$mysql->query('CREATE TABLE `only_for_test` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `content` varchar(32) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8');

        @unlink($this->logfile);
    }
    /* }}} */

    /* {{{ protected void tearDown() */
    protected function tearDown()
    {
        @unlink($this->logfile);
        parent::tearDown();
    }
    /* }}} */

    /* {{{ public void test_should_mysql_factory_works_fine() */
    public function test_should_mysql_factory_works_fine()
    {
        try {
            $mysql	= Mysql::instance('i_am_not_exists');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof \Myfox\Lib\Exception);
            $this->assertContains('Undefined mysql instance named as "i_am_not_exists"', $e->getMessage());
        }

        $mysql	= new Mysql('', 'mysql3');
        $this->assertEquals(Mysql::instance('MYSQl3'), $mysql);

        Mysql::register('test1', array(
            'dbname'    => 'test',
            'prefix'    => 'myfox_',
        ));
        $mysql  = Mysql::instance('test1');
        $this->assertEquals('test', $mysql->option('dbname'));
        $this->assertEquals('myfox_', $mysql->option('prefix'));
        $this->assertEquals('utf8', $mysql->option('charset'));
    }
    /* }}} */

    /* {{{ public void test_should_simple_query_works_fine() */
    public function test_should_simple_query_works_fine()
    {
        $mysql  = new Mysql(__DIR__ . '/ini/mysql_test.ini');
        try {
            $mysql->connectToSlave();
        } catch (\Exception $e) {
            $this->assertContains(
                "\tCONNECT_ERROR\t-\t{\"host\":\"127.0.0.1\",\"port\":3306,\"user\":\"user_ro\",\"pass\":\"**\",\"error\":",
                self::getLogContents($this->logfile, -1)
            );
        }

        $rs = self::$mysql->getAll(self::$mysql->query('SHOW DATABASES'));
        $this->assertContains("\tQUERY_OK\t-\t{\"sql\":\"SHOW DATABASES\"", self::getLogContents($this->logfile, -1));
        $this->assertContains(array('Database' => 'test'), $rs);

        $this->assertFalse(self::$mysql->query('I AM A WRONG QUERY'));
        $this->assertContains(
            "\tQUERY_ERROR\t-\t{\"sql\":\"I AM A WRONG QUERY\",\"async\":false,\"error\":",
            self::getLogContents($this->logfile, -1)
        );

        $this->assertEquals(1, self::$mysql->query('INSERT INTO only_for_test (content) VALUES ("aabbcc")'));
        $lastId = self::$mysql->lastId();
        $this->assertEquals($lastId, self::$mysql->getOne(self::$mysql->query(
            'SELECT MAX(id) FROM only_for_test'
        )));
        self::$mysql->query('INSERT INTO only_for_test (content) VALUES ("aabbcc2")');
        $this->assertTrue($lastId < self::$mysql->lastId());
    }
    /* }}} */ 

    /* {{{ public void test_should_escape_works_fine() */
    public function test_should_escape_works_fine()
    {
        $this->assertEquals(array(
            'a' => 'i\\\'m chinese',
            '\\\''  => '省',
        ), self::$mysql->escape(array(
            'a' => 'i\'m chinese',
            "'" => '省',
        )));
    }
    /* }}} */

    /* {{{ public void test_should_async_query_works_fine() */
    public function test_should_async_query_works_fine()
    {
        $id = self::$mysql->async(
            'INSERT INTO only_for_test (content) VALUES ("aabbcc")'
        );
        $this->assertEquals($id + 1, self::$mysql->async('SELECT MAX(id) FROM only_for_test'));

        $this->assertEquals(1, self::$mysql->wait($id));
        $this->assertEquals(1, self::$mysql->getOne(self::$mysql->wait($id + 1)));
    }
    /* }}} */

    /* {{{ public void test_should_mysql_reconnect_when_gone_away_works_fine() */
    public function test_should_mysql_reconnect_when_gone_away_works_fine()
    {
        $mysql  = new Mysql(__DIR__ . '/ini/mysql.ini');
        $result = $mysql->getAll($mysql->query('SHOW DATABASES'));
        $this->assertEquals(0, $mysql->query(sprintf('KILL %d', $mysql->thread_id)));
        $this->assertEquals($result, $mysql->getAll($mysql->query('SHOW DATABASES')));
    }
    /* }}} */

}

