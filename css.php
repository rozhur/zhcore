<?php

$dir = __DIR__;

require $dir . '/src/Core.php';

Core::init($dir);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$app = Core::setupApp('classes\App');

$cssWriter = $app->cssWriter();
if (isset($_GET['css'])) $cssWriter->addAll($_GET['css']);

$cssWriter->send();
