<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +--------------------------------------------------------------------+
// | 异常处理类	    													|
// +--------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>								|
// +--------------------------------------------------------------------+
//
// $Id: exception.php 2010-04-19 13:54:32 zhangxc83 Exp $

namespace Myfox\Lib;

class Exception extends \Exception
{

    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return sprintf('%s : [%s] : %s', __CLASS__, $this->code, $this->message);
    }

}

