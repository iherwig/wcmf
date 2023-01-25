<?php
define('WCMF_BASE', realpath(dirname(__FILE__).'/../../src').'/');
define('TEST_BASE', realpath(dirname(__FILE__).'/..').'/');
define('TEST_SERVER', 'localhost:8500');

if (!class_exists('wcmf\lib\core\ClassLoader')) {
  require_once(dirname(WCMF_BASE).'/vendor/autoload.php');
}

use wcmf\lib\core\ClassLoader;
use wcmf\lib\io\FileUtil;
new ClassLoader(WCMF_BASE);
new ClassLoader(TEST_BASE);

setup();
register_shutdown_function('cleanup');

/**
 * Set up test resources
 */
function setup(): void {
  if (file_exists(WCMF_BASE.'app/test-db.sq3')) {
    unlink(WCMF_BASE.'app/test-db.sq3');
  }
  if (file_exists('router-error.txt')) {
    unlink('router-error.txt');
  }
  $fileUtil = new FileUtil();
  $fileUtil->mkdirRec(WCMF_BASE.'app/public');
  $fileUtil->emptyDir(WCMF_BASE.'app/cache');
  $fileUtil->emptyDir(WCMF_BASE.'app/log');
  $fileUtil->emptyDir(WCMF_BASE.'app/searchIndex');
  $fileUtil->copyRecDir('resources/app/', WCMF_BASE.'app/');
  if (file_exists(WCMF_BASE.'app/public/soap-interface.php')) {
    copy(WCMF_BASE.'app/public/soap-interface.php', 'soap-interface.php');
  }
  else {
    throw new \Exception('Generated application files are missing. Please run `ant` command in tests/model/.');
  }
}

/**
 * Clean up test resources
 */
function cleanup(): void {
  if (file_exists('soap-interface.php')) {
      unlink('soap-interface.php');
  }
}
?>
