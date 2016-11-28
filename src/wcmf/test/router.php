<?php
/**
 * Router script for test server
 */
error_reporting(E_ALL | E_PARSE);
define('WCMF_BASE', realpath(dirname(__FILE__).'/../..').'/');

use wcmf\lib\core\ClassLoader;
use wcmf\lib\presentation\Application;
use wcmf\lib\util\TestUtil;

// remove everything after ? from url
$requestedFile = preg_replace('/\?.*$/', '', $_SERVER["REQUEST_URI"]);
if (is_file(WCMF_BASE.'app/public'.$requestedFile)) {
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
      $application->handleException($ex);
    }
    catch (Exception $unhandledEx) {
      var_export($unhandledEx);
    }
  }
}
?>