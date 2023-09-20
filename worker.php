<?php

$dir = __DIR__;

require $dir . '/src/Core.php';

try
{
    Core::init($dir);
    $worker_connect = new \classes\WorkerConnect();
    $worker_connect->open();
} catch (\Throwable $e) {
    printf($e);
}