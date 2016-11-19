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
namespace wcmf\lib\util;

use wcmf\lib\config\Configuration;
use wcmf\lib\config\impl\InifileConfiguration;
use wcmf\lib\core\impl\DefaultFactory;
use wcmf\lib\core\impl\MonologFileLogger;
use wcmf\lib\core\LogManager;
use wcmf\lib\core\ObjectFactory;

/**
 * TestUtil provides helper methods for testing wCMF functionality.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class TestUtil {

  /**
   * Set up the wcmf framework. The method makes the following assumptions
   * about file locations:
   * - main configuration in $configPath.'config.ini'
   * - optional additional configuration in $configPath.'test.ini'
   * - logging configuration in $configPath.'log.ini'
   * @param $configPath The path to the configuration directory
   */
  public static function initFramework($configPath) {
    if (!file_exists($configPath) || is_file($configPath)) {
      throw new \Exception('Configuration path '.$configPath.' is not a directory. '.
              'Did you forget to generate code from the model?');
    }

    // setup logging
    $logger = new MonologFileLogger('main', $configPath.'log.ini');
    LogManager::configure($logger);

    // setup configuration
    $configuration = new InifileConfiguration($configPath);
    $configuration->addConfiguration('config.ini');
    $configuration->addConfiguration('test.ini');

    // setup object factory
    ObjectFactory::configure(new DefaultFactory($configuration));
    ObjectFactory::registerInstance('configuration', $configuration);

    $cache = ObjectFactory::getInstance('dynamicCache');
    $cache->clearAll();
  }

  /**
   * Create the test database, if sqlite is configured
   * @return Associative array with connection parameters and key 'connection'
   */
  public static function createDatabase() {
    // get connection from first entity type
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $types = $persistenceFacade->getKnownTypes();
    $mapper = $persistenceFacade->getMapper($types[0]);
    $pdo = $mapper->getConnection();

    // create db
    $params = $mapper->getConnectionParams();
    switch($params['dbType']) {
      case 'sqlite':
        $numTables = $pdo->query('SELECT count(*) FROM sqlite_master WHERE type = "table"')->fetchColumn();
        if ($numTables == 0) {
          $schema = file_get_contents(WCMF_BASE.'install/tables_sqlite.sql');
          $pdo->exec($schema);
        }
        break;
      case 'mysql':
        $numTables = $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = "'.$params['dbName'].'"')->fetchColumn();
        if ($numTables == 0) {
          $schema = file_get_contents(WCMF_BASE.'install/tables_mysql.sql');
          $pdo->exec($schema);
        }
        break;
    }
    $params['connection'] = $pdo;
    return $params;
  }

  /**
   * Start the built-in webserver
   * @param $documentRoot Document root directory
   * @param $router Router script filename (optional)
   * @param $killOnExit Boolean, whether to kill the server process after script execution or not (optional, default: false)
   */
  public static function startServer($documentRoot, $router='', $killOnExit=false) {
    if (!is_dir($documentRoot)) {
      throw new \Exception('Document root '.$documentRoot.' does not exist');
    }
    if (!defined('SERVER_HOST')) {
      define('SERVER_HOST', 'localhost');
      define('SERVER_PORT', 8500);
    }
    $cmd = sprintf('php -S %s:%d -t %s %s', SERVER_HOST, SERVER_PORT, $documentRoot, $router);

    $descriptorspec = array(
      0 => array('pipe', 'r'), // stdin
      1 => array('pipe', 'w'), // stdout
      2 => array('pipe', 'a') // stderr
    );
    $pipes = null;
    if (self::isWindows()) {
      $resource = proc_open("start /B ".$cmd, $descriptorspec, $pipes);
    }
    else {
      $resource = proc_open("nohup ".$cmd, $descriptorspec, $pipes);
    }
    if (!is_resource($resource)) {
      throw new \Exception("Failed to execute ".$cmd);
    }

    if ($killOnExit) {
      // kill the web server when the process ends
      register_shutdown_function(function() use ($resource) {
        $status = proc_get_status($resource);
        $pid = $status['pid'];
        if (self::isWindows()) {
          $output = array_filter(explode(" ", shell_exec("wmic process get parentprocessid,processid | find \"$pid\"")));
          array_pop($output);
          $pid = end($output);
          exec("taskkill /F /T /PID $pid");
        }
        else {
          $pid = $pid+1;
          exec("kill -9 $pid");
        }
      });
    }
  }

  /**
   * Process a request as if it was sent to main.php
   * @param $request The Request instance
   * @return The Response instance (result of the last ActionMapper::processAction call)
   */
  public static function simulateRequest($request) {
    // set format
    $request->setFormat('null');
    $request->setResponseFormat('null');

    // run request
    $actionMapper = ObjectFactory::getInstance('actionMapper');
    $response = ObjectFactory::getNewInstance('response');
    $actionMapper->processAction($request, $response);
    return $response;
  }

  /**
   * Start a session. This is usefull for simulateRequest calls
   * @param $user The name of the user
   * @param $password The password of the user
   * @return The session id. Use this as data['sid'] parameter for
   *    subsequent simulateRequest calls
   */
  public static function startSession($user, $password) {
    $session = ObjectFactory::getInstance('session');
    $authManager = ObjectFactory::getInstance('authenticationManager');
    $authUser = $authManager->login(array(
        'login' => $user,
        'password' => $password
    ));
    if ($authUser) {
      $session->clear();
      $session->setAuthUser($authUser->getLogin());
    }
    else {
      throw new \RuntimeException("Session could not be started for user '$user'");
    }
    return $session->getID();
  }

  /**
   * End a session.
   */
  public static function endSession() {
    $session = ObjectFactory::getInstance('session');
    $session->destroy();
    $session->__destruct();
  }

  /**
   * Set a configuration value
   * @see Configuration::setValue()
   */
  public static function setConfigValue($key, $value, $section) {
    $config = ObjectFactory::getInstance('configuration');
    $config->setValue($key, $value, $section);
  }

  /**
   * Call a protected/private method of an instance (PHP >= 5.3.2)
   * @param $instance The instance
   * @param $methodName The method name
   * @param $args An array of method arguments
   */
  public static function callProtectedMethod($instance, $methodName, $args=null) {
    $className = get_class($instance);
    $class = new \ReflectionClass($className);
    $method = $class->getMethod($methodName);
    $method->setAccessible(true);

    if ($args != null) {
      return $method->invokeArgs($instance, $args);
    }
    else {
      return $method->invoke($instance);
    }
  }

  public static function getSizeof($var) {
      $startMemory = memory_get_usage();
      $var = unserialize(serialize($var));
      return memory_get_usage() - $startMemory - PHP_INT_SIZE * 8;
  }

  public static function isWindows() {
    return (substr(php_uname(), 0, 7) == "Windows");
  }
}
?>