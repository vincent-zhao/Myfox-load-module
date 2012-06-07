<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

use \Myfox\App\Bucket;

require_once(__DIR__ . '/../../lib/TestShell.php');

class BucketTest extends \Myfox\Lib\TestShell
{

    /* {{{ public void test_should_bucket_allot_works_fine() */
    public function test_should_bucket_allot_works_fine()
    {
        $bucket	= new Bucket(10, 0.2);
        $bucket->push('aa', 0);
        $cargos = array(
            array('id'    => 1, 'count' => 12),
            array('id'    => 2, 'count' => 13),
            array('id'    => 3, 'count' => 11),
            array('id'    => 4, 'count' => 1),
            array('id'    => 5, 'count' => 1),
        );
        foreach ($cargos AS $row) {
            $bucket->push($row, $row['count']);
        }

        $this->assertEquals(
            array(
                array(
                    array(
                        'size'  => 12,
                        'data'  => $cargos[0],
                    ),
                ),
                array(
                    array(
                        'size'  => 11,
                        'data'  => $cargos[2],
                    ),
                    array(
                        'size'  => 1,
                        'data'  => $cargos[3],
                    ),
                ),
                array(
                    array(
                        'size'  => 10,
                        'data'  => $cargos[1],
                    ),
                    array(
                        'size'  => 1,
                        'data'  => $cargos[4],
                    ),
                ),
                array(
                    array(
                        'size'  => 3,
                        'data'  => $cargos[1],
                    ),
                ),
            ),
            $bucket->allot()
        );
    }
    /* }}} */

    /* {{{ public void test_should_zero_cubage_works_fine() */
    /**
     * XXX : THIS IS A BUG CASE
     */
    public function test_should_zero_cubage_works_fine()
    {
        $bucket	= new Bucket(0, 0.2);
        $bucket->push('aa', 0);
        $cargos = array(
            array('id'    => 1, 'count' => 2410000),
        );
        foreach ($cargos AS $row) {
            $bucket->push($row, $row['count']);
        }

        $this->assertEquals(
            array(
                array(
                    array(
                        'size'  => 2000000,
                        'data'  => $cargos[0],
                    ),
                ),
                array(
                    array(
                        'size'  => 410000,
                        'data'  => $cargos[0],
                    ),
                ),
            ),
            $bucket->allot()
        );
    }
    /* }}} */

}

