<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 文件切分类		    					    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>	    							|
// +------------------------------------------------------------------------+
//
// $Id: fsplit.php 22 2010-04-15 16:28:45Z zhangxc83 $

namespace Myfox\Lib;

class Fsplit
{

    /* {{{ 静态常量 */

    const SPLIT_PATH    = '/tmp/myfox/split';

    const BUFFER_SIZE   = 16777216;      /**<    16M */

    const END_OF_LINE   = "\n";

    /* }}} */

    /* {{{ 成员变量 */

    private $fname  = null;

    private $bfsize = 0;

    private $buffer = '';

    private $lnsize = 0;

    private $eofl   = self::END_OF_LINE;

    private $error  = null;

    private $handle = null;

    /* }}} */

    /* {{{ public static Mixture chunk() */
    /**
     * 按行切分文件
     *
     * @access public static
     * @return Mixture
     */
    public static function chunk($fname, $slice, $spath = self::SPLIT_PATH)
    {
        $ob	= new self($fname);
        return $ob->split($slice, $spath);
    }
    /* }}} */

    /* {{{ public String lastError() */
    /**
     * 返回上次错误
     * @access public
     * @return String
     */
    public function lastError()
    {
        return $this->error;
    }
    /* }}} */

    /* {{{ public void __construct() */
    /**
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct($fname, $eofl = self::END_OF_LINE, $bfsize = self::BUFFER_SIZE)
    {
        $this->fname    = trim($fname);
        $this->bfsize   = (int)$bfsize;
        if (strlen($eofl) > 0) {
            $this->eofl = $eofl;
        }
    }
    /* }}} */

    /* {{{ public void __destruct() */
    /**
     * 析构函数
     *
     * @access public
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }
    /* }}} */

    /* {{{ public Mixture split() */
    /**
     * 进行文件切分
     *
     * @access public
     * @return Mixture
     */
    public function split($slice, $spath = '')
    {
        $fn = realpath($this->fname);
        if (empty($fn)) {
            $this->error    = sprintf('No such file named as "%s".', $this->fname);
            return false;
        }

        if (!is_dir($spath) && !@mkdir($spath, 0755, true)) {
            $this->error    = sprintf('Directory "%s" created failed.', $spath);
            return false;
        }

        $this->fname    = $fn;
        if (false === ($this->handle = fopen($this->fname, 'rb'))) {
            $this->error    = sprintf('File "%s" open failed.', $this->fname);
            return false;
        }

        if (empty($this->lnsize) && !$this->test()) {
            return false;
        }

        $spath  = sprintf('%s/%s', realpath($spath), basename($this->fname));
        $slice  = (array)$slice;

        $wfname = $spath . '_' . key($slice);
        if (!$this->truncate($wfname)) {
            $this->close();
            return false;
        }

        $chunks = array($wfname);
        $offset = (int)ceil(current($slice) * $this->lnsize);

        $buffer = $this->buffer;
        $this->buffer   = '';

        while (1) {
            $buffer .= fread($this->handle, $this->bfsize);
            $length = strlen($buffer);

            while ($length >= $offset) {
                $endpos = strpos($buffer, $this->eofl, $offset);
                if (false === $endpos) {
                    break;
                }

                if (!$this->append($wfname, substr($buffer, 0, $endpos + 1))) {
                    $this->close();
                    return false;
                }

                $buffer = substr($buffer, $endpos + 1);
                $length = strlen($buffer);
                if (false !== ($next = next($slice))) {
                    $wfname = $spath . '_' . key($slice);
                    $offset = (int)ceil($this->lnsize * $next);
                    if (!$this->truncate($wfname)) {
                        $this->close();
                        return false;
                    }
                    $chunks[]   = $wfname;
                }
            }

            if (feof($this->handle)) {
                if ($length > 0) {
                    if (!$this->append($wfname, $buffer)) {
                        $this->close();
                        return false;
                    }
                    $buffer = '';
                    $length = 0;
                }
                break;
            }
        }
        $this->close();

        return $chunks;
    }
    /* }}} */

    /* {{{ private void close() */
    /**
     * 关闭文件句柄
     *
     * @access private
     * @return void
     */
    private function close()
    {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle   = null;
        }
    }
    /* }}} */

    /* {{{ private Boolean test() */
    /**
     * 测试文件, 读取每行大小
     *
     * @access private
     * @return Boolean true or false
     */
    private function test()
    {
        $this->buffer   = (string)fread($this->handle, self::BUFFER_SIZE);
        $buffer = explode($this->eofl, $this->buffer);
        if (empty($buffer) || !isset($buffer[1])) {
            $this->error    = sprintf(
                'Unrecognized text formmat, or line size larger than %d.', self::BUFFER_SIZE
            );
            return false;
        }

        $count  = count($buffer) - 1;
        $buffer[$count] = $this->eofl;
        $this->lnsize   = strlen(implode($this->eofl, $buffer)) / $count;

        return true;
    }
    /* }}} */

    /* {{{ private Boolean truncate() */
    /**
     * 清理已有文件
     *
     * @access private
     * @return Boolean true or false
     */
    private function truncate($fname)
    {
        $rt = true;
        if (is_file($fname) && !unlink($fname)) {
            $this->error    = sprintf('File "%s" already exists, and unlink failed.', $fname);
            $rt = false;
        }

        return $rt;
    }
    /* }}} */

    /* {{{ private Boolean append() */
    /**
     * 写入文件
     *
     * @access private
     * @return Boolean true or false
     */
    private function append($fname, $data)
    {
        if (false === file_put_contents($fname, $data, FILE_APPEND, null)) {
            $this->error    = sprintf('Append file "%s" failed.', $fname);
            return false;
        }

        return true;
    }
    /* }}} */

}
