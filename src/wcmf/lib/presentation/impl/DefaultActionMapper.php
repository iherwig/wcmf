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
namespace wcmf\lib\presentation\impl;

use wcmf\lib\config\ActionKey;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationEvent;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * Default ActionMapper implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultActionMapper implements ActionMapper {

  private $_lastResponses = array();

  /**
   * @see ActionMapper::processAction()
   */
  public function processAction(Request $request) {
    $isDebugEnabled = Log::isDebugEnabled(__CLASS__);

    // allow static call
    $eventManager = ObjectFactory::getInstance('eventManager');

    $eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_ROUTE_ACTION, $request));

    $referrer = $request->getSender();
    $context = $request->getContext();
    $action = $request->getAction();
    $response = new Response($referrer, $context, '');
    $response->setFormat($request->getResponseFormat());

    $config = ObjectFactory::getConfigurationInstance();
    $permissionManager = ObjectFactory::getInstance('permissionManager');

    // check authorization for controller/context/action triple
    if (!$permissionManager->authorize($referrer, $context, $action)) {
      $authUser = $permissionManager->getAuthUser();
      $login = $authUser->getLogin();
      Log::error("Authorization failed for '".$referrer.'?'.$context.'?'.$action."' user '".$login."'", __CLASS__);
      throw new ApplicationException($request, $response, ApplicationError::get('PERMISSION_DENIED'));
    }

    // get best matching action key from inifile
    $actionKey = ActionKey::getBestMatch('actionmapping', $referrer, $context, $action);

    if ($isDebugEnabled) {
      Log::debug($referrer."?".$context."?".$action.' -> '.$actionKey, __CLASS__);
    }

    $controllerClass = null;
    if (strlen($actionKey) == 0) {
      // return last response, if action key is not defined
      $lastResponse = array_pop($this->_lastResponses);
      return $lastResponse;
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
    if ($isDebugEnabled) {
      Log::debug("Request: ".$request->__toString(), __CLASS__);
    }
    Formatter::deserialize($request);

    // initialize controller
    if ($isDebugEnabled) {
      Log::debug("Execute ".$controllerClass." with request: ".$request->__toString(), __CLASS__);
    }
    $eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_INITIALIZE_CONTROLLER, $request, $response, $controllerObj));
    $controllerObj->initialize($request, $response);

    // execute controller
    $eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_EXECUTE_CONTROLLER, $request, $response, $controllerObj));
    $controllerObj->execute();
    $eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::AFTER_EXECUTE_CONTROLLER, $request, $response, $controllerObj));

    Formatter::serialize($response);

    // check if an action key exists for the return action
    $nextActionKey = ActionKey::getBestMatch('actionmapping', $controllerClass,
            $response->getContext(), $response->getAction());

    if (strlen($nextActionKey) == 0) {
      // stop processing
      return $response;
    }
    else {
      // store last response
      $this->_lastResponses[] = $response;

      // proceed based on the result
      $nextRequest = new Request($controllerClass, $response->getContext(), $response->getAction());
      $nextRequest->setFormat($response->getFormat());
      $nextRequest->setValues($response->getValues());
      $nextRequest->setErrors($response->getErrors());
      $nextRequest->setResponseFormat($request->getResponseFormat());
      $this->processAction($nextRequest);
    }
  }

  /**
   * @see ActionMapper::reset()
   */
  public function reset() {
    $this->_lastResponses = array();
  }
}
?>
