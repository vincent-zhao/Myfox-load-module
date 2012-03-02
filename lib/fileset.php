<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 文件获取类		    					    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>	    							|
// +------------------------------------------------------------------------+
//
// $Id: fileset.php 22 2010-04-15 16:28:45Z zhangxc83 $

namespace Myfox\Lib;

class Fileset
{

    /* {{{ 静态常量 */

    const TEMP_PATH	= '/tmp/myfox/download';

    /* }}} */

    /* {{{ 静态变量 */

    private static $lastError	= '';

    /* }}} */

    /* {{{ public static Mixture getfile() */
    /**
     * 从给定URL获取文件
     *
     * @access public static
     * @return Mixture
     */
    public static function getfile($url, $path = '', $cache = true)
    {
        $option	= parse_url((string)$url);
        if (empty($option) || empty($option['path'])) {
            self::setError(sprintf('Unrecognized url as "%s" for fileset.', $url));
            return false;
        }

        if (empty($option['scheme'])) {
            $fname	= realpath($option['path']);
            if (empty($fname)) {
                self::setError(sprintf('File not found as the path "%s"', $option['path']));
                return false;
            }

            return $fname;
        }

        if (empty($path)) {
            $fpath  = self::TEMP_PATH;
            $fname  = basename($option['path']);
        } elseif ('/' == substr($path, -1)) {
            $fpath  = rtrim($path, '/');
            $fname  = basename($option['path']);
        } else {
            $fpath  = dirname($path);
            $fname  = basename($path);
        }

        if (!is_dir($fpath) && !@mkdir($fpath, 0755, true)) {
            self::setError(sprintf('Path "%s" doesn\'t exist, and create failed.', $fpath));
            return false;
        }

        $class	= sprintf('%s\\Fetcher\\%s', __NAMESPACE__, ucfirst($option['scheme']));
        try {
            $worker	= new $class($option);
            $fname  = sprintf('%s/%s', $fpath, $fname);
            if (!$worker->fetch($fname, $cache)) {
                self::setError($worker->lastError());
                return false;
            }

            return $fname;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
        }

        return false;
    }
    /* }}} */

    /* {{{ public String lastError() */
    /**
     * 获取错误信息
     *
     * @access public
     * @return String
     */
    public static function lastError()
    {
        return self::$lastError;
    }
    /* }}} */

    /* {{{ private static void setError() */
    /**
     * 设置错误描述
     *
     * @access private static
     * @return void
     */
    private static function setError($error)
    {
        self::$lastError	= trim($error);
    }
    /* }}} */

}
