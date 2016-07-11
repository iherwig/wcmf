<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
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

  private $startTime = null;
  private $request = null;
  private $response = null;

  private $debug = true;

  private static $logger = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->startTime = microtime(true);
    if (self::$logger == null) {
      self::$logger = LogManager::getLogger(__CLASS__);
    }
    ob_start(array($this, "outputHandler"));
    new ErrorHandler();
  }

  /**
   * Destructor
   */
  public function __destruct() {
    // log resource usage
    if (self::$logger->isDebugEnabled()) {
      $timeDiff = microtime(true)-$this->startTime;
      $memory = number_format(memory_get_peak_usage()/(1024*1024), 2);
      $msg = "Time[".round($timeDiff, 2)."s] Memory[".$memory."mb]";
      if ($this->request != null) {
        $msg .= " Request[".$this->request->getSender()."?".
                $this->request->getContext()."?".$this->request->getAction()."]";
      }
      $msg .= " URI[".$_SERVER['REQUEST_URI']."]";
      self::$logger->debug($msg);
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

    // create the Request and Response instances
    $this->request = ObjectFactory::getInstance('request');
    $this->response = ObjectFactory::getInstance('response');

    $this->request->initialize($this->response,
            $defaultController, $defaultContext, $defaultAction);

    // initialize session
    $session = ObjectFactory::getInstance('session');

    // load user configuration
    $principalFactory = ObjectFactory::getInstance('principalFactory');
    $authUser = $principalFactory->getUser($session->getAuthUser(), true);
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
    return $this->request;
  }

  /**
   * Run the application with the given request
   * @param $request
   * @return Response instance
   */
  public function run(Request $request) {
    // process the requested action
    ObjectFactory::getInstance('actionMapper')->processAction($request, $this->response);
    return $this->response;
  }

  /**
   * Default exception handling method. Rolls back the transaction and
   * executes 'failure' action.
   * @param $exception The Exception instance
   */
  public function handleException(\Exception $exception) {
    self::$logger->error($exception->getMessage()."\n".$exception->getTraceAsString());

    try {
      if (ObjectFactory::getInstance('configuration') != null) {
        // rollback current transaction
        $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
        $persistenceFacade->getTransaction()->rollback();

        // redirect to failure action
        if ($this->request) {
          $error = ApplicationError::fromException($exception);
          $this->request->addError($error);
          $this->response->addError($error);
          $this->request->setAction('failure');
          $this->response->setAction('failure');
          $this->response->setStatus($error->getStatusCode());
          $this->response->setFinal();
          ObjectFactory::getInstance('actionMapper')->processAction($this->request, $this->response);
          return;
        }
      }
      throw $exception;
    }
    catch (Exception $ex) {
      self::$logger->error($ex->getMessage()."\n".$ex->getTraceAsString());
    }
  }

  /**
   * This method is run as ob_start callback
   * @note must be public
   * @param $buffer The content to be returned to the client
   * @return String
   */
  public function outputHandler($buffer) {
    // log last error, if it's level is enabled
    $error = error_get_last();
    if ($error !== null && ($error['type'] & ini_get('error_reporting'))) {
      // suppress error message in browser
      if (!$this->debug) {
        header('HTTP/1.1 500 Internal Server Error');
        $buffer = '';
      }
    }
    return trim($buffer);
  }
}
?>
