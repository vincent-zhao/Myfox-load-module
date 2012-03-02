<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 黑洞类			    					    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: blackhole.php 22 2010-04-15 16:28:45Z zhangxc83 $

namespace Myfox\Lib;

class Blackhole
{

	public function __call($name, $args)
	{
	}

	public static function __callStatic($name, $args)
	{
	}

	public function __get($key)
	{
	}

	public function __set($key, $value)
	{
	}

}

