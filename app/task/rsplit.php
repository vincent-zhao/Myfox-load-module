<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | rsplit.php (数据文件获取并切分)                                        |
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: rsplit.php 18 2010-04-13 15:40:37Z zhangxc83 $
//

namespace Myfox\App\Task;

use \Myfox\Lib\Config;
use \Myfox\Lib\Fileset;
use \Myfox\Lib\Fsplit;

use \Myfox\App\Queque;
use \Myfox\App\Model\Router;
use \Myfox\App\Model\Table;

class Rsplit extends \Myfox\App\Task
{

	/* {{{ public Integer execute() */
    /**
     * OPTIONS:
     * ----------------------------------------------------------------------
     * @file    : complete file url, such as "ftp://user:pass@hostname/path"
     * @table   : name of the logic table
     * @route   : route value of the file, ex: thedate=20111001,cid=210
     * @lines   :
     * ---------------------------------------------------------------------
     */
	public function execute()
	{
        if (!$this->isReady('table', 'route', 'lines', 'file', 'priority')) {
            return self::IGNO;
        }

        $table  = Table::instance($this->option('table'));
        if (!$table->get('autokid')) {
            $this->setError(sprintf('Undefined table named as "%s".', $this->option('table')));
            return self::IGNO;
        }

        try {
            $routes = Router::set($this->option('table'), array(array(
                'field' => Router::parse($this->option('route'), $this->option('table')),
                'count' => (int)$this->option('lines')
            )));
            if (!is_array($routes)) {
                $this->setError('route failed.');
                return self::FAIL;
            }

            $config = Config::instance('default');
            $fname  = Fileset::getfile($this->option('file'), $config->get('path/download'));
            if (empty($fname)) {
                $this->setError(sprintf('getfile:%s', Fileset::lastError()));
                return self::FAIL;
            }

            $splits = array();
            foreach ($routes AS $key => $bucket) {
                foreach ($bucket AS $item) {
                    $splits[] = $item['rows'];
                }
            }

            $fsplit = new Fsplit($fname, "\n", 16777216);
            $chunks = $fsplit->split($splits, $config->get('path/filesplit'));
            if (empty($chunks)) {
                $this->setError(sprintf('fsplit failed,%s', $fsplit->lastError()));
                return self::FAIL;
            }

            if (preg_match('/^\w+:/i', $this->option('file'))) {
                @unlink($fname);
            }

            $result = array();
            $queque = Queque::instance();
            foreach ($routes AS $key => $bucket) {
                foreach ($bucket AS $item) {
                    $fname  = array_shift($chunks);
                    if (empty($fname)) {
                        break 2;
                    }

                    $info   = array(
                        'table'     => $this->option('table'),
                        'route'     => $key,
                        'file'      => $fname,
                        'bucket'    => $item['table'],
                        'hosts'     => $item['hosts'],
                    );

                    $option = array(
                        'openrace'  => 0,
                        'priority'  => (int)$this->option('priority') - 1,
                        'trytimes'  => 3,
                        'task_flag' => Queque::FLAG_WAIT,
                        'adduser'   => 'rsplit',
                    );

                    if (!$queque->insert('import', $info, -1, $option)) {
                        $this->setError(sprintf('queque: %s', $queque->lastError()));
                        return self::FAIL;
                    }

                    $result[]   = $queque->lastId();
                }
            }
            $this->result   = implode(',', $result);
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return self::FAIL;
        }

        return self::SUCC;
	}
	/* }}} */

}

