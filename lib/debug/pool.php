<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +--------------------------------------------------------------------+
// | Debug工具类	        											|
// +--------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>								|
// +--------------------------------------------------------------------+
//
// $Id: pool.php 2010-04-23  zhangxc83 Exp $

namespace Myfox\Lib\Debug;

class Pool
{

    /* {{{ 静态变量 */

    private static $open  = false;

    private static $debug = array();

    /* }}} */

    /* {{{ public static void init() */
    /**
     * 切换debug开关
     *
     * @access public static
     * @param Boolean $open
     * @return void
     */
    public static function init($open)
    {
        self::$open = (bool)$open;
    }
    /* }}} */

    /* {{{ public static void push() */
    /**
     * 压入debug数据
     *
     * @access public static
     * @param  String $key
     * @param  Mixture $val
     * @return void
     */
    public static function push($key, $val)
    {
        if (!self::$open) {
            return false;
        }

        if (!isset(self::$debug[$key])) {
            self::$debug[$key] = $val;
            return $val;
        }

        if (!is_array(self::$debug[$key])) {
            self::$debug[$key] = array(self::$debug[$key]);
        }
        self::$debug[$key][] = $val;

        return count(self::$debug[$key]);
    }
    /* }}} */

    /* {{{ public static void clean() */
    /**
     * 清理所有debug数据
     *
     * @access public static
     * @return void
     */
    public static function clean()
    {
        self::$debug = array();
    }
    /* }}} */

    /* {{{ public static String dump() */
    /**
     * 打出debug数据
     *
     * @access public static
     * @param  String $key (default null)
     * @return String
     */
    public static function dump($key = null)
    {
        if (null === $key) {
            return var_export(self::$debug, true);
        }

        if (!isset(self::$debug[$key])) {
            return 'NULL';
        }

        return var_export(self::$debug[$key], true);
    }
    /* }}} */

}

