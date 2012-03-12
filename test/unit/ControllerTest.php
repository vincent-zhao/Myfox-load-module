<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Controller;

require_once(__DIR__ . '/../../lib/TestShell.php');

class ControllerTest extends \Myfox\Lib\TestShell
{

    /* {{{ protected void setUp() */
    protected function setUp()
    {
        parent::setUp();

        \Myfox\Lib\Mysql::register('default', __DIR__ . '/ini/mysql.ini');
    }
    /* }}} */

    /* {{{ protected void tearDown() */
    protected function tearDown()
    {
        parent::tearDown();
    }
    /* }}} */

    /* {{{ public void test_should_throw_exception_when_action_not_defined() */
    public function test_should_throw_exception_when_action_not_defined()
    {
        $controller = new Controller();
        try {
            $controller->execute('i_am_not_defined', array());
            $this->assertTrue(false, 'Exception should be throwed out.');
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof \Myfox\Lib\Exception);
            $this->assertContains('Undefined action named as "i_am_not_defined"', $e->getMessage());
        }
    }
    /* }}} */

    /* {{{ public void test_should_index_action_works_fine() */
    public function test_should_index_action_works_fine()
    {
        $controller = new Controller();

        ob_start();
        $controller->execute('', array());
        $output = ob_get_contents();
        ob_clean();

        $this->assertContains('<!--STATUS OK-->', $output);

        $controller->execute('INdEX', array());
        $this->assertEquals(ob_get_contents(), $output);
        ob_end_clean();
    }
    /* }}} */

    /* {{{ public void test_should_import_index_works_fine() */
    public function test_should_import_index_works_fine()
    {
        $controller = new \Myfox\App\Control\Import();

        \Myfox\App\Setting::set('last_date', '20121031');
        
        ob_start();
        $controller->execute('index', array());
        $output = ob_get_contents();
        $this->assertContains('[0] OK', $output);
        $this->assertContains('"last_date":"20121031"', $output);

        @ob_clean();
    }
    /* }}} */

    /* {{{ public void test_should_import_route_works_fine() */
    public function test_should_import_route_works_fine()
    {
        $controller = new \Myfox\App\Control\Import();

        \Myfox\Lib\Context::register('__ip__', '127.0.0.1');

        ob_start();
        $controller->execute('route', array('table' => 'numsplit_v2'));
        $output = ob_get_contents();
        $this->assertContains('[1200] Param "route" is required.', $output);

        ob_clean();
        ob_start();
        $controller->execute('route', array(
            'table' => 'numsplit_v2',
            'route' => 'thedate:20111012,cid:1',
            'lines' => 1208,
        ));
        $output = ob_get_contents();
        $this->assertContains('[0] OK', $output);
        $this->assertContains("numsplit_v2\tcid:1,thedate:20111012\t1000", $output);
        $this->assertContains("numsplit_v2\tcid:1,thedate:20111012\t208", $output);

        @ob_clean();
    }
    /* }}} */

    /* {{{ public void test_should_import_queque_works_fine() */
    public function test_should_import_queque_works_fine()
    {
        $controller = new \Myfox\App\Control\Import();

        \Myfox\Lib\Context::register('__ip__', '119.32.212.64');

        ob_start();
        $controller->execute('queque', array('table' => 'numsplit_v2'));
        $output = ob_get_contents();
        $this->assertContains('[1100] Access Denied.', $output);

        \Myfox\Lib\Context::register('__ip__', '127.0.0.1');

        ob_clean();
        ob_start();
        $controller->execute('queque', array('table' => 'numsplit_v2'));
        $output = ob_get_contents();
        $this->assertContains('[1201] Param "file" is required in post data.', $output);

        ob_clean();
        ob_start();
        $controller->execute('queque', array('table' => 'numsplit_v2'), array(
            'file'      => 'ftp://user:pass@www.helloworld.com/test_file.txt',
            'route'     => 'cid:1,thedate:20111001',
            'bucket'    => 'numsplit_0.t_1_1',
            'hosts'     => '{3,1}',
        ));
        $output = ob_get_contents();
        $this->assertContains('[0] OK', $output);

        @ob_clean();
    }
    /* }}} */

    /* {{{ public void test_should_import_ready_works_fine() */
    public function test_should_import_ready_works_fine()
    {
        $controller = new \Myfox\App\Control\Import();

        \Myfox\Lib\Context::register('__ip__', '119.32.212.64');

        ob_start();
        $controller->execute('ready', array('table' => 'numsplit_v2'));
        $output = ob_get_contents();
        $this->assertContains('[1100] Access Denied.', $output);

        \Myfox\Lib\Context::register('__ip__', '127.0.0.1');

        ob_clean();
        ob_start();
        $controller->execute('ready', array('date' => '2012-1-2'));
        $output = ob_get_contents();
        $this->assertContains('[0] OK', $output);

        @ob_clean();
    }
    /* }}} */

    /* {{{ public void test_should_import_hello_works_fine() */
    public function test_should_import_hello_works_fine()
    {
        $controller = new \Myfox\App\Control\Import();

        \Myfox\Lib\Context::register('__ip__', '119.32.212.64');

        ob_start();
        $controller->execute('hello', array('table' => 'numsplit_v2'));
        $output = ob_get_contents();
        $this->assertContains('[1100] Access Denied.', $output);

        \Myfox\Lib\Context::register('__ip__', '127.0.0.1');

        ob_clean();
        ob_start();
        $controller->execute('hello', array('table' => 'numsplit_v2'));
        $output = ob_get_contents();
        $this->assertContains('[1200] Param "route" is required.', $output);

        ob_clean();
        ob_start();
        $controller->execute('hello', array(
            'table' => 'numsplit_v2',
            'file'  => 'ftp://user:pass@www.helloworld.com/test_file.txt',
            'route' => 'cid:1,thedate:20111001',
            'lines' => 1111111,
        ));
        $output = ob_get_contents();
        $this->assertContains('[0] OK', $output);

        @ob_clean();
    }
    /* }}} */
}

