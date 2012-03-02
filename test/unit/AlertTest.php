<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Alert;

require_once(__DIR__ . '/../../lib/TestShell.php');

class AlertTest extends \Myfox\Lib\TestShell
{

	/* {{{ public void test_should_alert_works_fine() */
	public function test_should_alert_works_fine()
	{
		Alert::init(__DIR__ . '/ini/alert.ini');

		ob_start();
		Alert::push('test', Alert::URGENCE);
		$this->assertContains(' [dev] test', ob_get_contents());

		@ob_end_clean();
	}
	/* }}} */

}

