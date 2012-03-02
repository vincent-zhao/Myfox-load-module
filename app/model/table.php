<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: table.php 18 2010-04-13 15:40:37Z zhangxc83 $

namespace Myfox\App\Model;

class Table
{

    /* {{{ 静态变量 */

    private static $objects	= array();

    private static $mysql   = null;

    /**
     * @Myfox定义的数据类型
     */
    private static $global_column_types = array(
        'autokid'   => array(
            'mysql' => 'int($$LEN$$) unsigned not null auto_increment primary key',
        ),
        'int'       => array(
            'mysql' => 'int($$LEN$$)',
        ),
        'uint'      => array(
            'mysql' => 'int($$LEN$$) unsigned',
        ),
        'float'     => array(
            'mysql' => 'decimal($$LEN$$)',
        ),
        'ufloat'    => array(
            'mysql' => 'decimal($$LEN$$) unsigned',
        ),
        'char'      => array(
            'mysql' => 'varchar($$LEN$$)',
        ),
        'date'      => array(
            'mysql' => 'date',
        ),
        'time'      => array(
            'mysql' => 'datetime',
        ),
        'money'     => array(
            'mysql' => 'decimal($$LEN$$)',
        ),
    );

    /* }}} */

    /* {{{ 成员变量 */

    public $queries = 0;

    private $option	= null;

    private $route  = null;

    private $column = array();

    private $index  = array();

    private $autoid = null;

    /* }}} */

    /* {{{ public static void register_column_type() */
    /**
     * 注册字段类型
     *
     * @access public static
     * @param  String $name : type name
     * @param  String $desc : type desc
     * @return void
     */
    public static function register_column_type($name, $types)
    {
        $name   = strtolower(trim($name));
        if (isset(self::$global_column_types[$name])) {
            return;
        }

        self::$global_column_types[$name]   = (array)$types;
    }
    /* }}} */

    /* {{{ public static Mixture all_defined_types() */
    /**
     * 获取所有已定义的数据类型
     *
     * @access public static
     * @return Mixture
     */
    public static function all_defined_types()
    {
        return self::$global_column_types;
    }
    /* }}} */

    /* {{{ public static Object instance() */
    /**
     * 获取表的实例
     *
     * @access public static
     * @param  String $tbname
     * @return Object
     */
    public static function instance($tbname)
    {
        $tbname	= strtolower(trim($tbname));
        if (empty(self::$objects[$tbname])) {
            self::$objects[$tbname]	= new self($tbname);
        }

        return self::$objects[$tbname];
    }
    /* }}} */

    /* {{{ public static void cleanAllStatic() */
    /**
     * 清理静态数据
     *
     * @access public static
     * @return void
     */
    public static function cleanAllStatic()
    {
        self::$objects  = array();
    }
    /* }}} */

    /* {{{ public Mixture get() */
    /**
     * 返回属性
     *
     * @access public
     * @return Mixture
     */
    public function get($key, $default = null)
    {
        if (null === $this->option) {
            $info   = self::$mysql->getRow(self::$mysql->query(sprintf(
                "SELECT * FROM %stable_list WHERE table_name = '%s'",
                self::$mysql->option('prefix', ''),
                self::$mysql->escape($this->tbname)
            )));
            $this->option   = (array)$info;
            $this->queries++;
        }
        $key	= strtolower(trim($key));
        return isset($this->option[$key]) ? $this->option[$key] : $default;
    }
    /* }}} */

    /* {{{ public Mixture column() */
    /**
     * 返回表字段
     *
     * @access public
     * @return Mixture
     */
    public function column()
    {
        if (empty($this->column)) {
            $column = self::$mysql->getAll(self::$mysql->query(sprintf(
                "SELECT * FROM %stable_column WHERE table_name='%s' ORDER BY column_order ASC, autokid ASC",
                self::$mysql->option('prefix'), self::$mysql->escape($this->tbname)
            )));
            $this->queries++;
            $this->column   = array();
            foreach ((array)$column AS $row) {
                $this->column[$row['column_name']]  = $row;
                if ('autokid' == $row['column_type']) {
                    $this->autoid   = $row['column_name'];
                }
            }
        }

		return $this->column;
    }
    /* }}} */

    /* {{{ public Mixture index() */
    /**
     * 返回表索引
     *
     * @access public
     * @return Mixture
     */
    public function index()
    {
        if (empty($this->index)) {
            $index  = self::$mysql->getAll(self::$mysql->query(sprintf(
                "SELECT * FROM %stable_index WHERE table_name='%s' ORDER BY autokid ASC",
                self::$mysql->option('prefix'), self::$mysql->escape($this->tbname)
            )));
            $this->queries++;
            $this->index    = array();
            foreach ((array)$index AS $row) {
                $this->index[$row['index_name']]  = $row;
            }
        }

		return $this->index;
    }
    /* }}} */

    /* {{{ public Mixture route() */
    /**
     * 获取表的路由类型
     *
     * @access public
     * @return Mixture
     */
    public function route()
    {
        if (null === $this->route) {
            $routes = self::$mysql->getAll(self::$mysql->query(sprintf(
                "SELECT column_name,tidy_return FROM %stable_route WHERE table_name = '%s' AND is_primary > 0",
                self::$mysql->option('prefix', ''),  self::$mysql->escape($this->tbname)
            )));
            $this->route    = array();
            foreach ((array)$routes AS $item) {
                $this->route[strtolower(trim($item['column_name']))] = strtolower(trim($item['tidy_return']));
            }
            ksort($this->route);
        }

        return $this->route;
    }
    /* }}} */

    /* {{{ public String sqlcreate() */
    /**
     * 获取建表SQL
     *
     * @access public
     * @param  Mixture $engine  : 'mysql'
     * @return String
     */
    public function sqlcreate($engine = 'mysql')
    {
        $create = array();
        foreach ((array)$this->column() AS $name => $option) {
            if (isset(self::$global_column_types[$option['column_type']]) &&
                isset(self::$global_column_types[$option['column_type']][$engine])
            ) {
                $schema = self::$global_column_types[$option['column_type']][$engine];
                if (false !== strpos($schema, '$$LEN$$')) {
                    $schema = str_replace('$$LEN$$', $option['column_size'], $schema);
                }

                if ('autokid' != $option['column_type']) {
                    $schema = sprintf(
                        "%s not null default '%s'", $schema, self::$mysql->escape($option['default_value'])
                    );
                }

                $create[]   = sprintf('%s %s', $name, $schema);
            }
        }

        if ('mysql' == $engine) {
            foreach ((array)$this->index() AS $name => $option) {
                $create[]   = sprintf(
                    'KEY %s (%s)', $option['index_name'], $option['index_text']
                );
            }
        }

        return implode(",\n", $create);
    }
    /* }}} */

    /* {{{ public String autokid() */
    /**
     * 表的自增列字段
     *
     * @access public
     * @return String
     */
    public function autokid()
    {
        if (empty($this->column)) {
            $this->column();
        }

        return $this->autoid;
    }
    /* }}} */

    /* {{{ private void __construct() */
    /**
     * 构造函数
     *
     * @access private
     * @return void
     */
    private function __construct($tbname)
    {
        $this->tbname   = strtolower(trim($tbname));
        $this->option   = null;

        if (empty(self::$mysql)) {
            self::$mysql    = \Myfox\Lib\Mysql::instance('default');
        }
    }
    /* }}} */

}

