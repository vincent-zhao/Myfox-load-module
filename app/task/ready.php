<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | ready.php (数据装载完成，更新系统last_date                             |
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//

namespace Myfox\App\Task;

use \Myfox\App\Setting;
use \Myfox\App\Model\Router;

class Ready extends \Myfox\App\Task
{

    /* {{{ public Integer wait() */
    public function wait()
    {
        return self::SUCC;
    }
    /* }}} */

    /* {{{ public Integer execute() */
    public function execute()
    {
        if (!$this->isReady('thedate', 'priority')) {
            return self::IGNO;
        }

        $date1  = date('Ymd', strtotime(Setting::get('last_date')));
        $date2  = date('Ymd', strtotime($this->option('thedate')));
        if ($date1 >= $date2) {
            return self::IGNO;
        }

        $mysql  = \Myfox\Lib\Mysql::instance('default');
        $count  = (int)$mysql->getOne($mysql->query(sprintf(
            'SELECT COUNT(*) FROM %stask_queque WHERE autokid < %u AND priority <= %u '.
            "AND task_flag NOT IN (%d,%d) AND task_type='import'",
            $mysql->option('prefix'), $this->id, $this->option('priority'),
            \Myfox\App\Queque::FLAG_IGNO, \Myfox\App\Queque::FLAG_DONE
        )));

        if ($count > 0) {
            $this->setError(sprintf('Waiting for %d import task(s)', $count));
            return self::FAIL;
        }

        if (!Router::flush()) {
            $this->setError('flush route failed');
            return self::FAIL;
        }

        Setting::set('last_date', $date2, '', '集群数据最新日期');

        // TODO:
        // CALL nodefox to reload route info info cache

        return self::SUCC;
    }
    /* }}} */

}

