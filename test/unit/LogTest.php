<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +--------------------------------------------------------------------+
// | Log Test Shell														|
// +--------------------------------------------------------------------+
//
// $Id: LogTest.php 92 2010-06-02 13:20:01Z zhangxc83 $

use \Myfox\Lib\Log;

require_once(__DIR__ . '/../../lib/TestShell.php');

class LogTest extends \Myfox\Lib\TestShell
{

    private $file;

    /* {{{ protected setUp() */
    protected function setUp()
    {
        parent::setUp();
    }
    /* }}} */

    /* {{{ protected tearDown() */
    protected function tearDown()
    {
        clearstatcache();
        @unlink($this->file);
        $this->file = null;

        parent::tearDown();
    }
    /* }}} */

    private static function getLastLine($file)
    {
        if (false === ($arr = @file($file))) {
            return '';
        }

        return trim(array_pop($arr));
    }

    public function test_should_write_debug_log_ok()
    {
        $log = new Log(sprintf(
            'log://debug/%s/log_for_test.log?buffer=0', __DIR__
        ));
        $this->assertEquals(0, $log->cache, 'Init Log Buffer Error.');

        $this->file = $log->file;

        $log->debug('test_DEBUG', 'only for test');
        $log->notice('test_notice', array('a' => 'b', 'c' => $log), 'token');
        $log->warn('test_warn', array('a' => 'b', 'c' => $log), 'token');
        $log->error('test_error', array('a' => 'b', 'c' => $log), 'token');

        clearstatcache();
        $this->assertEquals(
            preg_match(
                "/^DEBUG:\t\[.+?\]\t.+?\tTEST_DEBUG\t-\tonly for test/",
                self::getLastLine($log->file)
            ),
            1, 'Debug Log Content Error.'
        );
    }

    public function test_should_write_notice_log_ok()
    {
        $log = new Log(sprintf(
            'log://notice.blablalla/%s/log_for_test.log?buffer=02', __DIR__
        ));
        $this->assertEquals(2, $log->cache, 'Init Log Buffer Error.');

        $data = array(
            'a' => 'b',
            'c' => $log,
        );
        $log->debug('test_DEBUG', 'only for test');
        $log->notice('test_notice', $data, 'token');
        $log->warn('test_warn', $data, 'token');
        $log->error('test_error', $data, 'token');

        clearstatcache();
        $this->assertEquals(
            preg_match(
                sprintf("/^NOTICE:\t\[.+?\]\t.+?\tTEST_NOTICE\ttoken\t%s/", json_encode($data)),
                self::getLastLine($log->file)
            ),
            1, 'Notice Log Content Error.'
        );
        $this->file = $log->file;
    }

    public function test_should_write_warn_log_ok()
    {
        $log = new Log(sprintf(
            'log://WarN.blablalla/%s/log_for_test.log?buffer=02s02s', __DIR__
        ));
        $this->assertEquals(2, $log->cache, 'Init Log Buffer Error.');

        $data = array(
            'a' => 'b',
            'c' => $log,
        );
        $log->debug('test_DEBUG', 'only for test');
        $log->notice('test_notice', $data, 'token');
        $log->warn('test_warn', $data, 'token');
        $log->error('test_error', $data, 'token');

        clearstatcache();
        $this->assertEquals(
            preg_match(
                sprintf("/^WARN:\t\[.+?\]\t.+?\tTEST_WARN\ttoken\t%s/", json_encode($data)),
                self::getLastLine($log->file)
            ),
            1, 'Warn Log Content Error.'
        );
        $this->file = $log->file;
    }

    public function test_should_write_error_log_ok()
    {
        $log = new Log(sprintf(
            'log://ERROR.blablalla/%s/log_for_test.log?buffer=02', __DIR__
        ));

        $data = array(
            'a' => 'b',
            'c' => $log,
        );
        $log->debug('test_DEBUG', 'only for test');
        $log->notice('test_notice', $data, 'token');
        $log->warn('test_warn', $data, 'token');
        $log->error('test_error', $data, 'token');

        clearstatcache();
        $this->assertEquals(
            preg_match(
                sprintf("/^ERROR:\t\[.+?\]\t.+?\tTEST_ERROR\ttoken\t%s/", json_encode($data)),
                self::getLastLine($log->file)
            ),
            1, 'Error Log Content Error.'
        );
        $this->file = $log->file;
    }

    public function test_should_buffer_control_fine()
    {
        $log = new Log(sprintf(
            'log://debug.warn.notice.ERROR.blablalla/%s/log_for_test.log', __DIR__
        ));

        $data = array(
            'a' => 'b',
            'c' => $log,
        );
        $log->debug('test_DEBUG', 'only for test');
        $log->notice('test_notice', $data, 'token');
        $log->warn('test_warn', $data, 'token');
        $log->error('test_error', $data, 'token');
        $this->file = $log->file;

        clearstatcache();
        $this->assertEquals('', self::getLastLine($log->file), 'File MUST BE STILL IN BUFFER.');

        $log->debug('BUFFER', str_repeat('a', 4096 - 47 - strlen($log->buffer)));
        $this->assertEquals(1, $log->iotime, 'Log I/OTIME ERROR.');
        $this->assertEquals(
            preg_match(
                "/^DEBUG:\t\[.+?\]\t.+?\tBUFFER\t-\ta{1,}$/",
                self::getLastLine($log->file)
            ),
            1, 'Buffer Control Error.'
        );
    }

    public function test_should_flush_data_when_destruct()
    {
        $log = new Log(sprintf(
            'log://debug.warn.notice.ERROR.blablalla/%s/log_for_test.log', __DIR__
        ));

        $this->file = $log->file;

        $log->debug('DESTRUCT', null);

        $log = 'sf';

        clearstatcache();
        $this->assertEquals(
            preg_match(
                "/^DEBUG:\t\[.+?\]\t.+?\tDESTRUCT/",
                self::getLastLine($this->file)
            ),
            1, 'Data lost when log destruct.'
        );
    }

}

