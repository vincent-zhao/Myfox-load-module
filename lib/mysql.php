<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | MySQL操作类	    					    							|
// |                                                                        | 
// | Based on php5.3.3, with mysqli & mysqlnd                               |
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>	    							|
// +------------------------------------------------------------------------+
//
// $Id: mysql.php 22 2010-04-15 16:28:45Z zhangxc83 $

namespace Myfox\Lib;

use \Myfox\Lib\Config;
use \Myfox\Lib\LiveBox;

class Mysql
{

    /* {{{ 静态常量 */

    const NOMANIPULATE  = '/^(SELECT|SHOW|DESC|DESCRIBE)\s+/is';

    /* }}} */

    /* {{{ 静态变量 */

    private static $objects	= array();

    private static $alias	= array();

    private static $queque  = array();      /**<    异步请求列表   */

    private static $counter = 0;            /**<    异步请求计数器 */

    /* }}} */

    /* {{{ 成员变量 */

    private $master;

    private $slave;

    private $handle;

    private $isMaster   = false;

    private $transact   = 0;

    private $option = array(
        'timeout'   => 5,
        'persist'   => false,
        'charset'   => 'utf8',
        'dbname'    => '',
        'prefix'    => '',
        'logurl'    => '',
    );

    private $error  = '';

    private $log;

    /* }}} */

    /* {{{ public static Object instance() */
    /**
     * 获取实例
     *
     * @access public static
     * @return object
     */
    public static function instance($name)
    {
        $name	= self::normalize($name);
        if (empty(self::$objects[$name])) {
            if (!isset(self::$alias[$name])) {
                throw new \Myfox\Lib\Exception(sprintf('Undefined mysql instance named as "%s"', $name));
            }
            self::$objects[$name]	= new self(self::$alias[$name], $name);
        }

        return self::$objects[$name];
    }
    /* }}} */

    /* {{{ public static void register() */
    /**
     * 注册别名
     *
     * @access public static
     * @return void
     */
    public static function register($name, $config = null)
    {
        self::$alias[self::normalize($name)] = $config;
    }
    /* }}} */

    /* {{{ public static void removeAllNames() */
    /**
     * 清理所有对象
     *
     * @access public static
     * @return void
     */
    public static function removeAllNames()
    {
        foreach (self::$objects AS $mysql) {
            $mysql->disconnect();
        }

        self::$objects	= array();
        self::$alias	= array();
    }
    /* }}} */

    /* {{{ public void __construct() */
    /**
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct($config = null, $name = null)
    {
        if (is_scalar($config) && !empty($config)) {
            try {
                $config = \Myfox\Lib\Config::instance($config);
            } catch (\Exception $e) {
                $config = new \Myfox\Lib\Config($config);
            }
            $config = $config->get('');
        }

        $config = (array)$config;
        foreach ($config AS $key => $val) {
            if (isset($this->option[$key])) {
                $this->option[$key] = $val;
            }
        }

        $prefix = empty($name) ? md5(json_encode($config)) : strtolower(trim($name));
        $this->master   = new LiveBox('#MYSQL#' . $prefix . '/master', 10);
        if (!empty($config['master'])) {
            $this->init('addMaster', $config['master']);
        }

        $this->slave    = new LiveBox('#MYSQL#' . $prefix . '/slave', 300);
        if (!empty($config['slave'])) {
            $this->init('addSlave', $config['slave']);
        }

        if (empty($this->option['logurl'])) {
            $this->log  = new \Myfox\Lib\BlackHole();
        } else {
            $this->log  = new \Myfox\Lib\Log($this->option['logurl']);
        }

        $name   = self::normalize($name);
        if (!empty($name) && !isset(self::$objects[$name])) {
            self::$objects[$name]  = &$this;
        }
    }
    /* }}} */

    /* {{{ public void __destruct() */
    /**
     * 析构函数
     *
     * @access public
     * @return void 
     */
    public function __destruct()
    {
        $this->disconnect();
    }
    /* }}} */

    /* {{{ public void addMaster() */
    /**
     * 添加主库
     *
     * @access public
     * @return void
     */
    public function addMaster($host, $user, $pass, $port = 3306)
    {
        $this->master->register(array(
            'host'  => $host,
            'port'  => $port,
            'user'  => $user,
            'pass'  => $pass,
        ));
    }
    /* }}} */

    /* {{{ public void addSlave() */
    /**
     * 添加从库
     *
     * @access public
     * @return void
     */
    public function addSlave($host, $user, $pass, $port = 3306)
    {
        $this->slave->register(array(
            'host'  => $host,
            'port'  => $port,
            'user'  => $user,
            'pass'  => $pass,
        ));
    }
    /* }}} */

    /* {{{ public void begin() */
    public function begin()
    {
        if (!$this->transact) {
            try {
                $this->connectToMaster();
            } catch (\Exception $e) {
            
            }
            if (empty($this->handle) || empty($this->isMaster)) {
                throw new \Myfox\Lib\Exception('Connect to master failed.');
            }
            $this->handle->autocommit(false);
        }
        $this->transact++;
    }
    /* }}} */

