<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | monitor.php (集群状态监控) 			    							|
// +------------------------------------------------------------------------+
// | Copygight (c) 2003 - 2011 Taobao.com. All Rights Reserved				|
// +------------------------------------------------------------------------+
// | Author: pengchun <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+

namespace Myfox\App\Worker;

use \Myfox\Lib\Alert;

use \Myfox\App\Setting;
use \Myfox\App\Model\Consist;
use \Myfox\App\Model\Router;

class Monitor extends \Myfox\App\Worker
{

    /* {{{ 静态变量 */

    private static $hostmap = array();

    private static $last_ts = 0;

    /* }}} */

    /* {{{ 成员变量 */

    protected $option	= array(
        'sleep'     => 300,
    );

    /* }}} */

    /* {{{ public Boolean execute() */
    /**
     * 执行函数
     *
     * @access public
     * @return Boolean true or false : next loop ?
     */
    public function execute($loop = true)
    {
        $status = array_filter(self::queque_status_monitor());
        if (!empty($status)) {
            Alert::push(sprintf(
                'queque_status: (%s)', json_encode($status)
            ), Alert::NORMAL | Alert::URGENCE);
        }

        $status = self::import_consist_monitor();
        if (!empty($status)) {
            Alert::push(sprintf(
                'import_consist: (%s)', json_encode($status)
            ), Alert::NORMAL | Alert::URGENCE);
        }

        return $loop;
    }
    /* }}} */

    /* {{{ public Integer interval() */
    /**
     * sleep时间, ms
     *
     * @access public
     * @return Integer
     */
    public function interval()
    {
        return $this->option['sleep'];
    }
    /* }}} */

    /* {{{ public static Mixture import_consist_monitor() */
    /**
     * 数据装载一致性监控
     *
     * @access public static
     * @return Mixture
     */
    public static function import_consist_monitor()
    {
        $mysql  = \Myfox\Lib\Mysql::instance('default');
        $tables = $mysql->getAll($mysql->query(sprintf(
            "SHOW TABLES LIKE '%sroute_info%%'", $mysql->option('prefix')
        )));

        $status = array();
        $offset = (int)strtotime(Setting::get('monitor_consist_check'));
        foreach ($tables AS $table) {
            $table  = end($table);
            if (preg_match('/(_merge)$/i', $table)) {
                continue;
            }
            $split  = $mysql->getAll($mysql->query(sprintf(
                'SELECT real_table,hosts_list,route_text,table_name FROM %s WHERE modtime >= %d AND route_flag IN (%d, %d)',
                $table, $offset, Router::FLAG_IMPORT_END, Router::FLAG_NORMAL_USE
            )));
            if (empty($split)) {
                continue;
            }

            foreach ($split AS $row) {
                $server = array();
                foreach (explode(',', trim($row['hosts_list'], '{},$')) AS $id) {
                    $server[] = self::hostname($id);
                }
                $server = array_filter($server);
                if (empty($server)) {
                    continue;
                }

                $result = Consist::check($row['real_table'], $server);
                if (is_array($result)) {
                    $status[]   = array(
                        'table'     => $row['table_name'],
                        'route'     => $row['route_text'],
                        'bucket'    => $row['real_table'],
                        'checks'    => $result,
                    );
                }
            }
        }
        Setting::set('monitor_consist_check', date('Y-m-d H:i:s'));

        return $status;
    }
    /* }}} */

    /* {{{ public static Mixture queque_status_monitor() */
    /**
     * 任务队列状态监控
     *
     * @access public
     * @return void
     */
    public static function queque_status_monitor()
    {
        $mysql  = \Myfox\Lib\Mysql::instance('default');
        $tables = $mysql->getAll($mysql->query(sprintf(
            "SHOW TABLES LIKE '%stask_queque%%'",
            $mysql->option('prefix')
        )));

        $status = array();
        $offset = strlen($mysql->option('prefix', '') . 'task_queque');
        foreach ((array)$tables AS $queque) {
            $queque = end($queque);
            $status[(string)substr($queque, $offset)] = self::queque_status($queque);
        }

        return $status;
    }
    /* }}} */

    /* {{{ private static Mixture queque_status() */
    /**
     * 单个queque的状态
     *
     * @access private static
     * @return Mixture
     */
    private static function queque_status($table)
    {
        $mysql  = \Myfox\Lib\Mysql::instance('default');
        $mysql->query(sprintf(
            "UPDATE %s SET task_flag = %u WHERE task_flag = %u AND begtime < '%s'",
            $table, \Myfox\App\Queque::FLAG_WAIT, \Myfox\App\Queque::FLAG_LOCK,
            date('Y-m-d H:i:s', time() - 1800)
        ));

        return array(
            'failed'    => (int)$mysql->getOne($mysql->query(sprintf(
                'SELECT COUNT(*) FROM %s WHERE task_flag = %u AND trytimes < 1',
                $table, \Myfox\App\Queque::FLAG_WAIT
            ))),
            'pending'   => (int)$mysql->getOne($mysql->query(sprintf(
                "SELECT COUNT(*) FROM %s WHERE task_flag = %u AND addtime < '%s'",
                $table, \Myfox\App\Queque::FLAG_WAIT, date('Y-m-d H:i:s', time() - 300)
            ))),
        );
}
/* }}} */

/* {{{ private static Mixture hostname() */
/**
 * 根据ID获取机器名字
 *
 * @access private static
 * @return Mixture
 */
private static function hostname($id)
{
    if (time() - self::$last_ts >= 300) {
        $mysql  = \Myfox\Lib\Mysql::instance('default');
        $query  = sprintf('SELECT host_id, host_name FROM %shost_list', $mysql->option('prefix'));

        self::$hostmap  = array();
        foreach ($mysql->getAll($mysql->query($query)) AS $row) {
            self::$hostmap[(int)$row['host_id']] = trim($row['host_name']);
        }
    }

    return isset(self::$hostmap[$id]) ? self::$hostmap[$id] : null;
}
/* }}} */

}

