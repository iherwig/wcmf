<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
error_reporting(E_ALL | E_PARSE);

define('WCMF_BASE', realpath(dirname(__FILE__).'/../..').'/');
require_once(dirname(WCMF_BASE)."/vendor/autoload.php");

use \Exception;
use wcmf\lib\presentation\Application;

$application = new Application();
try {
  // initialize the application
  $request = $application->initialize(WCMF_BASE.'app/config/', 'config.ini', '', '', 'cms');

  // run the application
  $application->run($request);
}
catch (Exception $ex) {
  try {
    $application->handleException($ex, isset($request) ? $request : null);
  }
  catch (Exception $unhandledEx) {
    $error = "An unhandled exception occured. Please see log file for details.";
  }
}
?>