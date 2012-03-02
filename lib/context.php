<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 上下文环境																|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: context.php 4 2010-03-09 05:20:36Z zhangxc83 $

namespace Myfox\Lib;

class Context
{

    /* {{{ 静态变量 */

    /**
     * @数据列表
     */
    private static $data    = array();

    /* }}} */

    /* {{{ public static void register() */
    /**
     * 注册一个变量
     *
     * @access public static
     * @param  String $key
     * @param  Mixture $val
     * @return void
     */
    public static function register($key, $val)
    {
        self::$data[(string)$key] = $val;
    }
    /* }}} */

    /* {{{ public static void unregister() */
    /**
     * 注销一个变量
     *
     * @access public static
     * @param  String $key
     * @return void
     */
    public static function unregister($key)
    {
        $key = (string)$key;
        if (isset(self::$data[$key])) {
            unset(self::$data[$key]);
        }
    }
    /* }}} */

    /* {{{ public static void cleanAllContext() */
    /**
     * 清理所有上下文数据
     *
     * @access public static
     * @return void
     */
    public static function cleanAllContext()
    {
        self::$data = array();
    }
    /* }}} */

    /* {{{ public static Mixture get() */
    /**
     * 获取变量
     *
     * @access public static
     * @param  String $key
     * @param  Mixture $default : default null
     * @return Mixture
     */
    public static function get($key, $default = null)
    {
        $key = (string)$key;
        if (isset(self::$data[$key])) {
            return self::$data[$key];
        }

        return $default;
    }
    /* }}} */

    /* {{{ public static Mixture addr() */
    /**
     * 获取本地IP地址
     *
     * @access public static
     * @return Mixture
     */
    public static function addr($int = false)
    {
        if (null === ($ip = self::get('__addr__'))) {
            $ip = trim(exec('hostname -i'));
            self::register('__addr__', $ip);
        }

        return $int ? sprintf('%u', ip2long($ip)) : $ip;
    }
    /* }}} */

    /* {{{ public static Mixture userip() */
    /**
     * 获取当前用户IP
     *
     * @access public static
     * @param  Boolean $bolInt (default false)
     * @return String or Integer
     */
    public static function userip($bolInt = false)
    {
        if (null === ($ret = self::get('__ip__'))) {
            $ret = self::_userip();
            self::register('__ip__', $ret);
        }

        return $bolInt ? sprintf('%u', ip2long($ret)) : $ret;
    }
    /* }}} */

    /* {{{ public static Integer pid() */
    /**
     * 获取当前进程号
     *
     * @access public static
     * @return Integer
     */
    public static function pid()
    {
        if (null === ($ret = self::get('__pid__'))) {
            $ret = is_callable('posix_getpid') ? posix_getpid() : getmypid();
            self::register('__pid__', $ret);
        }

        return $ret;
    }
    /* }}} */

    /* {{{ private static String _userip() */
    /**
     * 读取用户实际IP
     *
     * @access private static
     * @return String
     */
    private static function _userip()
    {
        $check  = array(
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        );

        foreach ($check AS $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }

            if (!preg_match_all('/\d+\.\d+\.\d+.\d+/', $_SERVER[$key], $match)) {
                continue;
            }

            return end($match[0]);
        }

        return 'unknown';
    }
    /* }}} */

}

