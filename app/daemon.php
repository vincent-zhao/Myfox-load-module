<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 后台运行daemon类						    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: daemon.php 22 2010-04-15 16:28:45Z zhangxc83 $
//

namespace Myfox\App;

class Daemon
{

    /* {{{ 静态变量 */

    private static $signal  = array(
        SIGTERM     => 'SIGTERM',
    );

    /* }}} */

    /* {{{ 成员变量 */

    private $master = true;

    private $worker = null;

    private $isrun  = false;

    /* }}} */

    /* {{{ public static void run() */
    /**
     * 进入工作模式
     *
     * @access public static
     * @return void
     */
    public static function run($ini, $args = null)
    {
        $run = array_shift($args);
        if (sizeof($args) < 1 || 0 === strcasecmp('help', reset($args))) {
            self::usage($run);
            exit(1);
        }

        $master = new self($ini, array_shift($args), self::parse($args));
        $master->dispatch();
    }
    /* }}} */

    /* {{{ public static Mixture parse() */
    /**
     * 解析命令行参数
     *
     * @access public static
     * @return Mixture
     */
    public static function parse($args)
    {
        $rt = array();
        for ($i = 0, $max = count($args); $i < $max; $i++) {
            $at = $args[$i];
            if (0 === strncmp($at, '--', 2)) {
                $at = explode('=', ltrim($at, '-'), 2);
                $id = array_shift($at);
                $at = (string)reset($at);
            } elseif (0 === strncmp($at, '-', 1) && strlen($at) > 1) {
                $id = substr($at, 1, 1);
                $at = (string)substr($at, 2);
            } else {
                continue;
            }

            if (strlen($at) > 0) {
                $rt[$id]    = $at;
            } elseif (isset($args[$i + 1]) && 0 !== strncmp($args[$i + 1], '-', 1)) {
                $rt[$id]    = $args[++$i];
            } else {
                $rt[$id]    = true;
            }
        }

        return $rt;
    }
    /* }}} */

    /* {{{ private static void usage() */
    /**
     * 打印Usage信息
     *
     * @access private static
     * @return void
     */
    private static function usage($run)
    {
        printf("Usage: ./%s CLASS [METHOD] [OPTION1] ...\n", basename($run));
    }
    /* }}} */

    /* {{{ public void sigaction() */
    /**
     * 信号处理
     *
     * @access public
     * @return void
     */
    public function sigaction($signal)
    {
        $this->isrun    = false;
        printf("[%s]\tGot signal (%d), exiting...\n", date('Y-m-d H:i:s'), $signal);
    }
    /* }}} */

    /* {{{ private void __construct() */
    /**
     * 构造函数
     *
     * @access private
     * @return void
     */
    private function __construct($ini, $class, $option = null)
    {
        require_once __DIR__ . '/application.php';
        Application::init($ini);

        $worker = sprintf(__NAMESPACE__ . '\worker\%s', ucfirst(strtolower($class)));
        $this->worker   = new $worker((array)$option);
        if (!$this->worker instanceof \Myfox\App\Worker) {
            printf("Class \"%s\" is not a subclass extended from \"Worker\".\n", $worker);
            exit;
        }
    }
    /* }}} */

    /* {{{ private void dispatch() */
    /**
     * 任务分发
     *
     * @access private
     * @return void
     */
    private function dispatch()
    {
        $check  = version_compare(phpversion(), '5.3.0', 'ge');
        if (empty($check)) {
            declare(ticks = 1);
        }

        foreach (self::$signal AS $sig => $txt) {
            pcntl_signal($sig, array(&$this, 'sigaction'));
        }

        $this->isrun    = true;
        while ($this->isrun) {
            $check && pcntl_signal_dispatch();
            $this->worker->cleanup();
            if ($this->isrun = $this->worker->execute()) {
                usleep(1000 * max(1, $this->worker->interval()));
            }
        }
    }
    /* }}} */

}
