<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Security;

require_once(__DIR__ . '/../../lib/TestShell.php');

class SecurityTest extends \Myfox\Lib\TestShell
{

	/* {{{ public void test_should_ip_allow_and_deny_works_fine() */
	public function test_should_ip_allow_and_deny_works_fine()
    {
        $sec    = new Security(__DIR__ . '/ini/security.ini');
        $this->assertEquals(10,     $sec->priority('127.0.0.2'));
        $this->assertEquals(911,    $sec->priority('221.3.1.2'));
        $this->assertEquals(false,  $sec->priority('221.31.1.2'));

        $this->assertEquals(array(
            'task_flag' => 100,
            'trytimes'  => 3,
            'tasktype'  => 'asdf',
            'i_am_none' => 'lalalal',
        ), $sec->modify(array(
            'task_flag' => 11,
            'trytimes'  => 2,
            'tasktype'  => 'asdf',
            'i_am_none' => 'lalalal',
        )));
	}
	/* }}} */

}
