<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Fileset;

require_once(__DIR__ . '/../../lib/TestShell.php');

class FilesetTest extends \Myfox\Lib\TestShell
{

	/* {{{ public void test_should_get_local_file_works_fine() */
	public function test_should_get_local_file_works_fine()
	{
		$this->assertEquals(false, Fileset::getfile('/i/am/not/exists?query string does\'t effect'));
		$this->assertContains('File not found as the path "/i/am/not/exists"', Fileset::lastError());
		$this->assertEquals(__FILE__, Fileset::getfile(__FILE__));
	}
	/* }}} */

	/* {{{ public void test_should_getfile_exception_works_fine() */
	public function test_should_getfile_exception_works_fine()
	{
		$this->assertEquals(false, Fileset::getfile('http://www.taobao.com', '/i/am/not/exists/'));
		$this->assertContains('Unrecognized url as "http://www.taobao.com" for fileset.', Fileset::lastError());

		$this->assertEquals(false, Fileset::getfile('http://www.taobao.com/index.html', '/i/am/not/exists/taobao.txt'));
        $this->assertContains('Path "/i/am/not/exists" doesn\'t exist, and create failed.', Fileset::lastError());

		$this->assertEquals(false, Fileset::getfile('undefined://user:pass@ftp.a.com//b.txt'));
		$this->assertContains(sprintf(
			'File "%s/lib/fetcher/undefined.php" Not Found.',
			realpath(__DIR__ . '/../../')
		), Fileset::lastError());
	}
	/* }}} */

	/* {{{ public void test_should_get_file_from_ftp_works_fine() */
	public function test_should_get_file_from_ftp_works_fine()
    {
        $this->assertEquals(false, Fileset::getfile('ftp://www.baidu.com/aa.txt'));
        $this->assertContains('Connect failed with the host as "www.baidu.com" on port 21', Fileset::lastError());
        
        $fname  = Fileset::getfile('ftp://ftp.adobe.com/license.txt?timeout=5');
        $this->assertEquals(sprintf('%s/license.txt', Fileset::TEMP_PATH), $fname);
	}
	/* }}} */

}
