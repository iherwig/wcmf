<?php
require_once('config.php');
if (!class_exists('\wcmf\lib\core\ClassLoader')) {
  require_once(dirname(WCMF_BASE)."/vendor/autoload.php");
}

use wcmf\lib\core\ClassLoader;
use wcmf\lib\io\FileUtil;
new ClassLoader(WCMF_BASE);

setup();
register_shutdown_function("cleanup");

/**
 * Set up test resources
 */
function setup() {
  @unlink(WCMF_BASE.'app/test-db.sq3');
  @unlink('router-error.txt');
  $fileUtil = new FileUtil();
  $fileUtil->mkdirRec(WCMF_BASE.'app/public');
  $fileUtil->emptyDir(WCMF_BASE.'app/cache');
  $fileUtil->emptyDir(WCMF_BASE.'app/log');
  $fileUtil->emptyDir(WCMF_BASE.'app/searchIndex');
  $fileUtil->copyRecDir('resources/app/', WCMF_BASE.'app/');
  copy(WCMF_BASE.'app/public/soap-interface.php', 'soap-interface.php');
}

/**
 * Clean up test resources
 */
function cleanup() {
  @unlink('soap-interface.php');
}
?>
