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
require_once(WCMF_BASE."wcmf/lib/util/Message.php");
require_once(WCMF_BASE."wcmf/lib/presentation/format/Formatter.php");
require_once(WCMF_BASE."wcmf/lib/presentation/ApplicationError.php");
require_once(WCMF_BASE."wcmf/lib/i18n/Localization.php");
require_once(WCMF_BASE."wcmf/lib/util/Log.php");

/**
 * @class Controller
 * @ingroup Presentation
 * @brief Controller is the base class of all controllers. If a Controller has a view
 * it is expected to reside in the directory configured in section smarty.templateDir.
 * Additional smarty directories ('templates_c', 'configs', 'cache') are expected in a
 * subdirectory of the template directory named 'smarty'.
 *
 * Error Handling:
 * - throw an Exception or use action='failure' to signal fatal errors
 *    (displays FailureController)
 * - add an ApplicationError to the response to signal non fatal errors
 *    (the message will be displayed in the next view)
 *
 * @param[in/out] action The action to be executed
 * @param[in/out] sid The session id of the current user session
 * @param[in/out] language The language of the requested data, optional
 * @param[out] controller The name of the executed controller
 * @param[out] success True/False whether the action completed successfully or not
 * @param[out] errorMessage An error message which is displayed to the user, if success == false
 * @param[out] errorCode An error code, describing the type of error, if success == false
 * @param[out] errorData Some error codes require to transmit further information to the client,
 *                       if success == false
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class Controller
{
  private $_request = null;
  private $_response = null;
  private $_executionResult = false;

  private $_delegate = null;

  /**
   * Constructor.
   * @param delegate A ControllerDelegate instance, if one is defined in the configuration (optional, default null does not work in PHP4).
   */
  public function __construct($delegate)
  {
    $this->_request = new Request(null, null, null);
    $this->_response = new Response(null, null, null);
    $this->_delegate = $delegate;
  }
  /**
   * Initialize the Controller with request/response data. Which data is required is defined by the Controller.
   * The base class method just stores the parameters in a member variable. Specialized Controllers may overide
   * this behaviour for further initialization.
   * @attention It lies in its responsibility to fail or do some default action if some data is missing.
   * @param request A reference to the Request sent to the Controller. The sender attribute of the Request is the
   * last controller's name, the context is the current context and the action is the requested one.
   * All data sent from the last controller are accessible using the Request::getValue method. The request is
   * supposed to be read-only. It will not be used any more after beeing passed to the controller.
   * @param response A reference to the Response that will be modified by the Controller. The initial values for
   * context and action are the same as in the request parameter and are meant to be modified according to the
   * performed action. The sender attribute of the response is set to the current controller. Initially there
   * are no data stored in the response.
   */
  public function initialize(Request $request, Response $response)
  {
    $response->setController($this);

    $this->_request = $request;
    $this->_response = $response;

    // restore the error messages of a previous call
    if ($request->hasErrors()) {
      foreach ($request->getErrors() as $error) {
        $response->addError($error);
      }
    }
    if ($this->_delegate !== null) {
      $this->_delegate->postInitialize($this);
    }
  }
  /**
   * Check if the data given by initialize() meet the requirements of the Controller.
   * Subclasses will override this method to validate against their special requirements.
   * @return True/False whether the data are ok or not.
   */
  protected function validate()
  {
    return true;
  }
  /**
   * Check if the Controller has a view. The default implementation
   * returns true, if the execution result is false and the response format is MSG_FORMAT_HTML.
   * @return True/False whether the Controller has a view or not.
   */
  public function hasView()
  {
    $hasView = $this->_executionResult === false &&
      $this->_response->getFormat() == MSG_FORMAT_HTML;
    return $hasView;
  }
  /**
   * Execute the Controller resulting in its Action processed and/or its View beeing displayed.
   * @return True/False wether following Controllers should be executed or not.
   */
  public function execute()
  {
    if (Log::isDebugEnabled(__CLASS__))
    {
      Log::debug('Executing: '.get_class($this), __CLASS__);
      Log::debug('Request: '.$this->_request, __CLASS__);
    }

    // validate controller data
    $validationFailed = false;
    if ($this->_delegate !== null) {
      if (!$this->_delegate->preValidate($this)) {
        $validationFailed = true;
      }
    }
    if (!$validationFailed && !$this->validate()) {
      $validationFailed = true;
    }

    // execute controller logic
    if (!$validationFailed)
    {
      if ($this->_delegate !== null) {
        $this->_delegate->preExecute($this);
      }
      $this->_executionResult = $this->executeKernel();
      if ($this->_delegate !== null) {
        $this->_executionResult = $this->_delegate->postExecute($this, $this->_executionResult);
      }
    }
    else {
      // don't process further if validation failed
      $this->_executionResult = false;
    }

    // prepare the response
    $this->assignResponseDefaults();
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug('Response: '.$this->_response, __CLASS__);
    }

    // log errors
    $errors = $this->_response->getErrors();
    for ($i=0,$count=sizeof($errors); $i<$count; $i++) {
      Log::error($errors[$i]->__toString(), __CLASS__);
    }
    return $this->_executionResult;
  }
  /**
   * Do the work in execute(): Load and process model and maybe assign data to response.
   * Subclasses process their Action and assign the Model to the response.
   * @return True/False wether ActionMapper should proceed with the next controller or not.
   */
  protected abstract function executeKernel();
  /**
   * Delegate the current request to another action. The context is the same as
   * the current context and the source controller will be set to TerminateController,
   * which means that the application flow will return after the action (and possible
   * sub actions) are executed. The request and response format will MSG_FORMAT_NULL
   * which means that all request values should be passed in the application internal
   * format and all response values will have that format.
   * @param action The name of the action to execute
   * @param requestValues An associative array of key value pairs overwriting,
   *        the current request values, optional [default: empty array]
   * @return Response instance
   */
  protected function executeSubAction($action, array $requestValues=array())
  {
    $curRequest = $this->getRequest();
    $subRequest = new Request('TerminateController', $curRequest->getContext(), $action);
    $subRequest->setValues($curRequest->getValues());
    foreach ($requestValues as $key => $value) {
      $subRequest->setValue($key, $value);
    }
    $subRequest->setFormat(MSG_FORMAT_NULL);
    $subRequest->setResponseFormat(MSG_FORMAT_NULL);
    $response = ActionMapper::getInstance()->processAction($subRequest);
    return $response;
  }
  /**
   * Get the Request object.
   * @return Request object
   */
  public function getRequest()
  {
    return $this->_request;
  }
  /**
   * Get the Response object.
   * @return Response object
   */
  public function getResponse()
  {
    return $this->_response;
  }
  /**
   * Get the controller delegate.
   * @return ControllerDelegate / or null if none is existing
   */
  protected function getDelegate()
  {
    return $this->_delegate;
  }
  /**
   * Get a string value that uniquely identifies the current request data. This value
   * maybe used to compare two requests and return cached responses based on the result.
   * The default implementation returns null. Subclasses should override this to
   * return reasonable values based on the expected requests.
   * @return The id or null, if no cache id should be used.
   */
  public function getCacheId()
  {
    return null;
  }
  /**
   * Assign default variables to the response. This method is called after Controller execution.
   * This method may be used by derived controller classes for convenient response setup.
   * @attention Internal use only.
   */
  protected function assignResponseDefaults()
  {
    // set default values on the response
    $session = SessionData::getInstance();
    $this->_response->setValue('sid', $session->getID());

    // return the first error
    $errors = $this->_response->getErrors();
    if (sizeof($errors) > 0) {
      $error = $errors[0];
      $this->_response->setValue('errorCode', $error->getCode());
      $this->_response->setValue('errorMessage', $error->getMessage());
      $this->_response->setValue('errorData', $error->getData());
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
    $this->_response->setValue('responseFormat', $this->_response->getFormat());

    if ($this->_delegate !== null) {
      $this->_delegate->assignAdditionalResponseValues($this);
    }
  }
  /**
   * Check if the current request is localized. This is true,
   * if it has a language parameter that is not equal to Localization::getDefaultLanguage().
   * @return True/False wether the request is localized or not
   */
  protected function isLocalizedRequest()
  {
    $localization = Localization::getInstance();
    if ($this->_request->hasValue('language') &&
      $this->_request->getValue('language') != $localization->getDefaultLanguage()) {
      return true;
    }
    return false;
  }
}
?>
