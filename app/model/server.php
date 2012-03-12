<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: server.php 18 2010-04-13 15:40:37Z zhangxc83 $

namespace Myfox\App\Model;

class Server
{

    /* {{{ 静态常量 */

    const TYPE_REALITY  = 1;
    const TYPE_ARCHIVE  = 2;

    const STAT_ONLINE   = 0;
    const STAT_PREPARE  = 1;
    const STAT_ISDOWN   = 9;

    /* }}} */

    /* {{{ 静态变量 */

    private static $objects = array();

    private static $mysql   = null;

    /* }}} */

    /* {{{ 成员变量 */

    private $dbname = '';

    private $option = null;

    /* }}} */

    /* {{{ public static Object instance() */
    /**
     * 获取一个实例
     *
     * @access public static
     * @return Object
     */
    public static function instance($name)
    {
        $name   = strtolower(trim($name));
        if (empty(self::$objects[$name])) {
            self::$objects[$name]   = new self($name);
        }

        return self::$objects[$name];
    }
    /* }}} */

    /* {{{ public Mixture option() */
    /**
     * 返回属性
     *
     * @access public
     * @return Mixture
     */
    public function option($key, $default = null)
    {
        $this->init();
        $key    = strtolower(trim($key));
        return isset($this->option[$key]) ? $this->option[$key] : $default;
    }
    /* }}} */

    /* {{{ public Object getlink() */
    /**
     * 获取数据库连接
     *
     * @access public
     * @return object
     */
    public function getlink()
    {
        $this->init();
        $id = sprintf('__%s', $this->dbname);

        try {
            return \Myfox\Lib\Mysql::instance($id);
        } catch (\Exception $e) {
            \Myfox\Lib\Mysql::register($id, array(
                'persist'   => false,
                'master'    => array(sprintf(
                    'mysql://%s:%s@%s:%d', rawurlencode($this->option['write_user']),
                    rawurlencode($this->option('write_pass')),
                    $this->option['conn_host'], $this->option['conn_port']
                )),
                'slave'     => array(sprintf(
                    'mysql://%s:%s@%s:%d', rawurlencode($this->option['read_user']),
                    rawurlencode($this->option('read_pass')),
                    $this->option['conn_host'], $this->option['conn_port']
                )),
            ));

            return \Myfox\Lib\Mysql::instance($id);
        }
    }
    /* }}} */

    /* {{{ private void __construct() */
    /**
     * 构造函数
     *
     * @access private
     * @return void
     */
    private function __construct($name)
    {
        if (empty(self::$mysql)) {
            self::$mysql    = \Myfox\Lib\Mysql::instance('default');
        }

        $this->dbname   = $name;
        $this->option   = null;
    }
    /* }}} */

    /* {{{ private void init() */
    /**
     * 初始化server对象
     *
     * @access private
     * @return void
     */
    private function init()
    {
        if (null !== $this->option) {
            return;
        }

        $this->option   = self::$mysql->getRow(self::$mysql->query(sprintf(
            "SELECT * FROM %shost_list WHERE host_name = '%s'",
            self::$mysql->option('prefix'), self::$mysql->escape($this->dbname)
        )));
        if (empty($this->option)) {
            throw new \Myfox\Lib\Exception(sprintf(
                'Undefined mysql server named as "%s"', $this->dbname
            ));
        }
        $this->option   = array_change_key_case((array)$this->option, CASE_LOWER);
    }
    /* }}} */

}
