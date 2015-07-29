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
use wcmf\lib\config\impl\ConfigActionKeyProvider;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationEvent;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\presentation\Request;
use wcmf\lib\security\principal\impl\AnonymousUser;

/**
 * Default ActionMapper implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultActionMapper implements ActionMapper {

  private $_lastResponses = array();

  private static $_logger = null;

  /**
   * Constructor
   */
  public function __construct() {
    if (self::$_logger == null) {
      self::$_logger = ObjectFactory::getInstance('logManager')->getLogger(__CLASS__);
    }
  }

  /**
   * @see ActionMapper::processAction()
   */
  public function processAction(Request $request) {
    $isDebugEnabled = self::$_logger->isDebugEnabled();

    $eventManager = ObjectFactory::getInstance('eventManager');
    $eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_ROUTE_ACTION, $request));
    $actionKeyProvider = new ConfigActionKeyProvider();
    $actionKeyProvider->setConfigSection('actionmapping');

    $referrer = $request->getSender();
    $context = $request->getContext();
    $action = $request->getAction();
    $response = ObjectFactory::getInstance('response');
    $response->setSender($referrer);
    $response->setContext($context);
    $response->setFormat($request->getResponseFormat());

    $config = ObjectFactory::getConfigurationInstance();
    $permissionManager = ObjectFactory::getInstance('permissionManager');

    // check authorization for controller/context/action triple
    if (!$permissionManager->authorize($referrer, $context, $action)) {
      $session = ObjectFactory::getInstance('session');
      $authUser = $session->getAuthUser();
      if ($authUser instanceof AnonymousUser) {
        self::$_logger->error("Session invalid. The request was: ".$request->__toString());
        throw new ApplicationException($request, $response, ApplicationError::get('SESSION_INVALID'));
      }
      else {
        $login = $authUser->getLogin();
        self::$_logger->error("Authorization failed for '".$referrer.'?'.$context.'?'.$action."' user '".$login."'");
        throw new ApplicationException($request, $response, ApplicationError::get('PERMISSION_DENIED'));
      }
    }

    // get best matching action key from inifile
    $actionKey = ActionKey::getBestMatch($actionKeyProvider, $referrer, $context, $action);

    if ($isDebugEnabled) {
      self::$_logger->debug($referrer."?".$context."?".$action.' -> '.$actionKey);
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
      self::$_logger->debug("Request: ".$request->__toString());
    }
    Formatter::deserialize($request);

    // initialize controller
    if ($isDebugEnabled) {
      self::$_logger->debug("Execute ".$controllerClass." with request: ".$request->__toString());
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
    $nextActionKey = ActionKey::getBestMatch($actionKeyProvider, $controllerClass,
            $response->getContext(), $response->getAction());
    if ($isDebugEnabled) {
      self::$_logger->debug("Next action key: ".$nextActionKey);
    }

    if (strlen($nextActionKey) == 0) {
      if ($isDebugEnabled) {
        self::$_logger->debug("Terminate");
      }
      // stop processing
      return $response;
    }
    else {
      if ($isDebugEnabled) {
        self::$_logger->debug("Processing next action");
      }
      // store last response
      $this->_lastResponses[] = $response;

      // proceed based on the result
      $nextRequest = ObjectFactory::getInstance('request');
      $nextRequest->setSender($controllerClass);
      $nextRequest->setContext($response->getContext());
      $nextRequest->setAction($response->getAction());
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
