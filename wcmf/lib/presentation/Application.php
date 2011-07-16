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
require_once("base_dir.php");

require_once(WCMF_BASE."wcmf/lib/util/SessionData.php");
require_once(WCMF_BASE."wcmf/lib/output/LogOutputStrategy.php");
require_once(WCMF_BASE."wcmf/lib/presentation/WCMFInifileParser.php");
require_once(WCMF_BASE."wcmf/lib/presentation/Request.php");
require_once(WCMF_BASE."wcmf/lib/persistence/PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/security/AuthUser.php");
require_once(WCMF_BASE."wcmf/lib/util/JSONUtil.php");
require_once(WCMF_BASE."wcmf/lib/util/Log.php");
require_once(WCMF_BASE."wcmf/lib/core/ErrorHandler.php");

/**
 * @class Application
 * @ingroup Presentation
 * @brief The main application class. Does all the initialization.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Application
{
  private static $_instance = null;

  private $_requestValues = array();
  private $_rawPostBody = null;
  private $_rawPostBodyIsJson = false;

  private $_startTime = null;
  private $_initialRequest = null;

  /**
   * Constructor
   */
  private function __construct()
  {
    $this->_startTime = microtime(true);
    register_shutdown_function(array($this, "shutdown"));
  }
  /**
   * Returns an instance of the class.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance()
  {
    if (!isset(self::$_instance)) {
      self::$_instance = new Application();
    }
    return self::$_instance;
  }
  /**
   * Initialize the application.
   * - Parses and processes the configuration
   * - Sets global variables
   * - Initialize the session and other main application classes
   * - Extracts the application parameters
   * @param configPath The path where config files reside (as seen from main.php), optional [default: 'include/']
   * @param mainConfigFile The main configuration file to use, optional [default: 'config.ini']
   * @param defaultController The controller to call if none is given in request parameters, optional [default: 'LoginController']
   * @param defaultContext The context to set if none is given in request parameters, optional [default: '']
   * @param defaultAction The action to perform if none is given in request parameters, optional [default: 'login']
   * @param defaultResponseFormat The response format if none is given in request parameters, optional [default: HTML]
   * @return Request instance representing the current HTTP request
   * TODO: return request instance, maybe use default parameters from a config section?
   * TODO: allow configPath array to search from different locations, simplifies inclusion
   */
  public function initialize($configPath='include/', $mainConfigFile='config.ini',
    $defaultController='LoginController', $defaultContext='', $defaultAction='login',
    $defaultResponseFormat='HTML')
  {
    // collect all request data
    $this->_requestValues = array_merge($_GET, $_POST, $_FILES);
    $this->_rawPostBody = file_get_contents('php://input');
    // add the raw post data if they are json encoded
    $json = JSONUtil::decode($this->_rawPostBody, true);
    if (is_array($json))
    {
      $this->_rawPostBodyIsJson = true;
      foreach ($json as $key => $value) {
        $this->_requestValues[$key] = $value;
      }
    }

    self::setupGlobals($configPath, $mainConfigFile);
    $parser = WCMFInifileParser::getInstance();

    // include files from implementation section
    // NOTE: this must be done before the session is started to avoid incomplete object definitions
    $values = array_values($parser->getSection("implementation"));
    foreach($values as $class)
    {
      if (is_array($class)) {
        foreach ($class as $c) {
          ObjectFactory::loadClassDefinition($c);
        }
      }
      else {
        ObjectFactory::loadClassDefinition($class);
      }
    }

    // get controller/context/action triple
    // (defaults to /LoginController//login in this application)
    $controller = $this->getRequestValue('controller', $defaultController);
    $context = $this->getRequestValue('context', $defaultContext);
    $action = $this->getRequestValue('action', $defaultAction);
    $readonly = $this->getRequestValue('readonly', false);

    // determine message formats based on request headers
    if (isset($_SERVER['CONTENT_TYPE'])) {
      $requestFormat = self::getMessageFormatFromHeader(
        strtolower($_SERVER['CONTENT_TYPE']), $defaultResponseFormat);
      $requestFormat = $this->getRequestValue('requestFormat', $requestFormat);
    }
    else {
      $requestFormat = $this->getRequestValue('requestFormat', $defaultResponseFormat);
    }
    if (isset($_SERVER['HTTP_ACCEPT'])) {
      $responseFormat = self::getMessageFormatFromHeader(
        strtolower($_SERVER['HTTP_ACCEPT']), $defaultResponseFormat);
      $responseFormat = $this->getRequestValue('responseFormat', $responseFormat);
    }
    else {
      $responseFormat = $this->getRequestValue('responseFormat', $defaultResponseFormat);
    }

    // create the Request instance
    $this->_initialRequest = new Request($controller, $context, $action);
    $this->_initialRequest->setFormat($requestFormat);
    $this->_initialRequest->setResponseFormat($responseFormat);
    $this->_initialRequest->setValues($this->_requestValues);

  // TODO:
  // - request headers should be added to the Request class
  //   foreach (getallheaders() as $name => $value) {
  //     Log::error("$name: $value", __CLASS__);
  //   }

    // initialize session with session id if given
    $sessionId = $this->_initialRequest->getValue('sid', null);
    SessionData::init($sessionId);

    // clear errors
    $session = SessionData::getInstance();
    $session->clearErrors();

    // load user configuration
    $rightsManager = RightsManager::getInstance();
    $authUser = $rightsManager->getAuthUser();
    if ($authUser && strlen($authUser->getConfig()) > 0) {
      $parser->parseIniFile($GLOBALS['CONFIG_PATH'].$authUser->getConfig(), true);
    }

    // prepare PersistenceFacade
    $persistenceFacade = PersistenceFacade::getInstance();
    if ($parser->getValue('logDBActions', 'cms') == 1) {
      $persistenceFacade->enableLogging(new LogOutputStrategy());
    }
    if ($readonly) {
      $persistenceFacade->setReadOnly(true);
    }

    // return the request
    return $this->_initialRequest;
  }
  /**
   * This method is automatically called after script execution
   */
  public function shutdown()
  {
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

    // log last error
    $error = error_get_last();
    if ($error !== NULL) {
      $info = "Error: ".$error['message']." in ".$error['file']." on line ".$error['line'];
      Log::error($info, __CLASS__);
    }
  }
  /**
   * Default exception handling method. Rolls back the transaction and
   * re-executes the last request (expected in the session variable 'lastRequest').
   * @param exception The exception instance
   */
  public function handleException(Exception $exception)
  {
    if ($exception instanceof ApplicationException)
    {
      $error = $exception->getError();
      if ($error->getCode() == 'SESSION_INVALID') {
        $request = $exception->getRequest();
        $request->setAction('logout');
        $request->addError($error);
        ActionMapper::processAction($request);
        return;
      }
    }

    Log::error($exception->getMessage()."\n".$exception->getTraceAsString(), 'main');

    // rollback current transaction
    $persistenceFacade = PersistenceFacade::getInstance();
    $persistenceFacade->getTransaction()->rollback();

    // process last successful request
    $lastRequest = SessionData::getInstance()->get('lastRequest');
    if ($lastRequest) {
      ActionMapper::processAction($lastRequest);
    }
    else {
      print $exception;
    }
  }
  /**
   * Get a value from the request parameters (GET, POST variables)
   * @param name The name of the parameter
   * @param default The value to return, if no value is given
   * @return The value
   */
  protected function getRequestValue($name, $default)
  {
    $value = $default;
    if (array_key_exists($name, $this->_requestValues)) {
      $value = $this->_requestValues[$name];
    }
    return $value;
  }
  /**
   * Setup global variables. Does nothing related to session, users and persistence.
   * Use this method if you just want to have all global variables initialized and need access to config values.
   * @param configPath The path where config files reside (as seen from main.php), maybe null [default: include/]
   * @param mainConfigFile The main configuration file to use, maybe null [default: config.ini]
   */
  public function setupGlobals($configPath='include/', $mainConfigFile='config.ini')
  {
    // globals
    $GLOBALS['CONFIG_PATH'] = $configPath;
    $GLOBALS['CONFIG_EXTENSION'] = "ini";
    $GLOBALS['MAIN_CONFIG_FILE'] = $mainConfigFile;

    // get configuration from file
    $parser = WCMFInifileParser::getInstance();
    $parser->parseIniFile($GLOBALS['CONFIG_PATH'].$GLOBALS['MAIN_CONFIG_FILE'], true);

    // message globals
    $GLOBALS['MESSAGE_LOCALE_DIR'] = $parser->getValue('localeDir', 'cms');
    $GLOBALS['MESSAGE_LANGUAGE'] = $parser->getValue('language', 'cms');

    // set locale
    if ($GLOBALS['MESSAGE_LANGUAGE'] !== false) {
      setlocale(LC_ALL, $GLOBALS['MESSAGE_LANGUAGE']);
    }
  }
  /**
   * Get the stack trace
   * @return The stack trace as string
   */
  public static function getStackTrace()
  {
    ob_start();
    debug_print_backtrace();
    $trace = ob_get_contents();
    ob_end_clean();

    // remove first item from backtrace as it's this function which is redundant.
    $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);

    // renumber backtrace items.
    $trace = preg_replace ('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);

    return $trace;
  }
  /**
   * Get an unique id for the application based on the installation location.
   * @return The id
   */
  public static function getId()
  {
    return md5(__FILE__);
  }
  /**
   * Determine the message format from a HTTP header value
   * @param header The header value
   * @param defaultFormat The default format to be used if no other can be determined
   * @return One of the message formats (JSON, SOAP, ...)
   */
  protected static function getMessageFormatFromHeader($header, $defaultFormat)
  {
    $format = $defaultFormat;
    if (strpos($header, 'application/json') !== false) {
      $format = 'JSON';
    }
    else if (strpos($header, 'application/soap') !== false) {
      $format = 'SOAP';
    }
    return $format;
  }
}
?>
