<?php

$dir = __DIR__;

require $dir . '/src/Core.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

try
{
    Core::init($dir);
    Core::runApp('classes\App');
} catch (\Throwable $e) {
    printf($e);
}