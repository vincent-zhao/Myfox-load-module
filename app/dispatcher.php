<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | 请求转发器		    					    							|
// +------------------------------------------------------------------------+
// | Author: aleafs <pengchun@taobao.com>									|
// +------------------------------------------------------------------------+
//
// $Id: dispatcher.php 18 2010-04-13 15:40:37Z zhangxc83 $

namespace Myfox\App;

class Dispatcher
{

    /* {{{ 成员变量 */

    private $url;

    private $log;

    private $prefix;

    private $config;

    private static $timeout    = false;

    /* }}} */

    /* {{{ public static void run() */
    /**
     * 处理器入口
     *
     * @access public static
     * @return void
     */
    public static function run($ini, $url, $post = null)
    {
        $dsp    = new self($ini);
        $dsp->dispach($url, $post);

        if (empty($GLOBALS['__in_debug_tools']) && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
    /* }}} */

    /* {{{ public void shutdownCallBack() */
    /**
     * 请求结束时的回调函数
     *
     * @access public
     * @return void
     */
    public function shutdownCallBack()
    {
        if (true === self::$timeout) {
            $this->log->error('RUN_TIMEOUT', array(
                'url' => $this->url,
            ));
        }
    }
    /* }}} */

    /* {{{ private void __construct() */
    /**
     * 构造函数
     *
     * @access public
     * @param String $ini
     * @return void
     */
    private function __construct($ini)
    {
        require_once __DIR__ . '/application.php';
        Application::init($ini);

        $this->config   = \Myfox\Lib\Config::instance('default');
        $this->prefix   = rtrim($this->config->get('url.prefix', ''), '/');

        $logurl = $this->config->get('log/default');
        if (empty($logurl)) {
            $this->log  = new \Myfox\Lib\BlackHole();
        } else {
            $this->log  = new \Myfox\Lib\Log($logurl);
        }
    }
    /* }}} */

    /* {{{ private void dispach() */
    /**
     * 分发处理
     *
     * @access private
     * @return void
     */
    private function dispach($url, $post = null)
    {
        $this->url  = preg_replace(
            sprintf('/^\/?%s/is', strtr($this->prefix, array('/' => '\\/'))),
            '', $url, 1
        );

        $url    = new \Myfox\Lib\Parser\Url($this->url);
        $module = $url->module();
        if (empty($module)) {
            $ctrl   = sprintf('Myfox\App\Controller');
        } else {
            $ctrl   = sprintf('Myfox\App\Control\%s', ucfirst(strtolower($module)));
        }

        set_time_limit($this->config->get('run.timeout', 30));
        register_shutdown_function(array(&$this, 'shutdownCallBack'));
        try {
            self::$timeout  = true;

            parse_str($post, $post);
            $ctrl   = new $ctrl();
            $ctrl->execute($url->action(), $url->param(), array_map('trim', $post));
            $this->log->debug('REQUEST', array(
                'url'   => $this->url,
                'post'  => $post,
            ));
            self::$timeout  = false;
        } catch (\Exception $e) {
            self::$timeout  = false;
            $this->log->error('EXCEPTION', array(
                'url'   => $this->url,
                'post'  => $post,
                'error' => $e->getMessage(),
            ));
        }
    }
    /* }}} */

}

