<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\Lib\Factory;

require_once(__DIR__ . '/../../lib/TestShell.php');

class FactoryTest extends \Myfox\Lib\TestShell
{

    protected function setUp()
    {
        parent::setUp();
        \Myfox\Lib\AutoLoad::register('myfox\\test\\factory', __DIR__ . '/factory');
    }

    /* {{{ protected void tearDown() */
    protected function tearDown()
    {
        Factory::removeAllObject(true);
        parent::tearDown();
    }
    /* }}} */

    /* {{{ public void test_should_get_object_works_fine() */
    public function test_should_get_object_works_fine()
    {
        $object = Factory::getObject('Myfox\\Test\\Factory\\Test', 'hello');
        $this->assertEquals('hello',    $object->a);
        $this->assertEquals('default',  $object->b);
        $object->b  = 'world';

        $this->assertEquals($object, Factory::getObject(
            'Myfox\\Test\\Factory\\Test', 'hello'
        ));
    }
    /* }}} */

}

