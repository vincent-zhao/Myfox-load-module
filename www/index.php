<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once(__DIR__ . '/../app/dispatcher.php');

\Myfox\App\Dispatcher::run(
    __DIR__ . '/../etc/myfox.ini',
    isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
    file_get_contents('php://input')
);
