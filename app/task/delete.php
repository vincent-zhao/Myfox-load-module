<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: delete.php 18 2010-04-13 15:40:37Z zhangxc83 $
//

namespace Myfox\App\Task;

use \Myfox\App\Model\Server;

class Delete extends \Myfox\App\Task
{

    /* {{{ 成员变量 */

    private $optimize   = false;

    private $dbpools    = array();

    /* }}} */

    /* {{{ public Integer execute() */
    public function execute()
    {
        if (!$this->isReady('host', 'path')) {
            return self::IGNO;
        }

        self::metadata($flush);

        $where  = trim((string)$this->option('where'));
        if ('' == $where) {
            $query  = sprintf('DROP TABLE IF EXISTS %s', $this->option('path'));
        } else {
            $query  = sprintf('DELETE FROM %s WHERE %s', $this->option('path'), $where);
            $this->optimize = true;
        }

        $ignore = array_flip(explode(',', (string)$this->status));
        foreach (explode(',', trim($this->option('host', '{}'))) AS $id) {
            if (!isset(self::$hosts[$id]) || isset($ignore[$id])) {
                continue;
            }

            $ignore[$id]    = true;
            $server = self::$hosts[$id];
            if (Server::TYPE_VIRTUAL == $server['type']) {
                continue;
            }

            $db = Server::instance($server['name'])->getlink();
            $this->dbpools[]    = array($db, $db->async($query), $server['name']);
        }

        return self::WAIT;
    }
    /* }}} */

    /* {{{ public Integer wait() */
    public function wait()
    {
        $rt = self::SUCC;
        $sc = array();
        foreach ($this->dbpools AS $pool) {
            list($db, $key, $host)  = $pool;
            if (false === $key || false === $db->wait($key)) {
                $rt = self::FAIL;
                $this->setError($db->lastError($key));
            } else {
                $sc[$host]  = true;
                if ($this->optimize) {
                    $db->async(sprintf('OPTIMIZE TABLE %s', $this->option('path')));
                }
            }
        }

        $this->dbpools  = array();
        $this->result   = implode(',', array_keys($sc));

        return $rt;
    }
    /* }}} */

}
