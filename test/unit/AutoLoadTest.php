<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\AutoLoad;

require_once(__DIR__ . '/../../lib/TestShell.php');

class AutoLoadTest extends \Myfox\Lib\TestShell
{

	protected function setUp()
	{
		parent::setUp();
		AutoLoad::removeAllRules();
	}

	protected function tearDown()
	{
		parent::tearDown();
	}

	public function test_should_class_loader_worked_fine()
	{
		AutoLoad::register('com', __DIR__ . '/autoload/com');
		AutoLoad::register('com\\\\aleafs', __DIR__ . '/autoload/com');

		$case1	= new \Com\Aleafs\AutoLoadTestClass();
		$this->assertEquals(1, \Com\Aleafs\AutoLoadTestClass::$requireTime, 'Class Load Failed.');

		$case2	= new \Com\Aleafs\AutoLoadTestClass();
		$this->assertEquals(1, \Com\Aleafs\AutoLoadTestClass::$requireTime, 'Class Load Duplicate.');
		$this->assertContains(
			strtr(__DIR__ . '/autoload/com/aleafs/autoloadtestclass.php', '\\', '/'),
			strtr($case2->path(), '\\', '/'),
			'Class Load Error Rules.'
		);
	}

	public function test_should_class_loader_by_order_worked_fine()
	{
		AutoLoad::register('com', __DIR__ . '/autoload/com');
		AutoLoad::register('com\\\\aleafs1', __DIR__ . '/autoload/com', 'com');
		AutoLoad::register('com\\\\aleafs2', __DIR__ . '/autoload/com', 'com');
		AutoLoad::unregister('com/aleafs2');
		AutoLoad::register('com\\\\aleafs', __DIR__ . '/autoload/com', 'com');

		$case = new \Com\Aleafs\AutoLoadOrderTestClass();
		$this->assertEquals(
			strtr(__DIR__ . '/autoload/com/autoloadordertestclass.php', '\\', '/'),
			strtr($case->path(), '\\', '/'),
			'Class Load by Order Error.'
		);
	}

	public function test_should_throw_file_not_found_when_cant_find_class_file()
	{
		AutoLoad::register('com', __DIR__ . '/autoload/com');
		try {
			$case1 = new \Com\I\Am\Not\Exists();
		} catch (\Exception $e) {
            $this->assertTrue($e instanceof \Myfox\Lib\Exception, 'Exception Type doesn\'t match,');
			$this->assertContains(
				sprintf('File "%s/autoload/com/i/am/not/exists.php', strtr(__DIR__, '\\', '/')),
				strtr($e->getMessage(), '\\', '/'),
				'Exception Message doesn\'t match.'
			);
		}
	}

	public function test_should_throw_class_not_found_when_rule_not_defined()
	{
		AutoLoad::register('com', __DIR__ . '/autoload/com');
		try {
			$case1 = new \I\Am\Not\Exists();
		} catch (\Exception $e) {
			$this->assertTrue($e instanceof \Myfox\Lib\Exception, 'Exception Type doesn\'t match,');
			$this->assertContains(
				'Class "I\Am\Not\Exists" Not Found',
				$e->getMessage(),
				'Exception Message doesn\'t match.'
			);
		}
	}

	public function test_should_throw_class_not_found_when_class_not_in_file()
	{
		AutoLoad::register('com', __DIR__ . '/autoload/com');
		try {
			$case1 = new \Com\Aleafs\AutoLoadTestCaseClassNameNotMatched();
		} catch (\Exception $e) {
			$this->assertTrue($e instanceof \Myfox\Lib\Exception, 'Exception Type doesn\'t match,');
			$this->assertTrue(
				(bool)preg_match(
					'/^Class "(.+?)" NOT FOUND IN "(.+?)"/is',
					$e->getMessage()
				), 
				'Exception Message doesn\'t match.'
			);
		}
	}
}

