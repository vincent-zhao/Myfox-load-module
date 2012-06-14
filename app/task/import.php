<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | import.php (数据切分并导入集群)                                        |
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>                                   |
// +------------------------------------------------------------------------+
//
// $Id: import.php 18 2010-04-13 15:40:37Z zhangxc83 $
//

namespace Myfox\App\Task;

use \Myfox\Lib\Fileset;

use \Myfox\App\Model\Router;
use \Myfox\App\Model\Server;
use \Myfox\App\Model\Table;

class Import extends \Myfox\App\Task
{

    /* {{{ 静态常量 */

    const IMPORTSQL = 'LOAD DATA LOCAL INFILE "{FILE}" INTO TABLE {TABLE} CHARACTER SET UTF8 FIELDS TERMINATED BY "{TAB}" ESCAPED BY ""';

    /* }}} */

    /* {{{ 成员变量 */

    /**
     * @异步队列标记
     */
    private $pools  = array();

    /**
     * @解析后的路由值
     */
    private $route  = array();

    /* }}} */

    /* {{{ public Integer execute() */
    public function execute()
    {
        /**
         * OPTIONS:
         * ----------------------------------------------------------------------
         * @file    : complete file url, such as "ftp://user:pass@hostname/path"
         * @table   : name of the logic table
         * @route   : route value of the file, ex: thedate=20111001,cid=210
         * @bucket  :
         * @hosts   :
         * ---------------------------------------------------------------------
         */
        $this->pools    = array();
        if (!$this->isReady('table', 'route', 'file', 'bucket', 'hosts')) {
            return self::IGNO;
        }

        $table  = Table::instance($this->option('table'));
        if (!$table->get('autokid')) {
            $this->setError(sprintf('Undefined table named as "%s".', $this->option('table')));
            return self::IGNO;
        }

        $fname  = null;
        $this->route    = Router::parse($this->option('route'));
        $this->result   = array_flip(explode(',', $this->status));
        foreach (array_unique(explode(',', trim($this->option('hosts'), '{}$'))) AS $host) {
            if (isset($this->result[$host])) {
                continue;
            }

            empty($fname) && ($fname = Fileset::getfile($this->option['file']));
            if (empty($fname)) {
                $this->setError(Fileset::lastError());
                return self::FAIL;
            }
            $config = \Myfox\Lib\Config::instance('default');
            $ibservers = array_unique(explode(',', trim($config->get('ib/servers'))));
            $ibtables  = array_unique(explode(',', trim($config->get('ib/tables'))));
            if (in_array($host, $ibservers) && in_array($this->option('table'), $ibtables)) {
                $this->onehost($host, $fname, 'BRIGHTHOUSE');
            } else {
                $this->onehost($host, $fname, 'MYISAM');
            }

        }

        return empty($this->pools) ? self::FAIL : self::WAIT;
    }
    /* }}} */

    /* {{{ public Integer wait() */
    /**
     * 异步结果返回
     *
     * @access public
     * @return Integer
     */
    public function wait()
    {
        $result = array();
        foreach ((array)$this->pools AS $host => $pool) {
            $failed = null;
            $mysql  = Server::instance($pool['server'])->getlink();
            $this->setError($mysql->lastError());
            if (false !== $pool['handle'] && false !== $mysql->wait($pool['handle'])) {
                $failed = false;
                foreach ((array)$pool['commit'] AS $query) {
                    if (false === $mysql->query($query)) {
                        $failed = true;
                        $this->setError(sprintf('[%s] %s', $pool['server'], $mysql->lastError()));
                        break;
                    }
                }
            }

            if (false === $failed) {
                $result[(int)$host]  = true;
            }
        }

        if (!empty($result)) {
            $succes = implode(',', array_keys($result));
            if (false === Router::effect($this->option['table'], $this->route, $this->option['bucket'], $succes)) {
                $this->setError(sprintf('Router effect failed [%s].', $succes));
                $result = array();
            }
        }

        foreach ($this->pools AS $host => $pool) {
            if (!empty($result[$host])) {
                unset($this->pools[$host]);
                continue;
            }
            foreach ((array)$pool['rollback'] AS $query) {
                $mysql->query($query);
            }
        }
        $this->result   = implode(',', array_keys($result));

        return empty($this->pools) ? self::SUCC : self::FAIL;
    }
    /* }}} */

    /* {{{ private Mixture onehost() */
    /**
     * 单机装入
     *
     * @access private
     * @return Mixture
     */
    private function onehost($host, $fname, $engine = 'MYISAM')
    {
        self::metadata($flush);
        if (!isset(self::$hosts[$host])) {
            return;
        }
        $table  = Table::instance($this->option('table'));
        $mysql  = Server::instance(self::$hosts[$host]['name'])->getlink();
        $this->pools[$host] = array(
            'server'    => self::$hosts[$host]['name'],
            'handle'    => null,
            'commit'   => array(),
            'rollback'  => array(),
        );

        list($dbname, $tbname)  = explode('.', $this->option('bucket'), 2);
        $querys = array(
            sprintf('CREATE DATABASE IF NOT EXISTS %s', $dbname),
            sprintf(
                'CREATE TABLE IF NOT EXISTS %s (%s) ENGINE=%s DEFAULT CHARSET=UTF8',
                $this->option('bucket'),
                ('BRIGHTHOUSE' == $engine) ? $table->sqlcreate('ib') : $table->sqlcreate(),
                $engine
            ),
        );

        if ($this->option('replace', $table->get('load_type', 0))) {
            array_unshift($querys, sprintf('DROP TABLE IF EXISTS %s', $this->option('bucket')));
            $this->pools[$host]['commit']  = array();
            $this->pools[$host]['rollback'] = array(sprintf(
                'DROP TABLE IF EXISTS %s', $this->option('bucket')
            ));
        } else {
            if('BRIGHTHOUSE' == $engine) {
                $this->pools[$host]['commit'] = array('COMMIT');
                $this->pools[$host]['rollback'] = array('ROLLBACK');
            } else {
                $maxid  = (int)$mysql->getOne($mysql->query(sprintf(
                    'SELECT MAX(%s) FROM %s', $table->autokid(), $this->option('bucket')
                )));
                if ($maxid) {
                    $this->pools[$host]['rollback'] = array(sprintf(
                        'DELETE FROM %s WHERE %s > %u', $this->option('bucket'),
                        $table->autokid(), $maxid
                    ));
                } else {
                    $this->pools[$host]['rollback'] = array(
                        'DROP TABLE IF EXISTS %s', $this->option('bucket')
                    );
                }
            }
        }
        if('BRIGHTHOUSE' == $engine) {
            array_push($querys, 'SET AUTOCOMMIT=0');
        }
        foreach ($querys AS $sql) {
            if (false === $mysql->query($sql)) {
                $this->setError($mysql->lastError());
                return false;
            }
        }

        $import = $table->get('sql_import');
        if (empty($import)) {
            $import = self::IMPORTSQL;
        }
        if('MYISAM' == $engine) {
            $import = preg_replace('/\s+ENCLOSED\s+BY\s+("|\')?NULL("|\')?/i', ' ENCLOSED BY ""', $import);
        }

        $this->pools[$host]['handle']   = $mysql->async(strtr($import, array(
            '{FILE}'    => $fname,
            '{TABLE}'   => $this->option('bucket'),
            '{FS}'      => chr(1),
            '{TAB}'     => chr(9),
        )));
    }
    /* }}} */

}
