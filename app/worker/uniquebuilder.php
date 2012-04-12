<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

namespace Myfox\App\Worker;

use \Myfox\Lib\Mysql;
use \Myfox\Lib\Log;
use \Myfox\App\Setting;
use \Myfox\App\Model\Server;

class Uniquebuilder extends \Myfox\App\Worker
{

    /*{{{ variables*/
    private $mysql;

    private $log;

    private $hosts = array();

    private $routetab = 'route_info';

    private $checkCount = 3;

    protected $option = array(
        'sleep'    => 3600000,
        'interval' => 1000000000000000
//        'interval' => 259200
    );
    /*}}}*/

    /*{{{ __construct()*/
    /**
     * 构造函数
     * @param void
     */
    public function __construct($option = array('m' => 'run'))
    {
        parent::__construct($option);

        $this->mysql = \Myfox\Lib\Mysql::instance('default');

        $this->log = new \Myfox\Lib\Log($this->mysql->option('logurl'));

        $hosts = $this->mysql->getAll($this->mysql->query(sprintf(
            'SELECT host_id,host_name FROM %shost_list',
            $this->mysql->option('prefix')
        )));

        foreach($hosts AS $host){
            $this->hosts[$host['host_id']] = $host['host_name'];
        }
    }
    /*}}}*/

    /*{{{ execute()*/
    /**
     * 执行函数
     * @param {bool} loop 是否循环
     * @return void
     */
    public function execute($loop = true)
    {

        if($this->option['m'] === 'init'){
            $this->init_table_unique();
        }else{
            $this->set_split_unique();
        }

    }
    /*}}}*/

    /*{{{ init_table_unique()*/
    /**
     * 初始化table的uniquekey
     */
    private function init_table_unique()
    {
        $this->log->notice('START INIT',array());
        $tables  = $this->get_table_list();
        if(empty($tables)){
            $this->log->error('GET TABLE LIST FAULT',array());
        };
        $length  = count($tables);
        $counter = 0;
        
        foreach($tables AS $table){
            $counter ++;
            $uniquekeys = array();
            $count      = 0;
            
            $routes = $this->get_route_infos($table['table_name']);
            if(empty($routes)){continue;}

            foreach($routes AS $route){
                $begin = time();
                if(++$count > $this->checkCount){
                    break;
                }
                $keys = $this->get_unique_keys($route);
                if(empty($keys)){
                    $count--;
                    continue;
                }
                $uniquekeys = array_unique(array_merge($uniquekeys,$keys));

                $timeuse = time() - $begin;
                $this->log->notice('INITING TABLE LIST',array(
                    'table_name' => $table['table_name'],
                    'percent'    => ($counter*100 / $length),
                    'route'      => $route,
                    'timeUse'    => $timeuse
                ));
            }

            $result = $this->set_table_unique_keys($table['table_name'],$uniquekeys);
            if(empty($result)){
                $this->log->error('SET TABLE FAULT',array(
                    'table'      => $table['table_name'],
                    'uniquekeys' => $uniquekeys
                ));
            }
        }
    }
    /*}}}*/

    /*{{{ set_split_unique()*/
    /**
     * 计算并设置分片uniquekey
     */
    private function set_split_unique()
    {
        $settings = Setting::get('unique_last_check');
        if(empty($settings)){
            Setting::set('unique_last_check','2000-01-01');
        }
        $this->log->notice('START CHECK',array());

        $routes = $this->get_route_infos();

        $length  = count($routes);
        $counter = 0;

        foreach($routes AS $route){
            $counter ++;
            $begin = time();

            $keys = $this->get_table_unique_keys($route['table_name']);
            $uniquekeys = $this->get_unique_keys($route,$keys);
            if(empty($uniquekeys)){
                continue;
            }

            $timeuse = time() - $begin;
            $this->log->notice('DEAL NEW SPLIT',array(
                'percent' => ($counter*100/$length),
                'route'   => $route,
                'timeUse' => $timeuse
            ));

            $result = $this->set_split_unique_keys($route,$uniquekeys);
            if(empty($result)){
                $this->log->error('SET SPLIT FAULT',array(
                    'route'      => $route,
                    'uniquekeys' => $uniquekeys
                ));
            }
        }
        Setting::set('unique_last_check',date('Y-m-d H:i:s', time()));
    }
    /*}}}*/

    /*{{{ interval()*/
    /**
     * 运行execute运行间隔时间
     * @param void
     * @return {int} 间隔时间
     */
    public function interval()
    {
        return $this->option['sleep'];
    }
    /*}}}*/

    /*{{{ get_route_infos()*/
    /**
     * 获取某条分片的信息
     * @param {String} split 路由分片表名
     * @param {String} table 某张具体表的路由信息
     * @return {Array} 此路由分片表中所有的分片信息
     */
    private function get_route_infos($table = '')
    {
        $sql = '';
        if(empty($table)){
            $sql = sprintf(
                'SELECT autokid,table_name,real_table,hosts_list FROM %s%s 
                WHERE route_flag >= %s AND route_flag < %s AND modtime >= %s',
                $this->mysql->escape($this->mysql->option('prefix')),
                $this->routetab,'300','400',
                strtotime(Setting::get('unique_last_check'))
            );
        }else{
            $sql = sprintf(
                "SELECT autokid,addtime,real_table,hosts_list FROM %s%s 
                WHERE table_name = '%s' AND route_flag >= %s AND route_flag < %s AND addtime > %s",
                $this->mysql->escape($this->mysql->option('prefix')),
                $this->routetab,
                $this->mysql->escape($table),'300','400',
                time() - $this->option['interval']
            );
        }
        return $this->mysql->getAll($this->mysql->query($sql));
    }
    /*}}}*/

