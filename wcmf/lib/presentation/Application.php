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

use \Exception;
use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\ErrorHandler;
use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\Request;

/**
 * @class Application
 * @ingroup Presentation
 * @brief The main application class. Does all the initialization.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Application {

  private $_requestValues = array();
  private $_rawPostBody = null;
  private $_rawPostBodyIsJson = false;

  private $_startTime = null;
  private $_initialRequest = null;

  /**
   * Constructor
   */
  public function __construct() {
    new ErrorHandler();
    $this->_startTime = microtime(true);
    register_shutdown_function(array($this, "shutdown"));
    ob_start(array($this, "outputHandler"));
  }

  /**
   * Initialize the application.
   * - Parses and processes the configuration
   * - Sets global variables
   * - Initialize the session and other main application classes
   * - Extracts the application parameters
   * @param configPath The path where config files reside (as seen from main.php), optional [default: 'config/']
   * @param mainConfigFile The main configuration file to use, optional [default: 'config.ini']
   * @param defaultController The controller to call if none is given in request parameters, optional [default: 'wcmf\application\controller\LoginController']
   * @param defaultContext The context to set if none is given in request parameters, optional [default: '']
   * @param defaultAction The action to perform if none is given in request parameters, optional [default: 'login']
   * @return Request instance representing the current HTTP request
   * TODO: maybe setup request with default values from a config section?
   * TODO: allow configPath array to search from different locations, simplifies inclusion
   */
  public function initialize($configPath='../config/', $mainConfigFile='config.ini',
    $defaultController='wcmf\application\controller\LoginController', $defaultContext='', $defaultAction='login') {

    // collect all request data
    $this->_requestValues = array_merge($_GET, $_POST, $_FILES);
    $this->_rawPostBody = file_get_contents('php://input');
    // add the raw post data if they are json encoded
    $json = json_decode($this->_rawPostBody, true);
    if (is_array($json)) {
      $this->_rawPostBodyIsJson = true;
      foreach ($json as $key => $value) {
        // TODO: filter values
        $this->_requestValues[$key] = $value;
      }
    }

    Log::configure($configPath.'log4php.properties');
    $config = new InifileConfiguration($configPath);
    $config->addConfiguration($mainConfigFile);
    ObjectFactory::configure($config);

    // get controller/context/action triple
    $controller = $this->getRequestValue('controller', $defaultController);
    $context = $this->getRequestValue('context', $defaultContext);
    $action = $this->getRequestValue('action', $defaultAction);
    $readonly = $this->getRequestValue('readonly', false);

    // create the Request instance
    $this->_initialRequest = new Request($controller, $context, $action);
    $this->_initialRequest->setValues($this->_requestValues);

    // add header values to request
    foreach (getallheaders() as $name => $value) {
      $this->_initialRequest->setHeader($name, $value);
    }

    // initialize session
    $session = ObjectFactory::getInstance('session');

    // clear errors
    $session->clearErrors();

    // load user configuration
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $authUser = $permissionManager->getAuthUser();
    if ($authUser && strlen($authUser->getConfig()) > 0) {
      $config->addConfiguration($authUser->getConfig(), true);
    }

    // prepare PersistenceFacade
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    if ($readonly) {
      $persistenceFacade->setReadOnly(true);
    }

    // return the request
    return $this->_initialRequest;
  }

  /**
   * Run the application with the given request
   * @param request
   */
  public function run(Request $request) {
    // process the requested action
    ObjectFactory::getInstance('actionMapper')->processAction($request);

    // store the last successful request
    $session = ObjectFactory::getInstance('session');
    $session->set('lastRequest', $request);
  }

  /**
   * Default exception handling method. Rolls back the transaction and
   * re-executes the last request (expected in the session variable 'lastRequest').
   * @param exception The Exception instance
   * @param request The Request instance
   */
  public function handleException(Exception $exception, Request $request) {
    if ($exception instanceof ApplicationException) {
      $error = $exception->getError();
      if ($error->getCode() == 'SESSION_INVALID') {
        $request = $exception->getRequest();
        $request->setAction('logout');
        $request->addError($error);
        ObjectFactory::getInstance('actionMapper')->processAction($request);
        return;
      }
    }

    Log::error($exception->getMessage()."\n".$exception->getTraceAsString(), 'main');

    // rollback current transaction
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $persistenceFacade->getTransaction()->rollback();

    $session = ObjectFactory::getInstance('session');
    $lastRequest = $session->get('lastRequest');
    if ($lastRequest) {
      // process last successful request if existing
      ObjectFactory::getInstance('actionMapper')->processAction($lastRequest);
    }
    else {
      // redirect to failure action
      $request->addError(ApplicationError::get('GENERAL_FATAL'));
      $request->setAction('failure');
      ObjectFactory::getInstance('actionMapper')->processAction($request);
    }
  }

  /**
   * This method is called on script shutdown
   * NOTE: must be public
   */
  public function shutdown() {
    // log resource usage
    if (Log::isDebugEnabled(__CLASS__)) {
      $timeDiff = microtime(true)-$this->_startTime;
      $memory = number_format(memory_get_peak_usage()/(1024*1024), 2);
      $msg = "Time[".round($timeDiff, 2)."s] Memory[".$memory."mb]";
      if ($this->_initialRequest != null) {
        $msg .= " Request[".$this->_initialRequest->getSender()."?".
                $this->_initialRequest->getContext()."?".$this->_initialRequest->getAction()."]";
      }
      Log::debug($msg, __CLASS__);
    }

    ob_end_flush();
  }

  /**
   * This method is run as ob_start callback
   * @param buffer The content to be returned to the client
   * @return String
   */
  protected function outputHandler($buffer) {
    // log last error
    $error = error_get_last();
    if ($error !== NULL) {
      $info = "Error: ".$error['message']." in ".$error['file']." on line ".$error['line'];
      Log::error($info, __CLASS__);

      // suppress error message in browser
      header('HTTP/1.1 500 Internal Server Error');
      exit(0);
    }
    else {
      return $buffer;
    }
  }

  /**
   * Get a value from the request parameters (GET, POST variables)
   * @param name The name of the parameter
   * @param default The value to return, if no value is given
   * @return The value
   */
  protected function getRequestValue($name, $default) {
    $value = $default;
    if (array_key_exists($name, $this->_requestValues)) {
      $value = $this->_requestValues[$name];
    }
    return $value;
  }

  /**
   * Get an unique id for the application based on main script location.
   * @return The id
   */
  public static function getId() {
    return md5(realpath($_SERVER['PHP_SELF']));
  }
}
?>
