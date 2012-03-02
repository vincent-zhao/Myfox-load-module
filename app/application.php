<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 应用初始化类	    					    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: application.php 18 2010-04-13 15:40:37Z zhangxc83 $

namespace Myfox\App;

class Application
{

    /* {{{ 静态变量 */

    private static $autoloaded	= false;

    /* }}} */

    /* {{{ public static void setAutoLoad() */
    /**
     * 自动加载初始化
     *
     * @access public static
     * @return void
     */
    public static function setAutoLoad()
    {
        if (self::$autoloaded) {
            return;
        }

        require_once __DIR__ . '/../lib/autoload.php';

        \Myfox\Lib\AutoLoad::init();
        \Myfox\Lib\AutoLoad::register('myfox\\app',    __DIR__);

        self::$autoloaded	= true;
    }
    /* }}} */

    /* {{{ public static void init() */
    /**
     * 各个对象初始化 
     *
     * @access public static
     * @return void
     */
    public static function init($ini)
    {
        self::setAutoLoad();

        /**
         * @配置文件
         */
        \Myfox\Lib\Config::register('default', $ini);
        $config	= \Myfox\Lib\Config::instance('default');

        /**
         * @数据库
         */
        foreach ((array)$config->get('mysql') AS $name => $file) {
            \Myfox\Lib\Mysql::register($name, $file);
        }

        /**
         * @告警提醒
         */
        \Myfox\Lib\Alert::init(__DIR__ . '/../etc/alert.ini');
    }
    /* }}} */

}
