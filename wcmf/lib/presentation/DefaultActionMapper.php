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

use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Action;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\presentation\format\Formatter;

/**
 * Default ActionMapper implementation.
 *
 * @author   ingo herwig <ingo@wemove.com>
 */
class DefaultActionMapper implements ActionMapper {

  private $_lastControllers = array();

  /**
   * @see ActionMapper::processAction()
   */
  public function processAction(Request $request) {
    // allow static call
    $eventManager = ObjectFactory::getInstance('eventManager');

    $eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_ROUTE_ACTION, $request));

    $referrer = $request->getSender();
    $context = $request->getContext();
    $action = $request->getAction();
    $response = new Response($referrer, $context, $action);

    // this array stores all controllers executed since the last view displayed (the last call of main.php)

    // store last controller
    $this->_lastControllers[] = $referrer;

    $config = ObjectFactory::getConfigurationInstance();
    $permissionManager = ObjectFactory::getInstance('permissionManager');

    // check authorization for controller/context/action triple
    if (!$permissionManager->authorize($referrer, $context, $action)) {
      $authUser = $permissionManager->getAuthUser();
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
      $controllerClass = $this->_lastControllers[0];
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
    $eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_INITIALIZE_CONTROLLER, $request, $response, $controllerObj));
    $controllerObj->initialize($request, $response);

    // execute controller
    $eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_EXECUTE_CONTROLLER, $request, $response, $controllerObj));
    $result = $controllerObj->execute();
    $eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
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
      $response = $this->processAction($nextRequest);
    }
    else {
      throw new ErrorException("Controller::execute must return true or false. Executed controller was ".$controllerClass.".");
    }
    return $response;
  }

  /**
   * @see ActionMapper::reset()
   */
  public function reset() {
    $this->_lastControllers = array();
  }
}
?>