    /* {{{ public void commit() */
    public function commit()
    {
        if ($this->transact > 0) {
            $this->handle->commit();
            $this->handle->autocommit(true);
        }
        $this->transact = 0;
    }
    /* }}} */

    /* {{{ public void rollback() */
    public function rollback()
    {
        if ($this->transact > 0) {
            $this->handle->rollback();
            $this->handle->autocommit(true);
        }
        $this->transact = 0;
    }
    /* }}} */

    /* {{{ public Mixture option() */
    /**
     * 获取配置属性
     *
     * @access public
     * @return Mixture
     */
    public function option($key)
    {
        return isset($this->option[$key]) ? $this->option[$key] : null;
    }
    /* }}} */

    /* {{{ public Mixture query() */
    /**
     * 执行query
     *
     * @access public
     * @return Mixture
     */
    public function query($query)
    {
        return $this->runsql($query, false);
    }
    /* }}} */

    /* {{{ public Mixture async() */
    /**
     * 异步请求
     *
     * @access public
     * @return void
     */
    public function async($query)
    {
        // xxx : 实际为阻塞
        if (false === ($rs = $this->runsql($query, false))) {
            return false;
        }

        self::$queque[self::$counter] = array(
            'sql'   => $query,
            'res'   => $rs,
        );

        return self::$counter++;
    }
    /* }}} */

    /* {{{ public Mixture wait() */
    /**
     * 等待异步结果
     *
     * @access public
     * @param  String $key
     * @return Mixture
     */
    public function wait($key)
    {
        if (!isset(self::$queque[$key])) {
            return false;
        }

        $id = $key;
        while (!isset(self::$queque[$key]['res'])) {
            $usleep = 500;
            $reads  = array($this->handle);
            $error  = $reject   = array();
            if (!$this->handle->poll($reads, $error, $reject, 0, $usleep)) {
                $usleep = (int)(1.2 * $usleep);
                continue;
            }

            $rs = $this->handle->reap_async_query();
            self::$queque[$id]['res']   = $rs;
        }

        $rt = self::$queque[$id]['res'];
        unset(self::$queque[$id]);

        return $rt;
    }
    /* }}} */

    /* {{{ public Integer lastId() */
    /**
     * 获取最后一次插入的ID
     *
     * @access public
     * @return Integer
     */
    public function lastId()
    {
        return $this->handle ? $this->handle->insert_id : 0;
    }
    /* }}} */

    /* {{{ public Mixture lastError() */
    /**
     * 获取上一次错误描述
     *
     * @access public
     * @return Mixture
     */
    public function lastError()
    {
        return $this->error;
    }
    /* }}} */

    /* {{{ public Mixture getAll() */
    /**
     * 获取结果集
     *
     * @access public
     * @return Mixture
     */
    public function getAll($rs, $limit = 0)
    {
        if ($rs instanceof \MySQLi_Result) {
            return self::fetchFromResult($rs, $limit);
        }

        return null;
    }
    /* }}} */

    /* {{{ public Mixture getRow() */
    /**
     * 获取
     *
     * @access public
     * @return Mixture
     */
    public function getRow($rs)
    {
        $rt = (array)$this->getAll($rs, 1);
        return reset($rt);
    }
    /* }}} */

    /* {{{ public Mixture getOne() */
    /**
     * 获取单元格
     *
     * @access public
     * @return Mixture
     */
    public function getOne($rs, $pos = 0)
    {
        $rt = array_values((array)$this->getRow($rs));
        return isset($rt[$pos]) ? $rt[$pos] : null;
    }
    /* }}} */

    /* {{{ public Mixture escape() */
    /**
     * 安全过滤
     *
     * @access public
     * @return Mixture
     */
    public function escape($val)
    {
        $this->connectToSlave();
        if (!$this->handle) {
            throw new \Myfox\Lib\Exception(
                $this->error ? $this->error : 'Mysql connect failed.'
            );
        }

        if (is_scalar($val) || is_null($val)) {
            return $this->handle->real_escape_string($val);
        }

        $rt = array();
        foreach ((array)$val AS $k => $v) {
            $rt[$this->handle->real_escape_string($k)]  = $this->escape($v);
        }

        return $rt;
    }
    /* }}} */

    /* {{{ public void connectToMaster() */
    /**
     * 连接到主库
     *
     * @access public
     * @return void
     */
    public function connectToMaster()
    {
        if (!empty($this->handle) && $this->isMaster) {
            return;
        }

        $this->disconnect();
        $this->connect($this->master);
        $this->isMaster = true;
    }
    /* }}} */

    /* {{{ public void connectToSlave() */
    /**
     * 连接到从库
     *
     * @access public
     * @return void
     */
    public function connectToSlave()
    {
        if (!empty($this->handle)) {
            return $this->handle->ping();
        }

        try {
            $this->connect($this->slave);
            $this->isMaster = false;
        } catch (\Exception $e) {
        }

        if (empty($this->handle)) {
            $this->connectToMaster();
        }
    }
    /* }}} */

