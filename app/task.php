<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: task.php 18 2010-04-13 15:40:37Z zhangxc83 $
//

namespace Myfox\App;

use \Myfox\App\Model\Server;

abstract class Task
{

    /* {{{ 静态常量 */

    const SUCC  = 0;
    const FAIL  = 1;
    const WAIT  = 2;
    const IGNO  = 9;

    const MAX_CACHE_TIME    = 900;

    /* }}} */

    /* {{{ 静态变量 */

    protected static $mysql;

    protected static $hosts;

    private static $load_ts = 0;

    /* }}} */

    /* {{{ 成员变量 */

    protected $id;

    protected $status;

    protected $result;

    private $option;

    private $lastError;

    /* }}} */

    /* {{{ public static Object create() */
    /**
     * 工厂模式创建任务
     *
     * @access public static
     * @return Object
     */
    final public static function create($info)
    {
        foreach (array('id', 'type', 'status', 'info') AS $k) {
            if (!isset($info[$k])) {
                throw new \Myfox\Lib\Exception(sprintf('Field "%s" is required for Task::create', $k));
            }
        }

        $class  = sprintf('%s\Task\%s', __NAMESPACE__, ucfirst(strtolower($info['type'])));
        try {
            $object = new $class((int)$info['id'], json_decode($info['info'], true), $info['status']);
        } catch (\Exception $e) {
            $object = false;
        }

        return $object;
    }
    /* }}} */

    /* {{{ abstract public Integer execute() */
    /**
     * 执行任务
     *
     * @access public
     * @return Integer
     */
    abstract public function execute();
    /* }}} */

    /* {{{ public void __construct() */
    /**
     * 构造函数
     *
     * @access public
     * @param  Integer $id
     * @param  Array $option
     * @return void
     */
    public function __construct($id, $option, $status = '')
    {
        $this->id       = (int)$id;
        $this->option   = (array)$option;
        $this->status   = $status;

        if (empty(self::$mysql)) {
            self::$mysql    = \Myfox\Lib\Mysql::instance('default');
        }
    }
    /* }}} */

    /* {{{ public Mixture __get() */
    /**
     * 魔术方法获取私有变量
     *
     * @access public
     * @return Mixture
     */
    public function __get($key)
    {
        return isset($this->$key) ? $this->$key : null;
    }
    /* }}} */

    /* {{{ public Integer wait() */
    /**
     * 等待异步执行结果
     *
     * @access public
     * @return Integer
     */
    public function wait()
    {
        return self::FAIL;
    }
    /* }}} */

    /* {{{ public String lastError() */
    /**
     * 获取错误描述
     *
     * @access public
     * @return String
     */
    public function lastError()
    {
        return $this->lastError;
    }
    /* }}} */

    /* {{{ public Mixture option() */
    /**
     * 返回任务属性
     *
     * @access public
     * @return Mixture
     */
    public function option($key, $default = null)
    {
        return isset($this->option[$key]) ? $this->option[$key] : $default;
    }
    /* }}} */

    /* {{{ public Mixture result() */
    /**
     * 返回执行结果
     *
     * @access public
     * @return Mixture
     */
    public function result()
    {
        return $this->result;
    }
    /* }}} */

    /* {{{ protected void setError() */
    /**
     * 设置错误消息
     *
     * @access protected
     * @param  String $error
     * @return void
     */
    protected function setError($error)
    {
        $this->lastError    = trim($error);
    }
    /* }}} */

    /* {{{ protected void metadata() */
    /**
     * 加载节点、主机信息
     *
     * @access protected
     * @return void
     */
    protected function metadata(&$flush)
    {
        $time   = time();
        $flush  = false;
        if ($time - (int)self::$load_ts <= self::MAX_CACHE_TIME) {
            return;
        }

        $flush  = true;
        self::$hosts    = array();
        $query  = 'SELECT host_id,host_name,host_type,host_pos';
        $query  = sprintf(
            '%s FROM %shost_list WHERE host_stat <> %d',
            $query, self::$mysql->option('prefix'), Server::STAT_ISDOWN
        );

        foreach ((array)self::$mysql->getAll(self::$mysql->query($query)) AS $row) {
            self::$hosts[$row['host_id']] = array(
                'name'  => trim(strtolower($row['host_name'])),
                'type'  => (int)$row['host_type'],
                'pos'   => (int)$row['host_pos'],
            );
        }

        self::$load_ts  = $time;
    }
    /* }}} */

    /* {{{ protected Boolean isReady() */
    /**
     * 检查参数是否完整
     *
     * @access protected
     * @return Boolean true or false
     */
    protected function isReady()
    {
        $args   = func_get_args();
        foreach ((array)$args AS $id) {
            if (!isset($this->option[$id])) {
                $this->setError(sprintf('Required column named as "%s"', $id));
                return false;
            }
        }

        return true;
    }
    /* }}} */

}
