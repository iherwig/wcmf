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

require_once(WCMF_BASE."wcmf/lib/util/class.SessionData.php");
require_once(WCMF_BASE."wcmf/lib/output/class.LogOutputStrategy.php");
require_once(WCMF_BASE."wcmf/lib/presentation/class.WCMFInifileParser.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/security/class.AuthUser.php");
require_once(WCMF_BASE."wcmf/lib/util/class.JSONUtil.php");
require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");
require_once(WCMF_BASE."wcmf/lib/core/class.ErrorHandler.php");

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
  private $_data = null;
  private $_errorHandler = null;

  /**
   * Constructor
   */
  private function __construct()
  {
    // collect all request data
    $this->_data = array_merge($_GET, $_POST, $_COOKIE, $_FILES);
    $json = JSONUtil::decode(file_get_contents('php://input'), true);
    if (is_array($json))
    {
      foreach ($json as $key => $value) {
        $this->_data[$key] = $value;
      }
    }
    // store data in global variables to make them accessible by error handlers
    $GLOBALS['data'] = &$this->_data;
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
   * @return An associative array with keys 'action', 'context', 'controller', 'data'
   * TODO: return request instance, maybe use default parameters from a config section?
   * TODO: allow configPath array to search from different locations, simplifies inclusion
   */
  public function initialize($configPath='include/', $mainConfigFile='config.ini',
    $defaultController='LoginController', $defaultContext='', $defaultAction='login',
    $defaultResponseFormat='HTML')
  {
    Application::setupGlobals($configPath, $mainConfigFile);
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
        
    // initialize session with session id if given
    $sessionId = Application::getCallParameter('sid', false);
    if ($sessionId === false) {
      $sessionId = Application::getCallParameter('PHPSESSID', false);
    }
    if ($sessionId !== false) {
      SessionData::init($sessionId);
    }
    // clear errors
    $session = SessionData::getInstance();
    $session->clearErrors();

    // get controller/context/action triple
    // (defaults to /LoginController//login in this application)
    $controller = Application::getCallParameter('controller', $defaultController);
    $context = Application::getCallParameter('context', $defaultContext);
    $action = Application::getCallParameter('action', $defaultAction);
    $readonly = Application::getCallParameter('readonly', false);
    
    // determine message formats based in request headers
    if (isset($_SERVER['CONTENT_TYPE'])) {
      $requestFormat = self::getMessageFormatFromHeader(
        strtolower($_SERVER['CONTENT_TYPE']), $defaultResponseFormat);
      $requestFormat = Application::getCallParameter('requestFormat', $requestFormat);
    }
    else {
      $requestFormat = Application::getCallParameter('requestFormat', $defaultResponseFormat);
    }    
    if (isset($_SERVER['HTTP_ACCEPT'])) {
      $responseFormat = self::getMessageFormatFromHeader(
        strtolower($_SERVER['HTTP_ACCEPT']), $defaultResponseFormat);
      $responseFormat = Application::getCallParameter('responseFormat', $responseFormat);
    }
    else {
      $responseFormat = Application::getCallParameter('responseFormat', $defaultResponseFormat);
    }    
      
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

    // store data in global variables to make them accessible by error handlers
    // TODO: get rid if these. better store the old request in the session for error handling
    $GLOBALS['controller'] = $controller;
    $GLOBALS['context'] = $context;
    $GLOBALS['action'] = $action;
    $GLOBALS['requestFormat'] = $requestFormat;
    $GLOBALS['responseFormat'] = $responseFormat;

    // return the parameters needed to process the requested action
    return array('action' => $action, 'context' => $context, 'controller' => $controller,
      'data' => &$this->_data, 'requestFormat' => $requestFormat, 'responseFormat' => $responseFormat);
  }
  /**
   * Get a value from the call parameters (GET, POST variables)
   * @param name The name of the parameter
   * @param default The value to return, if no value is given
   * @return The value
   */
  public static function getCallParameter($name, $default)
  {
    $value = $default;
    $application = Application::getInstance();
    if (array_key_exists($name, $application->_data))
      $value = $application->_data[$name];
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
   * Set a custom error handler to be used instead of the default implementation
   * Application::onError.
   * @param errorHandler A callback to be used, when
   */
  public function setErrorHandler($errorHandler)
  {
    $this->_errorHandler = $errorHandler;
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
