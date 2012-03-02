<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 数据装桶的类							    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: bucket.php 22 2010-04-15 16:28:45Z zhangxc83 $
//
namespace Myfox\App;

class Bucket
{

    /* {{{ 成员变量 */

    private $cubage;

    private $maxcnt;

    private $cargos;    /**<    原始数据 */

    private $slices;    /**<    切片数据 */

    private $marks;     /**<    使用标记 */

    /* }}} */

    /* {{{ public void __construct() */
    /**
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct($cubage, $float = 0)
    {
        $this->cubage	= (int)abs($cubage);
        $this->maxcnt	= (int)((1 + abs($float)) * $this->cubage);

        $this->cargos   = array();
        $this->slices   = array();
    }
    /* }}} */

    /* {{{ public void push() */
    /**
     * 压入一行数据
     *
     * @access public
     * @param Array $row
     * @param Integer $size
     * @return void
     */
    public function push($row, $size)
    {
        if ($size < 1) {
            return;
        }

        $offset = count($this->cargos);
        $this->cargos[$offset] = $row;
        foreach ($this->split($size) AS $zone) {
            $this->slices[] = array(
                'size'  => $zone,
                'key'   => $offset,
            );
        }
    }
    /* }}} */

    /* {{{ public Array allot() */
    /**
     * 分配
     *
     * @access public
     * @return Array
     */
    public function allot()
    {
        if (!usort($this->slices, function($a, $b) {return $a['size'] > $b['size'] ? -1 : 1;})) {
            return false;
        }

        $return = array();
        $this->marks    = array();
        foreach ($this->slices AS $key => $zone) {
            if (!empty($this->marks[$key])) {
                continue;
            }
            $this->marks[$key]  = true;
            $bucket = array(
                array(
                    'size'  => $zone['size'],
                    'data'  => $this->cargos[$zone['key']],
                ),
            );
            $offset = $key;
            $remain = $this->maxcnt - $zone['size'];
            while ($remain > 0) {
                $offset = $this->search($remain, $offset);
                if ($offset < 0) {
                    break;
                }

                $bucket[] = array(
                    'size'  => $this->slices[$offset]['size'],
                    'data'  => $this->cargos[$this->slices[$offset]['key']],
                );
                $this->marks[$offset] = true;
                $remain -= $this->slices[$offset]['size'];
            }

            $return[] = $bucket;
        }

        return $return;
    }
    /* }}} */

    /* {{{ private Array split() */
    /**
     * 数据切片
     *
     * @access private
     * @param Integer $count
     * @return Array
     */
    private function split($count)
    {
        $split  = array();
        $count  = max(0, (int)$count);
        while ($count > $this->maxcnt) {
            $count  -= $this->cubage;
            $split[] = $this->cubage;
        }
        $split[] = $count;

        return $split;
    }
    /* }}} */

    /* {{{ private Integer search() */
    /**
     * 二分法查找第一个小于$number的值
     *
     * @access private
     * @param  Integer $count
     * @return Integer
     */
    private function search($number, $left = 0, $right = -1)
    {
        $count  = count($this->slices);
        $right  = ($right < 0) ? $count : (int)$right;
        $middle = (int)(($left + $right) / 2);
        $comp   = $this->slices[$middle]['size'];
        if ($comp <= $number && ($middle - 1 <= $left || $this->slices[$middle - 1]['size'] > $number)) {
            for ($i = $middle; $i < $count; $i++) {
                if (empty($this->marks[$i])) {
                    return $i;
                }
            }

            return -1;
        }

        if (abs($right - $left) <= 1) {
            return -1;
        }

        if ($comp > $number) {
            return $this->search($number, $middle, $right);
        }

        return $this->search($number, $left, $middle);
    }
    /* }}} */

}

