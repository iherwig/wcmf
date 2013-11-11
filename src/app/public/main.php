<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
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
 *
 * $Id$
 */
error_reporting(E_ALL);

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
  Log::debug(memory_get_peak_usage(), 'main');
}
?>