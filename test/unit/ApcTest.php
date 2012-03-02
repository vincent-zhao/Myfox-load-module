<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Cache\Apc;

require_once(__DIR__ . '/../../lib/TestShell.php');

class ApcTest extends \Myfox\Lib\TestShell
{

    private $shell  = 0;

    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        Apc::cleanAllCache();
        parent::tearDown();
    }

    /* {{{ public void test_should_apc_without_compress_works_fine() */
    public function test_should_apc_without_compress_works_fine()
    {
        $val = array('a' => 'b', 'c' => array('d' => 'e'));
        $apc = new Apc(__METHOD__);
        $this->assertEquals(null, $apc->get('key1'), 'Apc should be empty.');

        $apc->set('key1', $val, 1);
        $apc->set('key2', $apc->get('key1'));

        $this->assertEquals($val, $apc->get('key1'), 'Apc set / get Error.');

        // XXX: 同一进程内apc不过期
        //$this->assertEquals(null, $apc->get('key1'), 'Apc should has been expired.');
        $this->assertEquals($val, $apc->get('key2'), 'Apc set / get Error.');

        $apc->delete('key1');
        $apc->delete('key2');
        $this->assertEquals(null, $apc->get('key2'), 'Apc should has been delete.');
    }
    /* }}} */

    /* {{{ public void test_should_apc_with_compress_works_fine() */
    public function test_should_apc_with_compress_works_fine()
    {
        $val = array('a' => 'b', 'c' => array('d' => 'e'));
        $apc = new Apc(__METHOD__, true);
        $this->assertEquals(null, $apc->get('key1'), 'Apc should be empty.');

        $apc->set('key1', $val, 1);
        $apc->set('key2', $apc->get('key1'));

        $this->assertEquals($val, $apc->get('key1'), 'Apc set / get Error with compress.');
    }
    /* }}} */

    /* {{{ public void test_should_cache_shell_works_fine() */
    public function test_should_cache_shell_works_fine()
    {
        $apc = new Apc(__METHOD__);
        $this->shell = 0;

        $this->assertEquals(md5(1), $apc->shell(array(&$this, 'loadShellData'), 1));
        $this->assertEquals(1, $this->shell);

        $this->assertEquals(md5(1), $apc->shell(array(&$this, 'loadShellData'), 1));
        $this->assertEquals(1, $this->shell);

        $this->assertEquals(md5(3), $apc->shell(array(&$this, 'loadShellData'), 3));
        $this->assertEquals(2, $this->shell);
    }
    /* }}} */

    /* {{{ public void test_should_apc_add_works_fine() */
    public function test_should_apc_add_works_fine()
    {
        $apc = new Apc(__METHOD__);
        $this->assertTrue($apc->add('key1', 'val1'));
        $this->assertFalse($apc->add('key1', 'val2'));
    }
    /* }}} */

    public function test_should_data_compress_works_fine()
    {
        $apc = new Apc(__METHOD__, true);

        $val = array(
            'a' => 1111,
            'b' => str_pad('a', Apc::COMPRESS_SIZE + 1),
        );
        $this->assertTrue($apc->set('key1', $val));

        // xxx: __destruct only for flush to apc
        $apc = null;

        $apc = new Apc(__METHOD__, true);
        $this->assertEquals($val, $apc->get('key1', false));
    }

    /* {{{ public Mixture loadShellData() */
    public function loadShellData($key)
    {
        $this->shell++;
        return md5($key);
    }
    /* }}} */

}

