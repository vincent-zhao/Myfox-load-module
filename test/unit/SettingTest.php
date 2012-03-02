<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Setting;

require_once(__DIR__ . '/../../lib/TestShell.php');

class SettingTest extends \Myfox\Lib\TestShell
{

	private static $mysql;

	/* {{{ protected void setUp() */
	protected function setUp()
	{
		parent::setUp();

		\Myfox\Lib\Mysql::register('default', __DIR__ . '/ini/mysql.ini');
		self::$mysql    = \Myfox\Lib\Mysql::instance('default');
		self::$mysql->query(sprintf(
			'DELETE FROM %ssettings WHERE ownname LIKE "unittest%%"',
			self::$mysql->option('prefix')
        ));

        Setting::$queries   = 0;
	}
	/* }}} */

	/* {{{ protected void tearDown() */
	protected function tearDown()
	{
		parent::tearDown();
	}
	/* }}} */

	/* {{{ public void test_should_setting_set_and_get_works_fine() */
	public function test_should_setting_set_and_get_works_fine()
	{
		Setting::init(1);

		$this->assertEquals(0, Setting::$queries);
		$this->assertEquals(null, Setting::get('key1', 'unittest1'));
		$this->assertEquals(1, Setting::$queries);

		$this->assertEquals(1, Setting::set('key1', 'a am a hacker', 'unittest1'));
		$this->assertEquals(2, Setting::$queries);

		$this->assertEquals('a am a hacker', Setting::get('key1', 'unittest1'));
		$this->assertEquals(3, Setting::$queries);

		$this->assertEquals('a am a hacker', Setting::get('key1', 'unittest1'));
		$this->assertEquals(3, Setting::$queries);

		usleep(1050000);

		$this->assertEquals('a am a hacker', Setting::get('key1', 'unittest1'));
		$this->assertEquals(4, Setting::$queries);

		$this->assertEquals(2, Setting::set('key1', 'i am not a hacker', 'unittest1'));
		$this->assertEquals(5, Setting::$queries);

		$this->assertEquals('i am not a hacker', Setting::get('key1', 'unittest1'));
		$this->assertEquals(6, Setting::$queries);
	}
	/* }}} */

    /* {{{ public void test_should_setting_set_without_comma_works_fine() */
    public function test_should_setting_set_without_comma_works_fine()
    {
        Setting::init(0);
        $this->assertEquals(1, Setting::set('key2', 1, 'unittest1'));
        $this->assertEquals(1, Setting::get('key2', 'unittest1'));

        $this->assertEquals(2, Setting::set('key2', 'IF(cfgvalue + 0 > 0, 2, 3)', 'unittest1', false));
        $this->assertEquals(2, Setting::get('key2', 'unittest1'));
    }
    /* }}} */

}
