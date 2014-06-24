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

use Exception;
use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\ClassLoader;
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

  private $_startTime = null;
  private $_initialRequest = null;

  private $_debug = true;

  /**
   * Constructor
   */
  public function __construct() {
    new ClassLoader();
    new ErrorHandler();
    $this->_startTime = microtime(true);
    register_shutdown_function(array($this, "shutdown"));
    ob_start(array($this, "outputHandler"));
  }

  /**
   * Initialize the application.
   * 
   * @param configPath The path where config files reside (as seen from main.php), optional [default: 'config/']
   * @param mainConfigFile The main configuration file to use, optional [default: 'config.ini']
   * @param defaultController The controller to call if none is given in request parameters, optional [default: 'wcmf\application\controller\TerminateController']
   * @param defaultContext The context to set if none is given in request parameters, optional [default: '']
   * @param defaultAction The action to perform if none is given in request parameters, optional [default: 'login']
   * @return Request instance representing the current HTTP request
   */
  public function initialize($configPath='../config/', $mainConfigFile='config.ini',
    $defaultController='wcmf\application\controller\TerminateController', $defaultContext='', $defaultAction='login') {

    Log::configure($configPath.'log4php.php');
    $config = new InifileConfiguration($configPath);
    $config->addConfiguration($mainConfigFile);
    ObjectFactory::configure($config);

    // create the Request instance
    $this->_initialRequest = Request::getDefault($defaultController, $defaultContext, $defaultAction);

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

    // load event listeners
    $listeners = $config->getValue('listeners', 'application');
    foreach ($listeners as $key) {
      $listener = ObjectFactory::getInstance($key);
    }

    // return the request
    return $this->_initialRequest;
  }

  /**
   * Run the application with the given request
   * @param request
   * @return Response instance
   */
  public function run(Request $request) {
    // process the requested action
    $response = ObjectFactory::getInstance('actionMapper')->processAction($request);

    // store the last successful request
    $session = ObjectFactory::getInstance('session');
    $session->set('lastRequest', $request);
    return $response;
  }

  /**
   * Default exception handling method. Rolls back the transaction and
   * re-executes the last request (expected in the session variable 'lastRequest').
   * @param exception The Exception instance
   * @param request The Request instance
   */
  public function handleException(Exception $exception, Request $request=null) {
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

    Log::error($exception->getMessage()."\n".$exception->getTraceAsString(), __CLASS__);

    // rollback current transaction
    if (ObjectFactory::getConfigurationInstance() != null) {
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $persistenceFacade->getTransaction()->rollback();

      // redirect to failure action
      if ($request == null) {
        $request = $this->_initialRequest;
      }
      $request->addError(ApplicationError::fromException($exception));
      $request->setAction('failure');
      ObjectFactory::getInstance('actionMapper')->processAction($request);
    }
    else {
      throw $exception;
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
   * @note must be public
   * @param buffer The content to be returned to the client
   * @return String
   */
  public function outputHandler($buffer) {
    // log last error
    $error = error_get_last();
    if ($error !== NULL) {
      $info = "Error: ".$error['message']." in ".$error['file']." on line ".$error['line'];
      Log::error($info, __CLASS__);

      // suppress error message in browser
      if (!$this->_debug) {
        header('HTTP/1.1 500 Internal Server Error');
        exit(0);
      }
    }
    return $buffer;
  }

  /**
   * Get an unique id for the application based on main script location.
   * @return The id
   */
  public static function getId() {
    return md5(__FILE__);
  }
}
?>
