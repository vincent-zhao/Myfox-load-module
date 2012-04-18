<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 数据装载API	    					    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: import.php 18 2010-04-13 15:40:37Z zhangxc83 $

namespace Myfox\App\Control;

use \Myfox\Lib\Context;

use \Myfox\App\Security;
use \Myfox\App\Model\Router;

class Import extends \Myfox\App\Controller
{

    /* {{{ private static void output() */
    /**
     * 消息输出
     *
     * @access private static
     * @return void
     */
    private static function output($code, $message, $data = '')
    {
        @header('Content-Type: text/plain;charset=utf-8');
        $header	= sprintf("[%u] %s", $code, trim($message));
        echo $header, "\n", is_scalar($data) ? (string)$data : json_encode($data);
    }
    /* }}} */

    /* {{{ private static integer priority() */
    /**
     * 判断任务优先级
     *
     * @access private static
     * @return Integer or Boolean false
     */
    private static function priority()
    {
        $secure = new Security(__DIR__ . '/../../etc/secure/import.ini');
        return $secure->priority(Context::userip());
    }
    /* }}} */

    /* {{{ protected void actionIndex() */
    /**
     * 数据装载状态查询
     *
     * @access protected
     * @return void
     */
    protected function actionIndex($param, $post = null)
    {
        self::output(0, 'OK', json_encode(array(
            'last_date' => \Myfox\App\Setting::get('last_date')
        )));
    }
    /* }}} */

    /* {{{ protected void actionReady() */
    /**
     * 数据装完的ready信号
     *
     * @access protected
     * @return void
     */
    protected function actionReady($gets, $data = null)
    {
        if (false === ($priority = self::priority())) {
            self::output(1100, 'Access Denied.');
            return false;
        }

        $rdate  = self::vars('date', $gets);
        $rdate  = date('Ymd', strtotime(empty($rdate) ? '-1 day' : $rdate));

        $option = array(
            'priority'  => $priority + 1,
            'trytimes'  => 3,
            'task_flag' => \Myfox\App\Queque::FLAG_WAIT,
            'adduser'   => sprintf('web:%s', Context::userip()),
        );

        $qinfo  = array(
            'thedate'   => $rdate,
            'priority'  => $priority,
        );
        $queque = \Myfox\App\Queque::instance();
        if (!$queque->insert('ready', $qinfo, 0, $option)) {
            self::output(1300, sprintf('Inner Error (%s).', $queque->lastError()));
            return false;
        }

        self::output(0, 'OK', array('id' => $queque->lastId()));
    }
    /* }}} */

    /* {{{ protected void actionHello() */
    /**
     * 未按行数切分的文件装载接口
     *
     * @access protected
     * @return void
     */
    protected function actionHello($param, $post)
    {
        if (false === ($priority = self::priority())) {
            self::output(1100, 'Access Denied.');
            return false;
        }

        foreach (array('table', 'route', 'file', 'lines') AS $key) {
            if (!isset($post[$key]) && !isset($param[$key])) {
                self::output(1200, sprintf('Param "%s" is required.', $key));
                return false;
            }
        }

        $info   = array(
            'table'     => self::vars('table',  $post, $param),
            'route'     => self::vars('route',  $post, $param),
            'file'      => self::vars('file',   $post, $param),
            'lines'     => self::vars('lines',  $post, $param),
            'priority'  => max(0, $priority - 1),
        );

        $ipaddr = Context::userip();
        $option = array(
            'priority'  => $priority,
            'trytimes'  => 3,
            'task_flag' => \Myfox\App\Queque::FLAG_WAIT,
            'adduser'   => sprintf('web:%s', $ipaddr),
        );

        $queque = \Myfox\App\Queque::instance();
        if (!$queque->insert('rsplit', $info, ip2long($ipaddr), $option)) {
            self::output(1300, sprintf('Inner Error (%s).', $queque->lastError()));
            return false;
        }

        self::output(0, 'OK', array('id' => $queque->lastId()));
    }
    /* }}} */

    /* {{{ protected void actionRoute() */
    /**
     * 计算特定路由的路由值并产生分片规则，外部程序切分文件前调用
     *
     * @access protected
     * @return void
     */
    protected function actionRoute($param, $post)
    {
        if (false === (self::priority())) {
            self::output(1100, 'Access Denied.');
            return false;
        }

        foreach (array('table', 'route', 'lines') AS $key) {
            if (empty($param[$key])) {
                self::output(1200, sprintf('Param "%s" is required.', $key));
                return false;
            }
        }

        $table  = self::vars('table', $param);
        $route  = array(array(
            'field' => Router::parse(self::vars('route', $param), $table),
            'count' => (int)self::vars('lines', $param),
        ));

        try {
            $routes = Router::set($table, $route);
            if (!is_array($routes)) {
                self::output(1300, 'Route failed.');
                return;
            }

            $output = array();
            foreach ($routes AS $key => $shard) {
                foreach ((array)$shard AS $split) {
                    $output[]   = sprintf(
                        "%s\t%s\t%u\t%s\t%s", $param['table'], $key, 
                        $split['rows'], $split['hosts'], $split['table']
                    );
                }
            }
            self::output(0, 'OK', implode("\n", $output));
        } catch (\Exception $e) {
            self::output(1400, $e->getMessage());
        }
    }
    /* }}} */

    /* {{{ protected void actionQueque() */
    /**
     * 切分好的分片数据装载, 外部切分程序调用
     *
     * @access protected
     * @return void
     */
    protected function actionQueque($gets, $data)
    {
        if (false === ($priority = self::priority())) {
            self::output(1100, 'Access Denied.');
            return false;
        }

        $table  = self::vars('table', $data, $gets);
        if (empty($table)) {
            self::output(1200, 'Param "table" is required.');
            return false;
        }

        foreach (array('file', 'route', 'bucket', 'hosts') AS $key) {
            if (empty($data[$key])) {
                self::output(1201, sprintf('Param "%s" is required in post data.', $key));
                return false;
            }
        }

        $info   = array(
            'table'     => $table,
            'route'     => $data['route'],
            'file'      => $data['file'],
            'bucket'    => $data['bucket'],
            'hosts'     => $data['hosts'],
        );

        $ipaddr = Context::userip();
        $agent  = isset($param['agent']) ? (int)$param['agent'] : ip2long($ipaddr);
        $option = array(
            'priority'  => $priority,
            'trytimes'  => 3,
            'task_flag' => \Myfox\App\Queque::FLAG_WAIT,
            'adduser'   => sprintf('web:%s', $ipaddr),
        );

        $queque = \Myfox\App\Queque::instance();
        if (!$queque->insert('import', $info, $agent, $option)) {
            self::output(1300, sprintf('Inner Error (%s).', $queque->lastError()));
            return false;
        }

        self::output(0, 'OK', array('id' => $queque->lastId()));
    }
    /* }}} */

}

