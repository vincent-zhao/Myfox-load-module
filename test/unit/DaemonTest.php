<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Daemon;

require_once(__DIR__ . '/../../lib/TestShell.php');

class DaemonTest extends \Myfox\Lib\TestShell
{

    /* {{{ 成员变量 */

    private $inifile;

    /* }}} */

    /* {{{ protected void setUp() */
    protected function setUp()
    {
        parent::setUp();
        $this->inifile	= __DIR__ . '/ini/myfox.ini';
    }
    /* }}} */

    /* {{{ public void test_should_daemon_works_fine() */
    public function test_should_daemon_works_fine()
    {
        \Myfox\App\Worker\Test::$number    = 0;

        $time1  = microtime(true);
        Daemon::run($this->inifile, array(
            'script',
            'test',
            '--sleep',
            10,
            '--loop'
        ));
        $this->assertEquals(
            true,
            abs(1000 * (microtime(true) - $time1) - 50) < 10
        );
        $this->assertEquals(
            5,
            \Myfox\App\Worker\Test::$number
        );
    }
    /* }}} */

    /* {{{ public void test_should_parse_option_works_fine() */
    public function test_should_parse_option_works_fine()
    {
        $this->assertEquals(array(
            'a'     => 1,
            'b'     => 2,
            'c'     => true,
            'f'     => 0,
            'data'  => 2,
            'debug' => true,
            'eof'   => 'abcdefg',
        ), Daemon::parse(array(
            '-a',
            '1',
            '-b2',
            '-c',
            '-f0',
            '--data',
            '2',
            '--debug',
            '--eof=abcdefg',
        )));
    }
    /* }}} */

}

