<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Config;
use \Myfox\App\Dispatcher;

require_once(__DIR__ . '/../../lib/TestShell.php');

class DispatcherTest extends \Myfox\Lib\TestShell
{

    private $prefix   = '';
    private $inifile  = '';
    private $logfile  = '';

    protected function setUp()
    {
        parent::setUp();

        $this->inifile  = __DIR__ . '/ini/myfox.ini';
        $config = new Config($this->inifile);
        $logurl = parse_url($config->get('log/default', ''));

        $this->prefix   = rtrim($config->get('url.prefix', ''), '/');
        $this->logfile  = $logurl['path'];
        @unlink($this->logfile);
        ob_start();
    }

    protected function tearDown()
    {
        @ob_end_clean();
        @unlink($this->logfile);
        parent::tearDown();
    }

    /* {{{ public void test_should_dispatcher_works_fine() */
    public function test_should_dispatcher_works_fine()
    {
        \Myfox\App\Dispatcher::run(
            __DIR__ . '/ini/myfox.ini',
            $this->prefix . '/',
            null
        );

        $this->assertContains('<!--STATUS OK-->', ob_get_clean());
        $this->assertContains(
            "\tREQUEST\t-\t{\"url\":\"\/\",\"post\":[]}",
            self::getLogContents($this->logfile, -1)
        );

        \Myfox\App\Dispatcher::run(
            __DIR__ . '/ini/myfox.ini',
            $this->prefix . '/i_am_not_exist',
            'a[]=b&c1=' . urlencode('//')
        );
        $this->assertContains(
            "\tEXCEPTION\t-\t{\"url\":\"\/i_am_not_exist\",\"post\":{\"a\":[\"b\"],\"c1\":\"\/\/\"},\"error\":",
            self::getLogContents($this->logfile, -1)
        );
    }
    /* }}} */

}
