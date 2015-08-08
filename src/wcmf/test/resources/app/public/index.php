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

use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\ClassLoader;
use wcmf\lib\core\impl\Log4phpLogger;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Application;

new ClassLoader(WCMF_BASE);

$configPath = WCMF_BASE.'app/config/';

// setup logging
$logger = new Log4phpLogger('main', $configPath.'log4php.php');
$logManager = new LogManager($logger);
ObjectFactory::registerInstance('logManager', $logManager);

// setup configuration
$config = new InifileConfiguration($configPath);
$config->addConfiguration('config.ini');
ObjectFactory::configure($config);

// create the application
$application = new Application();
try {
  // initialize the application
  $request = $application->initialize('', '', 'cms');

  // run the application
  $application->run($request);
}
catch (\Exception $ex) {
  try {
    $application->handleException($ex, isset($request) ? $request : null);
  }
  catch (\Exception $unhandledEx) {
    $error = "An unhandled exception occured. Please see log file for details.";
  }
}
?>