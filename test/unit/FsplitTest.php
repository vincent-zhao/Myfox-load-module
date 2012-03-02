<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Fsplit;

require_once(__DIR__ . '/../../lib/TestShell.php');

class FsplitTest extends \Myfox\Lib\TestShell
{

	/* {{{ protected void setUp() */
	protected function setUp()
	{
		parent::setUp();
		@exec(sprintf('rm -rf "%s"', __DIR__ . '/tmp'));
	}
	/* }}} */

	/* {{{ protected void tearDown() */
	protected function tearDown()
	{
		//@exec(sprintf('rm -rf "%s"', __DIR__ . '/tmp'));
		parent::tearDown();
	}
	/* }}} */

	/* {{{ private static String random() */
	private static function random($a, $b = 1000)
	{
		return implode('', array_fill(0, rand((int)$a, (int)$b), 'a'));
	}
	/* }}} */

    /* {{{ private static Integer fileline() */
    private static function fileline($fname)
    {
        $rt = exec(sprintf('wc -l "%s"', $fname), $output, $code);
        if (false === $rt || !empty($code)) {
            return false;
        }

        return (int)$rt;
    }
    /* }}} */

	/* {{{ private static Boolean prepare_test_file() */
	/**
	 * 准备测试文件
	 *
	 * @access private
	 * @return Boolean
	 */
	private static function prepare_test_file($fname, $lines = 2000)
	{
		$dr	= dirname($fname);
		if (!is_dir($dr) && !mkdir($dr, 0755, true)) {
			return false;
		}

		if (is_file($fname)) {
			@unlink($fname);
		}

		$rt	= array();
		for ($i = 0; $i < (int)$lines; $i++) {
			$rt[]	= self::random(100, 200);
			if (0 === ($i % 100)) {
				file_put_contents($fname, implode("\n", $rt) . "\n", FILE_APPEND, null);
				$rt	= array();
			}
		}

		if (!empty($rt)) {
			file_put_contents($fname, implode("\n", $rt) . "\n", FILE_APPEND, null);
		}

		return true;
	}
	/* }}} */

	/* {{{ public void test_should_file_split_by_line_works_fine() */
	public function test_should_file_split_by_line_works_fine()
    {
        $expect = array(
            __DIR__ . '/tmp/fsplit_test.txt_0'  => 10000,
            __DIR__ . '/tmp/fsplit_test.txt_1'  => 10000,
            __DIR__ . '/tmp/fsplit_test.txt_2'  => 6000,
        );

		$fname	= __DIR__ . '/tmp/fsplit_test.txt';
        $this->assertTrue(self::prepare_test_file($fname, 27000));
        $this->assertEquals(
            array_keys($expect),
            Fsplit::chunk($fname, array_values($expect), __DIR__ . '/tmp')
        );

        $total  = 0;
        foreach ($expect AS $fname => $lines) {
            $realn  = self::fileline($fname);
            $total  += $realn;
            if (false !== next($expect)) {
                $this->assertTrue($lines * 0.95 < $realn && $lines * 1.05 > $realn);
            }
        }
        $this->assertEquals(27000, $total);
	}
	/* }}} */

    /* {{{ public void test_should_ignore_chunks_more_than_total_line() */
    public function test_should_ignore_chunks_more_than_total_line()
    {
		$fname	= __DIR__ . '/tmp/fsplit_test.txt';
        $this->assertTrue(self::prepare_test_file($fname, 2700));
        $this->assertEquals(array(
            __DIR__ . '/tmp/fsplit_test.txt_0',
            __DIR__ . '/tmp/fsplit_test.txt_1',
        ), Fsplit::chunk($fname, array(2000, 1000, 1000), __DIR__ . '/tmp'));
    }
    /* }}} */

    /* {{{ public void test_should_get_last_error_works_fine() */
    public function test_should_get_last_error_works_fine()
    {
        $object = new Fsplit('/i/am/not/exits');
        $this->assertEquals(false, $object->split(array(100)));
        $this->assertContains('No such file named as "/i/am/not/exits"', $object->lastError());

        $object = new Fsplit(__FILE__);
        $this->assertEquals(false, $object->split(array(100), '/created/denied'));
        $this->assertContains('Directory "/created/denied" created failed', $object->lastError());

        $fname	= __DIR__ . '/tmp/fsplit_test.txt';
        !is_dir(dirname($fname)) && @mkdir(dirname($fname), 0755, true);
        file_put_contents($fname, 'bbbbbbb');

        $object = new Fsplit($fname);
        $this->assertEquals(false, $object->split(array(100), __DIR__ . '/tmp'));
        $this->assertContains('Unrecognized text formmat, or line size larger than ' . Fsplit::BUFFER_SIZE, $object->lastError());
    }
    /* }}} */

}

