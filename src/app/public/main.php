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
$startTime = microtime(true);

define('WCMF_BASE', realpath(dirname(__FILE__).'/../..').'/');
require_once(WCMF_BASE."/vendor/autoload.php");

use \Exception;
use wcmf\lib\core\Log;
use wcmf\lib\presentation\Application;

$application = new Application();
try {
  // initialize the application
  $request = $application->initialize();

  // run the application
  $application->run($request);
}
catch (Exception $ex) {
  $application->handleException($ex, $request);
}
if (Log::isDebugEnabled('main')) {
  Log::debug(number_format(memory_get_peak_usage()/(1024*1024), 2)." MB used [".
        $request->getSender()."?".$request->getContext()."?".$request->getAction()."]", 'main');
  Log::debug((microtime(true) - $startTime).' seconds', 'main');
}
?>