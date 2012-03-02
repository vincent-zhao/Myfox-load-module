<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Blackhole;

require_once(__DIR__ . '/../../lib/TestShell.php');

class BlackHoleTest extends \Myfox\Lib\TestShell
{

	public function test_should_blackhole_works_fine()
	{
		$object	= new Blackhole();

		$object->lalala	= '我不起作用的';

		$this->assertNull($object->lalala);
		$this->assertNull($object->helloworld());
		$this->assertNull(Blackhole::staticHello());
	}

}


