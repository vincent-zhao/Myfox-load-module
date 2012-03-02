<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 一致性检查类							    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: consist.php 22 2010-04-15 16:28:45Z zhangxc83 $
//
//
namespace Myfox\App\Model;

use \Myfox\App\Model\Server;

class Consist
{

    /* {{{ 静态常量 */

    const COUNTS    = 1;
    const BITXOR    = 2;

    /* }}} */

    /* {{{ 静态变量 */

    private static $callers = array(
        self::COUNTS    => 'counts',
        self::BITXOR    => 'bitxor',
    );

    private static $columns = array();

    /* }}} */

    /* {{{ public static Mixture check() */
    /**
     * 检查多个服务器之间表的数据一致性
     *
     * @access public static
     * @return Mixture
     */
    public static function check($table, $server, $type = null)
    {
        if (empty($server)) {
            throw new \Myfox\Lib\Exception('Empty server list for consistency check');
        }

        $dbname = self::idxname($table);
        if (empty(self::$columns[$dbname]) && !self::describe($table, $server, $dbname)) {
            return true;
        }

        $querys = array();
        $type   = empty($type) ? (self::COUNTS | self::BITXOR) : (int)$type;
        foreach (self::$callers AS $key => $call) {
            if (0 < ($key & $type)) {
                $querys[]   = self::$call($table);
            }
        }

        if (empty($querys)) {
            return false;
        }

        $query  = implode(' UNION ALL ', $querys);
        $pools  = array();
        foreach ((array)$server AS $name) {
            $pools[$name]   = Server::instance($name)->getlink()->async($query);
        }

        foreach ($pools AS $name => $id) {
            $db = Server::instance($name)->getlink();
            foreach ((array)$db->getAll($db->wait($id)) AS $row) {
                $result[$name][$row['c0']]  = $row['c1'];
            }
        }

        $item   = reset($result);
        $index  = key($result);
        foreach ($result AS $key => $val) {
            if ($key == $index) {
                continue;
            }

            if ($item != $val) {
                return $result;
            }
        }

        return true;
    }
    /* }}} */

    /* {{{ private static String idxname() */
    /**
     * 获取归一化的表键
     *
     * @access private static
     * @return String
     */
    private static function idxname($table)
    {
        $tbname = explode('.', $table);
        return strtolower(preg_replace('/_\d+$/', '', reset($tbname)));
    }
    /* }}} */

    /* {{{ private static void describe() */
    /**
     * 获取表结构
     *
     * @access private static
     * @return void
     */
    private static function describe($table, $server, $dbname)
    {
        foreach ((array)$server AS $name) {
            $mysql  = Server::instance($name)->getlink();
            $column = $mysql->getAll($mysql->query(sprintf('DESC %s', $table)));
            if (!empty($column)) {
                break;
            }
        }

        if (empty($column)) {
            return false;
        }

        $pk = 'autokid';
        $cl = array();
        foreach ((array)$column AS $row) {
            if (0 === strcasecmp('PRI', $row['Key'])) {
                $pk = $row['Field'];
            }

            if (0 !== strcasecmp('auto_increment', $row['Extra'])) {
                $cl[]   = $row['Field'];
            }
        }

        self::$columns[$dbname] = array(
            'prikey'    => $pk,
            'column'    => implode(',', $cl),
        );

        return true;
    }
    /* }}} */

    /* {{{ private static String counts() */
    /**
     * 求表的总行数
     *
     * @access private static
     * @return String
     */
    private static function counts($table)
    {
        return sprintf("SELECT 'counts' AS c0, COUNT(1) AS c1 FROM %s", $table);
    }
    /* }}} */

    /* {{{ private static String bitxor() */
    /**
     * 求表的BITXOR值
     *
     * @access private static
     * @return String
     */
    private static function bitxor($table)
    {
        $dbname = self::idxname($table);
        $query  = sprintf(
            "SELECT crc32(CONCAT_WS('', %s)) AS a FROM %s ORDER BY %s DESC LIMIT 100",
            self::$columns[$dbname]['column'], $table, self::$columns[$dbname]['prikey']
        );

        return sprintf(
            "SELECT 'bitxor' AS c0, BIT_XOR(a) AS c1 FROM (%s) b",
            $query
        );
    }
    /* }}} */

}
