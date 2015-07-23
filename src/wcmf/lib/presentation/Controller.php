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
namespace wcmf\lib\presentation;

use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Request;
use wcmf\lib\presentation\Response;

/**
 * Controller is the base class of all controllers.
 *
 * Error Handling:
 * - throw an Exception or use response action _failure_ to signal fatal errors
 *    (calls wcmf::application::controller::FailureController)
 * - add an ApplicationError to the response to signal non fatal errors
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

  private $_request = null;
  private $_response = null;

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
   * Check if the data given by initialize() meet the requirements of the Controller.
   * Subclasses will override this method to validate against their special requirements.
   * @return Boolean whether the data are ok or not.
   */
  protected function validate() {
    return true;
  }

  /**
   * Execute the Controller resulting in its action processed. The actual
   * processing is done in Controller::doExecute(), which is implemented
   * by concrete Controller subclasses.
   */
  public function execute() {
    $isDebugEnabled = Log::isDebugEnabled(__CLASS__);
    if ($isDebugEnabled) {
      Log::debug('Executing: '.get_class($this), __CLASS__);
      Log::debug('Request: '.$this->_request, __CLASS__);
    }

    // validate controller data
    $validationFailed = false;
    if (!$this->validate()) {
      $validationFailed = true;
    }

    // execute controller logic
    if (!$validationFailed) {
      $this->doExecute();
    }

    // prepare the response
    $this->assignResponseDefaults();
    if ($isDebugEnabled) {
      Log::debug('Response: '.$this->_response, __CLASS__);
    }

    // log errors
    $errors = $this->_response->getErrors();
    for ($i=0,$count=sizeof($errors); $i<$count; $i++) {
      Log::error($errors[$i]->__toString(), __CLASS__);
    }
  }

  /**
   * Process the request.
   * Subclasses process their action and assign the Model to the response.
   */
  protected abstract function doExecute();

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
    $formats = ObjectFactory::getInstance('formats');
    $nullFormat = $formats['null'];
    $subRequest->setFormat($nullFormat);
    $subRequest->setResponseFormat($nullFormat);
    $response = ObjectFactory::getInstance('actionMapper')->processAction($subRequest);
    return $response;
  }

  /**
   * Get the Request object.
   * @return Request object
   */
  public function getRequest() {
    return $this->_request;
  }

  /**
   * Get the Response object.
   * @return Response object
   */
  public function getResponse() {
    return $this->_response;
  }

  /**
   * Assign default variables to the response. This method is called after Controller execution.
   * This method may be used by derived controller classes for convenient response setup.
   */
  protected function assignResponseDefaults() {
    // return the first error
    $errors = $this->_response->getErrors();
    if (sizeof($errors) > 0) {
      $error = $errors[0];
      $this->_response->setValue('errorCode', $error->getCode());
      $this->_response->setValue('errorMessage', $error->getMessage());
      $this->_response->setValue('errorData', $error->getData());
      $this->_response->setStatus(Response::STATUS_400);
    }
    // set the success flag
    if (sizeof($errors) > 0) {
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
      $localization = ObjectFactory::getInstance('localization');
      if ($language != $localization->getDefaultLanguage()) {
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
      $localization = ObjectFactory::getInstance('localization');
      if (!in_array($language, array_keys($localization->getSupportedLanguages()))) {
        $this->_response->addError(ApplicationError::get('PARAMETER_INVALID',
                array('invalidParameters' => array('language'))));
        return false;
      }
    }
    return true;
  }
}
?>