    /* {{{ public void disconnect() */
    /**
     * 断开连接
     *
     * @access public
     * @return void
     */
    public function disconnect()
    {
        if (!empty($this->handle)) {
            $this->rollback();
            $this->handle->close();
            $this->handle   = null;
        }
    }
    /* }}} */

    /* {{{ private void init() */
    /**
     * 初始化机器配置
     *
     * @access private
     * @param  String $method
     * @param  Array $host
     * @return void
     */
    private function init($method, $host)
    {
        foreach ((array)$host AS $ln) {
            $cf = parse_url($ln);
            if (empty($cf)) {
                continue;
            }
            $this->$method(
                $cf['host'],
                empty($cf['user']) ? 'root' : rawurldecode($cf['user']),
                empty($cf['pass']) ? '' : rawurldecode($cf['pass']),
                empty($cf['port']) ? 3306 : $cf['port']
            );
        }
    }
    /* }}} */

    /* {{{ private void connect() */
    /**
     * 连接DB
     *
     * @access private
     * @return void
     */
    private function connect(&$box)
    {
        $cn = 0;
        $rs = null;
        while ($cn++ < 3 && empty($rs)) { 
            $my = $box->fetch();
            if (empty($my)) {
                continue;
            }

            $wr = error_reporting();
            $my['host'] = (empty($this->option['persist']) ? '' : 'p:') . $my['host'];
            error_reporting($wr - E_WARNING);
            foreach (array(10, 100, 1000) AS $us) {
                $is = mysqli_init();
                $is->options(MYSQLI_OPT_CONNECT_TIMEOUT, $this->option['timeout']);
                $rs = $is->real_connect($my['host'],$my['user'], $my['pass'], $this->option['dbname'], $my['port']);
                $this->error    = $is->connect_error;
                if (false !== $rs) {
                    break;
                }

                $is->kill($is->thread_id);
                $is->close();
                usleep($us * 1000);
            }
            error_reporting($wr);

            $my['pass'] = '**';
            if (empty($rs)) {
                $this->log->warn('CONNECT_ERROR', $my + array(
                    'error'     => $is->connect_error,
                ));
                $box->setOffline();
            } else {
                $this->log->debug('CONNECT_OK', $my);
                $this->handle   = $is;
                $this->handle->set_charset($this->option['charset']);
                $this->handle->autocommit(true);
            }
        }
    }
    /* }}} */

    /* {{{ private Mixture runsql() */
    /**
     * 执行query
     *
     * @access public
     * @return Mixture
     */
    private function runsql($query, $async = false)
    {
        $query  = self::sqlclean($query);
        $modify = self::ismodify($query);
        if ($modify) {
            $this->connectToMaster();
        } else {
            $this->connectToSlave();
        }

        if (empty($this->handle)) {
            throw new \Myfox\Lib\Exception(
                $this->error ? $this->error : 'Mysql connect failed.'
            );
        }

        $rs = $this->handle->query($query, $async ? MYSQLI_ASYNC : null);
        if ($rs && !($rs instanceof \mysqli_result)) {
            $rs = $this->handle->affected_rows;
        }
        $this->error    = $this->handle->error;

        if (false !== $rs) {
            $this->log->debug('QUERY_OK', array(
                'sql'   => $query,
                'async' => (bool)$async,
            ));
        } else {
            $this->log->warn('QUERY_ERROR', array(
                'sql'   => $query,
                'async' => (bool)$async,
                'error' => $this->error,
            ));
        }

        return $rs;
    }
    /* }}} */

    /* {{{ private static string normalize() */
    /**
     * 名字归一化
     *
     * @access private static
     * @return string
     */
    private static function normalize($name)
    {
        return strtolower(preg_replace('/\s+/', '', $name));
    }
    /* }}} */

    /* {{{ private static string sqlclean() */
    /**
     * SQL清洗
     *
     * @access private static
     * @return string
     */
    private static function sqlclean($query)
    {
        return trim(preg_replace('/\s{2,}/', ' ', $query), "; \t\r\n");
    }
    /* }}} */

    /* {{{ private static Boolean ismodify() */
    /**
     * 是否UPDATE语句
     *
     * @access private static
     * @return Boolean true or false
     */
    private static function ismodify($query)
    {
        return preg_match(self::NOMANIPULATE, trim($query)) ? false : true;
    }
    /* }}} */

    /* {{{ private static Mixture fetchFromResult() */
    /**
     * 从MySQLi_Result中获取结果
     *
     * @access private static
     * @return Mixture
     */
    private static function fetchFromResult($rs, $cn = 0)
    {
        $rt = array();
        $sz = 0;
        $rs->data_seek(0);
        while (($cn <= 0 || $sz++ < $cn) && $row = $rs->fetch_assoc()) {
            $rt[] = $row;
        }
        $rs->free();

        return $rt;
    }
    /* }}} */

}
