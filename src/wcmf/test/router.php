<?php
/**
 * Router script for test server
 */
error_reporting(E_ALL | E_PARSE);
require_once('config.php');

use wcmf\lib\core\ClassLoader;
use wcmf\lib\core\impl\MonologFileLogger;
use wcmf\lib\core\LogManager;
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

  $logger = new MonologFileLogger('main', 'router-log.ini');
  LogManager::configure($logger);
  $logger->debug('Requested uri: '.$requestedFile);

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
    file_put_contents(__DIR__."/router-error.txt",
        $ex->getMessage()."\n".$ex->getTraceAsString()."\nRequest:\n".$request->__toString());
    try {
      $application->handleException($ex);
    }
    catch (Exception $unhandledEx) {
      echo "Exception in request to ".$_SERVER["REQUEST_URI"]."\n".
          $unhandledEx->getMessage()."\n".$unhandledEx->getTraceAsString()."\n".
          file_get_contents(WCMF_BASE."app/log/".(new \DateTime())->format('Y-m-d').".log");
    }
  }
}
?>