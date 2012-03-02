<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +--------------------------------------------------------------------+
// | 配置读取类															|
// +--------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>								|
// +--------------------------------------------------------------------+
//
// $Id: config.php 1 2010-06-01 03:52:51Z zhangxc83 $
//

namespace Myfox\Lib;

class Config
{

    /* {{{ 静态变量 */

    private static $alias   = array();

    private static $objects = array();

    /* }}} */

    /* {{{ 成员变量 */

    private $scheme;

    private $params = null;

    /* }}} */

    /* {{{ public static Object instance() */
    /**
     * 根据名字获取实例
     *
     * @access public static
     * @return Object
     */
    public static function instance($name)
    {
        $name   = self::normalize($name);
        if (empty(self::$objects[$name])) {
            if (empty(self::$alias[$name])) {
                throw new \Myfox\Lib\Exception(sprintf(
                    'Undefined config name as "%s"', $name
                ));
            }
            self::$objects[$name]   = new self(self::$alias[$name]);
        }

        return self::$objects[$name];
    }
    /* }}} */

    /* {{{ public static void register() */
    /**
     * 注册别名
     *
     * @access public static
     * @return void
     */
    public static function register($name, $url)
    {
        self::$alias[self::normalize($name)] = trim($url);
    }
    /* }}} */

    /* {{{ public static void removeAllNames() */
    /**
     * 清理所有的对象
     *
     * @access public static
     * @return void
     */
    public static function removeAllNames()
    {
        self::$objects  = array();
        self::$alias    = array();
    }
    /* }}} */

    /* {{{ public Mixture get() */
    /**
     * 获取配置值
     *
     * @access public
     * @return void
     */
    public function get($key, $default = null)
    {
        if (null === $this->params) {
            $this->params   = (array)self::load($this->scheme);
        }

        $key    = trim($key);
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }

        if ('' == $key) {
            return $this->params;
        }

        $val    = $this->params;
        foreach (explode('/', $key) AS $id) {
            $id = trim($id);
            if (!isset($val[$id])) {
                return $default;
            }
            $val    = $val[$id];
        }

        return $val;
    }
    /* }}} */

    /* {{{ public void __construct() */
    /**
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct($url, $name = null)
    {
        $this->scheme   = trim($url);
        $this->params   = null;
        if (!empty($name)) {
            self::register($name, $url);
        }
    }
    /* }}} */

    /* {{{ private static String normalize() */
    /**
     * 名字归一化
     *
     * @access private static
     * @return String
     */
    private static function normalize($name)
    {
        return strtolower(preg_replace('/\s+/', '', $name));
    }
    /* }}} */

    /* {{{ private static Mixture load() */
    /**
     * 加载数据
     *
     * @access private static
     * @return Mixture
     */
    private static function load($url)
    {
        $url = parse_url($url);
        if (empty($url) || empty($url['path'])) {
            return false;
        }

        $class  = explode('.', trim($url['path'], "\x00..\x20."));
        $class  = sprintf('%s\%s', __CLASS__, ucfirst(strtolower(end($class))));
        $object = new $class($url);

        return $object->parse();
    }
    /* }}} */

}

