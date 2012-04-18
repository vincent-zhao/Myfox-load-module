<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 后台运行daemon类                                                       |
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>                                   |
// +------------------------------------------------------------------------+
//
// $Id: daemon.php 22 2010-04-15 16:28:45Z zhangxc83 $
//

namespace Myfox\App;

class Daemon
{

    /* {{{ 静态变量 */

    /**
     * @进程身份号
     */
    private static $identy  = '';

    /**
     * @运行环境 (test|rc1|rc2|online|dev)
     */
    private static $runmode = '';

    /* }}} */

    /* {{{ 成员变量 */

    private $master = true;

    private $worker = null;

    /**
     * @信号回调
     */
    private $signal = array();

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
        $master = self::instance($ini, $args);
        $master->dispatch();
    }
    /* }}} */

    /* {{{ public static Object instance() */
    /**
     * 返回daemon实例
     *
     * @access public static
     * @return Object
     */
    public static function instance($ini, $args = null)
    {
        $run = array_shift($args);
        if (sizeof($args) < 1 || 0 === strcasecmp('help', reset($args))) {
            self::usage($run);
            exit(1);
        }

        return new self($ini, array_shift($args), self::parse($args));
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

    /* {{{ private static void msleep() */
    /**
     * usleep 优化版
     * 1. fix usleep bug, really ms, not us
     * 2. sleep sharding
     * @return void 
     */
    private static function msleep($ms)
    {
        $me = 200000;
        $ms = $ms * 1000;
        while ($ms >= $me) {
            usleep($me);
            $ms -= $me;
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }

        if ($ms > 0) {
            usleep($ms);
        }
    }
    /* }}} */

    /* {{{ public void sigaction() */
    /**
     * 注册信号处理函数
     *
     * @access public
     * @return void
     */
    public function sigaction($signal, $callback)
    {
        if (is_callable($callback)) {
            $this->signal[(int)$signal] = $callback;
        }
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

        $config = new \Myfox\Lib\Config($ini);
        self::$runmode  = strtolower(trim($config->get('run.mode', 'online')));
        self::$identy   = sprintf('%d@%s', getmypid(), strtolower(trim(php_uname('n'))));

        $_clone = &$this;
        $this->sigaction(SIGTERM, function ($signal) use ($_clone) {
            printf("[%s]\tGot signal (%d)", date('Y-m-d H:i:s'), $signal);
            if (SIGTERM == $signal) {
                echo ", about to terminal ...\n";
                $_clone->freelock();
                exit(0);
            }
        });
    }
    /* }}} */

    /* {{{ public void dispatch() */
    /**
     * 任务分发
     *
     * @access public
     * @return void
     */
    public function dispatch()
    {
        $check  = version_compare(phpversion(), '5.3.0', 'ge');
        if (empty($check)) {
            declare(ticks = 1);
        }

        foreach ($this->signal AS $signal => $callback) {
            pcntl_signal($signal, $callback);
        }

        while (1) {
            $check && pcntl_signal_dispatch();
            if ($this->islocked($running)) {
                self::msleep(5 * $this->worker->interval());
                continue;
            }

            $this->worker->cleanup();
            if (!$this->worker->execute()) {
                break;
            }
            self::msleep($this->worker->interval());
        }
    }
    /* }}} */

    /* {{{ private Boolean islocked() */
    /**
     * 判断是否被锁定
     *
     * @access private
     * @return Boolean true or false
     */
    private function islocked(&$value, $expire = 1800)
    {
        $name   = strtolower(trim($this->worker->locker()));
        if (empty($name)) {
            return false;
        }

        $mysql  = \Myfox\Lib\Mysql::instance('default');
        $query  = sprintf(
            "SELECT wholock, modtime FROM %sprocess_locker WHERE lockkey='%s' AND lockenv='%s' LIMIT 1",
            $mysql->option('prefix'), $mysql->escape($name), $mysql->escape(self::$runmode)
        );

        $locker = $mysql->getRow($mysql->query($query));
        if (empty($locker) || !isset($locker['wholock'])) {
            $query  = sprintf(
                'INSERT INTO %sprocess_locker (addtime, modtime, lockkey, lockenv, wholock)',
                $mysql->option('prefix')
            );
            $query  = sprintf(
                "%s VALUES (%d, %d, '%s', '%s', '%s')",
                $query, time(), time(), $mysql->escape($name),
                $mysql->escape(self::$runmode), $mysql->escape(self::$identy)
            );

            return $mysql->query($query) ? false : true;
        }

        if (!empty($locker['wholock']) && 0 !== strcasecmp(self::$identy, trim($locker['wholock'])) 
            && (time() - (int)$locker['modtime']) <= $expire)
        {
            $value  = $locker['wholock'];
            return true;
        }

        $query  = sprintf(
            "UPDATE %sprocess_locker SET wholock='%s', modtime=%d WHERE lockkey='%s' AND lockenv='%s' AND wholock='%s'",
            $mysql->option('prefix'), $mysql->escape(self::$identy), time(),
            $mysql->escape($name), $mysql->escape(self::$runmode), $mysql->escape($locker['wholock'])
        );

        return $mysql->query($query) ? false : true;
    }
    /* }}} */

    /* {{{ public void freelock() */
    /**
     * 释放锁
     *
     * @access public
     * @return void
     */
    public function freelock()
    {
        $name   = strtolower(trim($this->worker->locker()));
        if (empty($name)) {
            return;
        }

        $mysql  = \Myfox\Lib\Mysql::instance('default');
        return $mysql->query(sprintf(
            "UPDATE %sprocess_locker SET wholock='' WHERE lockkey='%s' AND lockenv='%s' AND wholock='%s'",
            $mysql->option('prefix'), $mysql->escape($name), $mysql->escape(self::$runmode),
            $mysql->escape(self::$identy)
        ));
    }
    /* }}} */

}
