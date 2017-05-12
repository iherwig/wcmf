<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\presentation;

use wcmf\lib\config\Configuration;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\core\Session;
use wcmf\lib\i18n\Localization;
use wcmf\lib\i18n\Message;
use wcmf\lib\persistence\PersistenceFacade;
use wcmf\lib\presentation\ApplicationError;
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
abstract class Controller {

  const CSRF_TOKEN_PARAM = 'csrf_token';

  private $request = null;
  private $response = null;

  private $logger = null;
  private $session = null;
  private $persistenceFacade = null;
  private $permissionManager = null;
  private $actionMapper = null;
  private $localization = null;
  private $message = null;
  private $configuration = null;

  private $startedTransaction = false;

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
    $this->logger = LogManager::getLogger(get_class($this));
    $this->session = $session;
    $this->persistenceFacade = $persistenceFacade;
    $this->permissionManager = $permissionManager;
    $this->actionMapper = $actionMapper;
    $this->localization = $localization;
    $this->message = $message;
    $this->configuration = $configuration;
  }

  /**
   * Initialize the Controller with request/response data. Which data is required is defined by the Controller.
   * The base class method just stores the parameters in a member variable. Specialized Controllers may override
   * this behavior for further initialization.
   * @attention It lies in its responsibility to fail or do some default action if some data is missing.
   * @param $request Request instance sent to the Controller. The sender attribute of the Request is the
   * last controller's name, the context is the current context and the action is the requested one.
   * All data sent from the last controller are accessible using the Request::getValue method. The request is
   * supposed to be read-only. It will not be used any more after being passed to the controller.
   * @param $response Response instance that will be modified by the Controller. The initial values for
   * context and action are the same as in the request parameter and are meant to be modified according to the
   * performed action. The sender attribute of the response is set to the current controller. Initially there
   * are no data stored in the response.
   */
  public function initialize(Request $request, Response $response) {
    // set sender on response
    $response->setSender(get_class($this));

    $this->request = $request;
    $this->response = $response;
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
   * processing is delegated to the doExecute() method.
   * @param $method The name of the method to execute, will be passed to doExecute() (optional)
   */
  public function execute($method=null) {
    $isDebugEnabled = $this->logger->isDebugEnabled();
    if ($isDebugEnabled) {
      $this->logger->debug('Executing: '.get_class($this).($method ? '::'.$method: ''));
      $this->logger->debug('Request: '.$this->request);
    }

    // validate controller data
    $validationFailed = false;
    if (!$this->validate()) {
      $validationFailed = true;
    }

    // execute controller logic
    if (!$validationFailed) {
      try {
        $this->doExecute($method);
        $this->endTransaction(true);
      }
      catch (\Exception $ex) {
        $this->endTransaction(false);
      }
    }

    // return the last error
    $errors = array_merge($this->request->getErrors(), $this->response->getErrors());
    if (sizeof($errors) > 0) {
      $error = array_pop($errors);
      $this->response->setValue('errorCode', $error->getCode());
      $this->response->setValue('errorMessage', $error->getMessage());
      $this->response->setValue('errorData', $error->getData());
      $this->response->setStatus($error->getStatusCode());
    }

    // log errors
    for ($i=0,$count=sizeof($errors); $i<$count; $i++) {
      $error = $errors[$i];
      $message = $error->__toString();
      switch ($error->getLevel()) {
        case ApplicationError::LEVEL_WARNING:
          $this->logger->warn($message);
          break;
        default:
          $this->logger->error($message);
      }
    }

    if ($isDebugEnabled) {
      $this->logger->debug('Response: '.$this->response);
    }
  }

  /**
   * Execute the given controller method.
   * @param $method The name of the method to execute (optional)
   */
  protected abstract function doExecute($method=null);

  /**
   * Delegate the current request to another action. The context is the same as
   * the current context and the source controller will be set to this.
   * The request and response format will be NullFormat which means that all
   * request values should be passed in the application internal format and
   * all response values will have that format. Execution will return to the
   * calling controller instance afterwards.
   * @param $action The name of the action to execute
   * @return Response instance
   */
  protected function executeSubAction($action) {
    $curRequest = $this->getRequest();
    $subRequest = ObjectFactory::getNewInstance('request');
    $subRequest->setSender(get_class($this));
    $subRequest->setContext($curRequest->getContext());
    $subRequest->setAction($action);
    $subRequest->setHeaders($curRequest->getHeaders());
    $subRequest->setValues($curRequest->getValues());
    $subRequest->setFormat('null');
    $subRequest->setResponseFormat('null');
    $subResponse = ObjectFactory::getNewInstance('response');
    $this->actionMapper->processAction($subRequest, $subResponse);
    return $subResponse;
  }

  /**
   * Redirect to the given location with the given request data externally
   * (HTTP status code 302). The method will not return a result to the calling
   * controller method. The calling method should return immediatly in order to
   * avoid any side effects of code executed after the redirect. The given data
   * are stored in the session under the given key.
   * @param $location The location to redirect to
   * @param $key The key used as session variable name (optional)
   * @param $data The data to be stored in the session (optional)
   */
  protected function redirect($location, $key=null, $data=null) {
    if (strlen($key) > 0 && $data != null) {
      $session = $this->getSession();
      $session->set($key, $data);
    }
    $response = $this->getResponse();
    $response->setHeader('Location', $location);
    $response->setStatus(302);
    $response->setFormat('null'); // prevent any rendering
  }

  /**
   * Get the Request instance.
   * @return Request
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Get the Response instance.
   * @return Response
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Get the Logger instance.
   * @return Logger
   */
  protected function getLogger() {
    return $this->logger;
  }

  /**
   * Get the Session instance.
   * @return Session
   */
  protected function getSession() {
    return $this->session;
  }

  /**
   * Get the PersistenceFacade instance.
   * @return PersistenceFacade
   */
  protected function getPersistenceFacade() {
    return $this->persistenceFacade;
  }

  /**
   * Get the PermissionManager instance.
   * @return PermissionManager
   */
  protected function getPermissionManager() {
    return $this->permissionManager;
  }

  /**
   * Get the ActionMapper instance.
   * @return ActionMapper
   */
  protected function getActionMapper() {
    return $this->actionMapper;
  }

  /**
   * Get the Localization instance.
   * @return Localization
   */
  protected function getLocalization() {
    return $this->localization;
  }

  /**
   * Get the Message instance.
   * @return Message
   */
  protected function getMessage() {
    return $this->message;
  }

  /**
   * Get the Configuration instance.
   * @return Configuration
   */
  protected function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Start or join a transaction that will be committed at the end of execution.
   * If a transaction already is started it will be joined and committed by the
   * controller that started it. This allows to compose actions by using the
   * Controller::executeSubAction() method that all share the same transaction.
   * @return Boolean whether a new transaction was started or an existing was joined
   */
  protected function requireTransaction() {
    $tx = $this->getPersistenceFacade()->getTransaction();
    if (!$tx->isActive()) {
      $tx->begin();
      $this->startedTransaction = true;
    }
  }

  /**
   * End the transaction. Only if this controller instance started the transaction,
   * it will be committed or rolled back. Otherwise the call will be ignored.
   * @param $commit Boolean whether the transaction should be committed
   */
  protected function endTransaction($commit) {
    $tx = $this->getPersistenceFacade()->getTransaction();
    if ($this->startedTransaction && $tx->isActive()) {
      if ($commit) {
        $tx->commit();
      }
      else {
        $tx->rollback();
      }
    }
    $this->startedTransaction = false;
  }

  /**
   * Check if the current request is localized. This is true,
   * if it has a language parameter that is not equal to Localization::getDefaultLanguage().
   * Throws an exception if a language is given which is not supported
   * @return Boolean whether the request is localized or not
   */
  protected function isLocalizedRequest() {
    if ($this->request->hasValue('language')) {
      $language = $this->request->getValue('language');
      if ($language != $this->localization->getDefaultLanguage()) {
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
    if ($this->request->hasValue('language')) {
      $language = $this->request->getValue('language');
      if (!in_array($language, array_keys($this->localization->getSupportedLanguages()))) {
        $this->response->addError(ApplicationError::get('PARAMETER_INVALID',
                ['invalidParameters' => ['language']]));
        return false;
      }
    }
    return true;
  }

  /**
   * Create a CSRF token, store it in the session and set it in the response.
   * The name of the response parameter is Controller::CSRF_TOKEN_PARAM.
   * @param $name The name of the token to be used in Controller::validateCsrfToken()
   */
  protected function generateCsrfToken($name) {
    // generate token and store in session
    $token = base64_encode(openssl_random_pseudo_bytes(32));
    $this->getSession()->set(self::CSRF_TOKEN_PARAM.'_'.$name, $token);

    // set token in response
    $response = $this->getResponse();
    $response->setValue(self::CSRF_TOKEN_PARAM, $token);
  }

  /**
   * Validate the CSRF token contained in the request against the token stored
   * in the session. The name of the request parameter is Controller::CSRF_TOKEN_PARAM.
   * @param $name The name of the token as set in Controller::generateCsrfToken()
   * @return boolean
   */
  protected function validateCsrfToken($name) {
    // get token from session
    $session = $this->getSession();
    $tokenKey = self::CSRF_TOKEN_PARAM.'_'.$name;
    if (!$session->exist($tokenKey)) {
      return false;
    }
    $storedToken = $session->get($tokenKey);
    $session->remove($tokenKey);

    // compare session token with request token
    $token = $this->getRequest()->getValue(self::CSRF_TOKEN_PARAM);
    return $token === $storedToken;
  }

  /**
   * Set the value of a local session variable.
   * @param $key The key (name) of the session vaiable.
   * @param $default The default value if the key is not defined (optional, default: _null_)
   * @return The session var or null if it doesn't exist.
   */
  protected function getLocalSessionValue($key, $default=null) {
    $sessionVarname = get_class($this);
    $localValues = $this->session->get($sessionVarname, null);
    return array_key_exists($key, $localValues) ? $localValues[$key] : $default;
  }

  /**
   * Get the value of a local session variable.
   * @param $key The key (name) of the session vaiable.
   * @param $value The value of the session variable.
   */
  protected function setLocalSessionValue($key, $value) {
    $sessionVarname = get_class($this);
    $localValues = $this->session->get($sessionVarname, null);
    if ($localValues == null) {
      $localValues = [];
    }
    $localValues[$key] = $value;
    $this->session->set($sessionVarname, $localValues);
  }

  /**
   * Remove all local session values.
   * @param $key The key (name) of the session vaiable.
   * @param $value The value of the session variable.
   */
  protected function clearLocalSessionValues() {
    $sessionVarname = get_class($this);
    $this->session->remove($sessionVarname);
  }
}
?>
