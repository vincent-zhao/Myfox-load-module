<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | SCP获取文件                                                            |
// +------------------------------------------------------------------------+
// | Author: xuyi <xuyi.zl@taobao.com>                                      |
// +------------------------------------------------------------------------+

namespace Myfox\Lib\Fetcher;

class Scp
{

    /* {{{ 成员变量 */

    private $error    = '';

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
        if(!empty($option['query'])) {
            parse_str($option['query'], $query);
            $this->option   = (array)$option + (array)$query;
        }

        $this->option   = (array)$option + array(
            'user'  => get_current_user(),
        );
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
        if (!$this->isChange($fname)) {
            return true;
        }
        return self::ssh(sprintf('scp %s@%s:%s %s',
            escapeshellcmd($this->option['user']),
            escapeshellcmd($this->option['host']),
            escapeshellcmd($this->option['path']),
            escapeshellcmd($fname)
        ), $this->error);
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

    /* {{{ private Boolean ssh() */
    /**
     * ssh
     *
     * @access private
     * @return Boolean true or false
     */
    private static function ssh($script, &$output)
    {
        $return = exec(sprintf('%s 2>&1', $script), $output, $rt);
        $output = !empty($return) ? $return : $output;
        return 0 === $rt ? true : false;
    }
    /* }}}*/

    /*{{{ private Boolean isChange()*/
    /**
     * 文件是否变化
     *
     * @access private
     * @return Boolean true or false
     */

    public function isChange($file)
    {
        if (!is_file($file)) {
            return true;
        }
        $rt = self::ssh(sprintf(
            'ssh %s@%s "ls -l --full-time \"%s\""',
            escapeshellcmd($this->option['user']),
            escapeshellcmd($this->option['host']),
            escapeshellcmd($this->option['path'])
        ), $stat);
        if (false === $rt) {
            return true;
        }

        $stat   = self::parsestat(trim($stat));
        if ($stat['size'] != filesize($file) || $stat['mtime'] >= filemtime($file)) {
            return true;
        }
        return false;
    }
    /*}}}*/

    /* {{{ public static Mixture parsestat() */
    /**
     * 解析ls -l --full-time的结果
     *
     * @access public static
     * @return Mixture
     */
    public static function parsestat($stat)
    {
        $stat   = explode(' ', trim($stat));
        return array(
            'size'  => $stat[4],
            'mtime' => strtotime(sprintf('%s %s', $stat[5], substr($stat[6], 0, 8), $stat[7])),
        );
    }
    /* }}} */

}
