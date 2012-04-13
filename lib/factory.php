<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 工厂类             					    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>	    							|
// +------------------------------------------------------------------------+
//
// $Id: factory.php 22 2010-04-15 16:28:45Z zhangxc83 $

namespace Myfox\Lib;

class Factory
{

    /* {{{ 静态变量 */

    private static $objects = array();

    private static $logpool = array();

    /* }}} */

    /* {{{ public static Object getObject() */
    /**
     * 获取一个类的指定实例
     *
     * @access public static
     * @param  String $class
     * @param  String $name
     * @return Object (refferrence)
     */
    public static function &getObject($class)
    {
        $args   = func_get_args();
        $class  = array_shift($args);
        $index  = self::names($class, json_encode($args));
        if (empty(self::$objects[$index])) {
            if (!class_exists($class)) {
                \Myfox\Lib\AutoLoad::callback($class);
            }

            $rf = new \ReflectionClass($class);
            self::$objects[$index]  = $rf->newInstanceArgs($args);
        }

        return self::$objects[$index];
    }
    /* }}} */

    /* {{{ public static void removeAllObject() */
    /**
     * 清理掉所有对象和注册信息
     *
     * @access public static
     * @param  Boolean $reg (default false)
     * @return void
     */
    public static function removeAllObject($reg = false)
    {
        self::$objects  = array();
        self::$logpool  = array();
    }
    /* }}} */

    /* {{{ private static String names() */
    /**
     * 构造对象索引
     *
     * @access private static
     * @param  String $class
     * @param  String $name
     * @return String
     */
    private static function names($class, $name)
    {
        return sprintf('%s:%s', self::normalize($class), self::normalize($name));
    }
    /* }}} */

    /* {{{ private static String normalize() */
    /**
     * 类名归一化处理
     *
     * @access private static
     * @param  String $class
     * @return String
     */
    private static function normalize($class)
    {
        $class = preg_replace('/\s+/', '', preg_replace('/[\/\\\]+/', '/', $class));
        return strtolower(trim($class, '/'));
    }
    /* }}} */

    /* {{{ public static void registerLog() */
    /**
     * 注册log对象
     */
    public static function registerLog($name, $url)
    {
        self::$logpool[strtolower(trim($name))] = new \Myfox\Lib\Log($url);
    }
    /* }}} */

    /* {{{ public static Object getLog() */
    /**
     * 根据名字获取日志对象
     *
     * @access public static
     * @return Object
     */
    public static function getLog($name)
    {
        $name   = strtolower(trim($name));
        if (isset(self::$logpool[$name])) {
            return self::$logpool[$name];
        }

        return new \Myfox\Lib\BlackHole('Log:' . $name);
    }
    /* }}} */

}

