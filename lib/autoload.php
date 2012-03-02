<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 文件自动加载控制    					    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>	    							|
// +------------------------------------------------------------------------+
//
// $Id: autoload.php 22 2010-04-15 16:28:45Z zhangxc83 $

namespace Myfox\Lib;

class AutoLoad
{

    /* {{{ 静态变量 */
    /**
     * @路径解析规则
     */
    private static $rules	= array();

    private static $order   = array();

    private static $sorted  = false;

    private static $index   = 0;

    /* }}} */

    /* {{{ public static void init() */
    /**
     * 初始化
     *
     * @access public static
     * @return void
     */
    public static function init()
    {
        self::register('myfox\\lib',           __DIR__);

        spl_autoload_register(array(__CLASS__, 'callback'));
    }
    /* }}} */

    /* {{{ public static void register() */
    /*
     * 注册路径解析规则
     *
     * @access public static
     * @param  String $key
     * @param  String $dir
     * @param  String $pre = null
     * @return void
     */
    public static function register($key, $dir, $pre = null)
    {
        $dir = realpath($dir);
        if (empty($dir) || !is_dir($dir)) {
            return;
        }

        /**
         * 有unset动作，所以得用这个变量来标记曾经有过多少个元素
         * 避免register / unregister 导致排序不稳定
         */
        self::$index++;

        $key = self::normalize($key);
        $pre = self::normalize($pre);
        $idx = 1000 * self::$index;
        if (!empty($pre) && isset(self::$order[$pre])) {
            /**
             * AutoLoad::register('root', '...');
             * AutoLoad::register('son1', '...', 'root');
             * AutoLoad::register('son2', '...', 'root');
             */
            $idx = self::$order[$pre] - intval(1000 / self::$index + 0.5);  /*< 稳定排序 */
            self::$sorted = false;
        }

        self::$rules[$key] = $dir;
        self::$order[$key] = $idx;
    }
    /* }}} */

    /* {{{ public static void unregister() */
    /**
     * 注销路径解析规则
     *
     * @access public static
     * @param  String $name
     * @return void
     */
    public static function unregister($name)
    {
        $name = self::normalize($name);
        if (isset(self::$rules[$name])) {
            unset(self::$rules[$name]);
            unset(self::$order[$name]);
        }
    }
    /* }}} */

    /* {{{ public static void removeAllRules() */
    /**
     * @清理所有规则
     *
     * @access public static
     * @return void
     */
    public static function removeAllRules()
    {
        self::$rules    = array();
        self::$order    = array();
        self::$index    = 0;
        self::$sorted   = false;
    }
    /* }}} */

    /* {{{ public static void callback() */
    /**
     * 自动加载回调函数
     *
     * @access public static
     * @param  String $class
     * @return void
     */
    public static function callback($class)
    {
        $ordina	= $class;
        $class	= preg_replace('/[\/\\\]{1,}/', '/', $class);
        $index	= strrpos($class, '/');

        if (false === $index) {
            require_once(__DIR__ . '/exception.php');
            throw new \Myfox\Lib\Exception(sprintf('Unregistered namespace when class "%s" defined.', $class));
        }

        if (!self::$sorted && array_multisort(self::$order, self::$rules, SORT_ASC, SORT_NUMERIC)) {
            self::$sorted = true;
        }

        $path = strtolower(substr($class, 0, $index));
        $name = strtolower(substr($class, $index + 1));

        reset(self::$rules);
        foreach (self::$rules AS $key => $dir) {
            if (0 !== strpos($path, $key)) {
                continue;
            }

            $file = $dir . substr($path, strlen($key)) . '/' . $name . '.php';
            if (is_file($file)) {
                require $file;
            } else {
                require_once(__DIR__ . '/exception.php');
                throw new \Myfox\Lib\Exception(sprintf('File "%s" Not Found.', $file));
            }

            if (!class_exists($ordina) && !interface_exists($ordina)) {
                require_once(__DIR__ . '/exception.php');
                throw new \Myfox\Lib\Exception(sprintf('Class "%s" Not Found in "%s".', $ordina, $file));
            }

            return;
        }

        require_once(__DIR__ . '/exception.php');
        throw new \Myfox\Lib\Exception(sprintf('Class "%s" Not Found.', $ordina));
    }
    /* }}} */

    /* {{{ private static String normalize() */
    /**
     * 规则归一化处理
     *
     * @access private static
     * @param  String $name
     * @return String
     */
    private static function normalize($name)
    {
        $name = preg_replace('/[\/\\\]+/', '/', preg_replace('/\s+/', '/', $name));
        return strtolower(trim($name, '/'));
    }
    /* }}} */

}

