<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 报警处理类								    							|
// +------------------------------------------------------------------------+
// | Copygight (c) 2003 - 2012 Taobao.com. All Rights Reserved				|
// +------------------------------------------------------------------------+
// | Author: pengchun <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+

namespace Myfox\Lib;

use \Myfox\Lib\Config;

class Alert
{

    /* {{{ 静态常量 */

    const NORMAL    = 1;

    const URGENCE   = 2;

    /* }}} */

    /* {{{ 静态变量 */

    private static $inited  = false;

    private static $config  = null;

    /* }}} */

    /* {{{ public static void init() */
    /**
     * 配置初始化方法 
     *
     * @access public static
     * @return void
     */
    public static function init($ini)
    {
        self::$config   = new Config($ini);
        self::$inited   = true;
    }
    /* }}} */

    /* {{{ public static void push() */
    /**
     * 压入报警
     *
     * @access public static
     * @return void
     */
    public static function push($title, $level = self::NORMAL)
    {
        if (!self::$inited) {
            return;
        }

        $level  = (int)$level;
        $title  = sprintf('[%s] %s', self::$config->get('prefix'), trim($title));

        if (self::URGENCE & $level) {
            $rt = self::call('urgence', $title, $error);
            if (empty($rt)) {
                $title  = sprintf('%s (%s)', $title, $error);
                $level  = self::NORMAL;
            }
        }

        if ((self::NORMAL & $level) && !self::call('normal', $title, $error)) {
            printf("[%s] %s\n", date('Y-m-d H:i:s'), $title);
        }
    }
    /* }}} */

    /* {{{ private static Boolean call() */
    /**
     * alert命令调用
     *
     * @access private static
     * @return Boolean true or false
     */
    private static function call($name, $title, &$error)
    {
        $caller = self::$config->get(sprintf('%s/command', trim($name)));
        if (empty($caller)) {
            $error  = 'undefined command for ' . $name;
            return false;
        }

        $error  = system(strtr($caller, array(
            '{TITLE}'   => escapeshellcmd($title),
        )), $code);

        return empty($code) ? true : false;
    }
    /* }}} */

    /* {{{ private void __construct() */
    /**
     * 构造函数占位
     *
     * @access private
     * @return void
     */
    private function __construct()
    {
    }
    /* }}} */

}

