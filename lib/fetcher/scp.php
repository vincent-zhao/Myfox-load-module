<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | SCP获取文件	    					    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>	    							|
// +------------------------------------------------------------------------+
//
// $Id: scp.php 22 2012-03-13 16:28:45Z zhangxc83 $

namespace Myfox\Lib\Fetcher;

class Scp
{

	/* {{{ 成员变量 */

	private $error	= '';

	private $handle = null;

	private $option;

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
	}
	/* }}} */

	/* {{{ public Boolean fetch() */
	/**
	 * 获取文件
	 *
	 * @access public
	 * @return Boolean true or false
	 */
	public function fetch($fname, $cache = true)
	{
		return true;
	}
	/* }}} */

	/* {{{ public Mixture lastError() */
	/**
	 * 获取错误描述
	 *
	 * @access public
	 * @return Mixture
	 */
	public function lastError()
	{
		return $this->error;
	}
	/* }}} */

}