    /*{{{ get_table_list()*/
    /**
     * 获得所有表名
     * @param void
     * @return {Array|false} 查询正常返回表名数组，不正常返回false
     */
    private function get_table_list(){
        $result = $this->mysql->getAll($this->mysql->query(sprintf(
            'SELECT table_name FROM %stable_list',
            $this->mysql->escape($this->mysql->option('prefix'))
        )));
        return $result;
    }
    /*}}}*/

    /*{{{ get_unique_keys()*/
    /**
     * 获取某个分片表的unique key
     * @param {Array} route 某路由信息
     * @return {Array} unique key数组
     */
    private function get_unique_keys($route,$keys = array())
    {
        $hosts = preg_split('/,/',$route['hosts_list'],-1,PREG_SPLIT_NO_EMPTY);
        $poped = array_pop($hosts);
        if(substr($poped,-1) === '$'){
            array_push($hosts,substr($poped,0,strlen($poped)-1));
        }

        $mysql;
        $host = (int)array_pop($hosts);
        try{
            $mysql  = Server::instance($this->hosts[$host])->getlink();
        }catch(\Exception $e){
            $this->log->error('GET SERVER FAULT',array(
                'host' => $host
            ));
            return false;
        }

        $sql    = 'SELECT %s FROM '.$this->mysql->escape($route['real_table']);
        $result = array();

        if(empty($keys)){
            $columns = $mysql->getAll($mysql->query(sprintf(
                'DESCRIBE %s',
                $this->mysql->escape($route['real_table'])
            )));
            if(empty($columns)){
                return false;
            }

            $part = '';
            foreach($columns AS $column){
                if(!preg_match('/(int\([1]?[0-9]\))|(varchar\([1-6]?[0-9]\))/i',$column['Type']) || preg_match('/(num$)|(max$)|(min$)|(pv$)|(uv$)|(pv$)|(uv$)/i',$column['Field'])){
                    continue;
                }

                $part = $part.sprintf(
                    'COUNT(DISTINCT(%s)) AS %s,',
                    $this->mysql->escape($column['Field']),
                    $this->mysql->escape($column['Field'])
                );
            }
            $part = $part.'COUNT(*) AS total';

            $get  = $mysql->getAll($mysql->query(sprintf($sql,$part)));
            foreach($columns AS $column){
                if(empty($get[0][$column['Field']])){
                    continue;
                }

                if($get[0][$column['Field']] >= $get[0]['total']*0.8){
                    array_push($result,$column['Field']);
                }
            }
        }else{
            $part = '';
            foreach($keys AS $key){
                $part = $part.sprintf(
                    'COUNT(DISTINCT(%s)) AS %s,',
                    $this->mysql->escape($key),
                    $this->mysql->escape($key)
                );
            }
            $part = $part.'COUNT(*) AS total';

            $get  = $mysql->getAll($mysql->query(sprintf($sql,$part)));
            foreach($keys AS $key){
                if($get[0][$key] >= $get[0]['total']*0.8){
                    array_push($result,$key);
                }
            }
        }

        return $result;
    }
    /*}}}*/

    /*{{{ get_table_unique_keys()*/
    /**
     * 获得某张表的uniquekeys
     * @param {String} table 表名
     * @return {Array|bool} 正常返回表uniquekeys，数据库数据查询失败返回false
     */
    private function get_table_unique_keys($table){
        $result = $this->mysql->getAll($this->mysql->query(sprintf(
            "SELECT unique_key FROM %stable_list WHERE table_name = '%s'",
            $this->mysql->escape($this->mysql->option('prefix')),
            $this->mysql->escape($table)
        )));
        if(empty($result)){return $result;}

        $keystring    = $result[0]['unique_key'];
        $uniquekeys   = array();
        if(strlen($keystring) !== 0){
            $uniquekeys = preg_split('/;/',$keystring,-1,PREG_SPLIT_NO_EMPTY);
            array_push($uniquekeys,(substr(array_pop($uniquekeys),0,-1)));
        }

        return $uniquekeys;
    }
    /*}}}*/

    /*{{{ set_split_unique_keys()*/
    /**
     * 设置每个分片路由的uniquekey值
     * @param {Array} route分片路由信息
     * @param {Array} uniquekeys unique字段们
     * @return void
     */
    private function set_split_unique_keys($route,$uniquekeys)
    {
        array_push($uniquekeys,(array_pop($uniquekeys).'$'));
        $keystring = implode(';',$uniquekeys);
        return $this->mysql->query(sprintf(
            "UPDATE %s%s SET unique_key = '%s' WHERE autokid = %s",
            $this->mysql->escape($this->mysql->option('prefix')),
            $this->routetab,
            $this->mysql->escape($keystring),
            $this->mysql->escape($route['autokid'])
        ));
    }
    /*}}}*/

    /*{{{ set_table_unique_keys()*/
    /**
     * 设置表的uniqueKey值
     * @param {String} tableName 表名
     * @param {Array} uniqueKeys uniqueKey数组
     * @return void
     */
    private function set_table_unique_keys($tableName,$uniqueKeys)
    {
        if(empty($uniqueKeys)){return;}
        array_push($uniqueKeys,(array_pop($uniqueKeys).'$'));
        $uniqueKeyStr = implode(';',$uniqueKeys);
        $sql = sprintf(
            "UPDATE %stable_list SET unique_key = '%s' WHERE table_name = '%s'",
            $this->mysql->escape($this->mysql->option('prefix')),
            $this->mysql->escape($uniqueKeyStr),
            $this->mysql->escape($tableName)
        );
        return $this->mysql->query($sql);
    }
    /*}}}*/

}
