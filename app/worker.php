<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | worker.php	       	 					    							|
// +------------------------------------------------------------------------+
// | Copygight (c) 2003 - 2011 Taobao.com. All Rights Reserved				|
// +------------------------------------------------------------------------+
// | Author: pengchun <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+

namespace Myfox\App;

class Worker
{

    /* {{{ 成员变量 */

    protected $option	= array();

    protected $log      = null;

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
        $this->option	= (array)$option + $this->option;
        $this->cleanup();
        $this->log  = \Myfox\Lib\Factory::getLog('daemon');
    }
    /* }}} */

    /* {{{ public void cleanup() */
    /**
     * 临时数据清理接口
     *
     * @access public
     * @return void
     */
    public function cleanup()
    {
    }
    /* }}} */

    /* {{{ public Boolean execute() */
    /**
     * 执行
     *
     * @access public
     * @return Boolean true or false (next loop)
     */
    public function execute($loop = true)
    {
        return (bool)$loop;
    }
    /* }}} */

    /* {{{ public Integer interval() */
    /**
     * 下次运行sleep时间(ms)
     *
     * @access public
     * @return Integer
     */
    public function interval()
    {
        return 1;
    }
    /* }}} */

    /* {{{ public String locker() */
    /**
     * 进程锁名字
     *
     * @access public
     * @return String
     */
    public function locker()
    {
        return '';
    }
    /* }}} */

}

