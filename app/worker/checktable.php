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
        'sleep' => 300000,      /**<    300s        */
        'full'  => false,       /**<    full check  */
    );

    /* }}} */

    /* {{{ public String locker() */
    /**
     * 进程锁名字
     *
     * @access public
     * @return String
     */
    public function locker()
    {
        return 'checktable';
    }
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

        $count  = 0;
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

                foreach ((array)$db->getAll($db->query('SHOW TABLE STATUS FROM ' . $dbname)) AS $row) {
                    $table  = sprintf('%s.%s', $dbname, $row['Name']);
                    $logvar = array(
                        'server'    => $host['name'],
                        'table'     => $table,
                        'engine'    => $row['Engine'],
                        'create'    => $row['Create_time'],
                        'update'    => $row['Update_time'],
                        'check'     => $row['Check_time'],
                    );

                    if (true !== $this->option['full'] && $row['Check_time'] > $row['Update_time'] && !empty($row['Engine'])) {
                        $this->log->debug('CHECK_IGN1', $logvar);
                        continue;
                    }

                    switch (self::realcheck($table, $db)) {
                    case 0:
                        $this->log->debug('CHECK_IGN2', $logvar);
                        break;

                    case 1:
                        $this->log->notice('CHECK_OK', $logvar);
                        break;

                    default:
                        $this->log->debug('CHECK_FAIL', $logvar);
                        break;
                    }

                    if (((++$count) % 10) == 0) {
                        self::breakup();
                    }
                }
            } 
        }

        return false;
    }
    /* }}} */

    /* {{{ private static Integer realcheck() */
    /**
     * really check table
     *
     * @access private static
     * @return Integer
     */
    private static function realcheck($table, $mysql)
    {
        $query  = sprintf('SELECT * FROM %s LIMIT 1', $table);
        if ($mysql->query($query)/*|| false === stripos($mysql->lastError(), ' is marked as crashed')*/) {
            return 0;
        }

        foreach (array('FAST', 'MEDIUM', 'MEDIUM', 'MEDIUM', 'EXTENDED') AS $mode) {
            $check  = sprintf('CHECK TABLE %s %s', $table, $mode);
            if (self::success($mysql->getAll($mysql->query($check)))) {
                break;
            }
        }

        return $mysql->query($query) ? 1 : -1;
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
            if ('status' == $row['Msg_type'] && 0 == strcasecmp('OK', $row['Msg_text'])) {
                return true;
            }
        }

        return false;
    }
    /* }}} */

}
