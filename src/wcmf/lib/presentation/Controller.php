<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation;

use wcmf\lib\config\Configuration;
use wcmf\lib\core\IllegalArgumentException;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;
use wcmf\lib\security\PermissionManager;

/**
 * Controller is the base class of all controllers.
 *
 * Error Handling:
 * - throw an Exception or use response action _failure_ to signal fatal errors
 *    (calls wcmf::application::controller::FailureController)
 * - add an ApplicationError to the response to signal non fatal errors (e.g.
 *    validation errors)
 *
 * The following default request/response parameters are defined:
 *
 * | Parameter               | Description
 * |-------------------------|-------------------------
 * | _in_ / _out_ `action`   | The action to be executed
 * | _in_ / _out_ `context`  | The context of the action
 * | _in_ `language`         | The language of the requested data (optional)
 * | _out_ `controller`      | The name of the executed controller
 * | _out_ `success`         | Boolean whether the action completed successfully or not (depends on existence of error messages)
 * | _out_ `errorMessage`    | An error message which is displayed to the user
 * | _out_ `errorCode`       | An error code, describing the type of error
 * | _out_ `errorData`       | Some error codes require to transmit further information to the client
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Controller {

  private $_request = null;
  private $_response = null;

  private $_logger = null;
  private $_session = null;
  private $_persistenceFacade = null;
  private $_permissionManager = null;
  private $_actionMapper = null;
  private $_localization = null;
  private $_message = null;
  private $_configuration = null;

  /**
   * Constructor
   * @param $session
   * @param $persistenceFacade
   * @param $permissionManager
   * @param $actionMapper
   * @param $localization
   * @param $message
   * @param $configuration
   */
  public function __construct(Session $session,
          PersistenceFacade $persistenceFacade,
          PermissionManager $permissionManager,
          ActionMapper $actionMapper,
          Localization $localization,
          Message $message,
          Configuration $configuration) {
    $this->_logger = LogManager::getLogger(get_class($this));
    $this->_session = $session;
    $this->_persistenceFacade = $persistenceFacade;
    $this->_permissionManager = $permissionManager;
    $this->_actionMapper = $actionMapper;
    $this->_localization = $localization;
    $this->_message = $message;
    $this->_configuration = $configuration;
  }

  /**
   * Initialize the Controller with request/response data. Which data is required is defined by the Controller.
   * The base class method just stores the parameters in a member variable. Specialized Controllers may overide
   * this behaviour for further initialization.
   * @attention It lies in its responsibility to fail or do some default action if some data is missing.
   * @param $request A reference to the Request sent to the Controller. The sender attribute of the Request is the
   * last controller's name, the context is the current context and the action is the requested one.
   * All data sent from the last controller are accessible using the Request::getValue method. The request is
   * supposed to be read-only. It will not be used any more after beeing passed to the controller.
   * @param $response A reference to the Response that will be modified by the Controller. The initial values for
   * context and action are the same as in the request parameter and are meant to be modified according to the
   * performed action. The sender attribute of the response is set to the current controller. Initially there
   * are no data stored in the response.
   */
  public function initialize(Request $request, Response $response) {
    // set sender on response
    $response->setSender(get_class($this));

    $this->_request = $request;
    $this->_response = $response;
  }

  /**
   * Check if the request is valid.
   * Subclasses will override this method to validate against their special requirements.
   * Besides returning false, validation errors should be indicated by using the
   * Response::addError method.
   * @return Boolean whether the data are ok or not.
   */
  protected function validate() {
    return true;
  }

  /**
   * Execute the Controller resulting in its action processed. The actual
   * processing is delegated to the given method, which must be implemented
   * by concrete Controller subclasses.
   * @param $method The name of the method to execute (optional, defaults to 'doExecute' if not given)
   */
  public function execute($method=null) {
    $method = $method == null ? 'doExecute' : $method;
    $isDebugEnabled = $this->_logger->isDebugEnabled();
    if ($isDebugEnabled) {
      $this->_logger->debug('Executing: '.get_class($this).'::'.$method);
      $this->_logger->debug('Request: '.$this->_request);
    }

    // validate controller data
    $validationFailed = false;
    if (!$this->validate()) {
      $validationFailed = true;
    }

    // execute controller logic
    if (!$validationFailed) {
      if (method_exists($this, $method)) {
        call_user_func(array($this, $method));
      }
      else {
        throw new IllegalArgumentException("The method '".$method."' is not defined in class ".get_class($this));
      }
    }

    // prepare the response
    $this->assignResponseDefaults();
    if ($isDebugEnabled) {
      $this->_logger->debug('Response: '.$this->_response);
    }

    // log errors
    $errors = $this->_response->getErrors();
    for ($i=0,$count=sizeof($errors); $i<$count; $i++) {
      $this->_logger->error($errors[$i]->__toString());
    }
  }

  /**
   * Delegate the current request to another action. The context is the same as
   * the current context and the source controller will be set to this.
   * The request and response format will be NullFormat
   * which means that all request values should be passed in the application internal
   * format and all response values will have that format.
   * @param $action The name of the action to execute
   * @return Response instance
   */
  protected function executeSubAction($action) {
    $curRequest = $this->getRequest();
    $subRequest = ObjectFactory::getInstance('request');
    $subRequest->setSender(get_class($this));
    $subRequest->setContext($curRequest->getContext());
    $subRequest->setAction($action);
    $subRequest->setHeaders($curRequest->getHeaders());
    $subRequest->setValues($curRequest->getValues());
    $subRequest->setFormat('null');
    $subRequest->setResponseFormat('null');
    $subResponse = ObjectFactory::getInstance('response');
    $this->_actionMapper->processAction($subRequest, $subResponse);
    return $subResponse;
  }

  /**
   * Get the Request instance.
   * @return Request
   */
  public function getRequest() {
    return $this->_request;
  }

  /**
   * Get the Response instance.
   * @return Response
   */
  public function getResponse() {
    return $this->_response;
  }

  /**
   * Get the Logger instance.
   * @return Logger
   */
  protected function getLogger() {
    return $this->_logger;
  }

  /**
   * Get the Session instance.
   * @return Session
   */
  protected function getSession() {
    return $this->_session;
  }

  /**
   * Get the PersistenceFacade instance.
   * @return PersistenceFacade
   */
  protected function getPersistenceFacade() {
    return $this->_persistenceFacade;
  }

  /**
   * Get the PermissionManager instance.
   * @return PermissionManager
   */
  protected function getPermissionManager() {
    return $this->_permissionManager;
  }

  /**
   * Get the ActionMapper instance.
   * @return ActionMapper
   */
  protected function getActionMapper() {
    return $this->_actionMapper;
  }

  /**
   * Get the Localization instance.
   * @return Localization
   */
  protected function getLocalization() {
    return $this->_localization;
  }

  /**
   * Get the Message instance.
   * @return Message
   */
  protected function getMessage() {
    return $this->_message;
  }

  /**
   * Get the Configuration instance.
   * @return Configuration
   */
  protected function getConfiguration() {
    return $this->_configuration;
  }

  /**
   * Assign default variables to the response. This method is called after Controller execution.
   * This method may be used by derived controller classes for convenient response setup.
   */
  protected function assignResponseDefaults() {
    // return the last error
    $errors = $this->_response->getErrors();
    if (sizeof($errors) > 0) {
      $error = array_pop($errors);
      $this->_response->setValue('errorCode', $error->getCode());
      $this->_response->setValue('errorMessage', $error->getMessage());
      $this->_response->setValue('errorData', $error->getData());
      $this->_response->setStatus($error->getStatusCode());
      $this->_response->setValue('success', false);
    }
    else {
      $this->_response->setValue('success', true);
    }

    // set wCMF specific values
    $this->_response->setValue('controller', get_class($this));
    $this->_response->setValue('context', $this->_response->getContext());
    $this->_response->setValue('action', $this->_response->getAction());
  }

  /**
   * Check if the current request is localized. This is true,
   * if it has a language parameter that is not equal to Localization::getDefaultLanguage().
   * Throws an exception if a language is given which is not supported
   * @return Boolean whether the request is localized or not
   */
  protected function isLocalizedRequest() {
    if ($this->_request->hasValue('language')) {
      $language = $this->_request->getValue('language');
      if ($language != $this->_localization->getDefaultLanguage()) {
        return true;
      }
    }
    return false;
  }

  /**
   * Checks the language request parameter and adds an response error,
   * if it is not contained in the Localization::getSupportedLanguages() list.
   * @return Boolean
   */
  protected function checkLanguageParameter() {
    if ($this->_request->hasValue('language')) {
      $language = $this->_request->getValue('language');
      if (!in_array($language, array_keys($this->_localization->getSupportedLanguages()))) {
        $this->_response->addError(ApplicationError::get('PARAMETER_INVALID',
                array('invalidParameters' => array('language'))));
        return false;
      }
    }
    return true;
  }
}
?>
