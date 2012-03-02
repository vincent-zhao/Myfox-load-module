<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +--------------------------------------------------------------------+
// | 调用入口											            	|
// +--------------------------------------------------------------------+
// | Copyright (c) 2010 Taobao.com. All Rights Reserved					|
// +--------------------------------------------------------------------+
// | Author: pengchun <pengchun@taobao.com>								|
// +--------------------------------------------------------------------+
//
// $Id: run.php 48 2010-12-20 15:58:11Z pengchun $

require_once __DIR__ . '/../app/daemon.php';

\Myfox\App\Daemon::run(
	__DIR__ . '/../etc/myfox.ini',
	$_SERVER['argv']
);

