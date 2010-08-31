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
error_reporting(E_ALL | E_PARSE);

require_once("base_dir.php");
require_once(BASE."wcmf/lib/core/AutoLoader.php");
require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/presentation/class.Request.php");
require_once(BASE."wcmf/lib/presentation/class.Application.php");
require_once(BASE."wcmf/lib/presentation/class.ActionMapper.php");
require_once(BASE."wcmf/lib/util/class.SearchUtil.php");

// initialize the application
$application = Application::getInstance();
$callParams = $application->initialize();

// process the requested action (we don't use the result here)
$request = new Request(
  $callParams['controller'],
  $callParams['context'],
  $callParams['action'],
  $callParams['data']
);
$request->setFormat($callParams['requestFormat']);
$request->setResponseFormat($callParams['responseFormat']);
$result = ActionMapper::processAction($request);

register_shutdown_function('shutdown');
exit;

/**
 * Global error handling function. Assigned to EXCEPTION_HANDLER
 * which means it is called by WCMFException::throwEx()
 * @param message The error message
 * @param file The php file in which the error occured (optional)
 * @param line The line in the php file in which the error occured (optional)
 * @return The value
 */
function onError($message, $file='', $line='')
{
  global $controller, $context, $action, $data, $responseFormat;
  static $numCalled = 0;

  $data['errorMsg'] = $message;
  Log::error($message."\n".Application::getStackTrace(), 'main');

  // rollback current transaction
  $persistenceFacade = PersistenceFacade::getInstance();
  $persistenceFacade->rollbackTransaction();

  // prevent recursive calls
  $numCalled++;
  if ($numCalled == 2) {
    $request = new Request('FailureController', '', 'fatal', $data);
    $request->setResponseFormat($responseFormat);
    ActionMapper::processAction($request);
  }
  else if ($numCalled == 3)
  {
    // make sure that no error can happen in this stage
    if ($responseFormat == MSG_FORMAT_JSON)
      print JSONUtil::encode(array('success' => false, 'errorMsg' => $message));
    else
      Log::fatal($message, 'main');
  }
  else
  {
    // get old controller/context/action triple to restore application status
    $controller = Application::getCallParameter('old_controller', $controller);
    $context = Application::getCallParameter('old_context', $context);
    $action = Application::getCallParameter('old_usr_action', $action);
    $responseFormat = Application::getCallParameter('old_response_format', $responseFormat);

    // process old action
    $request = new Request($controller, $context, $action, $data);
    $request->setResponseFormat($responseFormat);
    ActionMapper::processAction($request);
  }
  exit;
}

function shutdown()
{
  SearchUtil::commitIndex();

  $error = error_get_last();
  if ($error !== NULL) {
    $info = "[SHUTDOWN] file:".$error['file']." | ln:".$error['line']." | msg:".$error['message'] .PHP_EOL;
    //Log::error($info, "main");
  }
  else{
    Log::debug("SHUTDOWN", "main");
  }
}
?>