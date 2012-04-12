<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Worker\Uniquebuilder;
use \Myfox\Lib\Config;
use \Myfox\Lib\Mysql;
use \Myfox\App\Setting;
use \Myfox\App\Model\Server;

require_once(__DIR__ . '/../../lib/TestShell.php');

class UniquebuilderTest extends \Myfox\Lib\TestShell{

	private $mysql;

	private $infos = array();

	/*{{{ 表备份*/
	private function table_bak($tableName){

		$this->mysql->query(sprintf(
			'CREATE TABLE %s%s LIKE %s%s',
			$this->mysql->option('prefix'),
			$tableName.'_bak',
			$this->mysql->option('prefix'),
			$tableName
		));

		$this->mysql->query(sprintf(
			'INSERT INTO %s%s SELECT * FROM %s%s',
			$this->mysql->option('prefix'),
			$tableName.'_bak',
			$this->mysql->option('prefix'),
			$tableName
		));

		$this->mysql->query(sprintf(
			'DELETE FROM %s%s',
			$this->mysql->option('prefix'),
			$tableName
		));

	}
	/*}}}*/

	/*{{{ 表恢复*/
	private function table_recover($tableName){

		$this->mysql->query(sprintf(
			'DELETE FROM %s%s',
			$this->mysql->option('prefix'),
			$tableName
		));

		$this->mysql->query(sprintf(
			'INSERT INTO %s%s SELECT * FROM %s%s',
			$this->mysql->option('prefix'),
			$tableName,
			$this->mysql->option('prefix'),
			$tableName.'_bak'
		));

		$this->mysql->query(sprintf(
			'DROP TABLE %s%s',
			$this->mysql->option('prefix'),
			$tableName.'_bak'
		));

	}
	/*}}}*/

	/*{{{ setUp() */
	/**
	 * 测试表及信息创建
	 */
	protected function setUp()
	{

		parent::setUp();
		\Myfox\Lib\Mysql::removeAllNames();

		\Myfox\Lib\Mysql::register('default',__DIR__.'/ini/mysql.ini');
		$this->mysql = \Myfox\Lib\Mysql::instance('default');

		$config = new \Myfox\Lib\Config(__DIR__.'/ini/mysql.ini');
		foreach($config->get('master') as $host){
			$urlRes = parse_url($host);
			$this->infos['host'] = $urlRes['host'];
			$this->infos['port'] = array_key_exists('port',$urlRes) ? $urlRes['port'] : 3306;
			$this->infos['user'] = array_key_exists('user',$urlRes) ? $urlRes['user'] : 'root';
			$this->infos['pass'] = array_key_exists('pass',$urlRes) ? $urlRes['pass'] : '';
		}

        self::cleanTable('default', 'route_info');
		$this->table_bak('table_list');
		$this->table_bak('host_list');
		$this->table_bak('settings');

		//插入测试分片表1
		$this->mysql->query('CREATE TABLE `test_table_real1` (
			`id` int(10) unsigned NOT NULL auto_increment,
			`key1` varchar(64) NOT NULL default "",
			`key2` int(10) NOT NULL default 0,
			PRIMARY KEY (`id`)
		)');

		//插入测试分片表2
		$this->mysql->query('CREATE TABLE `test_table_real2` (
			`id` int(10) unsigned NOT NULL auto_increment,
			`key1` varchar(64) NOT NULL default "",
			`key2` int(10) NOT NULL default 0,
			PRIMARY KEY (`id`)
		)');

		//插入测试分片表3
		$this->mysql->query('CREATE TABLE `test_table_real3` (
			`id` int(10) unsigned NOT NULL auto_increment,
			`key1` varchar(64) NOT NULL default "",
			`key2` int(10) NOT NULL default 0,
			PRIMARY KEY (`id`)
		)');

	}
	/*}}}*/

	/*{{{ tearDown()*/
	/**
	 * 删除用的表
	 */
	protected function tearDown()
	{

		$this->table_recover('table_list');
		$this->table_recover('host_list');
		$this->table_recover('settings');

		$this->mysql->query("DROP TABLE IF EXISTS test_table_real1");
		$this->mysql->query("DROP TABLE IF EXISTS test_table_real2");
		$this->mysql->query("DROP TABLE IF EXISTS test_table_real3");

	}
	/*}}}*/

	/*{{{ test_init_works_fine()*/
	public function test_init_works_fine()
	{
		//向dev_table_list测试表中插入数据
		$this->mysql->query(sprintf(
			"insert into %stable_list (table_name) values ('testTable')",
			$this->mysql->option("prefix")
		));

		//插入具体测试路由数据
		$this->mysql->query(sprintf(
			"insert into %sroute_info (table_name,addtime,modtime,real_table,hosts_list,route_flag) values ('testTable',%s,%s,'meta_myfox_config.test_table_real1','99$',300)",
			$this->mysql->option("prefix"),time(),time()
		));
		$this->mysql->query(sprintf(
			"insert into %sroute_info (table_name,addtime,modtime,real_table,hosts_list,route_flag) values ('testTable',%s,%s,'meta_myfox_config.test_table_real2','99$',300)",
			$this->mysql->option("prefix"),time(),time()
		));

		//插入host测试信息
		$this->mysql->query(sprintf(
			"insert into %shost_list (host_id,host_name,conn_host,conn_port,read_user,read_pass) values (99,'testHost','%s',%s,'%s','%s')",
			$this->mysql->option("prefix"),$this->infos["host"],$this->infos["port"],$this->infos["user"],$this->infos["pass"]
		));

		$this->mysql->query("insert into test_table_real1 (key1,key2) values ('a',2)");
		$this->mysql->query("insert into test_table_real1 (key1,key2) values ('b',2)");

		$this->mysql->query("insert into test_table_real2 (key1,key2) values ('a',2)");
		$this->mysql->query("insert into test_table_real2 (key1,key2) values ('b',1)");

		$uniqueBuilder = new \Myfox\App\Worker\Uniquebuilder(array(
			'm' => 'init',
			'checkInterval' => 1000000000000 
		));
		$uniqueBuilder->execute();

		//testTable有三个可能唯一字段
		$res = $this->mysql->getAll($this->mysql->query(sprintf(
			"select unique_key from %stable_list where table_name='testTable'",
			$this->mysql->option("prefix")
		)));
		$this->assertEquals(array(
			'unique_key' => 'id;key1;key2$'
		),$res[0]);

	}
	/*}}}*/

