<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation\impl;

use wcmf\lib\config\ActionKey;
use wcmf\lib\config\Configuration;
use wcmf\lib\config\impl\ConfigActionKeyProvider;
use wcmf\lib\core\EventManager;
use wcmf\lib\core\LogTrait;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\core\Session;
use wcmf\lib\presentation\ActionMapper;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationEvent;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\format\Formatter;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\security\PermissionManager;
use wcmf\lib\security\principal\impl\AnonymousUser;

/**
 * Default ActionMapper implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultActionMapper implements ActionMapper {
  use LogTrait;

  private Session $session;
  private PermissionManager $permissionManager;
  private EventManager $eventManager;
  private Formatter $formatter;
  private Configuration $configuration;

  private bool $isFinished = false;

  /**
   * Constructor
   * @param Session $session
   * @param PermissionManager $permissionManager
   * @param EventManager $eventManager
   * @param Formatter $formatter
   * @param Configuration $configuration
   */
  public function __construct(Session $session,
          PermissionManager $permissionManager,
          EventManager $eventManager,
          Formatter $formatter,
          Configuration $configuration) {
    $this->session = $session;
    $this->permissionManager = $permissionManager;
    $this->eventManager = $eventManager;
    $this->formatter = $formatter;
    $this->configuration = $configuration;
  }

  /**
   * @see ActionMapper::processAction()
   */
  public function processAction(Request $request, Response $response): void {
    $isDebugEnabled = self::logger()->isDebugEnabled();

    $this->eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_ROUTE_ACTION, $request));
    $actionKeyProvider = new ConfigActionKeyProvider($this->configuration, 'actionmapping');

    $referrer = $request->getSender();
    $context = $request->getContext();
    $action = $request->getAction();
    $response->setSender($referrer);
    $response->setContext($context);
    $response->setAction($action);
    $response->setFormat($request->getResponseFormat());

    // check authorization for controller/context/action triple
    if (!$this->permissionManager->authorize($referrer, $context, $action)) {
      $authUserLogin = $this->session->getAuthUser();
      if ($authUserLogin == AnonymousUser::USER_GROUP_NAME) {
        self::logger()->debug("Session invalid. The request was: ".$request->__toString());
        throw new ApplicationException($request, $response, ApplicationError::get('SESSION_INVALID'));
      }
      else {
        self::logger()->debug("Authorization failed for '".$referrer.'?'.$context.'?'.$action."' user '".$authUserLogin."'");
        throw new ApplicationException($request, $response, ApplicationError::get('PERMISSION_DENIED'));
      }
    }

    // get best matching action key from inifile
    $actionKey = ActionKey::getBestMatch($actionKeyProvider, $referrer, $context, $action);

    if ($isDebugEnabled) {
      self::logger()->debug($referrer."?".$context."?".$action.' -> '.$actionKey);
    }

    if (strlen($actionKey) == 0) {
      // return, if action key is not defined
      return;
    }

    // get next controller
    $controllerClass = null;
    $controllerDef = $this->configuration->getValue($actionKey, 'actionmapping');
    if (strlen($controllerDef) == 0) {
      self::logger()->error("No controller found for best action key ".$actionKey.". Request was $referrer?$context?$action");
      throw new ApplicationException($request, $response, ApplicationError::get('ACTION_INVALID'));
    }

    // check if the controller definition contains a method besides the class name
    $controllerMethod = null;
    if (strpos($controllerDef, '::') !== false) {
      list($controllerClass, $controllerMethod) = explode('::', $controllerDef);
    }
    else {
      $controllerClass = $controllerDef;
    }

    // instantiate controller
    $controllerObj = ObjectFactory::getInstanceOf($controllerClass);

    // everything is right in place, start processing
    if ($isDebugEnabled) {
      self::logger()->debug("Request: ".$request->__toString());
    }
    $this->formatter->deserialize($request);

    // execute controller
    if ($isDebugEnabled) {
      self::logger()->debug("Execute ".$controllerClass." with request: ".$request->__toString());
    }
    $response->setSender($controllerClass);
    $this->eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::BEFORE_EXECUTE_CONTROLLER, $request, $response, $controllerObj));
    $controllerObj->execute($request, $response, $controllerMethod);
    $this->eventManager->dispatch(ApplicationEvent::NAME, new ApplicationEvent(
            ApplicationEvent::AFTER_EXECUTE_CONTROLLER, $request, $response, $controllerObj));

    // return if we are finished
    if ($this->isFinished) {
      $this->formatter->serialize($response);
      return;
    }

    // check if an action key exists for the return action
    $nextActionKey = ActionKey::getBestMatch($actionKeyProvider, $controllerClass,
            $response->getContext(), $response->getAction());
    if ($isDebugEnabled) {
      self::logger()->debug("Next action key: ".$nextActionKey);
    }

    // terminate
    // - if there is no next action key or
    // - if the next action key is the same as the previous one (to prevent recursion)
    $terminate = strlen($nextActionKey) == 0 || $actionKey == $nextActionKey;
    if ($terminate) {
      if ($isDebugEnabled) {
        self::logger()->debug("Terminating with response format: ".$response->getFormat());
      }
      // stop processing
      $this->formatter->serialize($response);
      $this->isFinished = true;
      return;
    }

    // proceed with next action key
    if ($isDebugEnabled) {
      self::logger()->debug("Processing next action");
    }

    // set the request based on the result
    $nextRequest = ObjectFactory::getNewInstance('request');
    $nextRequest->setSender($controllerClass);
    $nextRequest->setContext($response->getContext());
    $nextRequest->setAction($response->getAction());
    $nextRequest->setFormat($response->getFormat());
    $nextRequest->setValues($response->getValues());
    $nextRequest->setErrors($response->getErrors());
    $nextRequest->setResponseFormat($request->getResponseFormat());
    $this->processAction($nextRequest, $response);
  }
}
?>
