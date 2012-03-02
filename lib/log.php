<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +--------------------------------------------------------------------+
// | 日志类	    														|
// +--------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>								|
// +--------------------------------------------------------------------+
//
// $Id: log.php 86 2010-06-01 03:52:51Z zhangxc83 $

namespace Myfox\Lib;

class Log
{

    /* {{{ 静态常量 */

    const LOG_DEBUG     = 1;
    const LOG_NOTICE    = 2;
    const LOG_WARN      = 8;
    const LOG_ERROR     = 16;

    /* }}} */

    /* {{{ 静态常量 */

    private static $symbol = array();         /**<  文件名中的通配符      */

    /* }}} */

    /* {{{ 成员变量 */

    private $url    = null;

    private $file   = null;

    private $level  = 0;          /**<  日志级别      */

    private $iotime = 0;          /**<  磁盘IO次数      */

    private $buffer = '';         /**<  数据缓冲区      */

    private $cache  = 4096;       /**<  最大缓冲量      */

    private $circuit = false;

    /* }}} */

    /* {{{ public void __construct() */
    /**
     * 构造函数
     *
     * @access public
     * @param  String $url
     * @return void
     */
    public function __construct($url)
    {
        $this->url  = trim($url);
        $this->file = null;
        $this->init();
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
        $this->flush(true);
    }
    /* }}} */

    /* {{{ public void debug() */
    /**
     * 写Debug日志
     *
     * @access private
     * @param  String $name
     * @param  String $data
     * @param  String $token
     * @return void
     */
    public function debug($name, $data, $token = null)
    {
        if ($this->level & self::LOG_DEBUG) {
            return $this->insert('DEBUG', $name, $data, $token);
        }
    }
    /* }}} */

    /* {{{ public void notice() */
    /**
     * 写Notice日志
     *
     * @access private
     * @param  String $name
     * @param  String $data
     * @param  String $token
     * @return void
     */
    public function notice($name, $data, $token = null)
    {
        if ($this->level & self::LOG_NOTICE) {
            return $this->insert('NOTICE', $name, $data, $token);
        }
    }
    /* }}} */

    /* {{{ public void warn() */
    /**
     * 写Warn日志
     *
     * @access private
     * @param  String $name
     * @param  String $data
     * @param  String $token
     * @return void
     */
    public function warn($name, $data, $token = null)
    {
        if ($this->level & self::LOG_WARN) {
            return $this->insert('WARN', $name, $data, $token);
        }
    }
    /* }}} */

    /* {{{ public void error() */
    /**
     * 写Error日志
     *
     * @access private
     * @param  String $name
     * @param  String $data
     * @param  String $token
     * @return void
     */
    public function error($name, $data, $token = null)
    {
        if ($this->level & self::LOG_ERROR) {
            return $this->insert('ERROR', $name, $data, $token);
        }
    }
    /* }}} */

    /* {{{ public Mixture __get() */
    /**
     * 魔术方法__get
     *
     * @access public
     * @return Mixture
     */
    public function __get($key)
    {
        if (!isset($this->$key)) {
            return null;
        }

        return $this->$key;
    }
    /* }}} */

    /* {{{ private Boolean insert() */
    /**
     * 写入一行日志
     *
     * @access private
     * @param  String $char : 日志级别
     * @param  String $name
     * @param  String $data
     * @param  String $token
     * @return Boolean true or false
     */
    private function insert($char, $name, $data, $token)
    {
        if (empty($this->file)) {
            return false;
        }

        $name = empty($name) ? 'UNKOWN' : $name;
        $data = empty($data) ? '-' : $data;
        $this->buffer .= sprintf(
            "%s:\t[%s]\t%s\t%s\t%s\t%s\n",
            $char, date('Y-m-d H:i:s'),
            Context::userip(),
            strtoupper($name),
            empty($token) ? '-' : $token,
            is_scalar($data) ? $data : json_encode($data)
        );

        if (strlen($this->buffer) >= $this->cache) {
            $this->flush();
        }

        return true;
    }
    /* }}} */

    /* {{{ private Boolean flush() */
    /**
     * 将日志固化在磁盘上
     *
     * @access private
     * @return Boolean true or false
     */
    private function flush($try = false)
    {
        if (empty($this->buffer) || ($try !== true && $this->circuit)) {
            return true;
        }

        $err = error_reporting();
        error_reporting($err ^ E_WARNING);

        if (!is_file($this->file)) {
            $dir = dirname($this->file);
            if (!is_dir($dir)) {
                mkdir($dir, 0744, true);
            }
        }
        $len = file_put_contents($this->file, $this->buffer, FILE_APPEND);
        error_reporting($err);

        $max = strlen($this->buffer);
        $this->buffer = (string)substr($this->buffer, (int)$len);
        $this->iotime++;

        if (false === $len || $len < $max) {
            $this->circuit = true;
            return false;
        }

        return true;
    }
    /* }}} */

    /* {{{ private Boolean init() */
    /**
     * 初始化日志对象
     *
     * @access private
     * @return Boolean true or false
     */
    private function init()
    {
        $url = parse_url($this->url);
        if (isset($url['query']) && preg_match('/[\?&]?buffer=(\d+)/is', $url['query'], $match)) {
            $this->cache = (int)$match[1];
        }

        $this->file     = isset($url['path']) ? $url['path'] : '';
        if (0 === strpos($this->file, '/')) {
            $this->file = substr($this->file, 1);
        }

        $this->level    = 0;
        $tmp = array_flip(explode('.', strtolower($url['host'])));
        if (isset($tmp['debug'])) {
            $this->level += self::LOG_DEBUG;
        }
        if (isset($tmp['notice'])) {
            $this->level += self::LOG_NOTICE;
        }
        if (isset($tmp['warn'])) {
            $this->level += self::LOG_WARN;
        }
        if (isset($tmp['error'])) {
            $this->level += self::LOG_ERROR;
        }

        if (empty(self::$symbol) && $this->level > 0) {
            self::$symbol = array(
                '{DATE}'  => date('Ymd'),
                '{HOUR}'  => date('H'),
                '{WEEK}'  => date('w'),
            );
        }

        if (!empty(self::$symbol)) {
            $this->file = str_replace(
                array_keys(self::$symbol),
                self::$symbol, $this->file
            );
        }

        return true;
    }
    /* }}} */

}

