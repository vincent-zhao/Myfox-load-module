<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | cleaner.php (清理系统中残余数据)                                       |
// +------------------------------------------------------------------------+
// | Copygight (c) 2003 - 2011 Taobao.com. All Rights Reserved              |
// +------------------------------------------------------------------------+
// | Author: pengchun <pengchun@taobao.com>                                 |
// +------------------------------------------------------------------------+

namespace Myfox\App\Worker;

use \Myfox\App\Model\Server;

class Checktable extends \Myfox\App\Worker
{

    /* {{{ 成员变量 */

    protected $option   = array(
        'sleep' => 300000,      /**<    300s */
    );

    /* }}} */

    /* {{{ public Integer interval() */
    /**
     * 下次运行sleep时间(ms)
     *
     * @access public
     * @return Integer
     */
    public function interval()
    {
        return $this->option['sleep'];
    }
    /* }}} */

    /* {{{ public Boolean execute() */
    /**
     * 执行
     *
     * @access public
     * @return Boolean true or false (next loop)
     */
    public function execute($loop = true)
    {
        $mysql  = \Myfox\Lib\Mysql::instance('default');

        $query  = sprintf('SELECT host_id AS id, host_name AS name FROM %shost_list', $mysql->option('prefix'));
        foreach ((array)$mysql->getAll($mysql->query($query)) AS $host) {
            $db = Server::instance($host['name'])->getlink();
            try {
                $databases  = $db->getAll($db->query('SHOW DATABASES'));
            } catch (\Exception $e) {
                $this->log->error('EXCEPTION', $e->getMessage());
                continue;
            }

            foreach ((array)$databases AS $dbname) {
                $dbname = reset($dbname);
                if (preg_match('/^(mysql|test|information_schema)$/', $dbname) || !preg_match('/^\w+_\d+$/', $dbname)) {
                    continue;
                }

                foreach ((array)$db->getAll($db->query('SHOW TABLES FROM ' . $dbname)) AS $tbname) {
                    $tbname = reset($tbname);
                    $data   = array(
                        'host'  => $host['name'],
                        'table' => sprintf('%s.%s', $dbname, $tbname),
                    );
                    $check  = sprintf('SELECT * FROM %s.%s LIMIT 1', $dbname, $tbname);
                    if ($db->query($check) /*|| false === stripos($db->lastError(), ' is marked as crashed')*/) {
                        $this->log->debug('CHECK_IGNORE', $data);
                        continue;
                    }

                    foreach (array('FAST', 'MEDIUM', 'MEDIUM', 'MEDIUM', 'EXTENDED') AS $mode) {
                        $query  = sprintf('CHECK TABLE %s.%s %s', $dbname, $tbname, $mode);
                        if (self::success($db->getAll($db->query($query)))) {
                            break;
                        }
                    }

                    if (!$db->query($check)) {
                        $this->log->error('CHECK_FAIL', $data);
                    } else {
                        $this->log->notice('CHECK_OK', $data);
                    }
                }
            } 
        }

        return (bool)$loop;
    }
    /* }}} */

    /* {{{ private static Boolean success() */
    /**
     * 判断check table是否成功
     *
     * @access private static
     * @return Boolean true or false
     */
    private static function success($result)
    {
        if (empty($result) || !is_array($result)) {
            return false;
        }

        foreach ((array)$result AS $row) {
            if (!isset($row['Msg_type']) || !isset($row['Msg_text'])) {
                continue;
            }
            if ('status' == $row['Msg_type'] && 0 != strcasecmp('OK', $row['Msg_text'])) {
                return false;
            }
        }

        return true;
    }
    /* }}} */

}
