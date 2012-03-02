<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | Test.php	       	 					    							|
// +------------------------------------------------------------------------+
// | Copygight (c) 2003 - 2011 Taobao.com. All Rights Reserved				|
// +------------------------------------------------------------------------+
// | Author: pengchun <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+

namespace Myfox\App\Worker;

class Test extends \Myfox\App\Worker
{

    /* {{{ 静态变量 */

    public static $number   = 0;

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
        return (++self::$number < 5) && $this->option['loop'];
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
        return $this->option['sleep'];
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
    }
    /* }}} */

}

