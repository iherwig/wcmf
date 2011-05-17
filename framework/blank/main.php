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
require_once(WCMF_BASE."wcmf/lib/core/AutoLoader.php");
require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");
require_once(WCMF_BASE."wcmf/lib/util/class.Message.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.Request.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.Application.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.ActionMapper.php");
require_once(WCMF_BASE."wcmf/lib/util/class.SearchUtil.php");

try {
  // initialize the application
  $application = Application::getInstance();
  $request = $application->initialize();

  // process the requested action
  $result = ActionMapper::processAction($request);

  // store the last successful request
  SessionData::getInstance()->set('lastRequest', $request);

  register_shutdown_function('shutdown');
  exit;
}
catch (Exception $ex) {
  handleException($ex);
}

/**
 * Global exception handling function.
 * @param exception The exception
 */
function handleException(Exception $exception)
{
  global $request;
  static $numCalled = 0;

  if ($exception instanceof ApplicationException)
  {
    $error = $exception->getError();
    if ($error->getCode() == 'SESSION_INVALID') {
      $request = $exception->getRequest();
      $request->setAction('logout');
      $request->addError($error);
      ActionMapper::processAction($request);
    }
  }

  Log::error( $exception->getMessage()."\n".Application::getStackTrace(), 'main');

  // rollback current transaction
  $persistenceFacade = PersistenceFacade::getInstance();
  $persistenceFacade->getTransaction()->rollback();

  // prevent recursive calls
  $numCalled++;
  if ($numCalled == 2) {
    $request->setAction('fatal');
    $request->addError(ApplicationError::get('GENERAL_FATAL',
          array('exception' => $exception)));
    ActionMapper::processAction($request);
  }
  else if ($numCalled == 3)
  {
    $message = $exception->getMessage();
    // make sure that no error can happen in this stage
    if ($responseFormat == MSG_FORMAT_JSON)
      print JSONUtil::encode(array('success' => false, 'errorMessage' => $message));
    else
      Log::fatal($message, 'main');
  }
  else
  {
    // process last successful request
    $lastRequest = SessionData::getInstance()->get('lastRequest');
    if ($lastRequest) {
      ActionMapper::processAction($lastRequest);
    }
    else {
      print $exception;
    }
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