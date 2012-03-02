<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 全局配置		    					    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: setting.php 22 2010-04-15 16:28:45Z zhangxc83 $

namespace Myfox\App;

class Setting
{

    /* {{{ 静态变量 */

    public static $queries  = 0;

    private static $mysql;

    private static $expire	= 60;

    private static $option	= array();

    /* }}} */

    /* {{{ public static void init() */
    /**
     * 初始化
     *
     * @access public static
     * @return void
     */
    public static function init($expire = 60)
    {
        self::$expire	= (int)$expire;
        if (empty(self::$mysql)) {
            self::$mysql	= \Myfox\Lib\Mysql::instance('default');
        }
    }
    /* }}} */

    /* {{{ public static Mixture get() */
    /**
     * 获取配置值
     *
     * @access public static
     * @return Mixture
     */
    public static function get($key, $own = '')
    {
        $now	= microtime(true);
        $idx	= self::idx($key, $own);
        if (!isset(self::$option[$idx]) || $now > self::$option[$idx]['t']) {
            self::init(self::$expire);

            $option	= self::$mysql->getAll(self::$mysql->query(sprintf(
                "SELECT cfgname,ownname,cfgvalue FROM %ssettings WHERE ownname='%s'",
                self::$mysql->option('prefix', ''),
                self::$mysql->escape($own)
            )));
            foreach ((array)$option AS $row) {
                self::$option[self::idx($row['cfgname'], $row['ownname'])]	= array(
                    't'	=> $now + self::$expire,
                    'v'	=> $row['cfgvalue'],
                );
            }
            self::$queries++;
        }

        return isset(self::$option[$idx]['v']) ? self::$option[$idx]['v'] : null;
    }
    /* }}} */

    /* {{{ public static Boolean set() */
    /**
     * 设置配置值
     *
     * @access public static
     * @return Boolean true or false
     */
    public static function set($key, $value, $own = '', $comma = true)
    {
        unset(self::$option[self::idx($key, $own)]);
        self::init(self::$expire);
        self::$queries++;

        $time	= date('Y-m-d H:i:s');
        $value  = self::$mysql->escape($value);
        if ($comma) {
            $value  = sprintf("'%s'", $value);
        }

        return self::$mysql->query(sprintf(
            "INSERT INTO %ssettings (cfgname,ownname,cfgvalue,addtime,modtime) VALUES ('%s','%s',%s,'%s','%s')" .
            " ON DUPLICATE KEY UPDATE modtime = '%s',cfgvalue=%s",
            self::$mysql->option('prefix', ''), self::$mysql->escape($key), self::$mysql->escape($own),
            $value, $time, $time, $time, $value
        ));
    }
    /* }}} */

    /* {{{ private static string idx() */
    /**
     * 组织KEY名字
     *
     * @access private static
     * @return String
     */
    private static function idx($key, $own)
    {
        return strtolower(sprintf('%s/%s', trim($own), trim($key)));
    }
    /* }}} */

}
