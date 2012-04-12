<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | (C) 2011-2012 Alibaba Group Holding Limited.                           |
// | This program is free software; you can redistribute it and/or          |
// | modify it under the terms of the GNU General Public License            |
// | version 2 as published by the Free Software Foundation.                |
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>                                   |
// +------------------------------------------------------------------------+
//
// $Id: checktable.php 18 2010-04-13 15:40:37Z zhangxc83 $
//

namespace Myfox\App\Task;

use \Myfox\App\Model\Server;

class Checktable extends \Myfox\App\Task
{

    /* {{{ public Integer execute() */
    /**
     * 执行check table操作
     */
    public function execute()
    {
        if (!$this->isReady('host', 'path')) {
            return self::IGNO;
        }

        $exist  = sprintf('DESC %s', self::$mysql->escape($this->option('path')));
        $query  = sprintf('CHECK TABLE %s', self::$mysql->escape($this->option('path')));

        $return = self::FAIL;
        $ignore = array_flip(explode(',', (string)$this->status));
        foreach (explode(',', trim($this->option('host', '{}'))) AS $id) {
            if (!isset(self::$hosts[$id]) || isset($ignore[$id])) {
                continue;
            }

            $mysql  = Server::instance(self::$hosts[$id]['name'])->getlink();
            if ((bool)$mysql->query($exist)) {

                // @see: http://dev.mysql.com/doc/refman/5.1/en/check-table.html

                $ok = false;
                foreach (array('FAST', 'MEDIUM', 'EXTENDED') AS $mode) {
                    if (self::success($mysql->getAll($mysql->query(sprintf('%s %s', $query, $mode))))) {
                        $ok = true;
                        break;
                    }
                }

                if (!$ok) {
                    continue;
                }
            }

            $return = self::SUCC;
            $ignore[$id]    = true;
        }

        return $return;
    }
    /* }}} */

    /* {{{ private static Boolean success() */
    /**
     * 判断check table是否成功
     *
     * @access private static
     * @return Boolean true or false
     */
    private static function success($result)
    {
        if (empty($result) || !is_array($result)) {
            return false;
        }

        foreach ((array)$result AS $row) {
            if (!isset($row['Msg_type']) || !isset($row['Msg_text'])) {
                continue;
            }
            if ('status' == $row['Msg_type'] && 0 != strcasecmp('OK', $row['Msg_text'])) {
                return false;
            }
        }

        return true;
    }
    /* }}} */

}

