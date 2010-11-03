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
require_once(BASE."wcmf/lib/util/class.ObjectFactory.php");
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/util/class.FormUtil.php");
require_once(BASE."wcmf/lib/util/class.FileUtil.php");
require_once(BASE."wcmf/lib/presentation/class.View.php");
require_once(BASE."wcmf/lib/presentation/format/class.Formatter.php");
require_once(BASE."wcmf/lib/presentation/class.WCMFInifileParser.php");
require_once(BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(BASE."wcmf/lib/i18n/class.Localization.php");
require_once(BASE."wcmf/lib/security/class.RightsManager.php");
require_once(BASE."wcmf/lib/util/class.Obfuscator.php");
require_once(BASE."wcmf/lib/util/class.Log.php");

/**
 * @class Controller
 * @ingroup Presentation
 * @brief Controller is the base class of all controllers. If a Controller has a view
 * it is expected to reside in the directory configured in section smarty.templateDir.
 * Additional smarty directories ('templates_c', 'configs', 'cache') are expected in a
 * subdirectory of the template directory named 'smarty'.
 *
 * Error Handling:
 * - throw an Exception or use action='failure' for fatal errors (displays FailureController)
 * - use field _errorMsg for non fatal errors (message will be appended to errorMsg-data
 *   which will be displayed in the next view)
 *
 * @param[in] language The language of the requested data, optional
 * @param[out] sid The session id
 * @param[out] controller The name of the controller
 * @param[out] errorMsg Any message set with setErrorMsg or appendErrorMsg, optional
 * @param[out] success True, if errorMsg is empty or does not exist, False else
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class Controller
{
  private $_request = null;
  private $_response = null;

  private $_errorMsg = '';
  private $_view = null;
  private $_delegate = null;

  /**
   * Constructor.
   * @param delegate A ControllerDelegate instance, if one is defined in the configuration (optional, default null does not work in PHP4).
   */
  public function __construct($delegate)
  {
    $this->_request = new Request(null, null, null, array());
    $this->_response = new Response(null, null, null, array());
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
    $this->_request = $request;
    $this->_response = $response;

    // restore the error message of a previous call
    $this->appendErrorMsg($request->getValue('errorMsg'));

    if ($this->_delegate !== null) {
      $this->_delegate->postInitialize($this);
    }
  }
  /**
   * Check if the data given by initialize() meet the requirements of the Controller.
   * Subclasses will override this method to validate against their special requirements.
   * @return True/False whether the data are ok or not.
   *         In case of False a detailed description is provided by getErrorMsg().
   */
  protected function validate()
  {
    if ($this->_delegate !== null) {
      return $this->_delegate->validate($this);
    }
    else {
      return true;
    }
  }
  /**
   * Check if the Controller has a view.
   * Subclasses must implement this method.
   * @return True/False whether the Controller has a view or not.
   * TODO: make decision based on response format and remove this from
   */
  protected abstract function hasView();
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
    if (!$this->validate()) {
      throw new ApplicationException($this->_request, $this->_response,
        Message::get("Validation failed for the following reason: %1%", array($this->_errorMsg)));
    }
    if ($this->_delegate !== null) {
      $this->_delegate->preExecute($this);
    }
    // set default values on response
    $session = SessionData::getInstance();
    $this->_response->setValue('sid', $session->getID());
    $this->_response->setValue('controller', get_class($this));

    // execute controller logic
    $result = $this->executeKernel();

    // append current error message to errorMsg-data
    if (strlen($this->getErrorMsg()) > 0) {
      $this->_response->appendValue('errorMsg', $this->getErrorMsg());
    }
    // create the view if existing
    // @todo move response format condition into hasView
    if ($this->hasView() && $result === false && $this->_response->getFormat() == MSG_FORMAT_HTML)
    {
      // check if a view template is defined
      $viewTpl = $this->getViewTemplate($this->_response->getSender(), $this->_request->getContext(), $this->_request->getAction());
      if (!$viewTpl) {
        throw new ConfigurationException("View definition missing for ".get_class($this).". Action key: ".$actionKey);
      }
      $this->_view = ObjectFactory::createInstanceFromConfig('implementation', 'View');
      $this->_view->setup();
      $this->_response->setView($this->_view);
      $this->assignViewDefaults($this->_view);
    }

    if ($this->_delegate !== null)
      $result = $this->_delegate->postExecute($this, $result);

    // add success flag
    if (strlen($this->getErrorMsg()) > 0)
      $this->_response->setValue('success', false);
    else
      $this->_response->setValue('success', true);

    Formatter::serialize($this->_response);

    // display the view if existing
    // @todo move response format condition into hasView
    if ($this->hasView() && $result === false && $this->_response->getFormat() == MSG_FORMAT_HTML)
    {
      $viewTpl = realpath(BASE.$this->getViewTemplate($this->_response->getSender(), $this->_request->getContext(), $this->_request->getAction()));
      if ($this->_view->caching && ($cacheId = $this->getCacheId()) !== null)
      {
        $this->_view->display($viewTpl, $cacheId);
      }
      else
        $this->_view->display($viewTpl);
    }

    if (Log::isDebugEnabled(__CLASS__))
    {
      Log::debug('Response: '.$this->_response, __CLASS__);
    }
    return $result;
  }
  /**
   * Do the work in execute(): Load and process model and maybe asign data to view.
   * Subclasses process their Action and assign the Model to the view.
   * @return False or an an assoziative array with keys 'context' and 'action' describing how to proceed.
   *         Return false to break the action processing chain.
   */
  protected abstract function executeKernel();
  /**
   * Get a detailed description of the last error.
   * @return The error message.
   */
  protected function getErrorMsg()
  {
    return $this->_errorMsg;
  }
  /**
   * Set a detailed description of the last error.
   * @param msg The error message.
   */
  protected function setErrorMsg($msg)
  {
    $this->_errorMsg = $msg;
  }
  /**
   * Append a detailed description of the last error to the existing errors.
   * @param msg The error message.
   */
  protected function appendErrorMsg($msg)
  {
    // ignore if the last message is the same as msg
    if (preg_match("/".$msg."$/", $this->_errorMsg)) {
      return;
    }
    if (strlen($this->_errorMsg) > 0)
      $this->_errorMsg .= "\n";
    $this->_errorMsg .= $msg;
  }
  /**
   * Get the Request object.
   * @return A reference to the Request object
   */
  protected function getRequest()
  {
    return $this->_request;
  }
  /**
   * Get the Response object.
   * @return A reference to the Response object
   */
  protected function getResponse()
  {
    return $this->_response;
  }
  /**
   * Get the controller view.
   * @return A reference to the controller view / or null if none is existing
   */
  protected function getView()
  {
    return $this->_view;
  }
  /**
   * Get the controller delegate.
   * @return A reference to the controller view / or null if none is existing
   */
  protected function getDelegate()
  {
    return $this->_delegate;
  }
  /**
   * Get the template filename for the view from the configfile.
   * @note static method
   * @param controller The name of the controller
   * @param context The name of the context
   * @param action The name of the action
   * @return The filename of the template or false, if now view is defined
   */
  protected function getViewTemplate($controller, $context, $action)
  {
    $view = '';
    $parser = WCMFInifileParser::getInstance();
    $actionKey = $parser->getBestActionKey('views', $controller, $context, $action);
    if (Log::isDebugEnabled(__CLASS__)) {
      Log::debug('Controller::getViewTemplate: '.$controller."?".$context."?".$action.' -> '.$actionKey, __CLASS__);
    }
    // get corresponding view
    $view = $parser->getValue($actionKey, 'views', false);
    return $view;
  }
  /**
   * Get the id which should be used when caching the controllers view.
   * This method will only be called, if the configuration entry smarty.caching is set to 1.
   * The default implementation returns null. Subclasses should return an id that is unique
   * to each different content of the same view.
   * @return The id or null, if no cache id should be used.
   */
  protected function getCacheId()
  {
    return null;
  }
  /**
   * Assign default variables to the view. This method is called after Controller execution.
   * This method may be used by derived controller classes for convenient View setup.
   * @param view A reference to the View to assign the variables to
   * @attention Internal use only.
   */
  protected function assignViewDefaults($view)
  {
    $parser = InifileParser::getInstance();
    $rightsManager = RightsManager::getInstance();
    $authUser = $rightsManager->getAuthUser();

    // assign current controller and context to smarty
    $view->assign('_controller', $this->_response->getSender());
    $view->assign('_context', $this->_response->getContext());
    $view->assign('_action', $this->_response->getAction());
    $view->assign('_responseFormat', $this->_response->getFormat());
    $view->assign('messageObj', new Message());
    $view->assign('formUtil', new FormUtil());
    $view->assign('nodeUtil', new NodeUtil());
    $view->assign('obfuscator', Obfuscator::getInstance());
    $view->assign('applicationTitle', $parser->getValue('applicationTitle', 'cms'));
    if ($authUser != null) {
      $view->assign_by_ref('authUser', $authUser);
    }
    if ($this->_delegate !== null) {
      $this->_delegate->assignAdditionalViewValues($this);
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
