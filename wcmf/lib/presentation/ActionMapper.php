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
namespace wcmf\lib\presentation;

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\core\EventManager;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Action;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\security\RightsManager;

/**
 * ActionMapper is the central class in our implementation of the mvc pattern.
 * It calls the different Controllers based on the referring Controller and the given action.
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
class ActionMapper {

  private static $_instance = null;
  private $_lastControllers = array();

  private function __construct() {}

  /**
   * Returns an instance of the class.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance() {
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
  public static function processAction($request) {
    // allow static call
    $actionMapper = ActionMapper::getInstance();

    EventManager::getInstance()->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_ROUTE_ACTION, $request));

    $referrer = $request->getSender();
    $context = $request->getContext();
    $action = $request->getAction();
    $response = new Response($referrer, $context, $action);

    // this array stores all controllers executed since the last view displayed (the last call of main.php)

    // store last controller
    $actionMapper->_lastControllers[] = $referrer;

    $config = ObjectFactory::getConfigurationInstance();
    $rightsManager = RightsManager::getInstance();

    // check authorization for controller/context/action triple
    if (!$rightsManager->authorize($referrer, $context, $action)) {
      $authUser = $rightsManager->getAuthUser();
      if (!$authUser) {
        Log::error("Session invalid. The request was: ".$request->__toString(), __CLASS__);
        throw new ApplicationException($request, $response, ApplicationError::get('SESSION_INVALID'));
      }
      else {
        $login = $authUser->getName();
        Log::error("Authorization failed for '".$referrer.'?'.$context.'?'.$action."' user '".$login."'", __CLASS__);
        throw new ApplicationException($request, $response, ApplicationError::get('PERMISSION_DENIED'));
      }
    }

    // get best matching action key from inifile
    $actionKey = Action::getBestMatch('actionmapping', $referrer, $context, $action);

    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug($referrer."?".$context."?".$action.' -> '.$actionKey, __CLASS__);
    }

    $controllerClass = null;
    if (strlen($actionKey) == 0) {
      // re-execute the initial referrer
      $controllerClass = $actionMapper->_lastControllers[0];
      Log::warn("No actionkey found for ".$referrer."?".$context."?".$action.". Executing ".$controllerClass." ...", __CLASS__);
    }
    else {
      // get next controller
      $controllerClass = $config->getValue($actionKey, 'actionmapping');
    }
    if (strlen($controllerClass) == 0) {
      throw new ApplicationException($request, $response, "No controller found for best action key ".$actionKey.". Request was $referrer?$context?$action");
    }

    // instantiate controller
    $controllerObj = new $controllerClass();

    // everything is right in place, start processing
    Formatter::deserialize($request);

    // create the response
    $response->setSender($controllerClass);
    $response->setFormat($request->getResponseFormat());

    // initialize controller
    EventManager::getInstance()->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_INITIALIZE_CONTROLLER, $request, $response, $controllerObj));
    $controllerObj->initialize($request, $response);

    // execute controller
    EventManager::getInstance()->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_EXECUTE_CONTROLLER, $request, $response, $controllerObj));
    $result = $controllerObj->execute();
    EventManager::getInstance()->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::AFTER_EXECUTE_CONTROLLER, $request, $response, $controllerObj));

    Formatter::serialize($response);
    if ($result === false) {
      // stop processing
      return $response;
    }
    else if ($result === true) {
      // proceed based on the result
      $nextRequest = new Request($controllerClass, $response->getContext(), $response->getAction());
      $nextRequest->setFormat($response->getFormat());
      $nextRequest->setValues($response->getValues());
      $nextRequest->setErrors($response->getErrors());
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
  public static function reset() {
    // allow static call
    $actionMapper = ActionMapper::getInstance();
    $actionMapper->_lastControllers = array();
  }
}
?>
