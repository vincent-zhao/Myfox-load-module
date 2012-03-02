<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Context;

require_once(__DIR__ . '/../../lib/TestShell.php');

class ContextTest extends \Myfox\Lib\TestShell
{

    protected function setUp()
    {
        parent::setUp();
        Context::cleanAllContext();
    }

    protected function tearDown()
    {
        Context::cleanAllContext();
        parent::tearDown();
    }

    /* {{{ public void test_should_register_and_get_works_fine() */
    public function test_should_register_and_get_works_fine()
    {
        Context::register('key1', 'val1');
        Context::register('key2', new StdClass());
        Context::unregister('key2');
        Context::unregister('I_am_not_exists');
        $this->assertEquals('val1', Context::get('key1', 'val2'));
        $this->assertEquals('default', Context::get('key2', 'default'));
    }
    /* }}} */

    /* {{{ public void test_should_get_correct_pid() */
    public function test_should_get_correct_pid()
    {
        $this->assertEquals(getmypid(), Context::pid());
    }
    /* }}} */

    /* {{{ public void test_should_get_correct_userip() */
    public function test_should_get_correct_userip()
    {
        $_SERVER	= array();
        $this->assertEquals('unknown', Context::userip());
        $this->assertEquals(0, Context::userip(true));

        $_SERVER['REMOTE_ADDR']	= '127.0.0.1';
        $this->assertEquals('unknown', Context::userip());
        $this->assertEquals(0, Context::userip(true));

        Context::cleanAllContext();
        $this->assertEquals('127.0.0.1', Context::userip());
        $this->assertEquals(ip2long('127.0.0.1'), Context::userip(true));

        $_SERVER['HTTP_X_FORWARDED_FOR'] = '202.111.111.123,59.66.192.112';
        Context::cleanAllContext();
        $this->assertEquals('59.66.192.112', Context::userip());
        $this->assertEquals(ip2long('59.66.192.112'), Context::userip(true));
    }
    /* }}} */

    /* {{{ public void test_should_addr_works_fine() */
    public function test_should_addr_works_fine()
    {
        $ip = trim(`hostname -i`);
        $this->assertEquals($ip, Context::addr());
    }
    /* }}} */

}

