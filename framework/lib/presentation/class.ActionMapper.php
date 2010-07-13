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
require_once(BASE."wcmf/lib/util/class.Log.php");
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/util/class.SessionData.php");
require_once(BASE."wcmf/lib/util/class.ObjectFactory.php");
require_once(BASE."wcmf/lib/presentation/class.Request.php");
require_once(BASE."wcmf/lib/presentation/class.Response.php");
require_once(BASE."wcmf/lib/presentation/class.WCMFInifileParser.php");
require_once(BASE."wcmf/lib/presentation/format/class.Formatter.php");
require_once(BASE."wcmf/lib/security/class.RightsManager.php");
require_once(BASE."wcmf/3rdparty/Bs_StopWatch.class.php");

/**
 * @class ActionMapper
 * @ingroup Presentation
 * @brief ActionMapper is the central class in our implementation of the mvc pattern.
 * It calls the different Controllers based on the referring Controller and the given action.
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
class ActionMapper
{
  private static $_instance = null;
  private $_controllerDelegate = null;
  private $_lastControllers = array();

  private function __construct() {}

  /**
   * Returns an instance of the class.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance()
  {
    if (!isset(self::$_instance)) {
      self::$_instance = new ActionMapper();
    }
    return self::$_instance;
  }
  /**
   * Process an action depending on a given referrer. The ActionMapper will instantiate the required Controller class
   * as configured in the iniFile and delegates the request to it.
   * @note This method is static so that it can be used without an instance. (This is necessary to call it in onError() which
   * cannot be a class method because php's set_error_handler() does not allow this).
   * @param request A reference to a Request instance
   * @return A reference to an Response instance or null on error.
   */
  public static function processAction($request)
  {
    // allow static call
    $actionMapper = ActionMapper::getInstance();

    $referrer = $request->getSender();
    $context = $request->getContext();
    $action = $request->getAction();

    // this array stores all controllers executed since the last view displayed (the last call of main.php)

    // store last controller
    array_push($actionMapper->_lastControllers, $referrer);

    $parser = WCMFInifileParser::getInstance();
    $rightsManager = RightsManager::getInstance();

    $logExecutionTime = $parser->getValue('logExecuteTime', 'cms');

    // check authorization for controller/context/action triple
    if (!$rightsManager->authorize($referrer, $context, $action))
    {
      $authUser = $rightsManager->getAuthUser();
      if (!$authUser)
      {
        Log::error("The request was: ".$request->toString(), __CLASS__);
        throw new ApplicationException($request, null, Message::get("Authorization failed: no valid user. Maybe your session has expired."));
      }
      else
      {
        $login = $authUser->getName();
        Log::error("Authorization failed for '".$actionKey."' user '".$login."'", __CLASS__);
        throw new ApplicationException($request, null, (Message::get("You don't have the permission to perform this action.")));
      }
    }

    // get best matching action key from inifile
    $actionKey = $parser->getBestActionKey('actionmapping', $referrer, $context, $action);

    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug($referrer."?".$context."?".$action.' -> '.$actionKey, __CLASS__);
    }

    if (strlen($actionKey) == 0)
    {
      // re-execute the initial referrer
      $controllerClass = $actionMapper->_lastControllers[0];
      Log::warn("No actionkey found for ".$referrer."?".$context."?".$action.". Executing ".$controllerClass." ...", __CLASS__);
    }
    else
    {
      // get next controller
      if (($controllerClass = $parser->getValue($actionKey, 'actionmapping')) === false) {
        throw new ConfigurationsException($parser->getErrorMsg());
      }
    }
    if (strlen($controllerClass) == 0) {
      throw new ApplicationException($request, null, "No controller found for best action key ".$actionKey.". Request was $referrer?$context?$action");
    }

    // create controller delegate instance if configured
    if ($actionMapper->_controllerDelegate == null)
    {
      if ($parser->getValue('ControllerDelegate', 'implementation') !== false)
      {
        $objectFactory = ObjectFactory::getInstance();
        $actionMapper->_controllerDelegate = $objectFactory->createInstanceFromConfig('implementation', 'ControllerDelegate');
      }
    }

    // instantiate controller
    if (($classFile = $parser->getValue($controllerClass, 'classmapping')) === false) {
      throw new ConfigurationsException($parser->getErrorMsg());
    }
    if (file_exists(BASE.$classFile))
    {
      require_once(BASE.$classFile);
      $controllerObj = new $controllerClass($actionMapper->_controllerDelegate);
    }
    else {
      throw new ConfigurationsException("Definition of Controller ".$controllerClass." in '".$classFile."' not found.");
    }

    // everything is right in place, start processing
    Formatter::deserialize($request);

    // create the response
    $response = new Response($controllerClass, $context, $action, array());
    $response->setFormat($request->getResponseFormat());

    // initialize controller
    $controllerObj->initialize($request, $response);

    // execute controller
    if ($logExecutionTime)
    {
      $stopWatch = new Bs_StopWatch();
      $stopWatch->reset();
    }
    $result = $controllerObj->execute();

    if ($logExecutionTime && Log::isDebugEnabled(__CLASS__)) {
      Log::debug($controllerClass." execution time: ".$stopWatch->getTime()." ms", __CLASS__);
    }
    if ($result === false)
    {
      // stop processing
      return $response;
    }
    else if ($result === true)
    {
      // proceed based on the result
      $nextRequest = new Request($controllerClass, $response->getContext(), $response->getAction(), $response->getData());
      $nextRequest->setResponseFormat($request->getResponseFormat());
      $response = ActionMapper::processAction($nextRequest);
    }
    else {
      throw new ErrorException("Controller::execute must return true or false. Executed controller was ".$controllerClass.".");
    }
    return $response;
  }
  /**
   * Reset the state of ActionMapper to initial. Especially clears the processed controller queue.
   */
  public static function reset()
  {
    // allow static call
    $actionMapper = ActionMapper::getInstance();
    $actionMapper->_lastControllers = array();
  }
}
?>