	/*{{{ test_set_split_works_fine()*/
	public function test_set_split_works_fine()
	{

		$this->test_init_works_fine();

		$this->mysql->query(sprintf(
			"insert into %ssettings (cfgname,cfgvalue,addtime,modtime) values ('table_last_sync','%s','%s','%s'),('hosts_last_sync','%s','%s','%s'),('route_last_sync','%s','%s','%s')",
			$this->mysql->option('prefix'),'2012-03-12 18:02:27','2012-03-12 18:02:30','2012-03-12 18:02:30','2012-03-12 18:02:30','2012-03-12 18:02:30','2012-03-12 18:02:30','2012-03-13 10:36:11','2012-03-13 09:56:27','2012-03-13 10:36:11'
		));

		$originalModtime = Setting::get('unique_last_check');

		$uniqueBuilder = new \Myfox\App\Worker\Uniquebuilder();
		$uniqueBuilder->execute();

		$res = $this->mysql->getAll($this->mysql->query(sprintf(
			"select unique_key from %sroute_info where table_name = 'testTable' and real_table = 'meta_myfox_config.test_table_real1'",
			$this->mysql->option("prefix")
		)));
		$this->assertEquals(array(
			'unique_key' => 'id;key1$'
		),$res[0]);

		$res = $this->mysql->getAll($this->mysql->query(sprintf(
			"select unique_key from %sroute_info where table_name = 'testTable' and real_table = 'meta_myfox_config.test_table_real2'",
			$this->mysql->option("prefix")
		)));
		$this->assertEquals(array(
			'unique_key' => 'id;key1;key2$'
		),$res[0]);

		$res = $this->mysql->getAll($this->mysql->query(sprintf(
			"select cfgvalue,modtime from %ssettings where cfgname = 'unique_last_check'",
			$this->mysql->option("prefix")
		)));

		$this->assertFalse($originalModtime === $res[0]['modtime']);

	}
	/*}}}*/

	/*{{{ test_table_modified_works_fine()*/
	public function test_table_modified_works_fine()
	{
		$this->test_set_split_works_fine();

		sleep(3);

		$this->mysql->query(sprintf(
			"UPDATE test_table_real1 SET key2 = 3 WHERE key1 = 'b'"
		));

		$this->mysql->query(sprintf(
			"UPDATE %sroute_info SET modtime = %s WHERE table_name = 'testTable' AND real_table = 'meta_myfox_config.test_table_real1'",
			$this->mysql->option('prefix'),time()
		));

		$originalModtime = Setting::get('unique_last_check');

		$uniqueBuilder = new \Myfox\App\Worker\Uniquebuilder();
		$uniqueBuilder->execute();

		$res = $this->mysql->getAll($this->mysql->query(sprintf(
			"select unique_key from %sroute_info where table_name='testTable' AND real_table = 'meta_myfox_config.test_table_real1'",
			$this->mysql->option("prefix")
		)));
		$this->assertEquals(array(
			'unique_key' => 'id;key1;key2$'
		),$res[0]);

		$res = $this->mysql->getAll($this->mysql->query(sprintf(
			"select cfgvalue,modtime from %ssettings where cfgname = 'unique_last_check'",
			$this->mysql->option("prefix")
		)));

		$this->assertFalse($originalModtime === $res[0]['modtime']);

	}
	/*}}}*/

	/*{{{ test_add_new_split_works_fine()*/
	public function test_add_new_split_works_fine()
	{
		$this->test_set_split_works_fine();

		sleep(3);

		$this->mysql->query(sprintf(
			"insert into %sroute_info (table_name,addtime,modtime,real_table,hosts_list,route_flag) values ('testTable',%s,%s,'meta_myfox_config.test_table_real3','99$',300)",
			$this->mysql->option("prefix"),time(),time()
		));

		$this->mysql->query("insert into test_table_real3 (key1,key2) values ('a',2)");
		$this->mysql->query("insert into test_table_real3 (key1,key2) values ('b',5)");

		$originalModtime = Setting::get('unique_last_check');

		$uniqueBuilder = new \Myfox\App\Worker\Uniquebuilder();
		$uniqueBuilder->execute();

		$res = $this->mysql->getAll($this->mysql->query(sprintf(
			"select unique_key from %sroute_info where table_name = 'testTable' and real_table = 'meta_myfox_config.test_table_real3'",
			$this->mysql->option("prefix")
		)));
		$this->assertEquals(array(
			'unique_key' => 'id;key1;key2$'
		),$res[0]);

		$res = $this->mysql->getAll($this->mysql->query(sprintf(
			"select cfgvalue,modtime from %ssettings where cfgname = 'unique_last_check'",
			$this->mysql->option("prefix")
		)));

		$this->assertFalse($originalModtime === $res[0]['modtime']);

	}
	/*}}}*/

}
