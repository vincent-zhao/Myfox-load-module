<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | processor.php (工作队列处理器) 		    							|
// +------------------------------------------------------------------------+
// | Copygight (c) 2003 - 2011 Taobao.com. All Rights Reserved				|
// +------------------------------------------------------------------------+
// | Author: pengchun <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+

namespace Myfox\App\Worker;

use \Myfox\App\Queque;

class Processor extends \Myfox\App\Worker
{

    /* {{{ 静态变量 */

    /**
     * @状态映射表
     */
    private static $codemap = array(
        \Myfox\App\Task::SUCC   => \Myfox\App\Queque::FLAG_DONE,
        \Myfox\App\Task::FAIL   => \Myfox\App\Queque::FLAG_WAIT,
        \Myfox\App\Task::WAIT   => \Myfox\App\Queque::FLAG_WAIT,
        \Myfox\App\Task::IGNO   => \Myfox\App\Queque::FLAG_IGNO,
    );

    /* }}} */

    /* {{{ 成员变量 */

    protected $option = array(
        'p' => null,                /**<    worker标记 */
        'n' => 10,                  /**<    每次取多少条记录 */
    );

    private $tasks  = array();

    /* }}} */

    /* {{{ public void __construct() */
    /**
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct($option)
    {
        parent::__construct($option);
        if (empty($this->option['p'])) {
            $this->option['p']  = ip2long(exec('hostname -i'));
        }
    }
    /* }}} */

    /* {{{ public void cleanup() */
    /**
     * 运行结束后数据清理
     *
     * @access public
     * @return void
     */
    public function cleanup()
    {
        $this->tasks    = array();
    }
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
        $this->tasks    = (array)Queque::instance()->fetch(
            $this->option['n'], $this->option['p']
        );

        if (2 > $this->option['n']) {
            $this->tasks    = array($this->tasks);
        }

        foreach ($this->tasks AS $queque) {
            if (!self::lock($queque['id'])) {
                continue;
            }

            $runner = \Myfox\App\Task::create($queque);
            if (!($runner instanceof \Myfox\App\Task)) {
                $flag   = Queque::FLAG_IGNO;
                $error  = sprintf("Undefined task_type named as '%s'", $queque['type']);
                $status = '';
            } else {
                $flag   = $runner->execute();
                while (\Myfox\App\Task::WAIT === $flag) {
                    $flag   = $runner->wait();
                }
                $flag   = isset(self::$codemap[$flag]) ? self::$codemap[$flag] : Queque::FLAG_WAIT;
                $error  = $runner->lastError();
                $status = $runner->result();
            }

            self::unlock($queque['id'], $flag, array(
                'last_error'    => $error,
                'tmp_status'    => $status,
            ));
        }

        return (bool)$loop;
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
        return empty($this->tasks) ? 120000 : 1;
    }
    /* }}} */

    /* {{{ private static Integer lock() */
    /**
     * 任务加锁
     *
     * @access private static
     * @return Integer
     */
    private static function lock($id)
    {
        return Queque::instance()->update($id, array(
            'begtime'   => sprintf(
                "IF(task_flag=%d, '%s', begtime)",
                Queque::FLAG_WAIT, date('Y-m-d H:i:s')
            ),
            'task_flag' => Queque::FLAG_LOCK,
        ), array(
            'begtime' => true,
        ));
    }
    /* }}} */

    /* {{{ public Boolean unlock() */
    /**
     * 完成后任务解锁
     *
     * @access public
     * @return Boolean true or false
     */
    private static function unlock($id, $flag = Queque::FLAG_DONE, $option = null, $comma = null)
    {
        return Queque::instance()->update($id, array(
            'trytimes'  => sprintf('IF(task_flag=%d && task_flag > 0,trytimes-1,trytimes)', Queque::FLAG_LOCK),
            'endtime'   => sprintf("IF(task_flag=%d,'%s',endtime)", Queque::FLAG_LOCK, date('Y-m-d H:i:s')),
            'task_flag' => $flag,
        ) + (array)$option, array(
            'trytimes'  => true,
            'endtime'   => true,
            'task_flag' => true,
        ) + (array)$comma);
    }
    /* }}} */

}

