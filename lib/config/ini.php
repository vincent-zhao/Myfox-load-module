<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +--------------------------------------------------------------------+
// | INI文件解析类														|
// +--------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>								|
// +--------------------------------------------------------------------+
//
// $Id: ini.php 1 2010-06-01 03:52:51Z zhangxc83 $
//

namespace Myfox\Lib\Config;

class Ini
{

    /* {{{ 成员变量 */

    private $inifile;
    private $section;

    /* }}} */

    /* {{{ public void __construct() */
    /**
     * 构造函数
     *
     * @access public
     * @param  Mixture $option
     * @return void
     */
    public function __construct($option)
    {
        if (is_string($option)) {
            $option = parse_url($option);
        }
        if (empty($option['path'])) {
            throw new \Myfox\Lib\Exception('Uncomplete ini option');
        }

        $this->inifile  = trim($option['path']);
        $this->section  = true;
    }
    /* }}} */

    /* {{{ public Mixture parse() */
    /**
     * 解析返回结果
     *
     * @access public
     * @return Mixture
     */
    public function parse()
    {
        return parse_ini_file($this->inifile, $this->section);
    }
    /* }}} */

}

