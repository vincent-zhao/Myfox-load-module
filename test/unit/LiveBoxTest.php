<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +--------------------------------------------------------------------+
// | LiveBoxTest.php	    											|
// +--------------------------------------------------------------------+
// | Copyright (c) 2010 Aleafs.com. All Rights Reserved					|
// +--------------------------------------------------------------------+
// | Author: aleafs <zhangxc83@sohu.com>								|
// +--------------------------------------------------------------------+
//
// $Id: LiveBoxTest.php 47 2010-04-26 05:27:46Z zhangxc83 $

use \Myfox\Lib\LiveBox;
require_once(__DIR__ . '/../../lib/TestShell.php');

class LiveBoxTest extends \Myfox\Lib\TestShell
{

    protected function setUp()
    {
        parent::setUp();
        $this->pool = new LiveBox(__CLASS__);
    }

    protected function tearDown()
    {
        if (!empty($this->pool)) {
            $this->pool->cleanAllCache();
            unset($this->pool);
        }

        parent::tearDown();
    }

    /* {{{ public function test_should_right_select_by_random() */
    public function test_should_random_select_works_fine()
    {
        $hosts  = array(
            '127.0.0.1:1234',
            '127.0.0.1:1234',
            '127.0.0.1:1235',
            '127.0.0.1:1236',
            '127.0.0.1:1237',
            '127.0.0.1:1238',
            '127.0.0.1:1239',
        );

        foreach ($hosts AS $host) {
            $this->pool->register($host);
        }
        $this->pool->register('I\m not exists');

        $result = array();
        for ($i = 0; $i < 10000; $i++) {
            $host = $this->pool->fetch();
            if (!preg_match('/^[\d\.]+:\d+$/is', $host)) {         /**<  模拟连接      */
                $this->pool->setOffline();
            }

            if (!isset($result[$host])) {
                $result[$host] = 1;
            } else {
                $result[$host]++;
            }
        }
        $this->assertTrue($result['I\m not exists'] < 5, 'setOffline Doesn\t work.');

        $total  = 10000 / count($hosts);
        $weight = array();
        foreach ($hosts AS $host) {
            $weight[$host]  = isset($weight[$host]) ? $weight[$host] + 1 : 1;
        }

        foreach ($weight AS $host => $wt) {
            $this->assertTrue(
                ($result[$host] >= 0.8 * $wt * $total) && ($result[$host] <= 1.2 * $wt * $total),
                sprintf('Host "%s" random selector error.', $host)
            );
        }

    }
    /* }}} */

    /* {{{ public function test_should_reset_when_all_offline() */
    public function test_should_reset_when_all_offline()
    {
        $this->pool->register('www.baidu.com', 'baidu')
            ->register('www.google.com', 'google')
            ->setOffline('baidu')
            ->setOffline('google');

        $this->assertContains(
            $this->pool->fetch(),
            array(
                'www.baidu.com',
                'www.google.com',
            )
        );
    }
    /* }}} */

    /* {{{ public function test_should_live_time_works_fine() */
    public function test_should_live_time_works_fine()
    {
        if (!function_exists('apc_add')) {
            return;
        }

        $pool = new LiveBox(__CLASS__, 2);
        $pool->register('www.baidu.com', 'www.baidu.com')->setOffline('www.baidu.com');
        unset($pool);

        $pool = new LiveBox(__CLASS__);
        $pool->register('www.baidu.com', 'www.baidu.com')->register('www.google.com');

        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue(
                $pool->fetch() != 'www.baidu.com',
                'Offline host should NOT appear!'
            );
        }
        unset($pool);

        sleep(2);
        $pool = new LiveBox(__CLASS__);
        $pool->register('www.baidu.com', 'www.baidu.com')->register('www.google.com');

        $return = array();
        for ($i = 0; $i < 1000; $i++) {
            $host = $pool->fetch();
            if (!isset($return[$host])) {
                $return[$host] = 1;
            } else {
                $return[$host]++;
            }
        }

        $this->assertTrue(
            400 <= $return['www.baidu.com'] &&
            $return['www.baidu.com'] <= 600
        );
        $this->assertTrue(
            400 <= $return['www.google.com'] &&
            $return['www.google.com'] <= 600
        );
    }
    /* }}} */

}

