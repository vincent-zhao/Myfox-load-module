<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +--------------------------------------------------------------------+
// | URL解析类			 				    							|
// +--------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>								|
// +--------------------------------------------------------------------+
//
// $Id: apc.php 380 2011-01-07 10:18:20Z zhangxc83 $

namespace Myfox\Lib\Parser;

class Url
{

    /* {{{ 成员变量 */

    private $url;

    private $module;

    private $action;

    private $param;

    /* }}} */

    /* {{{ public void __construct() */
    /**
     * 构造函数
     *
     * @access public
     * @param String $restUrl
     * @return void
     */
    public function __construct($url)
    {
        $this->url  = trim(ltrim($url, '/'));
        $this->parse();
    }
    /* }}} */

    /* {{{ public Mixture __get() */
    /**
     * __get魔术方法
     *
     * @access public
     * @return Mixture
     */
    public function __get($key)
    {
        return isset($this->$key) ? $this->$key : null;
    }
    /* }}} */

    /* {{{ public String module() */
    /**
     * 返回module
     *
     * @access public
     * @return String
     */
    public function module()
    {
        return $this->module;
    }
    /* }}} */

    /* {{{ public String action() */
    /**
     * 返回action
     *
     * @access public
     * @return String
     */
    public function action()
    {
        return $this->action;
    }
    /* }}} */

    /* {{{ public Mixture param() */
    /**
     * 获取URL中的参数值
     *
     * @access public
     * @return Mixture
     */
    public function param($key = null, $default = null)
    {
        if (null === $key) {
            return $this->param;
        }

        return isset($this->param[$key]) ? $this->param[$key] : $default;
    }
    /* }}} */

    /* {{{ public static string build() */
    /**
     * 构造URL
     *
     * @access public static
     * @param String $module
     * @param String $action
     * @param Mixture $param : default null
     * @return String
     */
    public static function build($module, $action, $param = null)
    {
        $parts  = array(
            self::escape($module),
            self::escape($action),
        );
        foreach ((array)$param AS $key => $val) {
            if (!is_scalar($val)) {
                continue;
            }
            $parts[] = sprintf('%s/%s', self::escape($key), urlencode($val));
        }

        return implode('/', $parts);
    }
    /* }}} */

    /* {{{ private void parse() */
    /**
     * URL解析程序
     *
     * @access private
     * @return void
     */
    private function parse()
    {
        $urls = explode('?', $this->url);
        $urls = array_values(array_filter(array_map('trim',
            explode('/', reset($urls))
        ), 'strlen'));
        $this->module	= isset($urls[0]) ? self::escape($urls[0]) : '';
        $this->action	= isset($urls[1]) ? self::escape($urls[1]) : '';
        $this->param	= array();

        for ($i = 2, $max = count($urls); $i < $max; $i++) {
            $name	= self::escape($urls[$i]);
            if (!isset($urls[++$i])) {
                $this->param[$name] = true;
            } else {
                $this->param[$name] = urldecode($urls[$i]);
            }
        }
    }
    /* }}} */

    /* {{{ private static String escape() */
    /**
     * 过滤URL中的非安全字符
     *
     * @access private static
     * @param String $str
     * @return String
     */
    private static function escape($str)
    {
        return trim(preg_replace('/[^\w]/is', '', $str));
    }
    /* }}} */

}
