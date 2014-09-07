<?php
define('WCMF_BASE', realpath(dirname(__FILE__).'/../..').'/');
require_once(dirname(WCMF_BASE)."/vendor/autoload.php");

use wcmf\lib\core\ClassLoader;
use wcmf\lib\io\FileUtil;
new ClassLoader();

// refresh resources
@unlink(WCMF_BASE.'app/test-db.sq3');
FileUtil::copyRecDir('resources/app/', WCMF_BASE.'app/');

// start the built-in web server
define('SERVER_HOST', 'localhost');
define('SERVER_PORT', 8500);
$cmd = sprintf('php -S %s:%d -t %s', SERVER_HOST, SERVER_PORT, WCMF_BASE.'app/public');
$resource = startProcess($cmd);

// kill the web server when the process ends
register_shutdown_function(function() use ($resource) {
  endProcess($resource);
});

function startProcess($cmd) {
  $descriptorspec = array(
    0 => array('pipe', 'r'), // stdin
    1 => array('pipe', 'w'), // stdout
    2 => array('pipe', 'a') // stderr
  );
  $pipes = null;
  if (isWindows()) {
    $resource = proc_open("start /B ".$cmd, $descriptorspec, $pipes);
  }
  else {
    $resource = proc_open("nohup ".$cmd, $descriptorspec, $pipes);
  }
  if (!is_resource($resource)) {
    exit("Failed to execute ".$cmd);
  }
  return $resource;
}

function endProcess($resource) {
  $status = proc_get_status($resource);
  $pid = $status['pid'];
  if (isWindows()) {
    $output = array_filter(explode(" ", shell_exec("wmic process get parentprocessid,processid | find \"$pid\"")));
    array_pop($output);
    $pid = end($output);
    exec("taskkill /F /T /PID $pid");
  }
  else {
    $pid = $pid+1;
    exec("kill -9 $pid");
  }
}

function isWindows() {
  return (substr(php_uname(), 0, 7) == "Windows");
}
?>
