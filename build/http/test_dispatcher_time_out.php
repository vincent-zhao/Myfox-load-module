<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

$usleep	= isset($_GET['t']) ? (int)$_GET['t'] : 1000;
usleep(1000 * $usleep);
echo $usleep;
