<?php
define('WCMF_BASE', realpath(dirname(__FILE__).'/../..').'/');
require_once(dirname(WCMF_BASE)."/vendor/autoload.php");

use wcmf\lib\core\ClassLoader;
use wcmf\lib\io\FileUtil;
new ClassLoader();

// copy resources
FileUtil::copyRecDir('resources/app/', WCMF_BASE.'app/');

//// starts the built-in web server
//$host = 'localhost';
//$port = 8000;
//$cmd = sprintf('php -S %s:%d -t %s', $host, $port, WCMF_BASE);
//$output = array();
//exec($cmd, $output);
//$pid = (int)$output[0];
//
//echo sprintf('%s - Web server started on %s:%d with PID %d', date('r'),
//        $host, $port, $pid).PHP_EOL;
//
//// kill the web server when the process ends
//register_shutdown_function(function() use ($pid) {
//  echo sprintf('%s - Killing process with ID %d', date('r'), $pid).PHP_EOL;
//  exec('kill '.$pid);
//});

// delay any output from phpunit until the script is finished
// in order to awoid conflicts with session headers
ob_start();
?>
