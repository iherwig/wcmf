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

use wcmf\lib\core\ErrorHandler;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Request;

/**
 * Application is the main application class, that does all the initialization.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Application {

  private $_startTime = null;
  private $_initialRequest = null;

  private $_debug = true;

  private static $_logger = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->_startTime = microtime(true);
    if (self::$_logger == null) {
      self::$_logger = LogManager::getLogger(__CLASS__);
    }
    ob_start(array($this, "outputHandler"));
    new ErrorHandler();
  }

  /**
   * Destructor
   */
  public function __destruct() {
    // log resource usage
    if (self::$_logger->isDebugEnabled()) {
      $timeDiff = microtime(true)-$this->_startTime;
      $memory = number_format(memory_get_peak_usage()/(1024*1024), 2);
      $msg = "Time[".round($timeDiff, 2)."s] Memory[".$memory."mb]";
      if ($this->_initialRequest != null) {
        $msg .= " Request[".$this->_initialRequest->getSender()."?".
                $this->_initialRequest->getContext()."?".$this->_initialRequest->getAction()."]";
      }
      self::$_logger->debug($msg);
    }
    ob_end_flush();
  }

  /**
   * Initialize the request.
   *
   * @param $defaultController The controller to call if none is given in request parameters (optional, default: '')
   * @param $defaultContext The context to set if none is given in request parameters (optional, default: '')
   * @param $defaultAction The action to perform if none is given in request parameters (optional, default: 'login')
   * @return Request instance representing the current HTTP request
   */
  public function initialize($defaultController='', $defaultContext='', $defaultAction='login') {
    $config = ObjectFactory::getInstance('configuration');

    // create the Request instance
    $this->_initialRequest = ObjectFactory::getInstance('request');
    $this->_initialRequest->initialize($defaultController, $defaultContext, $defaultAction);

    // initialize session
    $session = ObjectFactory::getInstance('session');

    // clear errors
    $session->clearErrors();

    // load user configuration
    $authUser = $session->getAuthUser();
    if ($authUser && strlen($authUser->getConfig()) > 0) {
      $config->addConfiguration($authUser->getConfig(), true);
    }

    // load event listeners
    $listeners = $config->getValue('listeners', 'application');
    foreach ($listeners as $key) {
      ObjectFactory::getInstance($key);
    }

    // set timezone
    date_default_timezone_set($config->getValue('timezone', 'application'));

    // return the request
    return $this->_initialRequest;
  }

  /**
   * Run the application with the given request
   * @param $request
   * @return Response instance
   */
  public function run(Request $request) {
    // process the requested action
    $response = ObjectFactory::getInstance('actionMapper')->processAction($request);
    return $response;
  }

  /**
   * Default exception handling method. Rolls back the transaction and
   * executes 'failure' action.
   * @param $exception The Exception instance
   * @param $request The Request instance
   */
  public function handleException(\Exception $exception, Request $request=null) {
    self::$_logger->error($exception->getMessage()."\n".$exception->getTraceAsString());

    // rollback current transaction
    if (ObjectFactory::getInstance('configuration') != null) {
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
   * This method is run as ob_start callback
   * @note must be public
   * @param $buffer The content to be returned to the client
   * @return String
   */
  public function outputHandler($buffer) {
    // log last error
    $error = error_get_last();
    if ($error !== NULL) {
      $info = "Error: ".$error['message']." in ".$error['file']." on line ".$error['line'];
      self::$_logger->error($info);

      // suppress error message in browser
      if (!$this->_debug) {
        header('HTTP/1.1 500 Internal Server Error');
        $buffer = '';
      }
    }
    return $buffer;
  }
}
?>
