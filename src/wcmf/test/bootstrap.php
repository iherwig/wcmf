<?php
define('WCMF_BASE', realpath(dirname(__FILE__).'/../..').'/');
require_once(dirname(WCMF_BASE)."/vendor/autoload.php");

use wcmf\lib\core\ClassLoader;
use wcmf\lib\io\FileUtil;
use wcmf\lib\util\TestUtil;
new ClassLoader(WCMF_BASE);

setup();
TestUtil::startServer(WCMF_BASE.'app/public');
register_shutdown_function("cleanup");

/**
 * Set up test resources
 */
function setup() {
  @unlink(WCMF_BASE.'app/test-db.sq3');
  $fileUtil = new FileUtil();
  $fileUtil->emptyDir('log');
  $fileUtil->emptyDir(WCMF_BASE.'app/cache');
  $fileUtil->emptyDir(WCMF_BASE.'app/log');
  $fileUtil->emptyDir(WCMF_BASE.'app/searchIndex');
  $fileUtil->copyRecDir('resources/app/', WCMF_BASE.'app/');
}

/**
 * Clean up test resources
 */
function cleanup() {
}
?>
