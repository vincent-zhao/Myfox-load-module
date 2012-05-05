<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Fileset;
use \Myfox\lib\fetcher\Scp;

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

	/* {{{ public void test_should_get_file_from_scp_works_fine() */
	public function test_should_get_file_from_scp_works_fine()
	{
        #exec('ssh-keygen -t rsa');
        /* 信任关系打通 设置免登 */
        exec('ssh-copy-id -i ~/.ssh/id_rsa.pub  10.232.128.63');
        #$user = get_current_user();
        $user  = array_shift(explode(' ', trim(exec('who am i'))));
        $fname = sprintf('Myfox_scp_test%s.txt', getmypid());

        $lpath = sprintf('/tmp/myfox/download/Myfox_scp_test%s.txt', getmypid());
        $rpath = sprintf('/home/%s/Myfox_scp_test%s.txt', $user, getmypid());

        $url = sprintf('scp://%s@10.232.128.63%s', $user, $rpath);

        exec(sprintf('ssh %s@10.232.128.63 "rm -rf %s"', $user, $rpath));
        @unlink($lpath);


        $this->assertEquals(false, Fileset::getfile($url));
        $this->assertContains('No such file or directory', Fileset::lastError());

        exec(sprintf('ssh %s@10.232.128.63 "touch %s"', $user, $rpath));
        sleep(2);
        $this->assertEquals( $lpath, Fileset::getfile($url));

        $scp = new Scp(array(
            'scheme' => 'scp',
            'host'   => '10.232.128.63',
            'user'   => $user,
            'path'   => $rpath,
        ));

        $this->assertEquals( false, $scp->isChange($lpath));

        exec(sprintf('ssh %s@10.232.128.63 "echo \"test data\" >> %s"', $user, $rpath));

        $this->assertEquals( $lpath, Fileset::getfile($url));
        $content = file_get_contents($lpath);
        $this->assertContains('test data', $content);
	}
	/* }}} */
}
