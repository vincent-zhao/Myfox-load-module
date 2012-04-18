<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | Myfox Agent API                                                        |
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>                                   |
// +------------------------------------------------------------------------+
//

namespace Myfox\App\Control;

use \Myfox\Lib\Context;
use \Myfox\App\Security;

class Agent extends \Myfox\App\Controller
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
        $header = sprintf("[%u] %s", $code, trim($message));
        echo $header, "\n", is_scalar($data) ? (string)$data : json_encode($data);
    }
    /* }}} */

    /* {{{ private static Integer access() */
    private static function access()
    {
        $secure = new \Myfox\App\Security(__DIR__ . '/../../etc/secure/agent.ini');
        return $secure->priority(Context::userip());
    }
    /* }}} */

    /* {{{ public void actionQueque() */
    /**
     * 获取agent任务队列
     *
     * @access public
     * @return void
     */
    public function actionQueque($gets, $data = null)
    {
    }
    /* }}} */

    /* {{{ public void actionCommit() */
    /**
     * 更新队列状态
     *
     * @access public
     * @return void
     */
    public function actionCommit($gets, $data)
    {
    }
    /* }}} */

    /* {{{ public void actionCreatetable() */
    /**
     * 创建表
     *
     * @access public
     * @return void
     */
    public function actionCreatetable($gets, $data = null)
    {
    }
    /* }}} */

    /* {{{ public void actionDroptable() */
    /**
     * 删除表
     *
     * @access public
     * @return void
     */
    public function actionDroptable($gets, $data = null)
    {
    }
    /* }}} */

}

