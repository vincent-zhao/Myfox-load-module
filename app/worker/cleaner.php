<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | cleaner.php (清理系统中残余数据)		    							|
// +------------------------------------------------------------------------+
// | Copygight (c) 2003 - 2011 Taobao.com. All Rights Reserved				|
// +------------------------------------------------------------------------+
// | Author: pengchun <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+

namespace Myfox\App\Worker;

use \Myfox\App\Model\Router;
use \Myfox\App\Queque;

class Cleaner extends \Myfox\App\Worker
{

    /* {{{ public Boolean execute() */
    /**
     * 执行函数
     *
     * @access public
     * @return Boolean true or false : next loop ?
     */
    public function execute($loop = true)
    {
        self::clean_invalidated_routes();
        return false;
    }
    /* }}} */

    /* {{{ public static void clean_invalidated_routes() */
    /**
     * 清理失效的路由
     *
     * @access public static
     * @return void
     */
    public static function clean_invalidated_routes()
    {
        $mysql  = \Myfox\Lib\Mysql::instance('default');
        $query  = sprintf("SHOW TABLES LIKE '%sroute_info%%'", $mysql->option('prefix'));
        foreach ((array)$mysql->getAll($mysql->query($query)) AS $table) {
            $table  = end($table);
            $query  = sprintf(
                'SELECT autokid,hosts_list,real_table,route_text FROM %s WHERE route_flag IN (%d) AND modtime < %d',
                $table, Router::FLAG_IS_DELETED, time() - 100000
            );

            $ids    = array();
            foreach ((array)$mysql->getAll($mysql->query($query)) AS $row) {
                $hosts  = trim($row['hosts_list'], '{}$');
                if (!empty($hosts)) {
                    $where  = array();
                    foreach (Router::parse($row['route_text']) AS $k => $v) {
                        $where[]    = sprintf('%s = %s', $k, $v);
                    }
                    $info   = array(
                        'host'  => $hosts,
                        'path'  => $row['real_table'],
                        'where' => implode(' AND ', $where),
                    );

                    if (!Queque::instance()->insert('delete', $info, 0, array('adduser' => 'cleaner'))) {
                        continue;
                    }
                }
                $ids[]  = (int)$row['autokid'];
            }

            $mysql->query(sprintf(
                'DELETE FROM %s WHERE (route_flag = %d AND addtime < %d)%s',
                $table, Router::FLAG_PRE_IMPORT, time() - 10 * 86400,
                empty($ids) ? '' : sprintf(' OR (autokid IN (%s))', implode(',', $ids))
            ));
        }
    }
    /* }}} */

}

