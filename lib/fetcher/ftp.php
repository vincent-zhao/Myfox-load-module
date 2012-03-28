<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | FTP获取文件	    					    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>	    							|
// +------------------------------------------------------------------------+
//
// $Id: ftp.php 22 2010-04-15 16:28:45Z zhangxc83 $

namespace Myfox\Lib\Fetcher;

class Ftp
{

    /* {{{ 成员变量 */

    private $error	= '';

    private $handle = null;

    private $option;

    private $tmout  = 2;                /**<    连接超时(s) */

    /* }}} */

    /* {{{ public void __construct() */
    /**
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct($option)
    {
        $this->option   = (array)$option + array(
            'host'  => '',
            'port'  => 21,
            'user'  => 'anonymous',
            'pass'  => '',
        );
        if (!empty($this->option['query'])) {
            parse_str($this->option['query'], $mc);
            if (!empty($mc['timeout'])) {
                $this->tmout    = (int)$mc['timeout'];
            }
        }
    }
    /* }}} */

    /* {{{ public void __destruct() */
    public function __destruct()
    {
        $this->close();
    }
    /* }}} */

    /* {{{ public Boolean fetch() */
    /**
     * 获取文件
     *
     * @access public
     * @return Boolean true or false
     */
    public function fetch($fname, $cache = true)
    {
        if (!$this->connect()) {
            return false;
        }

        // TODO:
        //
        // @ 基于md5文件的抓取 , 变更判断
        // @ 基于 filesize + filemtime 的变更判断
        if (!$this->get($this->option['path'], $fname)) {
            return false;
        }

        return true;
    }
    /* }}} */

    /* {{{ public Mixture lastError() */
    /**
     * 获取错误描述
     *
     * @access public
     * @return Mixture
     */
    public function lastError()
    {
        return $this->error;
    }
    /* }}} */

    /* {{{ private Boolean connect() */
    /**
     * 连接FTP服务器
     *
     * @access private
     * @return Boolean true or false
     */
    private function connect()
    {
        if (!empty($this->handle)) {
            return true;
        }

        $this->handle   = ftp_connect($this->option['host'], $this->option['port'], $this->tmout);
        if (empty($this->handle)) {
            $this->error    = sprintf(
                'Connect failed with the host as "%s" on port %d',
                $this->option['host'], $this->option['port']
            );
            return false;
        }

        if (!ftp_login($this->handle, $this->option['user'], $this->option['pass'])) {
            $this->error    = sprintf(
                'Access Denied for user "%s", use password %s',
                $this->option['user'], empty($this->option['pass']) ? 'NO' : 'YES'
            );
            return false;
        }
        ftp_pasv($this->handle, true);

        return true;
    }
    /* }}} */

    /* {{{ private void close() */
    /**
     *  关闭连接
     *
     *  @access private
     *  @return void
     */
    private function close()
    {
        if (!empty($this->handle)) {
            ftp_close($this->handle);
        }
        $this->handle   = null;
    }
    /* }}} */

    /* {{{ private Boolean get() */
    /**
     * 获取文件
     *
     * @access private
     * @return Boolean true or false
     */
    private function get($remote, $local)
    {
        $fname  = sprintf('%s.%d', $local, getmypid());
        if (!ftp_get($this->handle, $fname, $remote, FTP_BINARY)) {
            @unlink($fname);
            $this->error    = sprintf('');
            return false;
        }

        return rename($fname, $local);
    }
    /* }}} */

}
