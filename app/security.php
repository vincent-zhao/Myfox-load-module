<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | security.php (ip地址进行权限验证)			        					|
// +------------------------------------------------------------------------+
// | Copygight (c) 2003 - 2010 Taobao.com. All Rights Reserved				|
// +------------------------------------------------------------------------+
// | Author: pengchun <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+

namespace Myfox\App;

use \Myfox\Lib\Config;

class Security
{

    /* {{{ 成员变量 */

    private $config;

    /* }}} */

    /* {{{ public void __construct() */
    /**
     * 
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct($ini)
    {
        $this->config   = new Config($ini);
    }
    /* }}} */

    /* {{{ public Boolean priority() */
    /**
     * 获取优先级
     *
     * @access public
     * @return Boolean true or false
     */
    public function priority($remote)
    {
        $allows = (array)$this->config->get('priority');
        if (isset($allows[$remote])) {
            return $allows[$remote];
        }

        foreach ($allows AS $rule => $value) {
            if (self::ipmatch($remote, $rule)) {
                return $value;
            }
        }

        return false;
    }
    /* }}} */

    /* {{{ public Mixture modify() */
    /**
     * 修改参数
     *
     * @access public
     * @return Mixture
     */
    public function modify($param)
    {
        return (array)$this->config->get('option') + (array)$param;
    }
    /* }}} */

    /* {{{ private static Boolean ipmatch() */
    /**
     * 判断IP地址匹配
     *
     * @access private static
     * @return Boolean true or false
     */
    private static function ipmatch($ip, $str)
    {
        $ps	= -1;
        $at	= array_filter(explode('*', trim($str)));
        foreach ($at AS $tk) {
            $ps	= strpos($ip, $tk, $ps + 1);
            if (false === $ps) {
                return false;
            }
        }

        return true;
    }
    /* }}} */

}
