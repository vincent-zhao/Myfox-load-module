<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Debug\Pool;
use \Myfox\Lib\Debug\Timer;

require_once(__DIR__ . '/../../lib/TestShell.php');

class DebugTest extends \Myfox\Lib\TestShell
{

	/* {{{ protected void setUp() */
	protected function setUp()
	{
        parent::setUp();
        Pool::clean();
	}
	/* }}} */

	/* {{{ protected void tearDown() */
	protected function tearDown()
	{
		parent::tearDown();
	}
	/* }}} */

	/* {{{ public void test_should_debug_timer_works_fine() */
	public function test_should_debug_timer_works_fine()
	{
		Timer::init(false);
		Timer::start('key1');
		$this->assertEquals(null, Timer::elapsed('key1'));

		Timer::init(true);
		$t1	= microtime(true);
		Timer::start('key1');
		usleep(300000);	// 3ms
		$this->assertEquals(null, Timer::elapsed('key2'));

		$t2	= microtime(true);
		$tt	= Timer::elapsed('key1');
		$this->assertTrue(10 > 1000 * (int)abs($tt - $t2 + $t1));

		$this->assertEquals(null, Timer::elapsed('key1'));
	}
	/* }}} */

	/* {{{ public void test_should_ignore_push_when_debug_not_open() */
	/**
	 * 调试关闭时Push不起作用
	 * @return void
	 */
	public function test_should_ignore_push_when_debug_not_open()
	{
		Pool::init(false);
		$this->assertFalse(Pool::push('test', '啦啦啦啦'));
		$this->assertEquals('NULL', Pool::dump('test'));
	}
	/* }}} */

	/* {{{ public void test_should_push_message_works_fine() */
	/**
	 * 测试开启状态下的push功能
	 */
	public function test_should_push_message_works_fine()
	{
		Pool::init(true);

		$val = 'debug1';
		Pool::push('test1', $val);
		$this->assertEquals(var_export($val, true), Pool::dump('test1'));

		$exp = array(
			$val,
			array(
				'text'  => '我是中文',
			),
		);
		Pool::push('test1', end($exp));
		$this->assertEquals(var_export($exp, true), Pool::dump('test1'));

		$obj = new Stdclass();
		$obj->val1 = 'key1';
		$obj->val2 = array('啦啦啦');

		Pool::push('test2', $obj);
		$this->assertEquals(var_export($obj, true), Pool::dump('test2'));

		$exp = array(
			'test1' => $exp,
			'test2' => $obj,
		);
		$this->assertEquals(var_export($exp, true), Pool::dump());
	}
	/* }}} */

}
