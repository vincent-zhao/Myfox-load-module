<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 通用任务队列队列类						    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: queque.php 18 2010-04-13 15:40:37Z zhangxc83 $

namespace Myfox\App;

use \Myfox\Lib\Context;

class Queque
{

    /* {{{ 静态常量 */

    const FLAG_NEW	= 0;
    const FLAG_WAIT	= 100;
    const FLAG_LOCK	= 200;
    const FLAG_IGNO = 300;
    const FLAG_DONE	= 900;

    const MAX_TRIES = 3;

    /* }}} */

    /* {{{ 静态变量 */

    private static $mypos;

    private static $mysql;

    private static $objects = array();

    /* }}} */

    /* {{{ 成员变量 */

    private $name;          /**<    队列名字,作为表名后缀 */

    /* }}} */

    /* {{{ public static Object instance() */
    /**
     * 获取一个队列实例
     *
     * @access public static
     * @return Object
     */
    public static function instance($name = '')
    {
        $name   = strtolower(trim($name, "_ \t\r\n"));
        if (empty(self::$objects[$name])) {
            self::$objects[$name]   = new self($name);
        }

        return self::$objects[$name];
    }
    /* }}} */

    /* {{{ public Mixture fetch() */
    /**
     * 获取一条任务
     *
     * @access public
     * @param  Integer $limit : count of tasks
     * @param  Integer $pos   : agent position
     * @return Mixture
     */
    public function fetch($limit = 1, $pos = null, $flag = self::FLAG_WAIT, $type = '')
    {
        $query  = sprintf(
            'SELECT autokid AS `id`,task_type AS `type`,tmp_status AS `status`,task_info AS `info` FROM %stask_queque%s',
            self::$mysql->option('prefix', ''), $this->name
        );
        $pos    = (0 > $pos) ? self::$mypos : (int)$pos;
        $where  = array(
            sprintf('task_flag=%u', $flag),
            'trytimes > 0',
            sprintf('((agentpos = %d AND openrace = 0) OR openrace = 1)', $pos),
        );
        if ($type) {
            $where[]    = sprintf("task_type='%s'", self::$mysql->escape(trim($type)));
        }

        $order  = array(
            'priority ASC',
            'trytimes ASC',
            sprintf('ABS(agentpos - %d) ASC', $pos),
        );

        $tasks  = self::$mysql->getAll(self::$mysql->query(sprintf(
            '%s WHERE %s ORDER BY %s LIMIT 0, %d',
            $query, implode(' AND ', $where), implode(',', $order), $limit
        )));
        if (empty($tasks)) {
            return null;
        }

        if ($limit == 1) {
            $tasks  = reset($tasks);
        }

        return $tasks;
    }
    /* }}} */

    /* {{{ public Boolean insert() */
    /**
     * 插入一条队列
     *
     * @access public
     * @return Boolean true or false
     */
    public function insert($type, $info, $agent = 0, $option = null)
    {
        $column = array(
            'openrace'  => 1,
            'priority'  => 100,
            'trytimes'  => self::MAX_TRIES,
            'task_flag' => self::FLAG_WAIT,
            'adduser'   => '',
        );

        foreach ((array)$option AS $key => $val) {
            if (isset($column[$key])) {
                $column[$key]   = self::$mysql->escape($val);
            }
        }
        $column['addtime']  = date('Y-m-d H:i:s');
        $column['agentpos'] = ($agent >= 0) ? (int)$agent : self::$mypos;
        $column['task_type']= self::$mysql->escape(strtolower(trim($type)));
        $column['task_info']= self::$mysql->escape(json_encode($info));

        return (bool)self::$mysql->query(sprintf(
            "INSERT INTO %stask_queque%s (%s) VALUES ('%s')",
            self::$mysql->option('prefix', ''), $this->name,
            implode(',', array_keys($column)), implode("','", $column)
        ));
    }
    /* }}} */

    /* {{{ public Boolean update() */
    /**
     * 任务队列更改
     *
     * @access public
     * @return Boolean true or false
     */
    public function update($id, $option, $comma = null)
    {
        $column = array(
            'agentpos'  => true,
            'priority'  => true,
            'trytimes'  => true,
            'begtime'   => true,
            'endtime'   => true,
            'task_flag' => true,
            'task_type' => true,
            'adduser'   => true,
            'last_error'=> true,
            'tmp_status'=> "CONCAT(tmp_status, ',', '%s')",
        );

        $comma  = (array)$comma;
        $update = array();
        foreach ((array)$option AS $key => $val) {
            if (empty($column[$key])) {
                continue;
            }

            if (is_string($column[$key])) {
                $update[$key]   = sprintf('%s = %s', $key, sprintf($column[$key], self::$mysql->escape($val)));
            } elseif (!empty($comma[$key])) {
                $update[$key]   = sprintf('%s = %s', $key, $val);
            } else {
                $update[$key]   = sprintf("%s = '%s'", $key, self::$mysql->escape($val));
            }
        }

        if (empty($update) || empty($id)) {
            return false;
        }

        return self::$mysql->query(sprintf(
            'UPDATE %stask_queque%s SET %s WHERE autokid = %d',
            self::$mysql->option('prefix', ''), $this->name,
            implode(',', $update), $id
        ));
    }
    /* }}} */

    /* {{{ public String lastError() */
    /**
     * 返回错误描述
     *
     * @access public
     * @return String
     */
    public function lastError()
    {
        return self::$mysql->lastError();
    }
    /* }}} */

    /* {{{ public Integer lastId() */
    /**
     * 返回最后插入的ID
     *
     * @access public
     * @return Integer
     */
    public function lastId()
    {
        return self::$mysql->lastId();
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
        $this->name = empty($name) ? '' : '_' . $name;
        if (empty(self::$mysql)) {
            self::$mysql    = \Myfox\Lib\Mysql::instance('default');
        }
        if (empty(self::$mypos)) {
            self::$mypos    = Context::addr(true);
        }
    }
    /* }}} */

}

