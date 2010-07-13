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

/**
 * This script is used to run a request from the command line.
 * E.g. RPCClient instances of other wCMF instances use this script
 * to connect to this wCMF instance.  
 * 
 * Usage:
 * /path/to/php rpc_call.php request sid
 * 
 * Parameters:
 * - request A serialized and base64 encoded Request instance
 * - sid A session id [optional]
 */
error_reporting(E_ERROR | E_PARSE);

require_once("base_dir.php");  
require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/presentation/class.Request.php");
require_once(BASE."wcmf/lib/presentation/class.Application.php");
require_once(BASE."wcmf/lib/presentation/class.ActionMapper.php");

$arguments = $_SERVER['argv'];
array_shift($arguments);
$numArguments = sizeof($arguments);

if ($numArguments < 1)
{
  echo <<<END
Usage:
/path/to/php rpc_call.php request sid

Parameters:
- request request A serialized and base64 encoded Request instance
- sid A session id [optional]
END;
}

// if the call has two parameters, the second one is supposed to be
// the session id
if ($numArguments == 2) {
  $_POST['sid'] = $arguments[1];
}

// initialize the remote application
$application = &Application::getInstance();
$application->initialize('include/', 'config.ini', 'LoginController', '', 'login', 'Null');

// process the requested action
$serializedRequest = base64_decode($arguments[0]);
$request = unserialize($serializedRequest);
if ($request) {
  Log::debug("Process remote request:\n".$request->toString(), "cli");

  $response = ActionMapper::processAction($request);
  Log::debug("Response:\n".$response->toString(), "cli");
}
else {
  echo "Error: Invalid request.";
}
?>
