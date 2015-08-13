<?php
/**
 * Router script for test server
 */
error_reporting(E_ALL | E_PARSE);
define('WCMF_BASE', realpath(dirname(__FILE__).'/../..').'/');

use wcmf\lib\core\ClassLoader;
use wcmf\lib\presentation\Application;
use wcmf\lib\util\TestUtil;

if (is_file(WCMF_BASE.$_SERVER["REQUEST_URI"])) {
  // serve the requested resource as-is.
  return false;
}
else {
  require_once(dirname(WCMF_BASE)."/vendor/autoload.php");
  new ClassLoader(WCMF_BASE);

  TestUtil::initFramework(WCMF_BASE.'app/config/');

  // create the application
  $application = new Application();
  try {
    // initialize the application
    $request = $application->initialize('', '', 'cms');

    // run the application
    $application->run($request);
  }
  catch (Exception $ex) {
    try {
      $application->handleException($ex, isset($request) ? $request : null);
    }
    catch (Exception $unhandledEx) {
      echo("An unhandled exception occured. Please see log file for details.");
    }
  }
}
?>