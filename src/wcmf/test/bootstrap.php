<?php
define('WCMF_BASE', realpath(dirname(__FILE__).'/../..').'/');
require_once(dirname(WCMF_BASE)."/vendor/autoload.php");

use wcmf\lib\core\ClassLoader;
use wcmf\lib\io\FileUtil;
use wcmf\test\lib\TestUtil;
new ClassLoader();

// refresh resources
@unlink('log.txt');
FileUtil::emptyDir(WCMF_BASE.'app/log');
FileUtil::emptyDir(WCMF_BASE.'app/searchIndex');
FileUtil::copyRecDir('resources/app/', WCMF_BASE.'app/');

TestUtil::startServer(WCMF_BASE.'app/public');
?>
