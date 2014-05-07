<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 */
error_reporting(E_ALL);
$startTime = microtime(true);

require_once("base_dir.php");
require_once(WCMF_BASE."wcmf/lib/core/ClassLoader.php");

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